<?php // app/Services/UserService.php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function create(array $data): User
    {
        // password di-hash otomatis oleh cast 'hashed' di model User
        return User::create($data + ['is_active' => true])->load(['role', 'position']);
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $deactivating = array_key_exists('is_active', $data) && ! $data['is_active'];
            $roleChanging = isset($data['role_id']) && (int) $data['role_id'] !== (int) $user->role_id;

            if ($deactivating || $roleChanging) {
                $this->assertNotLastActiveAdmin($user);
            }

            $user->update($data);

            // Paksa login ulang bila akses/kredensial berubah
            if ($deactivating || $roleChanging || isset($data['password'])) {
                $user->tokens()->delete();
            }

            return $user->load(['role', 'position']);
        });
    }

    public function setActive(User $user, bool $active): User
    {
        return $this->update($user, ['is_active' => $active]);
    }

    public function resetPassword(User $user, string $password): void
    {
        DB::transaction(function () use ($user, $password): void {
            $user->update(['password' => $password]);
            $user->tokens()->delete();
        });
    }

    public function delete(User $user, User $actor): void
    {
        abort_if($user->is($actor), 422, 'Anda tidak bisa menghapus akun sendiri.');
        $this->assertNotLastActiveAdmin($user);

        // FK submissions memakai cascadeOnDelete -> hapus akun = hapus riwayat capaian.
        abort_if(
            $user->kpiSubmissions()->exists(),
            409,
            'Akun memiliki riwayat capaian. Nonaktifkan akun, jangan dihapus.'
        );

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->delete();
        });
    }

    private function assertNotLastActiveAdmin(User $user): void
    {
        if (! $user->hasRole('admin')) {
            return;
        }

        $otherAdmins = User::query()
            ->whereKeyNot($user->id)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['admin']))
            ->exists();

        if (! $otherAdmins) {
            throw ValidationException::withMessages([
                'user' => 'Tidak bisa menonaktifkan/mengubah admin aktif terakhir.',
            ]);
        }
    }
}
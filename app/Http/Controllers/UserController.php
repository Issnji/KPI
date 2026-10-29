<?php // app/Http/Controllers/UserController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(private readonly UserService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $f = $request->validate([
            'search'      => ['nullable', 'string', 'max:100'],
            'role_id'     => ['nullable', 'integer'],
            'position_id' => ['nullable', 'integer'],
            'is_active'   => ['nullable', 'boolean'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $users = User::query()
            ->with(['role:id,name', 'position:id,name'])
            ->when($f['search'] ?? null, fn ($q, $v) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")
            ))
            ->when($f['role_id'] ?? null, fn ($q, $v) => $q->where('role_id', $v))
            ->when($f['position_id'] ?? null, fn ($q, $v) => $q->where('position_id', $v))
            ->when(isset($f['is_active']), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate($f['per_page'] ?? 15);

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->service->create($request->validated());

        return (new UserResource($user))
            ->additional(['message' => 'Akun berhasil dibuat.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load(['role:id,name', 'position:id,name']));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        return (new UserResource($this->service->update($user, $request->validated())))
            ->additional(['message' => 'Akun berhasil diperbarui.']);
    }

    public function updateStatus(Request $request, User $user): UserResource
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);

        return (new UserResource($this->service->setActive($user, (bool) $data['is_active'])))
            ->additional(['message' => 'Status akun diperbarui.']);
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:8']]);

        $this->service->resetPassword($user, $data['password']);

        return response()->json(['message' => 'Password berhasil direset. Semua sesi akun dikeluarkan.']);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->service->delete($user, $request->user());

        return response()->json(['message' => 'Akun berhasil dihapus.']);
    }
}
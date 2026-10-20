<?php

namespace App\Services;

use App\Enums\KpiSubmissionStatus;
use App\Models\KpiSubmission;
use App\Models\PositionKpi;
use App\Models\User;
use App\Support\KpiPeriod;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KpiSubmissionService
{
    /**
     * Simpan (draft) atau kirim (submit) capaian KPI milik user untuk periode berjalan.
     *
     * @param  array<int, array{position_kpi_id:int, answer_yes_no?:bool|null, numeric_value?:float|int|string|null}> $items
     * @return Collection<int, KpiSubmission>
     */
    public function saveForUser(User $user, array $items, bool $submit, ?CarbonInterface $date = null): Collection
    {
        abort_if(! $user->position_id, 422, 'Akun belum memiliki posisi/jabatan.');

        $date ??= now();

        // 1 query: hanya KPI aktif milik jabatan user (mencegah isi KPI jabatan lain)
        $positionKpis = PositionKpi::query()
            ->with('kpiIndicator:id,name,assessment_type')
            ->where('position_id', $user->position_id)
            ->where('is_active', true)
            ->whereIn('id', collect($items)->pluck('position_kpi_id'))
            ->get()
            ->keyBy('id');

        $this->assertItemsValid($items, $positionKpis);

        return transaction(function () use ($user, $items, $submit, $date, $positionKpis): Collection {
        // return DB::transaction(function () use ($user, $items, $submit, $date, $positionKpis): Collection {
            $saved = collect();

            foreach ($items as $i => $item) {
                /** @var PositionKpi $pk */
                $pk     = $positionKpis[(int) $item['position_kpi_id']];
                $period = KpiPeriod::for($pk->frequency, $date);
                $isYesNo = $pk->kpiIndicator->assessment_type === 'YES_NO';

                $submission = KpiSubmission::query()
                    ->where('user_id', $user->id)
                    ->where('position_kpi_id', $pk->id)
                    ->whereDate('period_start', $period['start'])
                    ->lockForUpdate()
                    ->first();

                if ($submission && ! $submission->status->isEditable()) {
                    throw ValidationException::withMessages([
                        "items.$i.position_kpi_id" => "KPI '{$pk->kpiIndicator->name}' periode ini sudah "
                            . strtolower($submission->status->value) . ' dan tidak bisa diubah.',
                    ]);
                }

                $submission ??= new KpiSubmission([
                    'user_id'         => $user->id,
                    'position_kpi_id' => $pk->id,
                    'period_start'    => $period['start'],
                    'period_end'      => $period['end'],
                ]);

                $submission->fill([
                    'answer_yes_no' => $isYesNo ? (bool) $item['answer_yes_no'] : null,
                    'numeric_value' => $isYesNo ? null : $item['numeric_value'],
                    'status'        => $submit ? KpiSubmissionStatus::Submitted : KpiSubmissionStatus::Draft,
                    'submitted_at'  => $submit ? now() : null,
                ])->save();

                $saved->push($submission);
            }

            return $saved;
        });
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param Collection<int, PositionKpi> $positionKpis
     */
    private function assertItemsValid(array $items, Collection $positionKpis): void
    {
        $errors = [];

        foreach ($items as $i => $item) {
            $pk = $positionKpis->get((int) $item['position_kpi_id']);

            if (! $pk) {
                $errors["items.$i.position_kpi_id"] = 'KPI tidak valid untuk jabatan Anda.';
                continue;
            }

            if ($pk->kpiIndicator->assessment_type === 'YES_NO') {
                if (! isset($item['answer_yes_no'])) {
                    $errors["items.$i.answer_yes_no"] = "Jawaban ya/tidak wajib diisi untuk '{$pk->kpiIndicator->name}'.";
                }
            } elseif (! isset($item['numeric_value'])) {
                $errors["items.$i.numeric_value"] = "Nilai wajib diisi untuk '{$pk->kpiIndicator->name}'.";
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
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
     * @param  array<int, array{position_kpi_id:int, answer_yes_no?:bool|null, numeric_value?:float|int|string|null}> $items
     * @return array{saved: Collection<int, KpiSubmission>, skipped: array<int, array<string, mixed>>}
     */
    public function saveForUser(User $user, array $items, bool $submit, ?CarbonInterface $date = null): array
    {
        abort_if(! $user->position_id, 422, 'Akun belum memiliki posisi/jabatan.');

        $date ??= now();

        $positionKpis = PositionKpi::query()
            ->with('kpiIndicator:id,name,assessment_type,requires_evidence')
            ->where('position_id', $user->position_id)
            ->where('is_active', true)
            ->whereIn('id', collect($items)->pluck('position_kpi_id'))
            ->get()
            ->keyBy('id');

        $this->assertItemsValid($items, $positionKpis);

        return DB::transaction(function () use ($user, $items, $submit, $date, $positionKpis): array {
            $saved   = collect();
            $skipped = [];

            foreach ($items as $i => $item) {
                /** @var PositionKpi $pk */
                $pk      = $positionKpis[(int) $item['position_kpi_id']];
                $period  = KpiPeriod::for($pk->frequency, $date);
                $isYesNo = $pk->kpiIndicator->assessment_type === 'YES_NO';

                $submission = KpiSubmission::query()
                    ->withCount('evidences')
                    ->where('user_id', $user->id)
                    ->where('position_kpi_id', $pk->id)
                    ->whereDate('period_start', $period['start'])
                    ->lockForUpdate()
                    ->first();

                if ($submission && ! $submission->status->isEditable()) {
                    // Indikator mingguan/bulanan yang periodenya sudah terkirim itu normal di hari berikutnya:
                    // lewati saja, jangan gagalkan seluruh laporan. Harian tetap ditolak (kirim ganda).
                    if ($pk->frequency !== 'DAILY') {
                        $skipped[] = [
                            'position_kpi_id' => $pk->id,
                            'name'            => $pk->kpiIndicator->name,
                            'frequency'       => $pk->frequency,
                            'status'          => $submission->status->value,
                            'period_end'      => $period['end'],
                        ];
                        continue;
                    }

                    throw ValidationException::withMessages([
                        "items.$i.position_kpi_id" => "KPI '{$pk->kpiIndicator->name}' periode ini sudah "
                            . strtolower($submission->status->value) . ' dan tidak bisa diubah.',
                    ]);
                }

                if (
                    $submit
                    && $pk->kpiIndicator->requires_evidence
                    && (! $submission || $submission->evidences_count === 0)
                ) {
                    throw ValidationException::withMessages([
                        "items.$i.position_kpi_id" => "Unggah foto bukti untuk '{$pk->kpiIndicator->name}' sebelum mengirim.",
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
                    'report_date'   => $submission->report_date?->toDateString() ?? $date->toDateString(),
                ])->save();

                $saved->push($submission);
            }

            if ($saved->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Semua indikator pada kiriman ini sudah terkirim untuk periodenya.',
                ]);
            }

            return ['saved' => $saved, 'skipped' => $skipped];
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
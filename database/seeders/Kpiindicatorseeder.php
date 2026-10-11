<?php

namespace Database\Seeders;

use App\Models\KpiCategory;
use App\Models\KpiIndicator;
use App\Models\Position;
use App\Models\PositionKpi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Seeder kategori, indikator, dan penugasan KPI per posisi (Roti Wonosari).
 * Jalankan SETELAH RolePositionSeeder (posisi harus sudah ada):
 *   php artisan db:seed --class=KpiIndicatorSeeder
 *
 * Idempotent: aman dijalankan berulang (updateOrCreate by name).
 * Target bertanda [USULAN] belum disebut owner di form -> konfirmasi dulu.
 */
class KpiIndicatorSeeder extends Seeder
{
    private const NUM  = 'NUMERIC';
    private const YN   = 'YES_NO';
    private const HIGH = 'HIGHER_IS_BETTER';
    private const LOW  = 'LOWER_IS_BETTER';

    private const D = 'DAILY';
    private const W = 'WEEKLY';
    private const M = 'MONTHLY';

    /** @var array<string, string> code => calculation_type */
    private array $calc = [];

    public function run(): void
    {
        DB::transaction(function (): void {
            $ids = $this->seedIndicators();
            $this->seedPositionKpis($ids);
        });
    }

    /**
     * @return array<string, int> code => kpi_indicators.id
     */
    private function seedIndicators(): array
    {
        $ids = [];

        foreach ($this->catalog() as $categoryName => $items) {
            $category = KpiCategory::updateOrCreate(['name' => $categoryName]);

            foreach ($items as [$code, $name, $unit, $assessment, $calc, $description]) {
                $indicator = KpiIndicator::updateOrCreate(
                    ['name' => $name],
                    [
                        'category_id'      => $category->id,
                        'description'      => $description,
                        'unit'             => $unit,
                        'assessment_type'  => $assessment,
                        'calculation_type' => $calc,
                        'is_active'        => true,
                    ]
                );

                $ids[$code]        = $indicator->id;
                $this->calc[$code] = $calc;
            }
        }

        return $ids;
    }

    /**
     * @param array<string, int> $ids
     */
    private function seedPositionKpis(array $ids): void
    {
        foreach ($this->assignments() as $positionName => $rows) {
            $total = array_sum(array_column($rows, 2));
            if ($total !== 100) {
                throw new RuntimeException("Total bobot {$positionName} = {$total}, harus 100.");
            }

            $position = Position::where('name', $positionName)->firstOrFail();

            foreach ($rows as [$code, $target, $weight, $frequency]) {
                PositionKpi::updateOrCreate(
                    [
                        'position_id'      => $position->id,
                        'kpi_indicator_id' => $ids[$code],
                    ],
                    [
                        'target'           => $target,
                        'weight'           => $weight,
                        'frequency'        => $frequency,
                        'calculation_type' => $this->calc[$code],
                        'is_active'        => true,
                    ]
                );
            }
        }
    }

    /**
     * kategori => [[code, name, unit, assessment_type, calculation_type, description], ...]
     * Untuk YES_NO: nilai 'ya' = 100%, 'tidak' = 0% (target di position_kpis = 1).
     */
    private function catalog(): array
    {
        return [
            'Penjualan' => [
                ['SAL_TARGET', 'Pencapaian Target Omzet', '%', self::NUM, self::HIGH, 'Realisasi omzet dibanding target (harian/bulanan)'],
                ['SAL_GROWTH', 'Pertumbuhan Omzet vs Bulan Lalu', '%', self::NUM, self::HIGH, 'Persentase pertumbuhan omzet dibanding bulan sebelumnya'],
                ['SAL_BASKET', 'Kenaikan Nilai Transaksi Rata-rata', '%', self::NUM, self::HIGH, 'Kenaikan average basket dibanding periode sebelumnya'],
                ['SAL_UPSELL', 'Upselling & Penawaran Promo', '%', self::NUM, self::HIGH, 'Persentase transaksi yang disertai upselling/penawaran produk promo'],
            ],
            'Laba & Biaya' => [
                ['FIN_FOODCOST', 'Food Cost terhadap Omzet', '%', self::NUM, self::LOW, 'Food cost aktual dibanding omzet, harus di bawah standar perusahaan'],
                ['FIN_WASTE', 'Waste / Shrinkage Produk', '%', self::NUM, self::LOW, 'Produk rusak, expired, dan selisih stok sebagai persen omzet'],
                ['FIN_CASHACC', 'Akurasi Kasir', 'ya/tidak', self::YN, self::HIGH, 'Tidak ada selisih kas, salah input harga, dan nota sesuai transaksi'],
            ],
            'Persediaan & Stok' => [
                ['INV_ACCURACY', 'Akurasi Stok', '%', self::NUM, self::HIGH, 'Kesesuaian stok fisik dengan stok sistem'],
                ['INV_OPNAME', 'Stock Opname Tepat Waktu', 'ya/tidak', self::YN, self::HIGH, 'Stock opname dilaksanakan sesuai jadwal'],
            ],
            'Pelayanan Pelanggan' => [
                ['SVC_CSAT', 'Kepuasan Pelanggan', '%', self::NUM, self::HIGH, 'Persentase pelanggan puas (survei/rating)'],
                ['SVC_COMPLAINT', 'Komplain Terselesaikan Maks. 1x24 Jam', '%', self::NUM, self::HIGH, 'Persentase komplain yang selesai dalam 1x24 jam'],
                ['SVC_QUALITY', 'Kualitas Pelayanan & Komunikasi', 'skor', self::NUM, self::HIGH, 'Skor 0-100: sapaan, keramahan, kecepatan, komunikasi, penanganan komplain'],
                ['SVC_PRODKNOW', 'Product Knowledge', 'skor', self::NUM, self::HIGH, 'Skor 0-100: fungsi bahan kue, stok & lokasi produk, kemampuan rekomendasi'],
            ],
            'Operasional & SOP' => [
                ['OPS_SOP', 'Kepatuhan SOP', '%', self::NUM, self::HIGH, 'Persentase item checklist SOP terpenuhi (seragam, buka/tutup toko, pelayanan, keamanan)'],
                ['OPS_OPENTIME', 'Toko Buka Tepat Waktu', 'ya/tidak', self::YN, self::HIGH, 'Toko buka sesuai jam operasional'],
                ['OPS_DISPLAY', 'Kelengkapan Display Saat Jam Operasional', '%', self::NUM, self::HIGH, 'Persentase waktu operasional dengan display produk lengkap'],
                ['OPS_PROMO', 'Eksekusi Promo Sesuai Jadwal', '%', self::NUM, self::HIGH, 'Persentase promo yang dijalankan tepat jadwal'],
                ['OPS_REPORT', 'Pelaporan & Administrasi Tepat Waktu', '%', self::NUM, self::HIGH, 'Laporan penjualan, stok, kas, dan kejadian outlet terkumpul tepat waktu'],
                ['OPS_IMPROVE', 'Inisiatif Improvement', 'ide', self::NUM, self::HIGH, 'Jumlah ide promo/peningkatan penjualan/efisiensi kerja yang diajukan'],
            ],
            'Kebersihan & Display' => [
                ['CLN_STORE', 'Kebersihan Toko (Audit)', 'skor', self::NUM, self::HIGH, 'Nilai audit 0-100: area jual, meja display, gudang, toilet'],
                ['CLN_DISPLAY', 'Kerapian Display & FIFO/FEFO', '%', self::NUM, self::HIGH, 'Rak rapi, label harga lengkap, rotasi produk FIFO/FEFO, area kerja bersih'],
                ['CLN_PROD', 'Kebersihan Area Produksi', '%', self::NUM, self::HIGH, 'Checklist: meja, oven, mixer, lantai, rak, tempat sampah tertutup'],
            ],
            'SDM & Disiplin' => [
                ['HR_ATTEND', 'Kehadiran & Kedisiplinan', '%', self::NUM, self::HIGH, 'Hadir, tidak terlambat, tidak pulang lebih awal, patuh jadwal & briefing, berpakaian rapi'],
                ['HR_TEAM_ATTEND', 'Kehadiran Tim', '%', self::NUM, self::HIGH, 'Rata-rata kehadiran seluruh anggota tim'],
                ['HR_BRIEFING', 'Briefing Harian Terlaksana', '%', self::NUM, self::HIGH, 'Persentase hari kerja dengan briefing terlaksana'],
                ['HR_TRAINING', 'Training Karyawan', 'kali', self::NUM, self::HIGH, 'Jumlah training yang diadakan per bulan'],
                ['HR_TEAMMGMT', 'Manajemen Tim', 'skor', self::NUM, self::HIGH, 'Skor 0-100: pembagian tugas, disiplin staff, kontrol absensi'],
            ],
            'Produksi' => [
                ['PRD_OUTPUT', 'Pencapaian Target Produksi', '%', self::NUM, self::HIGH, 'Jumlah roti diproduksi dibanding rencana harian'],
                ['PRD_REJECT', 'Produk Reject', '%', self::NUM, self::LOW, 'Produk gosong, bantat, bentuk/ukuran tidak standar dibanding total produksi'],
                ['PRD_ONTIME', 'Ketepatan Waktu Produksi', '%', self::NUM, self::HIGH, 'Produk selesai sebelum jadwal kirim/display dan tidak menunda buka toko'],
                ['PRD_RMWASTE', 'Waste Bahan Baku', '%', self::NUM, self::LOW, 'Pemakaian tepung, mentega, telur, dll di atas standar resep'],
                ['PRD_SOP', 'Kepatuhan SOP Produksi', '%', self::NUM, self::HIGH, 'Timbang sesuai resep, waktu fermentasi, APD, pencatatan batch'],
                ['PRD_EQUIP', 'Perawatan Alat & Tanpa Kerusakan Kelalaian', 'ya/tidak', self::YN, self::HIGH, 'Checklist perawatan harian selesai dan tidak ada kerusakan akibat kelalaian operator'],
            ],
            'Pengiriman & Kendaraan' => [
                ['DLV_ONTIME', 'Ketepatan Pengiriman', '%', self::NUM, self::HIGH, 'Pengiriman sesuai jam dan prioritas yang ditentukan'],
                ['DLV_SAFETY', 'Keamanan Barang', '%', self::NUM, self::HIGH, 'Persentase pengiriman sampai dalam kondisi baik tanpa komplain kelalaian driver'],
                ['DLV_ADMIN', 'Kelengkapan Dokumen Pengiriman', '%', self::NUM, self::HIGH, 'Surat jalan/nota dibawa, ditandatangani penerima, dan dikembalikan lengkap'],
                ['DLV_VEHICLE', 'Perawatan & Kebersihan Kendaraan', 'ya/tidak', self::YN, self::HIGH, 'Kendaraan bersih; cek oli, ban, lampu, rem; kerusakan dilaporkan dini'],
                ['DLV_FUEL', 'Efisiensi BBM & Rute', '%', self::NUM, self::LOW, 'Pemakaian BBM aktual dibanding jatah; tanpa pemakaian pribadi'],
            ],
            'Marketing & Promosi' => [
                ['MKT_REVENUE', 'Pencapaian Omzet Program Marketing', '%', self::NUM, self::HIGH, 'Realisasi penjualan dari program marketing dibanding target'],
                ['MKT_NEWCUST', 'Customer Baru', 'orang', self::NUM, self::HIGH, 'Jumlah customer baru'],
                ['MKT_REPEAT', 'Repeat Order Customer', '%', self::NUM, self::HIGH, 'Persentase customer yang kembali membeli'],
                ['MKT_PROMO', 'Efektivitas Promo', '%', self::NUM, self::HIGH, 'Penjualan dari promo dibanding target promo'],
                ['MKT_CONTENT', 'Konten Sosmed Diunggah', 'konten', self::NUM, self::HIGH, 'Jumlah konten yang diupload'],
                ['MKT_ENGAGE', 'Engagement Sosmed', 'interaksi', self::NUM, self::HIGH, 'Total like, komentar, share, dan save'],
                ['MKT_LEADS', 'Leads Customer Baru', 'leads', self::NUM, self::HIGH, 'Data customer baru terkumpul (WA/member)'],
                ['MKT_PARTNER', 'Mitra Kerjasama Baru', 'mitra', self::NUM, self::HIGH, 'Mitra baru: kantor, sekolah, reseller, event'],
                ['MKT_ACTIVATION', 'Aktivasi Marketing', 'kegiatan', self::NUM, self::HIGH, 'Event, sampling, booth, promo offline'],
                ['MKT_COMPETITOR', 'Laporan Analisa Kompetitor', 'laporan', self::NUM, self::HIGH, 'Laporan harga & promo pesaing'],
                ['MKT_BUDGET', 'Efisiensi Budget Marketing', '%', self::NUM, self::LOW, 'Realisasi biaya promosi dibanding budget'],
                ['MKT_NPD', 'Evaluasi Produk Baru (NPD)', 'produk', self::NUM, self::HIGH, 'Riset & launching produk baru'],
            ],
        ];
    }

    /**
     * posisi => [[code, target, bobot, frequency], ...]  (total bobot = 100)
     * Target dari form owner kecuali yang ditandai [USULAN].
     */
    private function assignments(): array
    {
        return [
            'Store Manager' => [
                ['SAL_TARGET', 100, 15, self::M],
                ['SAL_GROWTH', 5, 6, self::M],
                ['SAL_BASKET', 10, 6, self::M],
                ['FIN_FOODCOST', 35, 8, self::M],      // [USULAN] owner: "sesuai target perusahaan"
                ['FIN_WASTE', 1, 7, self::M],
                ['INV_ACCURACY', 98, 6, self::W],
                ['INV_OPNAME', 1, 3, self::W],
                ['SVC_CSAT', 90, 7, self::M],
                ['SVC_COMPLAINT', 100, 5, self::D],
                ['CLN_STORE', 95, 5, self::W],
                ['OPS_SOP', 95, 8, self::D],
                ['OPS_DISPLAY', 98, 5, self::D],
                ['HR_TEAM_ATTEND', 98, 6, self::M],
                ['HR_BRIEFING', 100, 4, self::D],
                ['HR_TRAINING', 1, 4, self::M],
                ['OPS_PROMO', 100, 5, self::W],
            ],
            'Frontliner' => [
                ['SAL_TARGET', 100, 20, self::D],
                ['SAL_BASKET', 10, 8, self::M],
                ['SAL_UPSELL', 30, 7, self::D],        // [USULAN]
                ['SVC_QUALITY', 90, 12, self::D],      // [USULAN]
                ['HR_ATTEND', 98, 12, self::D],        // [USULAN]
                ['SVC_PRODKNOW', 85, 8, self::M],      // [USULAN]
                ['FIN_CASHACC', 1, 12, self::D],
                ['CLN_DISPLAY', 95, 11, self::D],      // [USULAN]
                ['OPS_SOP', 95, 10, self::D],          // [USULAN]
            ],
            'Produksi' => [
                ['PRD_OUTPUT', 100, 18, self::D],
                ['PRD_REJECT', 2, 15, self::D],
                ['PRD_ONTIME', 100, 12, self::D],
                ['PRD_RMWASTE', 3, 10, self::D],       // [USULAN]
                ['PRD_SOP', 100, 15, self::D],
                ['CLN_PROD', 95, 10, self::D],         // [USULAN]
                ['HR_ATTEND', 98, 12, self::D],        // [USULAN]
                ['PRD_EQUIP', 1, 8, self::D],
            ],
            'Driver' => [
                ['DLV_ONTIME', 95, 20, self::D],       // [USULAN]
                ['DLV_SAFETY', 100, 18, self::D],
                ['DLV_ADMIN', 100, 12, self::D],
                ['DLV_VEHICLE', 1, 12, self::D],
                ['DLV_FUEL', 100, 10, self::W],        // maks 100% dari jatah BBM
                ['HR_ATTEND', 98, 15, self::D],        // [USULAN]
                ['SVC_QUALITY', 90, 13, self::W],      // [USULAN]
            ],
            'Sales' => [
                ['MKT_REVENUE', 100, 22, self::M],
                ['MKT_NEWCUST', 30, 9, self::M],       // [USULAN] semua target marketing
                ['MKT_REPEAT', 40, 8, self::M],
                ['MKT_PROMO', 100, 8, self::M],
                ['MKT_CONTENT', 20, 6, self::M],
                ['MKT_ENGAGE', 500, 6, self::M],
                ['MKT_LEADS', 50, 8, self::M],
                ['MKT_PARTNER', 2, 7, self::M],
                ['MKT_ACTIVATION', 2, 7, self::M],
                ['MKT_COMPETITOR', 1, 5, self::M],
                ['MKT_BUDGET', 100, 8, self::M],
                ['MKT_NPD', 1, 6, self::M],
            ],
            'SPV' => [
                ['SAL_TARGET', 100, 20, self::D],
                ['OPS_OPENTIME', 1, 5, self::D],
                ['OPS_SOP', 95, 8, self::D],
                ['OPS_DISPLAY', 98, 5, self::D],
                ['SVC_QUALITY', 90, 8, self::D],
                ['SVC_COMPLAINT', 100, 4, self::D],
                ['CLN_DISPLAY', 95, 8, self::D],
                ['FIN_WASTE', 1, 8, self::M],
                ['INV_ACCURACY', 98, 8, self::W],
                ['CLN_STORE', 95, 6, self::W],
                ['HR_TEAMMGMT', 85, 8, self::W],       // [USULAN]
                ['HR_TEAM_ATTEND', 98, 4, self::M],
                ['OPS_REPORT', 100, 4, self::D],       // [USULAN]
                ['OPS_IMPROVE', 1, 4, self::M],        // [USULAN]
            ],
        ];
    }
}
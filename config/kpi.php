<?php

return [
    // Indikator dianggap "tercapai" bila skor pencapaian >= nilai ini (%)
    'achieved_threshold' => 100.0,

    'estimated_minutes' => '5-10',

    // nama kategori di DB => label grup di dashboard (urutan = urutan tampil)
    'dashboard_groups' => [
        'Penjualan'              => 'Penjualan',
        'Laba & Biaya'           => 'Laba & Persediaan',
        'Persediaan & Stok'      => 'Laba & Persediaan',
        'Pelayanan Pelanggan'    => 'Pelayanan',
        'Operasional & SOP'      => 'Operasional & SDM',
        'Kebersihan & Display'   => 'Operasional & SDM',
        'SDM & Disiplin'         => 'Operasional & SDM',
        'Marketing & Promosi'    => 'Promosi & Event',
        'Produksi'               => 'Produksi',
        'Pengiriman & Kendaraan' => 'Pengiriman',
    ],
];
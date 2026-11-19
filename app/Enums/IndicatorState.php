<?php 

namespace App\Enums;

enum IndicatorState: string
{
    case Achieved    = 'ACHIEVED';      // hijau  - Tercapai
    case Unfilled    = 'UNFILLED';      // kuning - Belum diisi
    case NotAchieved = 'NOT_ACHIEVED';  // merah  - Tidak tercapai
}
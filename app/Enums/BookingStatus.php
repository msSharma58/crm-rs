<?php

declare(strict_types=1);

namespace App\Enums;

enum BookingStatus: string
{
    case Draft = 'draft';
    case Reserved = 'reserved';
    case Booked = 'booked';
    case Cancelled = 'cancelled';
    case Sold = 'sold';
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadSource: string
{
    case Facebook = 'facebook';
    case Website = 'website';
    case LandingPage = 'landing_page';
    case WhatsApp = 'whatsapp';
    case Phone = 'phone';
    case Manual = 'manual';
    case Referral = 'referral';
    case WalkIn = 'walk_in';
    case ImportCsv = 'import_csv';
}

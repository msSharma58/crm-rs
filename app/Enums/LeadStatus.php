<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Interested = 'interested';
    case Qualified = 'qualified';
    case SiteVisitScheduled = 'site_visit_scheduled';
    case Visited = 'visited';
    case Negotiation = 'negotiation';
    case Booking = 'booking';
    case PaymentPending = 'payment_pending';
    case Sold = 'sold';
    case Lost = 'lost';
    case Cancelled = 'cancelled';
}

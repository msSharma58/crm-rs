export type LeadStatus =
    | 'new'
    | 'contacted'
    | 'interested'
    | 'qualified'
    | 'site_visit_scheduled'
    | 'visited'
    | 'negotiation'
    | 'booking'
    | 'payment_pending'
    | 'sold'
    | 'lost'
    | 'cancelled';

export const LEAD_STATUSES: LeadStatus[] = [
    'new',
    'contacted',
    'interested',
    'qualified',
    'site_visit_scheduled',
    'visited',
    'negotiation',
    'booking',
    'payment_pending',
    'sold',
    'lost',
    'cancelled',
];

export const LEAD_STATUS_LABELS: Record<LeadStatus, string> = {
    new: 'New',
    contacted: 'Contacted',
    interested: 'Interested',
    qualified: 'Qualified',
    site_visit_scheduled: 'Site Visit Scheduled',
    visited: 'Visited',
    negotiation: 'Negotiation',
    booking: 'Booking',
    payment_pending: 'Payment Pending',
    sold: 'Sold',
    lost: 'Lost',
    cancelled: 'Cancelled',
};

export const LEAD_STATUS_COLORS: Record<LeadStatus, string> = {
    new: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    contacted: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    interested: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300',
    qualified: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
    site_visit_scheduled: 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
    visited: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
    negotiation: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    booking: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
    payment_pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
    sold: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    lost: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    cancelled: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
};

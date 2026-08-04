export type {
    LeadStatus,
    LEAD_STATUSES,
    LEAD_STATUS_LABELS,
} from './lead';

export interface Organization {
    id: number;
    name: string;
    slug: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    country: string | null;
    timezone: string | null;
    currency: string | null;
    locale: string | null;
    branding: Record<string, unknown> | null;
    status: string;
    created_at: string | null;
}

export interface User {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    organization_id: number;
    is_super_admin: boolean;
    is_active: boolean;
    avatar_path: string | null;
    last_login_at: string | null;
    roles?: string[];
    organization?: Organization;
    created_at: string | null;
}

export interface Lead {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    location: string | null;
    budget: number | null;
    preferred_property: string | null;
    source: string | null;
    external_provider?: string | null;
    external_id?: string | null;
    integration_meta?: Record<string, unknown> | null;
    status: import('./lead').LeadStatus;
    priority: string | null;
    ai_score: number | null;
    notes: string | null;
    last_contacted_at: string | null;
    converted_at: string | null;
    project?: Project;
    assignee?: User;
    campaign?: Campaign;
    activities?: LeadActivity[];
    created_at: string | null;
    updated_at: string | null;
}

export interface LeadActivity {
    id: number;
    type: string;
    title?: string | null;
    description: string;
    properties?: Record<string, unknown> | null;
    meta?: Record<string, unknown> | null;
    created_at: string | null;
    user?: User;
}

export interface IntegrationSetting {
    id: number;
    provider: 'facebook' | 'whatsapp' | string;
    is_active: boolean;
    settings: Record<string, unknown>;
    webhook_verify_token: string | null;
    has_page_access_token: boolean;
    has_access_token: boolean;
    last_synced_at: string | null;
    webhook_url: string;
    updated_at: string | null;
}

export interface IntegrationMessage {
    id: number;
    lead_id: number | null;
    provider: string;
    direction: 'inbound' | 'outbound' | string;
    channel: string;
    external_id: string | null;
    from_number: string | null;
    to_number: string | null;
    message_type: string;
    body: string | null;
    status: string;
    sent_at: string | null;
    created_at: string | null;
}

export interface FamilyMember {
    id: number;
    name: string;
    relation: string | null;
    phone: string | null;
}

export interface Customer {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    location: string | null;
    occupation: string | null;
    meta: Record<string, unknown> | null;
    lead_id: number | null;
    assignee?: User;
    lead?: Lead;
    family_members?: FamilyMember[];
    bookings?: Booking[];
    created_at: string | null;
    updated_at: string | null;
}

export interface Project {
    id: number;
    name: string;
    code: string | null;
    location: string | null;
    latitude: number | null;
    longitude: number | null;
    description: string | null;
    status: string;
    amenities: string[] | null;
    units_count?: number;
    buildings_count?: number;
    units?: Unit[];
    created_at: string | null;
    updated_at: string | null;
}

export interface Unit {
    id: number;
    code: string;
    type: string | null;
    area_sqft: number | null;
    price: number | null;
    status: string;
    attributes: Record<string, unknown> | null;
    project?: Project;
    created_at: string | null;
    updated_at: string | null;
}

export interface Booking {
    id: number;
    code: string;
    status: string;
    booking_amount: number | null;
    total_amount: number | null;
    discount_amount: number | null;
    booked_at: string | null;
    negotiation_notes: string | null;
    customer?: Customer;
    unit?: Unit;
    project?: Project;
    sales_executive?: User;
    created_at: string | null;
    updated_at: string | null;
}

export interface Payment {
    id: number;
    amount: number;
    method: string | null;
    status: string;
    receipt_no: string | null;
    paid_at: string | null;
    notes: string | null;
    customer?: Customer;
    booking?: Booking;
    created_at: string | null;
}

export interface Visit {
    id: number;
    scheduled_at: string | null;
    checked_in_at: string | null;
    status: string;
    location: string | null;
    latitude: number | null;
    longitude: number | null;
    notes: string | null;
    lead?: Lead;
    customer?: Customer;
    project?: Project;
    assignee?: User;
    created_at: string | null;
}

export interface FollowUp {
    id: number;
    channel: string | null;
    title: string;
    notes: string | null;
    due_at: string | null;
    completed_at: string | null;
    status: string;
    priority: string | null;
    lead?: Lead;
    customer?: Customer;
    assignee?: User;
    created_at: string | null;
}

export interface Task {
    id: number;
    title: string;
    description: string | null;
    priority: string | null;
    status: string;
    due_at: string | null;
    completed_at: string | null;
    assignee?: User;
    creator?: User;
    created_at: string | null;
}

export interface Document {
    id: number;
    type: string;
    title: string;
    current_path: string | null;
    version: number;
    uploader?: User;
    created_at: string | null;
    updated_at: string | null;
}

export interface Campaign {
    id: number;
    name: string;
    channel: string | null;
    budget: number | null;
    spend: number | null;
    starts_at: string | null;
    ends_at: string | null;
    status: string;
    leads_count?: number;
    converted_count?: number;
    conversion_rate?: number;
    cost_per_lead?: number;
    budget_utilization?: number;
    roi?: number | null;
    created_at: string | null;
}

export interface ReportsOverview {
    total_leads: number;
    total_bookings: number;
    total_revenue: number;
    conversion_rate: number;
    leads: DashboardKpis['leads'];
    sales: DashboardKpis['sales'];
    operations: DashboardKpis['operations'];
    inventory: DashboardKpis['inventory'];
}

export interface LeadsReport {
    period: { from: string; to: string };
    by_source: Record<string, number>;
    by_status: Record<string, number>;
    total: number;
}

export interface OutstandingPayments {
    total_outstanding: number;
    count: number;
    items: Array<{
        id: number;
        label: string;
        amount: number;
        paid: number;
        outstanding: number;
        due_date: string | null;
        status: string;
        booking: {
            id: number;
            code: string;
            customer?: { id: number; name: string; phone?: string };
            unit?: { id: number; code: string };
        } | null;
    }>;
}

export interface DashboardKpis {
    leads: {
        total: number;
        new_this_month: number;
        converted: number;
        conversion_rate: number;
        by_status: Record<string, number>;
        hot_leads: number;
    };
    sales: {
        total_bookings: number;
        active_bookings: number;
        revenue_collected: number;
        pending_payments: number;
        sold_units: number;
    };
    operations: {
        visits_today: number;
        visits_this_week: number;
        pending_follow_ups: number;
        overdue_follow_ups: number;
        total_customers: number;
    };
    inventory: {
        total_units: number;
        available: number;
        reserved: number;
        sold: number;
        blocked: number;
    };
}

export interface PaginationMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
}

export interface Paginated<T> {
    data: T[];
    meta: PaginationMeta;
    links?: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
}

export interface ApiResponse<T> {
    success: boolean;
    message: string;
    data: T;
}

export interface LoginResponse {
    token: string;
    user: User;
}

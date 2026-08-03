<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\LeadPriority;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\UnitStatus;
use App\Models\AutomationRule;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\FollowUp;
use App\Models\Floor;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TilottamaHomesSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::create([
            'name' => 'Tilottama Homes',
            'slug' => 'tilottama-homes',
            'email' => 'info@tilottamahomes.com.np',
            'phone' => '+977-61-123456',
            'address' => 'Lakeside Road, Pokhara',
            'city' => 'Pokhara',
            'country' => 'NP',
            'timezone' => 'Asia/Kathmandu',
            'currency' => 'NPR',
            'locale' => 'en',
            'branding' => [
                'primary_color' => '#1f6feb',
                'logo' => null,
            ],
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $roles = [
            'org_admin',
            'sales_manager',
            'sales_executive',
            'marketing_manager',
            'accountant',
            'support_staff',
        ];

        $orgRoles = [];
        foreach ($roles as $roleName) {
            $globalRole = Role::where('name', $roleName)->whereNull('organization_id')->first();
            $orgRole = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'organization_id' => $organization->id,
            ]);
            if ($globalRole) {
                $orgRole->syncPermissions($globalRole->permissions);
            }
            $orgRoles[$roleName] = $orgRole;
        }

        $users = [
            'org_admin' => User::create([
                'name' => 'Rajesh Sharma',
                'email' => 'admin@tilottamahomes.com.np',
                'password' => Hash::make('password'),
                'phone' => '+977-9800000001',
                'organization_id' => $organization->id,
                'is_active' => true,
            ]),
            'sales_manager' => User::create([
                'name' => 'Sunita Gurung',
                'email' => 'sales.manager@tilottamahomes.com.np',
                'password' => Hash::make('password'),
                'phone' => '+977-9800000002',
                'organization_id' => $organization->id,
                'is_active' => true,
            ]),
            'sales_executive' => User::create([
                'name' => 'Anil Thapa',
                'email' => 'anil@tilottamahomes.com.np',
                'password' => Hash::make('password'),
                'phone' => '+977-9800000003',
                'organization_id' => $organization->id,
                'is_active' => true,
            ]),
            'sales_executive_2' => User::create([
                'name' => 'Priya Karki',
                'email' => 'priya@tilottamahomes.com.np',
                'password' => Hash::make('password'),
                'phone' => '+977-9800000004',
                'organization_id' => $organization->id,
                'is_active' => true,
            ]),
            'marketing_manager' => User::create([
                'name' => 'Bikash Adhikari',
                'email' => 'marketing@tilottamahomes.com.np',
                'password' => Hash::make('password'),
                'phone' => '+977-9800000005',
                'organization_id' => $organization->id,
                'is_active' => true,
            ]),
            'accountant' => User::create([
                'name' => 'Maya Shrestha',
                'email' => 'accounts@tilottamahomes.com.np',
                'password' => Hash::make('password'),
                'phone' => '+977-9800000006',
                'organization_id' => $organization->id,
                'is_active' => true,
            ]),
            'support_staff' => User::create([
                'name' => 'Kiran Poudel',
                'email' => 'support@tilottamahomes.com.np',
                'password' => Hash::make('password'),
                'phone' => '+977-9800000007',
                'organization_id' => $organization->id,
                'is_active' => true,
            ]),
        ];

        $users['org_admin']->assignRole($orgRoles['org_admin']);
        $users['sales_manager']->assignRole($orgRoles['sales_manager']);
        $users['sales_executive']->assignRole($orgRoles['sales_executive']);
        $users['sales_executive_2']->assignRole($orgRoles['sales_executive']);
        $users['marketing_manager']->assignRole($orgRoles['marketing_manager']);
        $users['accountant']->assignRole($orgRoles['accountant']);
        $users['support_staff']->assignRole($orgRoles['support_staff']);

        User::create([
            'name' => 'Super Admin',
            'email' => 'super@homora.test',
            'password' => Hash::make('password'),
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $campaign = Campaign::create([
            'organization_id' => $organization->id,
            'name' => 'Facebook Spring Campaign 2026',
            'channel' => 'facebook',
            'budget' => 150000,
            'spend' => 45000,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonths(2),
            'status' => 'active',
        ]);

        $project = Project::create([
            'organization_id' => $organization->id,
            'name' => 'Tilottama Residency',
            'code' => 'TR-01',
            'location' => 'Lakeside, Pokhara',
            'latitude' => 28.2096,
            'longitude' => 83.9856,
            'description' => 'Premium residential apartments with lake view.',
            'status' => 'active',
            'amenities' => ['parking', 'gym', 'swimming_pool', 'security'],
        ]);

        $building = Building::create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'name' => 'Tower A',
            'floors_count' => 5,
        ]);

        $floors = [];
        for ($i = 1; $i <= 5; $i++) {
            $floors[$i] = Floor::create([
                'organization_id' => $organization->id,
                'building_id' => $building->id,
                'name' => "Floor {$i}",
                'level' => $i,
            ]);
        }

        $units = [];
        $unitTypes = ['1BHK', '2BHK', '3BHK'];
        $unitNumber = 1;
        foreach ($floors as $level => $floor) {
            for ($u = 1; $u <= 4; $u++) {
                $status = match (true) {
                    $unitNumber <= 3 => UnitStatus::Sold,
                    $unitNumber <= 6 => UnitStatus::Reserved,
                    $unitNumber === 7 => UnitStatus::Blocked,
                    default => UnitStatus::Available,
                };

                $units[] = Unit::create([
                    'organization_id' => $organization->id,
                    'project_id' => $project->id,
                    'building_id' => $building->id,
                    'floor_id' => $floor->id,
                    'code' => sprintf('A-%d%02d', $level, $u),
                    'type' => $unitTypes[($unitNumber - 1) % 3],
                    'area_sqft' => 850 + ($unitNumber * 50),
                    'price' => 3500000 + ($unitNumber * 250000),
                    'status' => $status,
                    'attributes' => ['facing' => $u % 2 === 0 ? 'lake' : 'garden'],
                ]);
                $unitNumber++;
            }
        }

        $leadStatuses = [
            LeadStatus::New,
            LeadStatus::Contacted,
            LeadStatus::Interested,
            LeadStatus::Qualified,
            LeadStatus::SiteVisitScheduled,
            LeadStatus::Visited,
            LeadStatus::Negotiation,
            LeadStatus::Booking,
            LeadStatus::PaymentPending,
            LeadStatus::Sold,
            LeadStatus::Lost,
        ];

        $leadNames = [
            'Ram Bahadur KC', 'Sita Devi', 'Hari Prasad', 'Gita Sharma',
            'Mohan Rai', 'Laxmi Gurung', 'Bishnu Tamang', 'Sarita Limbu',
            'Dipak Magar', 'Anju Thapa', 'Ramesh Koirala', 'Pooja Adhikari',
        ];

        $leads = [];
        foreach ($leadNames as $index => $name) {
            $status = $leadStatuses[$index % count($leadStatuses)];
            $leads[] = Lead::create([
                'organization_id' => $organization->id,
                'name' => $name,
                'phone' => '+977-98'.str_pad((string) (1000000 + $index), 7, '0', STR_PAD_LEFT),
                'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
                'location' => 'Pokhara',
                'budget' => 4000000 + ($index * 500000),
                'preferred_property' => $unitTypes[$index % 3],
                'project_id' => $project->id,
                'source' => collect(LeadSource::cases())->random()->value,
                'campaign_id' => $index % 3 === 0 ? $campaign->id : null,
                'status' => $status,
                'assigned_to' => $index % 2 === 0 ? $users['sales_executive']->id : $users['sales_executive_2']->id,
                'priority' => collect(LeadPriority::cases())->random()->value,
                'ai_score' => rand(40, 95),
                'created_by' => $users['sales_manager']->id,
            ]);
        }

        $customers = [];
        foreach (array_slice($leads, 0, 5) as $lead) {
            $customers[] = Customer::create([
                'organization_id' => $organization->id,
                'lead_id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'location' => $lead->location,
                'assigned_to' => $lead->assigned_to,
            ]);
            $lead->update(['converted_at' => now()->subDays(rand(1, 30))]);
        }

        foreach (array_slice($leads, 3, 4) as $index => $lead) {
            Visit::create([
                'organization_id' => $organization->id,
                'lead_id' => $lead->id,
                'project_id' => $project->id,
                'assigned_to' => $lead->assigned_to,
                'scheduled_at' => now()->addDays($index + 1),
                'status' => $index < 2 ? 'completed' : 'scheduled',
                'location' => $project->location,
                'checked_in_at' => $index < 2 ? now()->subDays($index) : null,
            ]);
        }

        foreach (array_slice($leads, 0, 6) as $index => $lead) {
            FollowUp::create([
                'organization_id' => $organization->id,
                'lead_id' => $lead->id,
                'assigned_to' => $lead->assigned_to,
                'channel' => ['call', 'whatsapp', 'email'][$index % 3],
                'title' => 'Follow up with '.$lead->name,
                'due_at' => now()->addDays($index),
                'status' => $index < 2 ? 'completed' : 'pending',
                'priority' => $lead->priority->value,
                'completed_at' => $index < 2 ? now() : null,
            ]);
        }

        $booking = Booking::create([
            'organization_id' => $organization->id,
            'customer_id' => $customers[0]->id,
            'lead_id' => $customers[0]->lead_id,
            'unit_id' => $units[0]->id,
            'project_id' => $project->id,
            'sales_executive_id' => $users['sales_executive']->id,
            'code' => 'BK-TILO-001',
            'status' => BookingStatus::Booked,
            'booking_amount' => 500000,
            'total_amount' => $units[0]->price,
            'discount_amount' => 100000,
            'booked_at' => now()->subDays(15)->toDateString(),
        ]);

        PaymentSchedule::create([
            'organization_id' => $organization->id,
            'booking_id' => $booking->id,
            'label' => 'Booking Amount',
            'amount' => 500000,
            'due_date' => now()->subDays(15)->toDateString(),
            'status' => 'paid',
            'sequence' => 1,
        ]);

        $schedule2 = PaymentSchedule::create([
            'organization_id' => $organization->id,
            'booking_id' => $booking->id,
            'label' => 'First Installment',
            'amount' => 1000000,
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'partial',
            'sequence' => 2,
        ]);

        Payment::create([
            'organization_id' => $organization->id,
            'booking_id' => $booking->id,
            'payment_schedule_id' => $schedule2->id,
            'customer_id' => $customers[0]->id,
            'recorded_by' => $users['accountant']->id,
            'amount' => 500000,
            'method' => 'bank',
            'status' => 'completed',
            'receipt_no' => 'RCP-001',
            'paid_at' => now()->subDays(5)->toDateString(),
        ]);

        Payment::create([
            'organization_id' => $organization->id,
            'booking_id' => $booking->id,
            'customer_id' => $customers[0]->id,
            'recorded_by' => $users['accountant']->id,
            'amount' => 500000,
            'method' => 'cash',
            'status' => 'completed',
            'receipt_no' => 'RCP-002',
            'paid_at' => now()->subDays(15)->toDateString(),
        ]);

        AutomationRule::create([
            'organization_id' => $organization->id,
            'name' => 'Auto follow-up on new lead',
            'trigger' => 'lead_created',
            'conditions' => null,
            'actions' => [
                ['type' => 'create_follow_up', 'title' => 'Initial contact call', 'due_in_hours' => 2, 'channel' => 'call'],
                ['type' => 'create_task', 'title' => 'Review lead details', 'due_in_days' => 1],
            ],
            'is_active' => true,
        ]);
    }
}

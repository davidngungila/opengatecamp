<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\Family;
use App\Models\ReceiptPayment;
use App\Models\FinancialYear;
use App\Models\Group;
use App\Models\Member;
use App\Models\Ministry;
use App\Models\Pledge;
use App\Models\PledgePayment;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $year = now()->year;

        FinancialYear::updateOrCreate(
            ['name' => 'FY '.$year],
            ['start_date' => "$year-01-01", 'end_date' => "$year-12-31", 'is_default' => true]
        );
        FinancialYear::updateOrCreate(
            ['name' => 'FY '.($year - 1)],
            ['start_date' => ($year - 1).'-01-01', 'end_date' => ($year - 1).'-12-31', 'is_default' => false]
        );

        foreach ([
            'Super Administrator' => Role::PERMISSIONS,
            'Chairperson' => Role::PERMISSIONS,
            'Secretary' => ['members.view', 'members.manage', 'events.manage', 'events.complete', 'pledges.manage', 'communication.send', 'documents.view', 'documents.manage', 'reports.view', 'reports.export', 'audit.view'],
            'Treasurer' => ['members.view', 'pledges.manage', 'finance.view', 'finance.manage', 'finance.approve', 'documents.view', 'reports.view', 'reports.export', 'audit.view'],
            'Committee Member' => ['members.view', 'events.manage', 'communication.send', 'documents.view', 'reports.view'],
        ] as $roleName => $permissions) {
            Role::updateOrCreate(['name' => $roleName], ['permissions' => $permissions]);
        }

        $super = Role::where('name', 'Super Administrator')->first();

        User::updateOrCreate(
            ['email' => 'camp.director@opengatecamp.org'],
            ['name' => 'Daniel Mwinuka', 'password' => Hash::make('password'), 'role_id' => $super->id, 'status' => 'Active']
        );
        User::updateOrCreate(
            ['email' => 'secretary@opengatecamp.org'],
            ['name' => 'Grace Kileo', 'password' => Hash::make('password'), 'role_id' => Role::where('name', 'Secretary')->value('id'), 'status' => 'Active']
        );
        User::updateOrCreate(
            ['email' => 'finance@opengatecamp.org'],
            ['name' => 'Peter Mwakalinga', 'password' => Hash::make('password'), 'role_id' => Role::where('name', 'Treasurer')->value('id'), 'status' => 'Active']
        );
        User::updateOrCreate(
            ['email' => 'programs@opengatecamp.org'],
            ['name' => 'Mary Massawe', 'password' => Hash::make('password'), 'role_id' => Role::where('name', 'Committee Member')->value('id'), 'status' => 'Suspended']
        );

        Setting::put('church.name', 'Open Gate Camp Mission');
        Setting::put('church.phone', '+255 713 000 123');
        Setting::put('church.email', 'info@opengatecamp.org');
        Setting::put('church.website', 'www.opengatecamp.org');
        Setting::put('church.address', 'Morogoro, Tanzania');
        Setting::put('church.chaplain', 'Daniel Mwinuka');

        Setting::put('notify.email', '1');
        Setting::put('notify.sms', '1');
        Setting::put('notify.push', '1');
        Setting::put('notify.digest', '0');
        Setting::put('notify.payment_alerts', '0');

        Setting::put('security.2fa', '0');
        Setting::put('security.strong_password', '1');
        Setting::put('security.auto_timeout', '1');

        Setting::put('fellowships.list', implode("\n", [
            'MoCU',
            'MWECAU',
            'KCMC University',
            'SMMUCo',
            'Mwenge Catholic University – Hedaru Campus',
            'SMMUCo – Mwika Centre',
            'KICHAS',
            'Northern College of Health and Allied Sciences',
            'Kilimanjaro School of Pharmacy',
            'Kilema College of Health Sciences',
            'Kibosho Institute of Health and Allied Sciences',
            'Faraja Health Training Institute',
            'Zawadi Memorial Health Training Institute',
            'Moshi Regional Vocational Training Centre',
            'FITI',
            'Kilimanjaro Agricultural Training Centre',
            'College of African Wildlife Management – Mweka',
            'Kilacha Agriculture and Livestock Training Institute',
            'TRITA – Moshi',
            'Tanzania Police School – Moshi',
            'Mwika Training College',
            'Moshi Institute of Technology (TAREO)',
            'Saint Luke Foundation – Kilimanjaro School of Pharmacy',
            'KCMC School of Nursing',
            'KCMC School of Physiotherapy',
            'Udzungwa Mountains College Trust',
            'Ewaso Maasai College Trust',
            'Northern Highlands Teachers\' College',
            'YCS College',
            'The Amazon College',
            'NM-AIST',
            'UoA',
            'TUMA',
            'SAUT – Arusha Centre',
            'ATC',
            'Arusha Adventist College',
            'Arusha Institute of Business Studies',
            'Arusha East African Training Institute',
            'National College of Tourism – Arusha',
            'Forestry Training Institute – Olmotonyi',
            'ESAMI',
            'IAA',
            'Kilimanjaro Institute of Health Sciences',
            'Centre for Educational Development in Health – Arusha',
            'City College of Health and Allied Sciences – Arusha',
            'Medical Missionaries of Mary School of Pharmaceutical Sciences',
            'Legacy College of Tourism and Business Studies',
            'MEGA College',
            'Ecassa Institute of Social Protection',
            'Fanikiwa Journalism School',
            'Tanzania Gemological Centre',
            'VHTTI',
            'Jr Institute of Information Technology',
            'Baptist Bible College and Community Center',
            'Savanna Bridge College',
            'Starlink Vocational Training Center – Arusha',
            'Dr. Richard Raj College of Health and Allied Sciences – Arusha',
            'Excellent College of Health and Allied Sciences – Arusha',
            'Arusha Lutheran Medical Training Centre',
            'Kilimanjaro International Institute for Telecommunications, Electronics and Computers',
            'Habari Maalum College',
            'Arusha College of Administration',
            'Other',
        ]));

        Setting::put('sms.api_token', '8b4d46ca83411b8457e0fb8c3a77d02a');
        Setting::put('sms.sender_id', 'TMCS MoCU');

        Setting::put('acct.default_cash', '1010');
        Setting::put('acct.default_bank', '1000');
        Setting::put('acct.default_mobile', '1020');
        Setting::put('acct.pledge_income', '4020');
        Setting::put('acct.attendee_income', '4040');

        $groupNames = ['St. Cecilia Choir', 'Young Adults', 'Legion of Mary', 'St. Vincent de Paul', 'Catholic Women Assoc.', 'Men of Open Gate', 'Altar Servers', 'Bible Study Circle'];
        foreach ($groupNames as $g) {
            Group::updateOrCreate(['name' => $g], ['meeting_schedule' => 'Every Sunday, 10:00 AM']);
        }

        $ministryNames = ['Youth Ministry', "Women's Ministry", "Men's Ministry", "Children's Ministry", 'Choir Ministry', 'Ushering Ministry', 'Evangelization', 'Media & Communications'];
        foreach ($ministryNames as $m) {
            Ministry::updateOrCreate(['name' => $m]);
        }

        $families = [
            ['Mushi Family', 'James Mushi'], ['Kileo Family', 'Grace Kileo'], ['Mwakalinga Family', 'Peter Mwakalinga'],
            ['Massawe Family', 'Mary Massawe'], ['Kimaro Family', 'John Kimaro'], ['Shayo Family', 'Elizabeth Shayo'],
        ];
        foreach ($families as [$name, $head]) {
            Family::updateOrCreate(['name' => $name], ['head' => $head]);
        }

        $rows = [
            // no, name, gender, phone, familyIdx, groupIdx, ministryIdx, status, memberType, staffType
            ['CHP-1001', 'James Mushi', 'Male', '+255 715 234 881', 0, 4, 4, 'Active', 'non_student', 'staff'],
            ['CHP-1002', 'Grace Kileo', 'Female', '+255 762 118 402', 1, 1, 0, 'New', 'student', null],
            ['CHP-1003', 'Peter Mwakalinga', 'Male', '+255 748 920 133', 2, 2, 2, 'Active', 'non_student', 'staff'],
            ['CHP-1004', 'Mary Massawe', 'Female', '+255 754 663 210', 3, 4, 1, 'Active', 'non_student', 'non_staff'],
            ['CHP-1005', 'John Kimaro', 'Male', '+255 782 340 556', 4, 6, 3, 'Inactive', 'non_student', 'staff'],
            ['CHP-1006', 'Elizabeth Shayo', 'Female', '+255 713 807 342', 5, 4, 1, 'Active', 'non_student', 'non_staff'],
            ['CHP-1007', 'Joseph Lyimo', 'Male', '+255 795 122 674', 0, 5, 2, 'New', 'student', null],
            ['CHP-1008', 'Anna Ndosi', 'Female', '+255 683 551 098', 1, 7, 6, 'Active', 'non_student', 'non_staff'],
            ['CHP-1009', 'Daniel Kessy', 'Male', '+255 717 442 908', 2, 7, 6, 'Active', 'student', null],
            ['CHP-1010', 'Veronica Sanga', 'Female', '+255 745 330 217', 3, 2, 1, 'Inactive', 'non_student', 'non_staff'],
            ['CHP-1011', 'Francis Temba', 'Male', '+255 764 208 533', 4, 1, 0, 'New', 'student', null],
            ['CHP-1012', 'Monica Urio', 'Female', '+255 689 774 120', 5, 3, 5, 'Active', 'non_student', 'staff'],
        ];

        foreach ($rows as [$no, $name, $gender, $phone, $familyIdx, $groupIdx, $ministryIdx, $status, $memberType, $staffType]) {
            Member::updateOrCreate(['member_no' => $no], [
                'name' => $name,
                'gender' => $gender,
                'phone' => $phone,
                'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
                'address' => rand(10, 300).' Kaloleni St, Dar es Salaam',
                'family_id' => Family::skip($familyIdx)->value('id'),
                'group_id' => Group::skip($groupIdx)->value('id'),
                'ministry_id' => Ministry::skip($ministryIdx)->value('id'),
                'marital_status' => 'Married',
                'status' => $status,
                'member_type' => $memberType,
                'staff_type' => $staffType,
                'joined_on' => $status === 'New' ? now()->subDays(rand(5, 60)) : now()->subMonths(rand(8, 30)),
            ]);
        }

$eventsData = [
            ['OGM-CAMP-2026', 'Open Gate Summer Camp', 'camp', 'Annual youth camp at Morogoro base camp.', 'Open Gate Base Camp', 'Morogoro', now()->subMonths(2)->toDateString(), now()->subMonths(2)->addDays(6)->toDateString(), '06:00', '17:30', 'completed', 'Daniel Mwinuka'],
            ['OGM-CONF-2026', 'Leadership Conference', 'conference', 'Equipping camp leaders for mission work.', 'Open Gate Conference Hall', 'Morogoro', now()->addDays(14)->toDateString(), now()->addDays(16)->toDateString(), '08:00', '18:00', 'open_registration', 'Grace Kileo'],
            ['OGM-MISS-2026', 'Coastal Mission Trip', 'mission_trip', 'Service mission to villages along the coast.', 'Coastal Villages', 'Dar es Salaam region', now()->addMonths(2)->toDateString(), now()->addMonths(2)->addDays(9)->toDateString(), '05:30', '19:00', 'planned', 'Daniel Mwinuka'],
            ['OGM-YTR-2026', 'Youth Evangelism Training', 'training', 'Practical evangelism skills for young leaders.', 'Open Gate Training Centre', 'Morogoro', now()->addDays(45)->toDateString(), now()->addDays(47)->toDateString(), '09:00', '16:00', 'planned', 'Mary Massawe'],
            ['OGM-SUN-2026', 'Sunday Camp Worship', 'worship', 'Gathering worship service for camp families.', 'Open Gate Grounds', 'Morogoro', now()->next()->startOfDay()->toDateString(), now()->next()->startOfDay()->toDateString(), '09:00', '12:00', 'open_registration', 'Daniel Mwinuka'],
        ];

        $events = [];
        foreach ($eventsData as [$no, $title, $type, $desc, $venue, $location, $start, $end, $startTime, $endTime, $status, $organizer]) {
            $events[] = Event::updateOrCreate(
                ['title' => $title],
                [
                    'event_type' => $type, 'description' => $desc, 'venue' => $venue, 'location' => $location,
                    'start_date' => $start, 'end_date' => $end, 'start_time' => $startTime, 'end_time' => $endTime,
                    'status' => $status, 'capacity' => 120, 'featured' => $type === 'camp',
                    'organizer' => $organizer, 'created_by' => Setting::get('church.chaplain'),
                ]
            );
        }

        $attendeePool = [
            ['James Mushi', '+255 715 234 881'], ['Grace Kileo', '+255 762 118 402'], ['Peter Mwakalinga', '+255 748 920 133'],
            ['Mary Massawe', '+255 754 663 210'], ['Elizabeth Shayo', '+255 713 807 342'], ['Joseph Lyimo', '+255 795 122 674'],
            ['Anna Ndosi', '+255 683 551 098'], ['Monica Urio', '+255 689 774 120'], ['Baraka John', '+255 700 555 001'],
            ['Neema Thomas', '+255 711 555 002'], ['Emanuel Gasper', '+255 732 555 003'], ['Amina Hassan', '+255 756 555 004'],
        ];
        $attendeeStatuses = ['confirmed', 'confirmed', 'attended', 'attended', 'pending', 'no_show', 'confirmed', 'attended'];
        $eventAttendees = 0;
        foreach ($events as $idx => $evt) {
            $count = in_array($evt->status, ['completed', 'ongoing']) ? 8 : 5;
            foreach (array_slice($attendeePool, 0, $count) as $i => [$name, $phone]) {
                $status = $attendeeStatuses[($idx + $i) % count($attendeeStatuses)];
                EventAttendee::updateOrCreate(
                    ['event_id' => $evt->id, 'name' => $name],
                    [
                        'phone' => $phone,
                        'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
                        'status' => $status,
                        'amount_paid' => $status === 'attended' ? 20000 : ($status === 'confirmed' ? 10000 : 0),
                        'payment_method' => $status === 'attended' ? 'mobile' : null,
                        'registered_on' => $evt->start_date->subDays(rand(5, 30))->toDateString(),
                        'checked_in_at' => $status === 'attended' ? $evt->start_date->addHours(1) : null,
                        'checked_in_by' => $status === 'attended' ? Setting::get('church.chaplain') : null,
                    ]
                );
                $eventAttendees++;
            }
        }

        $pledgePeople = [
            ['James Mushi', '+255 715 234 881', 500000, 'one_time', 'fulfilled', 500000],
            ['Peter Mwakalinga', '+255 748 920 133', 300000, 'monthly', 'partial', 100000],
            ['Mary Massawe', '+255 754 663 210', 400000, 'one_time', 'pending', 0],
            ['Elizabeth Shayo', '+255 713 807 342', 250000, 'weekly', 'partial', 150000],
            ['Anna Ndosi', '+255 683 551 098', 150000, 'one_time', 'fulfilled', 150000],
            ['Monica Urio', '+255 689 774 120', 100000, 'monthly', 'pending', 0],
        ];

        foreach ($pledgePeople as $i => [$name, $phone, $amount, $freq, $status, $paid]) {
            $event = $events[$i % count($events)];
            $existing = Pledge::where('event_id', $event->id)->where('name', $name)->first();
            if ($existing) {
                $pledge = $existing->update([
                    'phone' => $phone,
                    'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
                    'amount' => $amount, 'paid_amount' => $paid, 'status' => $status,
                    'frequency' => $freq, 'pledge_date' => now()->subMonths(2)->toDateString(),
                    'due_date' => now()->addMonths(2)->toDateString(), 'created_by' => Setting::get('church.chaplain'),
                ]) ? $existing : null;
            } else {
                $pledge = Pledge::create([
                    'event_id' => $event->id, 'pledge_no' => Pledge::nextPledgeNo(),
                    'name' => $name, 'phone' => $phone,
                    'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
                    'amount' => $amount, 'paid_amount' => $paid, 'status' => $status,
                    'frequency' => $freq, 'pledge_date' => now()->subMonths(2)->toDateString(),
                    'due_date' => now()->addMonths(2)->toDateString(), 'created_by' => Setting::get('church.chaplain'),
                ]);
            }
            if ($pledge && $paid > 0) {
                PledgePayment::updateOrCreate(
                    ['pledge_id' => $pledge->id, 'pay_date' => now()->subDays(rand(3, 40))->toDateString()],
                    ['amount' => $paid, 'method' => 'mobile', 'reference' => 'MP-'.rand(10000, 99999), 'recorded_by' => Setting::get('church.chaplain')]
                );
            }
        }

        $defaultFy = FinancialYear::where('is_default', true)->first();
        if ($defaultFy) {
            $chaplain = Setting::get('church.chaplain');
            foreach (Member::where('member_type', 'student')->where('member_no', '!=', 'CHP-1011')->get() as $student) {
                \App\Models\MemberActivation::updateOrCreate(
                    ['member_id' => $student->id, 'financial_year_id' => $defaultFy->id],
                    ['activated_at' => now(), 'activated_by' => $chaplain]
                );
            }
        }

        $accounts = [
            ['1000', 'Cash in Bank', 'asset'],
            ['1010', 'Cash on Hand (Petty)', 'asset'],
            ['1020', 'Mobile Money Float', 'asset'],
            ['1100', 'Pledges Receivable', 'asset'],
            ['2000', 'Accounts Payable', 'liability'],
            ['2100', 'Accrued Expenses', 'liability'],
            ['3000', 'Accumulated Fund', 'equity'],
            ['3100', 'Prior Year Surplus', 'equity'],
            ['4000', 'Tithe Income', 'income'],
            ['4010', 'Offering Income', 'income'],
            ['4020', 'Donation Income', 'income'],
            ['4030', 'Fundraising Income', 'income'],
            ['4040', 'Other Income', 'income'],
            ['5000', 'Utilities Expense', 'expense'],
            ['5010', 'Maintenance Expense', 'expense'],
            ['5020', 'Charity Support Expense', 'expense'],
            ['5030', 'Office Supplies Expense', 'expense'],
            ['5040', 'Transport Expense', 'expense'],
            ['5050', 'Events Expense', 'expense'],
            ['5060', 'Salaries & Wages Expense', 'expense'],
        ];
        $cashCodes = ['1000', '1010', '1020'];
        foreach ($accounts as [$code, $name, $type]) {
            Account::updateOrCreate(['code' => $code], [
                'name' => $name, 'type' => $type, 'is_cash' => in_array($code, $cashCodes),
            ]);
        }

        if ($defaultFy) {
            $acct = fn ($code) => Account::where('code', $code)->value('id');

            $samples = [
                ['JE-0001', $defaultFy->start_date, 'Opening balances brought forward', null, [
                    [$acct('1000'), 12500000, 0, null],
                    [$acct('1010'), 450000, 0, null],
                    [$acct('1020'), 800000, 0, null],
                    [$acct('3000'), 0, 13750000, 'Church accumulated fund'],
                ]],
                ['JE-0002', now()->subDays(20)->toDateString(), 'Sunday tithes and offerings banked', null, [
                    [$acct('1000'), 2350000, 0, null],
                    [$acct('4000'), 0, 1850000, 'Tithes'],
                    [$acct('4010'), 0, 500000, 'Offerings'],
                ]],
                ['JE-0003', now()->subDays(12)->toDateString(), 'Fundraising harvest for building project', 'FR-2026-01', [
                    [$acct('1020'), 1200000, 0, null],
                    [$acct('4030'), 0, 1200000, null],
                ]],
                ['JE-0004', now()->subDays(8)->toDateString(), 'Pay electricity bill - TANESCO', 'UTIL-AUG', [
                    [$acct('5000'), 185000, 0, 'Electricity'],
                    [$acct('1000'), 0, 185000, null],
                ]],
                ['JE-0005', now()->subDays(5)->toDateString(), 'Salaries paid for the month', 'SAL-AUG', [
                    [$acct('5060'), 1800000, 0, null],
                    [$acct('1000'), 0, 1800000, null],
                ]],
                ['JE-0006', now()->subDay()->toDateString(), 'Purchase of office stationery', null, [
                    [$acct('5030'), 96000, 0, null],
                    [$acct('1010'), 0, 96000, 'Paid from petty cash'],
                ]],
            ];

            foreach ($samples as [$no, $date, $desc, $ref, $lines]) {
                $entry = \App\Models\JournalEntry::updateOrCreate(
                    ['entry_no' => $no],
                    [
                        'entry_date' => $date, 'description' => $desc, 'reference' => $ref,
                        'status' => 'posted', 'created_by' => Setting::get('church.chaplain'),
                    ]
                );
                if ($entry->lines()->count() === 0) {
                    foreach ($lines as [$accountId, $debit, $credit, $lineDesc]) {
                        \App\Models\JournalLine::create([
                            'journal_entry_id' => $entry->id,
                            'account_id' => $accountId,
                            'description' => $lineDesc,
                            'debit' => $debit,
                            'credit' => $credit,
                        ]);
                    }
                }
            }

            $bank = $acct('1000');
            $petty = $acct('1010');
            $docs = [
                ['RCP-0001', 'receipt', now()->subDays(18)->toDateString(), 'Sunday congregation', '4010', $bank, 500000, 'cash', null, 'Cash offerings'],
                ['RCP-0002', 'receipt', now()->subDays(15)->toDateString(), 'Moshi Coffee Ltd', '4020', $bank, 750000, 'bank', 'DON-114', 'Corporate donation'],
                ['RCP-0003', 'receipt', now()->subDays(9)->toDateString(), 'Youth fundraising walk', '4030', $bank, 320000, 'mobile', 'MP-88121', 'Walk-a-thon proceeds'],
                ['PMT-0001', 'payment', now()->subDays(14)->toDateString(), 'Dar Water & Sewerage', '5000', $bank, 74000, 'bank', 'WB-AUG', 'Water bill'],
                ['PMT-0002', 'payment', now()->subDays(6)->toDateString(), 'Kariakoo Traders', '5020', $petty, 150000, 'cash', null, 'Groceries for needy families'],
            ];

            foreach ($docs as [$no, $type, $date, $party, $catCode, $moneyId, $amount, $method, $ref, $desc]) {
                \App\Models\ReceiptPayment::updateOrCreate(['doc_no' => $no], [
                    'type' => $type,
                    'pay_date' => $date,
                    'party' => $party,
                    'category_account_id' => $acct($catCode),
                    'money_account_id' => $moneyId,
                    'amount' => $amount,
                    'method' => $method,
                    'reference' => $ref,
                    'description' => $desc,
                    'created_by' => Setting::get('church.chaplain'),
                ]);
            }
        }

        // Document Categories
        $catData = [
            ['Policies', 'Church policies and guidelines', '#2563EB'],
            ['Certificates', 'Baptism, marriage, and other certificates', '#7C3AED'],
            ['Minutes', 'Meeting minutes and resolutions', '#059669'],
            ['Financial Statements', 'Financial reports and statements', '#D97706'],
            ['Forms', 'Registration and application forms', '#DC2626'],
            ['Correspondence', 'Letters and official correspondence', '#0891B2'],
            ['Liturgical', 'Mass schedules, readings, and liturgical documents', '#4F46E5'],
        ];

        foreach ($catData as [$name, $desc, $color]) {
            \App\Models\DocumentCategory::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name, 'description' => $desc, 'color' => $color, 'documents_count' => 0]
            );
        }

        // Sample Documents (no actual files, just metadata)
        $sampleUser = \App\Models\User::where('email', 'camp.director@opengatecamp.org')->first();
        $catMap = \App\Models\DocumentCategory::pluck('id', 'slug');

        $sampleDocs = [
            ['Child Protection Policy 2026', 'Annual child protection policy document', 'policies', 'all_staff', 1240000, 'pdf'],
            ['Safeguarding Guidelines', 'Staff safeguarding guidelines', 'policies', 'restricted', 980000, 'pdf'],
            ['Baptism Register Template', 'Official baptism register form', 'certificates', 'restricted', 340000, 'docx'],
            ['Marriage Preparation Guide', 'Pre-marriage counseling handbook', 'certificates', 'all_staff', 2100000, 'pdf'],
            ['Parish Council Minutes July 2026', 'Monthly council meeting minutes', 'minutes', 'all_staff', 480000, 'pdf'],
            ['Finance Committee Minutes', 'Quarterly finance review', 'minutes', 'restricted', 320000, 'pdf'],
            ['Q2 2026 Financial Report', 'Second quarter financial statements', 'financial-statements', 'admin_only', 2100000, 'pdf'],
            ['Annual Budget 2026', 'Approved annual budget', 'financial-statements', 'admin_only', 1800000, 'xlsx'],
            ['Membership Registration Form', 'New member registration form', 'forms', 'all_staff', 620000, 'pdf'],
            ['Group Leader Report Template', 'Monthly group report template', 'forms', 'all_staff', 240000, 'docx'],
            ['Official Letter Template', 'Standard parish letterhead', 'correspondence', 'all_staff', 156000, 'docx'],
            ['Mass Schedule 2026', 'Updated mass and service schedule', 'liturgical', 'all_staff', 890000, 'pdf'],
        ];

        foreach ($sampleDocs as [$title, $desc, $slug, $access, $size, $ext]) {
            $catId = $catMap[$slug] ?? $catMap['policies'];
            \App\Models\Document::updateOrCreate(
                ['title' => $title],
                [
                    'description' => $desc,
                    'file_path' => 'documents/sample-' . $slug . '.' . $ext,
                    'file_name' => $title . '.' . $ext,
                    'file_type' => 'application/' . ($ext === 'xlsx' ? 'vnd.openxmlformats-officedocument.spreadsheetml.sheet' : ($ext === 'docx' ? 'vnd.openxmlformats-officedocument.wordprocessingml.document' : 'pdf')),
                    'file_size' => $size,
                    'category_id' => $catId,
                    'access_level' => $access,
                    'uploaded_by' => $sampleUser->name ?? 'System',
                    'user_id' => $sampleUser?->id,
                ]
            );
        }

        // Update document counts
        foreach (\App\Models\DocumentCategory::all() as $cat) {
            $cat->update(['documents_count' => $cat->documents()->count()]);
        }
    }
}

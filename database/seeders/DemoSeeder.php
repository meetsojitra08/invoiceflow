<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Billing\InvoiceCalculator;
use App\Services\Tenancy\TenantManager;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Demo Workspace Company
        $company = Company::query()->updateOrCreate(
            ['email' => 'contact@democorp.io'],
            [
                'name' => 'Demo Corp',
                'currency' => 'INR',
                'tax_number' => '27AABCB1518L1ZS',
                'address' => "101 Cyber Heights, Tech Zone 4\nHitec City, Hyderabad, TG 500081",
                'invoice_prefix' => 'INV',
                'next_invoice_number' => 41,
            ]
        );

        // 2. Create the 3 Standard Demo Users
        $defaultPassword = Hash::make('password');

        $owner = User::query()->updateOrCreate(
            ['email' => 'owner@demo.com'],
            [
                'name' => 'Alex Owner',
                'password' => $defaultPassword,
                'current_company_id' => $company->id,
                'email_verified_at' => Carbon::now(),
            ]
        );

        $accountant = User::query()->updateOrCreate(
            ['email' => 'accountant@demo.com'],
            [
                'name' => 'Sam Accountant',
                'password' => $defaultPassword,
                'current_company_id' => $company->id,
                'email_verified_at' => Carbon::now(),
            ]
        );

        $staff = User::query()->updateOrCreate(
            ['email' => 'staff@demo.com'],
            [
                'name' => 'Chris Staff',
                'password' => $defaultPassword,
                'current_company_id' => $company->id,
                'email_verified_at' => Carbon::now(),
            ]
        );

        // Attach company memberships
        $company->users()->syncWithoutDetaching([
            $owner->id => ['role' => Role::OWNER->value],
            $accountant->id => ['role' => Role::ACCOUNTANT->value],
            $staff->id => ['role' => Role::STAFF->value],
        ]);

        app(TenantManager::class)->execute($company->id, function () use ($company): void {
            // 3. Create 10 Realistic Clients
            $clientSpecs = [
                ['name' => 'Acme Technologies Pvt Ltd', 'email' => 'billing@acmetech.in', 'phone' => '+91 9820011223', 'address' => '42 Brigade Road, Bengaluru, KA 560001', 'tax' => '29AAACA1122C1Z1'],
                ['name' => 'Nexus Global Inc', 'email' => 'ap@nexusglobal.com', 'phone' => '+1 415 555 2671', 'address' => '500 Howard Street, Suite 400, San Francisco, CA 94105', 'tax' => 'US941051234'],
                ['name' => 'Vanguard Logistics', 'email' => 'accounts@vanguardlogistics.in', 'phone' => '+91 9176033445', 'address' => 'Plot 88, Guindy Industrial Estate, Chennai, TN 600032', 'tax' => '33AABCV9988V1Z5'],
                ['name' => 'BluePeak Capital Advisors', 'email' => 'finance@bluepeak.co', 'phone' => '+91 9811099887', 'address' => '12 Barakhamba Road, Connaught Place, New Delhi 110001', 'tax' => '07AAACB5544B1Z8'],
                ['name' => 'Starlight Media Studios', 'email' => 'invoicing@starlightstudios.in', 'phone' => '+91 9930044556', 'address' => '204 Film City Complex, Goregaon East, Mumbai, MH 400065', 'tax' => '27AAACS7766S1Z2'],
                ['name' => 'Zenith Health Solutions', 'email' => 'billing@zenithhealth.io', 'phone' => '+91 9448055667', 'address' => '14 Infopark Road, Kakkanad, Kochi, KL 682042', 'tax' => '32AAACZ3322Z1Z9'],
                ['name' => 'UrbanCraft Living Retail', 'email' => 'vendor@urbancraft.in', 'phone' => '+91 9723066778', 'address' => '5th Floor, Alpha One Mall, Vastrapur, Ahmedabad, GJ 380054', 'tax' => '24AAACU1199U1Z7'],
                ['name' => 'Apex Cloud Systems', 'email' => 'invoices@apexcloud.dev', 'phone' => '+44 20 7946 0912', 'address' => '100 Bishopsgate, London EC2N 4AG, United Kingdom', 'tax' => 'GB998877665'],
                ['name' => 'Kitefin Analytics Corp', 'email' => 'payables@kitefin.ai', 'phone' => '+91 9650077889', 'address' => 'Tower B, DLF Cyber City, Sector 25, Gurugram, HR 122002', 'tax' => '06AAACK4433K1Z4'],
                ['name' => 'Solaria Clean Energy Ltd', 'email' => 'finance@solariaenergy.in', 'phone' => '+91 9884088990', 'address' => 'Sector 5, Salt Lake City, Bidhannagar, Kolkata, WB 700091', 'tax' => '19AAACS6655S1Z6'],
            ];

            $clients = [];
            foreach ($clientSpecs as $spec) {
                $clients[] = Client::query()->updateOrCreate(
                    ['company_id' => $company->id, 'email' => $spec['email']],
                    [
                        'name' => $spec['name'],
                        'phone' => $spec['phone'],
                        'billing_address' => $spec['address'],
                        'tax_number' => $spec['tax'],
                        'send_reminders' => true,
                    ]
                );
            }

            // 4. Create 15 Varied Catalog Products
            $productSpecs = [
                ['name' => 'Senior Architecture Consulting', 'desc' => 'System design, scalability, and technical architecture review', 'price' => 1500000, 'tax' => 18.0],
                ['name' => 'UI/UX Design Sprint', 'desc' => 'High-fidelity wireframes, interactive prototypes, and design systems', 'price' => 850000, 'tax' => 18.0],
                ['name' => 'Full-Stack Web Development (Sprint)', 'desc' => 'Two-week agile sprint of dedicated engineering delivery', 'price' => 2000000, 'tax' => 18.0],
                ['name' => 'Mobile App Development (React Native)', 'desc' => 'Cross-platform iOS and Android mobile development sprint', 'price' => 2200000, 'tax' => 18.0],
                ['name' => 'DevOps & CI/CD Pipeline Setup', 'desc' => 'Automated GitHub Actions, Docker containerization, and Kubernetes cluster setup', 'price' => 950000, 'tax' => 18.0],
                ['name' => 'PostgreSQL Database Optimization', 'desc' => 'Query tuning, index optimization, connection pooling, and replication', 'price' => 750000, 'tax' => 18.0],
                ['name' => 'Application Security Audit', 'desc' => 'Penetration testing, OWASP compliance review, and vulnerability remediation', 'price' => 1200000, 'tax' => 18.0],
                ['name' => 'Monthly Production Maintenance', 'desc' => '24/7 uptime monitoring, security patches, backups, and bugfixes', 'price' => 500000, 'tax' => 18.0],
                ['name' => 'Technical Documentation & Guides', 'desc' => 'Comprehensive API reference, developer onboarding, and architecture guides', 'price' => 450000, 'tax' => 18.0],
                ['name' => 'Code Quality & Security Review', 'desc' => 'In-depth pull request analysis and static security scan report', 'price' => 350000, 'tax' => 18.0],
                ['name' => 'Payment Gateway Integration', 'desc' => 'Seamless Stripe/Razorpay checkout, recurring billing, and webhook handling', 'price' => 600000, 'tax' => 18.0],
                ['name' => 'Custom Feature Implementation', 'desc' => 'End-to-end scope delivery for custom module requirements', 'price' => 1100000, 'tax' => 18.0],
                ['name' => 'Cloud Migration & Infrastructure Overhaul', 'desc' => 'Zero-downtime migration to cloud infrastructure with autoscaling', 'price' => 1800000, 'tax' => 18.0],
                ['name' => 'Frontend Performance Tuning', 'desc' => 'Vite bundle optimization, code splitting, and Core Web Vitals optimization', 'price' => 400000, 'tax' => 18.0],
                ['name' => 'Developer Team Training Workshop', 'desc' => 'Hands-on advanced Laravel, Inertia, and React masterclass (2 days)', 'price' => 650000, 'tax' => 0.0],
            ];

            $products = [];
            foreach ($productSpecs as $spec) {
                $products[] = Product::query()->updateOrCreate(
                    ['company_id' => $company->id, 'name' => $spec['name']],
                    [
                        'description' => $spec['desc'],
                        'unit_price' => $spec['price'],
                        'tax_rate' => $spec['tax'],
                        'is_active' => true,
                    ]
                );
            }

            // 5. Generate 40 Invoices Across All Statuses
            $statuses = [
                ...array_fill(0, 6, InvoiceStatus::DRAFT),
                ...array_fill(0, 10, InvoiceStatus::SENT),
                ...array_fill(0, 6, InvoiceStatus::PARTIALLY_PAID),
                ...array_fill(0, 12, InvoiceStatus::PAID),
                ...array_fill(0, 4, InvoiceStatus::OVERDUE),
                ...array_fill(0, 2, InvoiceStatus::VOID),
            ];

            $calculator = app(InvoiceCalculator::class);
            $methods = [PaymentMethod::BANK_TRANSFER, PaymentMethod::STRIPE, PaymentMethod::CASH];

            for ($i = 0; $i < 40; $i++) {
                $status = $statuses[$i];
                $invoiceNumber = sprintf('INV-%04d', $i + 1);
                $client = $clients[$i % count($clients)];

                // Spread dates over the last 6 months
                $daysAgo = ($i * 4) + 2;
                $issueDate = Carbon::today()->subDays($daysAgo);
                $dueDate = $status === InvoiceStatus::OVERDUE
                    ? Carbon::today()->subDays(random_int(3, 30))
                    : $issueDate->copy()->addDays(14);

                $selectedProducts = [
                    $products[$i % count($products)],
                    $products[($i + 3) % count($products)],
                ];

                $calcItems = [];
                foreach ($selectedProducts as $idx => $prod) {
                    $qty = ($idx === 0) ? 1.0 : (float) random_int(1, 3);
                    $calcItems[] = [
                        'description' => $prod->name,
                        'quantity' => $qty,
                        'unit_price' => $prod->unit_price,
                        'discount' => 0,
                        'tax_rate' => (float) $prod->tax_rate,
                    ];
                }

                $calculation = $calculator->calculate($calcItems);

                // Determine amount paid based on status
                $amountPaid = match ($status) {
                    InvoiceStatus::PAID => $calculation['total'],
                    InvoiceStatus::PARTIALLY_PAID => (int) round($calculation['total'] * 0.4),
                    default => 0,
                };

                $invoice = Invoice::query()->updateOrCreate(
                    ['company_id' => $company->id, 'number' => $invoiceNumber],
                    [
                        'client_id' => $client->id,
                        'status' => $status,
                        'issue_date' => $issueDate->toDateString(),
                        'due_date' => $dueDate->toDateString(),
                        'currency' => 'INR',
                        'subtotal' => $calculation['subtotal'],
                        'discount_total' => $calculation['discount_total'],
                        'tax_total' => $calculation['tax_total'],
                        'total' => $calculation['total'],
                        'amount_paid' => $amountPaid,
                        'notes' => 'Thank you for choosing Demo Corp for your technical engineering services.',
                        'public_token' => Str::random(40),
                        'sent_at' => in_array($status, [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::PAID, InvoiceStatus::OVERDUE], true) ? $issueDate->copy()->addHour() : null,
                        'paid_at' => $status === InvoiceStatus::PAID ? $dueDate->copy()->subDays(2) : null,
                        'voided_at' => $status === InvoiceStatus::VOID ? $issueDate->copy()->addDays(3) : null,
                        'void_reason' => $status === InvoiceStatus::VOID ? 'Client order superseded by updated agreement' : null,
                    ]
                );

                // Re-create invoice snapshot items
                $invoice->items()->delete();
                foreach ($calculation['items'] as $itemIdx => $calcItem) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $selectedProducts[$itemIdx]->id ?? null,
                        'description' => $calcItem['description'],
                        'quantity' => $calcItem['quantity'],
                        'unit_price' => $calcItem['unit_price'],
                        'tax_rate' => $calcItem['tax_rate'],
                        'discount' => $calcItem['discount'],
                        'line_total' => $calcItem['line_total'],
                        'position' => $itemIdx + 1,
                    ]);
                }

                // Create payments for paid and partially paid invoices
                $invoice->payments()->delete();
                if ($amountPaid > 0) {
                    $method = $methods[$i % count($methods)];
                    $paymentDate = $status === InvoiceStatus::PAID
                        ? $dueDate->copy()->subDays(random_int(1, 3))
                        : $issueDate->copy()->addDays(5);

                    Payment::create([
                        'company_id' => $company->id,
                        'invoice_id' => $invoice->id,
                        'amount' => $amountPaid,
                        'currency' => 'INR',
                        'method' => $method->value,
                        'reference' => 'DEMO-TXN-'.Str::upper(Str::random(8)),
                        'paid_at' => $paymentDate->toDateString(),
                        'notes' => $status === InvoiceStatus::PAID ? 'Settled in full' : 'Initial installment paid',
                    ]);
                }
            }
        });
    }
}

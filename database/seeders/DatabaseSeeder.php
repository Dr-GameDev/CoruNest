<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Campaign;
use App\Models\Event;
use App\Models\Donation;
use App\Models\Volunteer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@corunest.org',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '+27 21 123 4567',
            'email_verified_at' => now(),
        ]);

        // Create staff user
        $staff = User::create([
            'name' => 'Staff Member',
            'email' => 'staff@corunest.org',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'phone' => '+27 21 234 5678',
            'email_verified_at' => now(),
        ]);

        // Create sample donors
        $donors = [];
        for ($i = 1; $i <= 10; $i++) {
            $donors[] = User::create([
                'name' => "Donor {$i}",
                'email' => "donor{$i}@example.com",
                'password' => Hash::make('password'),
                'role' => 'donor',
                'phone' => '+27 ' . rand(21, 83) . ' ' . rand(100, 999) . ' ' . rand(1000, 9999),
                'email_verified_at' => now(),
            ]);
        }

        // Create sample volunteers
        $volunteers = [];
        for ($i = 1; $i <= 15; $i++) {
            $volunteers[] = User::create([
                'name' => "Volunteer {$i}",
                'email' => "volunteer{$i}@example.com",
                'password' => Hash::make('password'),
                'role' => 'volunteer',
                'phone' => '+27 ' . rand(21, 83) . ' ' . rand(100, 999) . ' ' . rand(1000, 9999),
                'email_verified_at' => now(),
            ]);
        }

        // Create sample campaigns
        $campaigns = [];
        $campaignData = [
            [
                'title' => 'Clean Water for Rural Communities',
                'summary' => 'Help us provide clean drinking water to remote villages in the Western Cape.',
                'body' => 'Many rural communities in the Western Cape still lack access to clean drinking water. Our project aims to install water purification systems in 5 villages, providing safe drinking water to over 2,000 people. Your donation will help us purchase equipment, hire local technicians, and maintain these systems for years to come.',
                'target_amount' => 150000,
                'category' => 'community',
                'featured' => true,
                'start_at' => now()->subDays(10),
                'end_at' => now()->addDays(30),
            ],
            [
                'title' => 'Education Support for Underprivileged Children',
                'summary' => 'Providing school supplies and tutoring for children in township schools.',
                'body' => 'Education is the key to breaking the cycle of poverty. We work with township schools to provide essential supplies, books, and after-school tutoring programs. Every child deserves the opportunity to learn and grow.',
                'target_amount' => 75000,
                'category' => 'education',
                'featured' => true,
                'start_at' => now()->subDays(5),
                'end_at' => now()->addDays(45),
            ],
            [
                'title' => 'Emergency Food Relief Program',
                'summary' => 'Feeding families in need during difficult times.',
                'body' => 'With rising unemployment and economic challenges, many families struggle to put food on the table. Our emergency food relief program distributes nutritious meals and food parcels to families in need across Cape Town.',
                'target_amount' => 50000,
                'category' => 'poverty',
                'featured' => false,
                'start_at' => now()->subDays(20),
                'end_at' => now()->addDays(10),
            ],
            [
                'title' => 'Tree Planting Initiative',
                'summary' => 'Restoring indigenous vegetation in fire-damaged areas.',
                'body' => 'Following recent fires in the Cape Peninsula, we are working to restore the natural fynbos vegetation. This project will plant 5,000 indigenous trees and shrubs, helping to prevent erosion and restore the ecosystem.',
                'target_amount' => 25000,
                'category' => 'environment',
                'featured' => false,
                'start_at' => now()->addDays(5),
                'end_at' => now()->addDays(60),
            ],
            [
                'title' => 'Mobile Healthcare Clinic',
                'summary' => 'Bringing medical care to remote communities.',
                'body' => 'Our mobile healthcare clinic travels to remote areas where medical facilities are scarce. We provide basic medical care, health screenings, and preventive care to underserved communities.',
                'target_amount' => 200000,
                'category' => 'healthcare',
                'featured' => true,
                'start_at' => now()->subDays(15),
                'end_at' => now()->addDays(75),
            ],
        ];

        foreach ($campaignData as $data) {
            $campaign = Campaign::create([
                ...$data,
                'slug' => Str::slug($data['title']),
                'status' => 'active',
                'created_by' => $admin->id,
            ]);
            $campaigns[] = $campaign;
        }

        // Create sample events
        $events = [];
        $eventData = [
            [
                'title' => 'Beach Cleanup Day',
                'description' => 'Join us for a community beach cleanup at Muizenberg Beach. We\'ll provide all cleaning supplies and refreshments. Help us keep our beaches clean and protect marine life.',
                'location' => 'Muizenberg Beach',
                'address' => 'Muizenberg Beach, Cape Town',
                'capacity' => 50,
                'starts_at' => now()->addDays(7)->setTime(9, 0),
                'ends_at' => now()->addDays(7)->setTime(12, 0),
                'signup_deadline' => now()->addDays(5),
                'category' => 'cleanup',
                'requirements' => ['Comfortable walking shoes', 'Sun hat', 'Water bottle'],
                'instructions' => 'Meet at the main parking area. Look for the CoruNest banner.',
            ],
            [
                'title' => 'Soup Kitchen Volunteer Day',
                'description' => 'Help us prepare and serve meals at our weekly soup kitchen. We feed over 200 people every Saturday.',
                'location' => 'Community Hall',
                'address' => '123 Main Road, Observatory, Cape Town',
                'capacity' => 20,
                'starts_at' => now()->addDays(14)->setTime(8, 0),
                'ends_at' => now()->addDays(14)->setTime(14, 0),
                'signup_deadline' => now()->addDays(12),
                'category' => 'food',
                'requirements' => ['Apron (provided)', 'Closed shoes'],
                'instructions' => 'Enter through the back entrance. Bring your ID for registration.',
            ],
            [
                'title' => 'School Garden Project',
                'description' => 'Help students create a vegetable garden at their school. We\'ll be planting vegetables that will provide fresh food for school meals.',
                'location' => 'Langa Primary School',
                'address' => 'Langa Primary School, Langa, Cape Town',
                'capacity' => 30,
                'starts_at' => now()->addDays(21)->setTime(8, 0),
                'ends_at' => now()->addDays(21)->setTime(15, 0),
                'signup_deadline' => now()->addDays(19),
                'category' => 'education',
                'requirements' => ['Gardening gloves', 'Old clothes', 'Water bottle'],
                'instructions' => 'Park in the school grounds. Register at the main office first.',
            ],
            [
                'title' => 'Elderly Care Visit',
                'description' => 'Spend time with elderly residents at a local care facility. Activities include reading, games, and general companionship.',
                'location' => 'Sunset Manor Care Home',
                'address' => '456 Oak Street, Rondebosch, Cape Town',
                'capacity' => 15,
                'starts_at' => now()->addDays(28)->setTime(10, 0),
                'ends_at' => now()->addDays(28)->setTime(15, 0),
                'signup_deadline' => now()->addDays(25),
                'category' => 'elderly',
                'requirements' => ['Police clearance certificate', 'Friendly attitude'],
                'instructions' => 'Sign in at reception. Ask for the volunteer coordinator.',
            ],
        ];

        foreach ($eventData as $data) {
            $event = Event::create([
                ...$data,
                'slug' => Str::slug($data['title']),
                'status' => 'active',
                'created_by' => $admin->id,
            ]);
            $events[] = $event;
        }

        // Create sample donations
        foreach ($campaigns as $campaign) {
            $donationCount = rand(3, 15);
            
            for ($i = 0; $i < $donationCount; $i++) {
                $donor = $donors[array_rand($donors)];
                $amount = rand(50, 2000);
                
                $donation = Donation::create([
                    'user_id' => $donor->id,
                    'campaign_id' => $campaign->id,
                    'amount' => $amount,
                    'currency' => 'ZAR',
                    'payment_provider' => rand(0, 1) ? 'yoco' : 'ozow',
                    'transaction_id' => Donation::generateTransactionId(),
                    'status' => 'completed',
                    'anonymous' => rand(0, 4) == 0, // 20% anonymous
                    'donor_message' => rand(0, 2) == 0 ? 'Keep up the great work!' : null,
                    'completed_at' => now()->subDays(rand(1, 30)),
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);
            }
            
            // Update campaign totals
            $campaign->updateTotals();
        }

        // Create sample volunteer signups
        foreach ($events as $event) {
            $volunteerCount = rand(5, min(20, $event->capacity ?? 20));
            $selectedVolunteers = collect($volunteers)->random($volunteerCount);
            
            foreach ($selectedVolunteers as $volunteer) {
                $status = ['pending', 'confirmed', 'confirmed', 'confirmed'][rand(0, 3)]; // More confirmed than pending
                
                Volunteer::create([
                    'user_id' => $volunteer->id,
                    'event_id' => $event->id,
                    'status' => $status,
                    'skills' => collect(Volunteer::getAvailableSkills())->keys()->random(rand(1, 3))->toArray(),
                    'message' => rand(0, 2) == 0 ? 'Looking forward to helping out!' : null,
                    'has_transport' => rand(0, 1),
                    'emergency_contact_provided' => rand(0, 1),
                    'emergency_contact_name' => rand(0, 1) ? 'John Doe' : null,
                    'emergency_contact_phone' => rand(0, 1) ? '+27 21 555 0123' : null,
                    'confirmed_at' => $status === 'confirmed' ? now()->subDays(rand(1, 10)) : null,
                    'confirmed_by' => $status === 'confirmed' ? $admin->id : null,
                    'created_at' => now()->subDays(rand(1, 15)),
                ]);
            }
            
            // Update event volunteer count
            $event->updateVolunteerCount();
        }

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin login: admin@corunest.org / password');
        $this->command->info('Staff login: staff@corunest.org / password');
        $this->command->info('Sample donor login: donor1@example.com / password');
        $this->command->info('Sample volunteer login: volunteer1@example.com / password');
    }
}
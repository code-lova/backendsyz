<?php

namespace Database\Seeders;

use App\Models\ServiceFlyer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceFlyerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first admin user
        $admin = User::where('role', 'admin')->first();

        if (!$admin) {
            $this->command->warn('No admin user found. Please create an admin user first.');
            return;
        }

        $flyers = [
            [
                'title' => 'Quality Healthcare Services',
                'description' => 'Professional healthcare services delivered with care and expertise. Book your appointment today.',
                'image_url' => 'https://res.cloudinary.com/demo/image/upload/sample.jpg',
                'image_public_id' => 'sample_flyer_1_' . Str::random(8),
                'target_audience' => 'both',
                'sort_order' => 1,
                'created_by' => $admin->uuid,
            ],
            [
                'title' => 'Home Care Services',
                'description' => 'Compassionate home care services for your loved ones. Available 24/7.',
                'image_url' => 'https://res.cloudinary.com/demo/image/upload/sample2.jpg',
                'image_public_id' => 'sample_flyer_2_' . Str::random(8),
                'target_audience' => 'client',
                'sort_order' => 2,
                'created_by' => $admin->uuid,
            ],
            [
                'title' => 'Join Our Healthcare Team',
                'description' => 'Exciting opportunities for healthcare professionals. Apply now and make a difference.',
                'image_url' => 'https://res.cloudinary.com/demo/image/upload/sample3.jpg',
                'image_public_id' => 'sample_flyer_3_' . Str::random(8),
                'target_audience' => 'healthworker',
                'sort_order' => 3,
                'created_by' => $admin->uuid,
            ],
            [
                'title' => 'Emergency Care Available',
                'description' => 'Round-the-clock emergency healthcare services. We are here when you need us most.',
                'image_url' => 'https://res.cloudinary.com/demo/image/upload/sample4.jpg',
                'image_public_id' => 'sample_flyer_4_' . Str::random(8),
                'target_audience' => 'client',
                'sort_order' => 4,
                'created_by' => $admin->uuid,
            ],
            [
                'title' => 'Continuing Education Program',
                'description' => 'Enhance your skills with our professional development programs for healthcare workers.',
                'image_url' => 'https://res.cloudinary.com/demo/image/upload/sample5.jpg',
                'image_public_id' => 'sample_flyer_5_' . Str::random(8),
                'target_audience' => 'healthworker',
                'sort_order' => 5,
                'created_by' => $admin->uuid,
            ],
        ];

        foreach ($flyers as $flyer) {
            ServiceFlyer::create($flyer);
        }

        $this->command->info('Service flyers seeded successfully!');
    }
}

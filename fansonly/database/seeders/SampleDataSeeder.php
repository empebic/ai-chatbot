<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $creator = User::firstOrCreate([
            'email' => 'creator@example.com',
        ], [
            'name' => 'Creator',
            'password' => bcrypt('password'),
        ]);
        $creator->assignRole('creator');

        $profile = $creator->profile()->create([
            'username' => 'creator'.Str::random(4),
            'display_name' => 'Awesome Creator',
            'is_creator' => true,
            'monthly_price' => 9.99,
            'currency' => 'USD',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            Post::create([
                'user_id' => $creator->id,
                'title' => "Sample Post $i",
                'body' => 'This is a sample post body for demo purposes.',
                'visibility' => 'public',
                'published_at' => now()->subDays(5 - $i),
            ]);
        }
    }
}

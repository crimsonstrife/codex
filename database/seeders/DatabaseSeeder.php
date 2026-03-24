<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            PageTemplateSeeder::class,
        ]);

        if (app()->environment('local')) {
            User::factory()->withPersonalTeam()->create([
                'name'  => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}

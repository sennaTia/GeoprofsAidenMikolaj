<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $afdeling1 = Department::create(['name' => 'Afdeling 1']);
        $afdeling2 = Department::create(['name' => 'Afdeling 2']);

        $test = User::create([
            'name' => 'Test Werknemer',
            'email' => 'test@geoprofs.test',
            'password' => 'Welkom123!',
            'role' => 'werknemer',
            'department_id' => $afdeling1->id,
        ]);

        $collega = User::create([
            'name' => 'Collega Werknemer',
            'email' => 'collega@geoprofs.test',
            'password' => 'Welkom123!',
            'role' => 'werknemer',
            'department_id' => $afdeling1->id,
        ]);

        $test2_0 = User::create([
            'name' => 'Test Werknemer 2.0',
            'email' => 'test2.0@geoprofs.test',
            'password' => 'Welkom123!',
            'role' => 'werknemer',
            'department_id' => $afdeling2->id,
        ]);

        $collega2_0 = User::create([
            'name' => 'Collega Werknemer 2.0',
            'email' => 'collega2.0@geoprofs.test',
            'password' => 'Welkom123!',
            'role' => 'werknemer',
            'department_id' => $afdeling2->id,
        ]);

        User::create([
            'name' => 'Manager Afdeling 1',
            'email' => 'manager@geoprofs.test',
            'password' => 'Welkom123!',
            'role' => 'manager',
            'department_id' => $afdeling1->id,
        ]);

        User::create([
            'name' => 'Manager Afdeling 2',
            'email' => 'manager2@geoprofs.test',
            'password' => 'Welkom123!',
            'role' => 'manager',
            'department_id' => $afdeling2->id,
        ]);

        User::create([
            'name' => 'Office Manager',
            'email' => 'office@geoprofs.test',
            'password' => 'Welkom123!',
            'role' => 'office-manager',
            'department_id' => $afdeling1->id,
        ]);

        User::create([
            'name' => 'Admin',
            'email' => 'admin@geoprofs.test',
            'password' => 'Welkom123!',
            'role' => 'admin',
        ]);

        LeaveBalance::create(['user_id' => $test->id, 'year' => 2026, 'total_days' => 30]);
        LeaveBalance::create(['user_id' => $collega->id, 'year' => 2026, 'total_days' => 30]);
        LeaveBalance::create(['user_id' => $test2_0->id, 'year' => 2026, 'total_days' => 30]);
        LeaveBalance::create(['user_id' => $collega2_0->id, 'year' => 2026, 'total_days' => 30]);

    }
}
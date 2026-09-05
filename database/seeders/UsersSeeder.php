<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $role = fn (string $name) => Role::where('name', $name)->value('id');

        $people = [
            ['Innocent', '+255 719 973 031', 'Secretary'],
            ['Gradness', '+255 763 305 434', 'Committee Member'],
            ['Julius', '+255 619 630 434', 'Committee Member'],
            ['Primi', '+255 767 906 586', 'Committee Member'],
            ['James', '+255 624 222 359', 'Committee Member'],
            ['Karisma', '0762192129', 'Treasurer'],
            ['Fidel', '+255 769 190 994', 'Super Administrator'],
            ['Tina', '+255 621 399 937', 'Committee Member'],
            ['Theo', '+255 617 559 939', 'Committee Member'],
            ['Emmanuel', '+255 654 413 045', 'Super Administrator'],
            ['Efraim', '+255 627 166 712', 'Committee Member'],
            ['Damsoni', '+255 752 106 372', 'Committee Member'],
            ['Ludan', '+255 715 639 802', 'Committee Member'],
            ['Veronica', '+255 764 167 594', 'Committee Member'],
            ['Emiliana', '+255 658 418 655', 'Committee Member'],
            ['Epimack Mbeteni', '+255 754 710 710', 'Super Administrator'],
            ['Reginand', '+255 768 520 442', 'Super Administrator'],
            ['David', '0622239304', 'Super Administrator'],
        ];

        foreach ($people as [$name, $phone, $roleName]) {
            User::updateOrCreate(
                ['phone' => $phone],
                [
                    'name'     => $name,
                    'email'    => strtolower((string) preg_replace('/[^A-Za-z0-9.]/', '', $name)).'@opengatecamp.org',
                    'password' => Hash::make('password'),
                    'role_id'  => $role($roleName),
                    'status'   => 'Active',
                ]
            );
        }
    }
}
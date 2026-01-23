<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->first() ?? User::factory()->create();

        $addresses = [
            [
                'country' => 'Spain',
                'state' => 'Madrid',
                'city' => 'Madrid',
                'postal_code' => '28023',
                'address_line_1' => 'Calle Rio Adaja 5',
                'address_line_2' => '',
            ],
            [
                'country' => 'United States',
                'state' => 'Wyoming',
                'city' => 'Sheridan',
                'postal_code' => '82801',
                'address_line_1' => '30 North Gould Street',
                'address_line_2' => '',
            ],
            [
                'country' => 'Colombia',
                'state' => 'Distrito Capital',
                'city' => 'Bogotá',
                'postal_code' => '110111',
                'address_line_1' => 'Carrera 12 # 119-44',
                'address_line_2' => 'Apt 301',
            ],
        ];

        foreach ($addresses as $address) {
            Address::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'address_line_1' => $address['address_line_1'],
                    'postal_code' => $address['postal_code'],
                ],
                [
                    'country' => $address['country'],
                    'state' => $address['state'],
                    'city' => $address['city'],
                    'address_line_2' => $address['address_line_2'],
                ]
            );
        }
    }
}

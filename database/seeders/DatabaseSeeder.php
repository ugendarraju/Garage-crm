<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $brands = [
            'Bajaj' => ['Pulsar 150', 'Pulsar 220F', 'Dominar 400', 'Avenger Cruise 220', 'Platina 100', 'CT 100'],
            'Hero' => ['Splendor Plus', 'HF Deluxe', 'Glamour', 'Passion Pro', 'Xtreme 160R', 'Destini 125'],
            'TVS' => ['Apache RTR 160', 'Apache RR 310', 'Jupiter', 'NTORQ 125', 'Raider 125', 'XL100'],
            'Honda' => ['Activa 6G', 'Shine', 'SP 125', 'Unicorn', 'Dio', 'Hness CB350'],
            'Royal Enfield' => ['Classic 350', 'Bullet 350', 'Meteor 350', 'Himalayan', 'Interceptor 650', 'Continental GT 650'],
            'Yamaha' => ['MT-15', 'R15 V4', 'FZ-S FI', 'Fascino 125', 'RayZR 125', 'Aerox 155'],
            'Suzuki' => ['Access 125', 'Burgman Street', 'Avenis', 'Gixxer SF', 'V-Strom SX', 'Hayabusa'],
            'KTM' => ['Duke 200', 'Duke 390', 'RC 200', 'RC 390', 'Adventure 390', 'Duke 250'],
        ];

        foreach ($brands as $brandName => $models) {
            $brand = VehicleBrand::create(['name' => $brandName]);
            foreach ($models as $modelName) {
                VehicleModel::create([
                    'brand_id' => $brand->id,
                    'name' => $modelName,
                ]);
            }
        }

        $garage = \App\Models\Garage::create([
            'name' => 'Demo Auto Shop',
            'email' => 'contact@demoauto.com',
            'phone' => '9876543210',
            'address' => '123 Mechanic Lane',
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@garage.com',
            'role' => 'super_admin',
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'name' => 'Demo Owner',
            'email' => 'owner@demoauto.com',
            'role' => 'owner',
            'password' => bcrypt('password'),
            'garage_id' => $garage->id,
        ]);
    }
}

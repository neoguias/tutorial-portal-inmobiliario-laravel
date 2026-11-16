<?php

namespace Database\Seeders;

use App\Models\Agency;
use Illuminate\Database\Seeder;

/**
 * Datos de ejemplo del portal: cuatro agencias inventadas y sus anuncios.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Agency::query()->delete();

        foreach ([
            ['Atalaya Inmobiliaria', '600 000 101', 'hola@atalaya.example'],
            ['Inmobiliaria Fanal', '600 000 202', 'info@fanal.example'],
            ['Brisa Pisos', '600 000 303', 'contacto@brisa.example'],
            ['Finques Marjal', '600 000 404', 'oficina@marjal.example'],
        ] as [$name, $phone, $email]) {
            Agency::create(compact('name', 'phone', 'email'));
        }

        $this->call(ListingSeeder::class);
    }
}

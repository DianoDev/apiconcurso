<?php

namespace Database\Seeders;

use App\Models\Pessoa;
use App\Models\ServidorEfetivo;
use Illuminate\Database\Seeder;

class ServidorEfetivoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servidoresEfetivos = [
            [
                'pes_id' => 1,
                'se_matricula' => '123456',
            ],
            [
                'pes_id' => 2,
                'se_matricula' => '234567',
            ],
            [
                'pes_id' => 3,
                'se_matricula' => '345678',
            ],
        ];

        foreach ($servidoresEfetivos as $servidor) {
            ServidorEfetivo::create($servidor);
        }
    }
}

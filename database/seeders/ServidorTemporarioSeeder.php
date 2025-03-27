<?php

namespace Database\Seeders;

use App\Models\Pessoa;
use App\Models\ServidorTemporario;
use Illuminate\Database\Seeder;

class ServidorTemporarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servidoresTemporarios = [
            [
                'pes_id' => 4,
                'st_data_admissao' => '2023-01-01',
                'st_data_demissao' => '2023-12-31',
            ],
            [
                'pes_id' => 5,
                'st_data_admissao' => '2023-02-15',
                'st_data_demissao' => '2024-02-14',
            ],
        ];

        foreach ($servidoresTemporarios as $servidor) {
            ServidorTemporario::create($servidor);
        }
    }
}

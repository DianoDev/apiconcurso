<?php

namespace Database\Seeders;

use App\Models\Cidade;
use Illuminate\Database\Seeder;

class CidadeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cidades = [
            [
                'cid_nome' => 'Cuiabá',
                'cid_uf' => 'MT',
            ],
            [
                'cid_nome' => 'Várzea Grande',
                'cid_uf' => 'MT',
            ],
            [
                'cid_nome' => 'Rondonópolis',
                'cid_uf' => 'MT',
            ],
            [
                'cid_nome' => 'Sinop',
                'cid_uf' => 'MT',
            ],
            [
                'cid_nome' => 'Tangará da Serra',
                'cid_uf' => 'MT',
            ],
        ];

        foreach ($cidades as $cidade) {
            Cidade::create($cidade);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Cidade;
use App\Models\Endereco;
use App\Models\Unidade;
use Illuminate\Database\Seeder;

class EnderecoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cuiaba = Cidade::where('cid_nome', 'Cuiabá')->first();
        $vgd = Cidade::where('cid_nome', 'Várzea Grande')->first();

        $enderecos = [
            [
                'end_tipo_logradouro' => 'Rua',
                'end_logradouro' => 'Júlio Domingos de Campos',
                'end_numero' => 100,
                'end_bairro' => 'Centro-Sul',
                'cid_id' => $cuiaba->cid_id,
            ],
            [
                'end_tipo_logradouro' => 'Avenida',
                'end_logradouro' => 'Historiador Rubens de Mendonça',
                'end_numero' => 3415,
                'end_bairro' => 'CPA',
                'cid_id' => $cuiaba->cid_id,
            ],
            [
                'end_tipo_logradouro' => 'Rua',
                'end_logradouro' => 'Barão de Melgaço',
                'end_numero' => 1500,
                'end_bairro' => 'Centro-Sul',
                'cid_id' => $cuiaba->cid_id,
            ],
            [
                'end_tipo_logradouro' => 'Avenida',
                'end_logradouro' => 'Fernando Corrêa da Costa',
                'end_numero' => 2367,
                'end_bairro' => 'Coxipó',
                'cid_id' => $cuiaba->cid_id,
            ],
            [
                'end_tipo_logradouro' => 'Avenida',
                'end_logradouro' => 'Governador Júlio Campos',
                'end_numero' => 500,
                'end_bairro' => 'Centro',
                'cid_id' => $vgd->cid_id,
            ],
        ];

        foreach ($enderecos as $index => $endereco) {
            $enderecoModel = Endereco::create($endereco);

            // Associar endereços às unidades
            $unidade = Unidade::find($index + 1);

            if ($unidade) {
                $unidade->enderecos()->attach($enderecoModel->end_id);
            }
        }
    }
}

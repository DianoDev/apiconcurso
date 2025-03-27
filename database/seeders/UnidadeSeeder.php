<?php

namespace Database\Seeders;

use App\Models\Unidade;
use Illuminate\Database\Seeder;

class UnidadeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unidades = [
            [
                'unid_nome' => 'Secretaria de Estado de Planejamento e Gestão',
                'unid_sigla' => 'SEPLAG',
            ],
            [
                'unid_nome' => 'Secretaria de Estado de Fazenda',
                'unid_sigla' => 'SEFAZ',
            ],
            [
                'unid_nome' => 'Secretaria de Estado de Educação',
                'unid_sigla' => 'SEDUC',
            ],
            [
                'unid_nome' => 'Secretaria de Estado de Saúde',
                'unid_sigla' => 'SES',
            ],
            [
                'unid_nome' => 'Secretaria de Estado de Segurança Pública',
                'unid_sigla' => 'SESP',
            ],
        ];

        foreach ($unidades as $unidade) {
            Unidade::create($unidade);
        }
    }
}

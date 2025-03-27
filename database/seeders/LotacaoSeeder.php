<?php

namespace Database\Seeders;

use App\Models\Lotacao;
use Illuminate\Database\Seeder;

class LotacaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lotacoes = [
            [
                'pes_id' => 1, // João Silva (efetivo)
                'unid_id' => 1, // SEPLAG
                'lot_data_lotacao' => '2010-01-15',
                'lot_data_remocao' => null,
                'lot_portaria' => 'Portaria nº 001/2010',
            ],
            [
                'pes_id' => 2, // Ana Oliveira (efetivo)
                'unid_id' => 2, // SEFAZ
                'lot_data_lotacao' => '2015-03-01',
                'lot_data_remocao' => null,
                'lot_portaria' => 'Portaria nº 045/2015',
            ],
            [
                'pes_id' => 3, // Pedro Santos (efetivo)
                'unid_id' => 3, // SEDUC
                'lot_data_lotacao' => '2012-06-10',
                'lot_data_remocao' => null,
                'lot_portaria' => 'Portaria nº 078/2012',
            ],
            [
                'pes_id' => 4, // Fernanda Costa (temporário)
                'unid_id' => 4, // SES
                'lot_data_lotacao' => '2023-01-01',
                'lot_data_remocao' => '2023-12-31',
                'lot_portaria' => 'Portaria nº 123/2023',
            ],
            [
                'pes_id' => 5, // Gabriel Souza (temporário)
                'unid_id' => 5, // SESP
                'lot_data_lotacao' => '2023-02-15',
                'lot_data_remocao' => '2024-02-14',
                'lot_portaria' => 'Portaria nº 154/2023',
            ],
            // Histórico de lotação (João Silva já teve outra lotação)
            [
                'pes_id' => 1, // João Silva (efetivo)
                'unid_id' => 3, // SEDUC
                'lot_data_lotacao' => '2005-02-01',
                'lot_data_remocao' => '2010-01-14',
                'lot_portaria' => 'Portaria nº 033/2005',
            ],
        ];

        foreach ($lotacoes as $lotacao) {
            Lotacao::create($lotacao);
        }
    }
}

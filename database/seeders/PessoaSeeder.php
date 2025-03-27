<?php

namespace Database\Seeders;

use App\Models\Cidade;
use App\Models\Endereco;
use App\Models\Pessoa;
use Illuminate\Database\Seeder;

class PessoaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar pessoas para servidores efetivos
        $pessoasEfetivas = [
            [
                'pes_nome' => 'João Silva',
                'pes_data_nascimento' => '1980-06-15',
                'pes_sexo' => 'M',
                'pes_mae' => 'Maria Silva',
                'pes_pai' => 'José Silva',
            ],
            [
                'pes_nome' => 'Ana Oliveira',
                'pes_data_nascimento' => '1985-03-22',
                'pes_sexo' => 'F',
                'pes_mae' => 'Lúcia Oliveira',
                'pes_pai' => 'Carlos Oliveira',
            ],
            [
                'pes_nome' => 'Pedro Santos',
                'pes_data_nascimento' => '1978-11-10',
                'pes_sexo' => 'M',
                'pes_mae' => 'Mariana Santos',
                'pes_pai' => 'Ricardo Santos',
            ],
        ];

        foreach ($pessoasEfetivas as $pessoa) {
            Pessoa::create($pessoa);
        }

        // Criar pessoas para servidores temporários
        $pessoasTemporarias = [
            [
                'pes_nome' => 'Fernanda Costa',
                'pes_data_nascimento' => '1992-04-25',
                'pes_sexo' => 'F',
                'pes_mae' => 'Sandra Costa',
                'pes_pai' => 'Roberto Costa',
            ],
            [
                'pes_nome' => 'Gabriel Souza',
                'pes_data_nascimento' => '1988-09-18',
                'pes_sexo' => 'M',
                'pes_mae' => 'Cláudia Souza',
                'pes_pai' => 'Marcos Souza',
            ],
        ];

        foreach ($pessoasTemporarias as $pessoa) {
            Pessoa::create($pessoa);
        }

        // Associar endereços residenciais a algumas pessoas
        $cuiaba = Cidade::where('cid_nome', 'Cuiabá')->first();

        $enderecoPessoa1 = Endereco::create([
            'end_tipo_logradouro' => 'Rua',
            'end_logradouro' => 'das Palmeiras',
            'end_numero' => 123,
            'end_bairro' => 'Jardim Cuiabá',
            'cid_id' => $cuiaba->cid_id,
        ]);

        $enderecoPessoa2 = Endereco::create([
            'end_tipo_logradouro' => 'Avenida',
            'end_logradouro' => 'Miguel Sutil',
            'end_numero' => 456,
            'end_bairro' => 'Goiabeiras',
            'cid_id' => $cuiaba->cid_id,
        ]);

        // Associar os endereços às pessoas
        $pessoa1 = Pessoa::find(1);
        $pessoa1->enderecos()->attach($enderecoPessoa1->end_id);

        $pessoa2 = Pessoa::find(2);
        $pessoa2->enderecos()->attach($enderecoPessoa2->end_id);
    }
}

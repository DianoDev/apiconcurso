<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\Pessoa;
use App\Models\ServidorEfetivo;
use App\Models\Unidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApiServidorEfetivoController extends Controller
{
    public function index(Request $request)
    {
        // Adiciona uma mensagem ao log
        \Illuminate\Support\Facades\Log::info('Listando servidores efetivos');
        $servidores = ServidorEfetivo::with(['pessoa'])->paginate(10);
        return response()->json($servidores);
    }


    public function show($id)
    {
        $servidor = ServidorEfetivo::with([
            'pessoa' => function ($query) {
                $query->with(['fotos', 'lotacoes' => function ($query) {
                    $query->with('unidade');
                }]);
            }
        ])->findOrFail($id);

        return response()->json($servidor);
    }


    public function destroy($id)
    {
        $servidor = ServidorEfetivo::findOrFail($id);

        DB::beginTransaction();

        try {
            // Delete servidor efetivo
            $servidor->delete();


            DB::commit();

            return response()->json(['message' => 'Servidor efetivo excluído com sucesso']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao excluir servidor: ' . $e->getMessage()], 500);
        }
    }

    public function porUnidade($unidadeId)
    {
        $unidade = Unidade::findOrFail($unidadeId);

        $servidores = $unidade->servidoresEfetivos()
            ->map(function ($servidor) use ($unidade) {
                $foto = $servidor->fotos()->latest('fp_data')->first();

                return [
                    'nome' => $servidor->pes_nome,
                    'idade' => $servidor->idade,
                    'unidade_lotacao' => $unidade->unid_nome,
                    'fotografia' => $foto ? $foto->url : null,
                ];
            });

        return response()->json($servidores);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'pes_nome' => 'required|string|max:200',
                'pes_data_nascimento' => 'required|date',
                'pes_sexo' => 'required|string|size:1',
                'pes_mae' => 'required|string|max:200',
                'pes_pai' => 'nullable|string|max:200',
                'se_matricula' => 'required|string|max:20|unique:servidor_efetivo',
                'unid_id' => 'nullable|exists:unidade,unid_id',
            ]);

            DB::beginTransaction();

            try {
                $pessoa = Pessoa::create([
                    'pes_nome' => $validated['pes_nome'],
                    'pes_data_nascimento' => $validated['pes_data_nascimento'],
                    'pes_sexo' => $validated['pes_sexo'],
                    'pes_mae' => $validated['pes_mae'],
                    'pes_pai' => $validated['pes_pai'],
                ]);

                $servidorEfetivo = ServidorEfetivo::create([
                    'pes_id' => $pessoa->pes_id,
                    'se_matricula' => $validated['se_matricula'],
                ]);

                if (!empty($validated['unid_id'])) {
                    $pessoa->lotacoes()->create([
                        'unid_id' => $validated['unid_id'],
                        'lot_data_lotacao' => now(),
                    ]);
                }

                DB::commit();

                return response()->json([
                    'message' => 'Servidor efetivo cadastrado com sucesso',
                    'servidor' => $servidorEfetivo->load('pessoa'),
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Erro ao cadastrar servidor: ' . $e->getMessage()], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $servidorEfetivo = ServidorEfetivo::findOrFail($id);

            $validated = $request->validate([
                'pes_nome' => 'required|string|max:200',
                'pes_data_nascimento' => 'required|date',
                'pes_sexo' => 'required|string|size:1',
                'pes_mae' => 'required|string|max:200',
                'pes_pai' => 'nullable|string|max:200',
                'se_matricula' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('servidor_efetivo')->ignore($id, 'pes_id'),
                ],
                'unid_id' => 'nullable|exists:unidade,unid_id',
            ]);

            DB::beginTransaction();

            try {
                $servidorEfetivo->pessoa->update([
                    'pes_nome' => $validated['pes_nome'],
                    'pes_data_nascimento' => $validated['pes_data_nascimento'],
                    'pes_sexo' => $validated['pes_sexo'],
                    'pes_mae' => $validated['pes_mae'],
                    'pes_pai' => $validated['pes_pai'],
                ]);

                $servidorEfetivo->update([
                    'se_matricula' => $validated['se_matricula'],
                ]);

                if (!empty($validated['unid_id'])) {
                    $lotacaoAtual = $servidorEfetivo->pessoa->lotacaoAtual();

                    if (!$lotacaoAtual || $lotacaoAtual->unid_id != $validated['unid_id']) {
                        // Fechar a lotação atual
                        if ($lotacaoAtual) {
                            $lotacaoAtual->update(['lot_data_remocao' => now()]);
                        }

                        // Criar nova lotação
                        $servidorEfetivo->pessoa->lotacoes()->create([
                            'unid_id' => $validated['unid_id'],
                            'lot_data_lotacao' => now(),
                        ]);
                    }
                }

                DB::commit();

                return response()->json([
                    'message' => 'Servidor efetivo atualizado com sucesso',
                    'servidor' => $servidorEfetivo->load('pessoa'),
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Erro ao atualizar servidor: ' . $e->getMessage()], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao encontrar servidor: ' . $e->getMessage()], 404);
        }
    }

    public function buscarPorNome(Request $request)
    {
        try {
            $request->validate([
                'nome' => 'required|string|min:1',
            ]);

            $servidores = Pessoa::whereHas('servidorEfetivo')
                ->where('pes_nome', 'like', "%{$request->nome}%")
                ->with(['lotacoes' => function ($query) {
                    $query->whereNull('lot_data_remocao')
                        ->with(['unidade.enderecos' => function ($query) {
                            $query->with('cidade');
                        }]);
                }])
                ->get()
                ->map(function ($servidor) {
                    $lotacao = $servidor->lotacaoAtual();
                    $endereco = $lotacao && $lotacao->unidade ? $lotacao->unidade->enderecoAtual() : null;

                    return [
                        'id' => $servidor->pes_id,
                        'nome' => $servidor->pes_nome,
                        'idade' => $servidor->idade,
                        'unidade_lotacao' => $lotacao ? $lotacao->unidade->unid_nome : null,
                        'endereco_funcional' => $endereco ? $endereco->endereco_completo : null,
                    ];
                });

            return response()->json($servidores);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        }
    }
}

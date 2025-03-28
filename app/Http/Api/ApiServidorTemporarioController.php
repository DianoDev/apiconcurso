<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\Pessoa;
use App\Models\ServidorTemporario;
use App\Models\Lotacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiServidorTemporarioController extends Controller
{
    public function index(Request $request)
    {
        try {
            $servidores = ServidorTemporario::with(['pessoa' => function ($query) {
                $query->select('pes_id', 'pes_nome', 'pes_data_nascimento');
            }])
                ->join('pessoa', 'servidor_temporario.pes_id', '=', 'pessoa.pes_id')
                ->select('servidor_temporario.*', 'pessoa.pes_nome')
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('pes_nome', 'like', "%{$request->search}%");
                })
                ->orderBy('pessoa.pes_nome')
                ->paginate(10);

            return response()->json([
                'message' => 'Servidores temporários listados com sucesso',
                'servidores' => $servidores
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar servidores temporários: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao listar servidores temporários: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pes_nome' => 'required|string|max:200',
            'pes_data_nascimento' => 'required|date',
            'pes_sexo' => 'required|string|size:1',
            'pes_mae' => 'required|string|max:200',
            'pes_pai' => 'nullable|string|max:200',
            'st_data_admissao' => 'required|date',
            'st_data_demissao' => 'nullable|date|after:st_data_admissao',
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

            $servidorTemporario = ServidorTemporario::create([
                'pes_id' => $pessoa->pes_id,
                'st_data_admissao' => $validated['st_data_admissao'],
                'st_data_demissao' => $validated['st_data_demissao'] ?? null,
            ]);

            if (!empty($validated['unid_id'])) {
                Lotacao::create([
                    'pes_id' => $pessoa->pes_id,
                    'unid_id' => $validated['unid_id'],
                    'lot_data_lotacao' => $validated['st_data_admissao'],
                    'lot_data_remocao' => $validated['st_data_demissao'] ?? null,
                ]);
            }

            DB::commit();

            // Carregar relacionamentos para retornar na resposta
            $servidorTemporario->load(['pessoa' => function ($query) {
                $query->with(['lotacoes' => function ($query) {
                    $query->with('unidade');
                }]);
            }]);

            return response()->json([
                'message' => 'Servidor temporário cadastrado com sucesso',
                'servidor' => $servidorTemporario
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao cadastrar servidor temporário: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao cadastrar servidor temporário: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $servidor = ServidorTemporario::with([
                'pessoa' => function ($query) {
                    $query->with(['fotos', 'lotacoes' => function ($query) {
                        $query->with('unidade');
                    }]);
                }
            ])->findOrFail($id);

            return response()->json([
                'message' => 'Servidor temporário encontrado',
                'servidor' => $servidor
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar servidor temporário: ' . $e->getMessage());
            return response()->json(['error' => 'Servidor temporário não encontrado'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $servidorTemporario = ServidorTemporario::findOrFail($id);

            $validated = $request->validate([
                'pes_nome' => 'required|string|max:200',
                'pes_data_nascimento' => 'required|date',
                'pes_sexo' => 'required|string|size:1',
                'pes_mae' => 'required|string|max:200',
                'pes_pai' => 'nullable|string|max:200',
                'st_data_admissao' => 'required|date',
                'st_data_demissao' => 'nullable|date|after:st_data_admissao',
                'unid_id' => 'nullable|exists:unidade,unid_id',
            ]);

            DB::beginTransaction();

            try {
                $servidorTemporario->pessoa->update([
                    'pes_nome' => $validated['pes_nome'],
                    'pes_data_nascimento' => $validated['pes_data_nascimento'],
                    'pes_sexo' => $validated['pes_sexo'],
                    'pes_mae' => $validated['pes_mae'],
                    'pes_pai' => $validated['pes_pai'],
                ]);

                $servidorTemporario->update([
                    'st_data_admissao' => $validated['st_data_admissao'],
                    'st_data_demissao' => $validated['st_data_demissao'] ?? null,
                ]);

                if (!empty($validated['unid_id'])) {
                    $lotacaoAtual = $servidorTemporario->pessoa->lotacaoAtual();

                    if (!$lotacaoAtual || $lotacaoAtual->unid_id != $validated['unid_id']) {
                        // Fechar a lotação atual
                        if ($lotacaoAtual) {
                            $lotacaoAtual->update(['lot_data_remocao' => now()]);
                        }

                        // Criar nova lotação
                        Lotacao::create([
                            'pes_id' => $servidorTemporario->pes_id,
                            'unid_id' => $validated['unid_id'],
                            'lot_data_lotacao' => now(),
                            'lot_data_remocao' => $validated['st_data_demissao'] ?? null,
                        ]);
                    }
                }

                DB::commit();

                // Recarregar o modelo com seus relacionamentos
                $servidorTemporario->load(['pessoa' => function ($query) {
                    $query->with(['lotacoes' => function ($query) {
                        $query->with('unidade');
                    }]);
                }]);

                return response()->json([
                    'message' => 'Servidor temporário atualizado com sucesso',
                    'servidor' => $servidorTemporario
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar servidor temporário: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao atualizar servidor temporário: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $servidor = ServidorTemporario::findOrFail($id);

            DB::beginTransaction();

            try {
                // Podemos marcar as lotações como encerradas em vez de excluí-las
                foreach ($servidor->pessoa->lotacoes as $lotacao) {
                    if (!$lotacao->lot_data_remocao) {
                        $lotacao->update(['lot_data_remocao' => now()]);
                    }
                }

                $servidor->delete();

                DB::commit();

                return response()->json([
                    'message' => 'Servidor temporário excluído com sucesso'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Erro ao excluir servidor temporário: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao excluir servidor temporário: ' . $e->getMessage()], 500);
        }
    }
}

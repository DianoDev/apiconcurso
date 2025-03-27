<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\Pessoa;
use App\Models\ServidorTemporario;
use App\Models\Lotacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ApiServidorTemporarioController extends Controller
{
    public function index(Request $request)
    {
        $servidores = ServidorTemporario::with(['pessoa' => function ($query) {
            $query->select('pes_id', 'pes_nome', 'pes_data_nascimento');
        }])
            ->join('pessoa', 'servidor_temporario.pes_id', '=', 'pessoa.pes_id')
            ->select('servidor_temporario.*', 'pessoa.pes_nome')
            ->orderBy('pessoa.pes_nome')
            ->paginate(10);

        return response()->json($servidores);
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

            return response()->json($servidorTemporario);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erro ao cadastrar servidor: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $servidor = ServidorTemporario::with([
            'pessoa' => function ($query) {
                $query->with(['fotos', 'lotacoes' => function ($query) {
                    $query->with('unidade');
                }]);
            }
        ])->findOrFail($id);

        return response()->json($servidor);
    }

    public function edit($id)
    {
        $servidor = ServidorTemporario::with([
            'pessoa' => function ($query) {
                $query->with(['lotacoes' => function ($query) {
                    $query->orderBy('lot_data_lotacao', 'desc');
                }]);
            }
        ])->findOrFail($id);

        return Inertia::render('ServidorTemporario/Edit', [
            'servidor' => $servidor
        ]);
    }

    public function update(Request $request, $id)
    {
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
            return response()->json($servidorTemporario);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erro ao atualizar servidor: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $servidor = ServidorTemporario::findOrFail($id);

        DB::beginTransaction();

        try {

            $servidor->delete();


            DB::commit();

            return response()->json('Deletado');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json('Erro ao Excluir');
        }
    }
}

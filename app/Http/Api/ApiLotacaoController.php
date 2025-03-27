<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\Lotacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiLotacaoController extends Controller
{
    public function index(Request $request)
    {
        $lotacoes = Lotacao::with(['pessoa', 'unidade'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->whereHas('pessoa', function ($query) use ($request) {
                    $query->where('pes_nome', 'like', "%{$request->search}%");
                })->orWhereHas('unidade', function ($query) use ($request) {
                    $query->where('unid_nome', 'like', "%{$request->search}%");
                });
            })
            ->orderBy('lot_data_lotacao', 'desc')
            ->paginate(10);

        return response()->json($lotacoes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pes_id' => 'required|exists:pessoa,pes_id',
            'unid_id' => 'required|exists:unidade,unid_id',
            'lot_data_lotacao' => 'required|date',
            'lot_data_remocao' => 'nullable|date|after:lot_data_lotacao',
            'lot_portaria' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();

        try {
            // Fechar lotação atual se existir
            $lotacaoAtual = Lotacao::where('pes_id', $validated['pes_id'])
                ->whereNull('lot_data_remocao')
                ->first();

            if ($lotacaoAtual) {
                $lotacaoAtual->update(['lot_data_remocao' => $validated['lot_data_lotacao']]);
            }

            // Criar nova lotação
            $lotacao = Lotacao::create([
                'pes_id' => $validated['pes_id'],
                'unid_id' => $validated['unid_id'],
                'lot_data_lotacao' => $validated['lot_data_lotacao'],
                'lot_data_remocao' => $validated['lot_data_remocao'] ?? null,
                'lot_portaria' => $validated['lot_portaria'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Lotação cadastrada com sucesso',
                'lotacao' => $lotacao->load(['pessoa', 'unidade']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao cadastrar lotação: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $lotacao = Lotacao::with(['pessoa', 'unidade.enderecos.cidade'])->findOrFail($id);

        return response()->json($lotacao);
    }

    public function update(Request $request, $id)
    {
        $lotacao = Lotacao::findOrFail($id);
        \Illuminate\Support\Facades\Log::info('update lotacao');
        $validated = $request->validate([
            'pes_id' => 'required|exists:pessoa,pes_id',
            'unid_id' => 'required|exists:unidade,unid_id',
            'lot_data_lotacao' => 'required|date',
            'lot_data_remocao' => 'nullable|date|after:lot_data_lotacao',
            'lot_portaria' => 'nullable|string|max:100',
        ]);
        \Illuminate\Support\Facades\Log::info('update lotacao validado');
        try {
            $lotacao->update([
                'pes_id' => $validated['pes_id'],
                'unid_id' => $validated['unid_id'],
                'lot_data_lotacao' => $validated['lot_data_lotacao'],
                'lot_data_remocao' => $validated['lot_data_remocao'] ?? null,
                'lot_portaria' => $validated['lot_portaria'] ?? null,
            ]);
            \Illuminate\Support\Facades\Log::info('update lotacao updatado');
            return response()->json([
                'message' => 'Lotação atualizada com sucesso',
                'lotacao' => $lotacao->load(['pessoa', 'unidade']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar lotação: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $lotacao = Lotacao::findOrFail($id);
            $lotacao->delete();

            return response()->json(['message' => 'Lotação excluída com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao excluir lotação: ' . $e->getMessage()], 500);
        }
    }
}

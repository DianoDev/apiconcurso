<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\Unidade;
use App\Models\Endereco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApiUnidadeController extends Controller
{
    public function index(Request $request)
    {
        $unidades = Unidade::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('unid_nome', 'like', "%{$request->search}%")
                    ->orWhere('unid_sigla', 'like', "%{$request->search}%");
            })
            ->orderBy('unid_nome')
            ->paginate(10);

        return response()->json($unidades);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'unid_nome' => 'required|string|max:200',
            'unid_sigla' => 'required|string|max:20|unique:unidade',
            // Endereço
            'end_tipo_logradouro' => 'required|string|max:30',
            'end_logradouro' => 'required|string|max:200',
            'end_numero' => 'nullable|integer',
            'end_bairro' => 'required|string|max:100',
            'cid_id' => 'required|exists:cidade,cid_id',
        ]);

        DB::beginTransaction();

        try {
            $unidade = Unidade::create([
                'unid_nome' => $validated['unid_nome'],
                'unid_sigla' => $validated['unid_sigla'],
            ]);

            $endereco = Endereco::create([
                'end_tipo_logradouro' => $validated['end_tipo_logradouro'],
                'end_logradouro' => $validated['end_logradouro'],
                'end_numero' => $validated['end_numero'],
                'end_bairro' => $validated['end_bairro'],
                'cid_id' => $validated['cid_id'],
            ]);

            $unidade->enderecos()->attach($endereco->end_id);

            DB::commit();

            return response()->json([
                'message' => 'Unidade cadastrada com sucesso',
                'unidade' => $unidade->load('enderecos.cidade'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao cadastrar unidade: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $unidade = Unidade::with(['enderecos.cidade'])->findOrFail($id);

        return response()->json($unidade);
    }

    public function update(Request $request, $id)
    {
        $unidade = Unidade::findOrFail($id);

        $validated = $request->validate([
            'unid_nome' => 'required|string|max:200',
            'unid_sigla' => [
                'required',
                'string',
                'max:20',
                Rule::unique('unidade')->ignore($id, 'unid_id'),
            ],
            // Endereço
            'end_id' => 'nullable|exists:endereco,end_id',
            'end_tipo_logradouro' => 'required|string|max:30',
            'end_logradouro' => 'required|string|max:200',
            'end_numero' => 'nullable|integer',
            'end_bairro' => 'required|string|max:100',
            'cid_id' => 'required|exists:cidade,cid_id',
        ]);

        DB::beginTransaction();

        try {
            $unidade->update([
                'unid_nome' => $validated['unid_nome'],
                'unid_sigla' => $validated['unid_sigla'],
            ]);

            // Atualizar endereço existente ou criar um novo
            if (!empty($validated['end_id'])) {
                $endereco = Endereco::findOrFail($validated['end_id']);
                $endereco->update([
                    'end_tipo_logradouro' => $validated['end_tipo_logradouro'],
                    'end_logradouro' => $validated['end_logradouro'],
                    'end_numero' => $validated['end_numero'],
                    'end_bairro' => $validated['end_bairro'],
                    'cid_id' => $validated['cid_id'],
                ]);
            } else {
                $endereco = Endereco::create([
                    'end_tipo_logradouro' => $validated['end_tipo_logradouro'],
                    'end_logradouro' => $validated['end_logradouro'],
                    'end_numero' => $validated['end_numero'],
                    'end_bairro' => $validated['end_bairro'],
                    'cid_id' => $validated['cid_id'],
                ]);

                $unidade->enderecos()->attach($endereco->end_id);
            }

            DB::commit();

            return response()->json([
                'message' => 'Unidade atualizada com sucesso',
                'unidade' => $unidade->load('enderecos.cidade'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao atualizar unidade: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $unidade = Unidade::findOrFail($id);

        // Verificar se existem lotações ativas para esta unidade
        $hasActiveServers = $unidade->lotacoes()
            ->whereNull('lot_data_remocao')
            ->exists();

        if ($hasActiveServers) {
            return response()->json([
                'error' => 'Não é possível excluir a unidade porque existem servidores lotados nela.'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Desanexar endereços (não exclui os endereços)
            $unidade->enderecos()->detach();

            // Excluir lotações relacionadas
            $unidade->lotacoes()->delete();

            // Excluir unidade
            $unidade->delete();

            DB::commit();

            return response()->json(['message' => 'Unidade excluída com sucesso']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao excluir unidade: ' . $e->getMessage()], 500);
        }
    }
}

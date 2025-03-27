<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\FotoPessoa;
use App\Models\Pessoa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApiFotoPessoaController extends Controller
{
    public function store(Request $request, $pessoaId)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,pdf|max:2048',
        ]);

        try {
            $pessoa = Pessoa::findOrFail($pessoaId);

            $file = $request->file('file');
            $hash = Str::random(40);
            $filename = $hash . '.' . $file->getClientOriginalExtension();

            // Fazer upload para o MinIO
            Storage::disk('s3')->put($filename, file_get_contents($file));

            $foto = FotoPessoa::create([
                'pes_id' => $pessoa->pes_id,
                'fp_data' => now(),
                'fp_bucket' => config('filesystems.disks.s3.bucket'),
                'fp_hash' => $filename,
            ]);

            return response()->json([
                'message' => 'Foto cadastrada com sucesso',
                'foto' => [
                    'id' => $foto->fp_id,
                    'url' => $foto->url
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao cadastrar foto: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $foto = FotoPessoa::findOrFail($id);

            return response()->json([
                'id' => $foto->fp_id,
                'data' => $foto->fp_data,
                'url' => $foto->url
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao recuperar foto: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $foto = FotoPessoa::findOrFail($id);

            // Excluir o arquivo do MinIO
            Storage::disk('s3')->delete($foto->fp_hash);

            // Excluir o registro do banco de dados
            $foto->delete();

            return response()->json(['message' => 'Foto excluída com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao excluir foto: ' . $e->getMessage()], 500);
        }
    }

    public function listarPorPessoa($pessoaId)
    {
        try {
            $pessoa = Pessoa::findOrFail($pessoaId);

            $fotos = $pessoa->fotos()->latest('fp_data')->get()->map(function ($foto) {
                return [
                    'id' => $foto->fp_id,
                    'data' => $foto->fp_data,
                    'url' => $foto->url
                ];
            });

            return response()->json($fotos);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao listar fotos: ' . $e->getMessage()], 500);
        }
    }
}

<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\FotoPessoa;
use App\Models\Pessoa;
use App\Services\MinioService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiFotoPessoaController extends Controller
{
    protected $minioService;

    public function __construct(MinioService $minioService)
    {
        $this->minioService = $minioService;
    }

    public function store(Request $request, $pessoaId)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $pessoa = Pessoa::findOrFail($pessoaId);
            $file = $request->file('file');

            // Usar o MinioService para fazer upload
            $result = $this->minioService->uploadFile($file, 'fotos');

            $foto = FotoPessoa::create([
                'pes_id' => $pessoa->pes_id,
                'fp_data' => now(),
                'fp_bucket' => $result['bucket'],
                'fp_hash' => $result['hash'],
            ]);

            // Gerar URL temporária para retornar no response
            $temporaryUrl = $this->minioService->getTemporaryUrl($result['path'], 5);

            return response()->json([
                'message' => 'Foto cadastrada com sucesso',
                'foto' => [
                    'id' => $foto->fp_id,
                    'url' => $temporaryUrl
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

            // Gerar URL temporária
            $temporaryUrl = $this->minioService->getTemporaryUrl($foto->fp_hash, 5);

            return response()->json([
                'id' => $foto->fp_id,
                'data' => $foto->fp_data,
                'url' => $temporaryUrl
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
            $this->minioService->deleteFile($foto->fp_hash);

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
                // Gerar URL temporária para cada foto
                $temporaryUrl = $this->minioService->getTemporaryUrl($foto->fp_hash, 5);

                return [
                    'id' => $foto->fp_id,
                    'data' => $foto->fp_data,
                    'url' => $temporaryUrl
                ];
            });

            return response()->json($fotos);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao listar fotos: ' . $e->getMessage()], 500);
        }
    }
}

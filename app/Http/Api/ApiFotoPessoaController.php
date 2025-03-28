<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\FotoPessoa;
use App\Models\Pessoa;
use App\Services\MinioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiFotoPessoaController extends Controller
{
    protected $minioService;

    public function __construct(MinioService $minioService)
    {
        $this->minioService = $minioService;
    }

    /**
     * Processa o upload de uma foto para uma pessoa
     */
    private function processPhotoUpload($file, $pessoaId)
    {
        $pessoa = Pessoa::findOrFail($pessoaId);

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

        return [
            'id' => $foto->fp_id,
            'data' => $foto->fp_data,
            'url' => $temporaryUrl
        ];
    }

    /**
     * Armazena uma nova foto ou múltiplas fotos para uma pessoa
     */
    public function store(Request $request, $pessoaId)
    {
        try {
            // Verificar se é um único arquivo ou múltiplos
            if ($request->hasFile('file')) {
                // Processar um único arquivo
                $request->validate([
                    'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                $file = $request->file('file');
                $photo = $this->processPhotoUpload($file, $pessoaId);

                return response()->json([
                    'message' => 'Foto cadastrada com sucesso',
                    'foto' => $photo
                ], 201);
            } elseif ($request->hasFile('files')) {
                // Processar múltiplos arquivos
                $files = $request->file('files');

                // Verificar se files é um array ou um único arquivo
                if (!is_array($files)) {
                    $files = [$files]; // Converter para array se for um único arquivo
                }

                $uploadedPhotos = [];

                foreach ($files as $file) {
                    // Validar cada arquivo individualmente
                    if (!$file->isValid() ||
                        !in_array($file->getClientMimeType(), ['image/jpeg', 'image/png', 'image/jpg', 'image/gif']) ||
                        $file->getSize() > 2048 * 1024) {
                        continue; // Pular arquivos inválidos
                    }

                    $uploadedPhotos[] = $this->processPhotoUpload($file, $pessoaId);
                }

                if (empty($uploadedPhotos)) {
                    return response()->json(['error' => 'Nenhum arquivo válido foi enviado'], 400);
                }

                return response()->json([
                    'message' => count($uploadedPhotos) . ' foto(s) cadastrada(s) com sucesso',
                    'fotos' => $uploadedPhotos
                ], 201);
            } else {
                return response()->json(['error' => 'Nenhum arquivo enviado'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao cadastrar fotos: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao cadastrar foto(s): ' . $e->getMessage()], 500);
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

<?php

namespace App\Services;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MinioService
{
    protected $client;
    protected $bucket;
    protected $endpoint;
    protected $url;

    /**
     * Inicializa o serviço MinIO
     */
    public function __construct()
    {
        $this->bucket = config('filesystems.disks.s3.bucket');
        $this->endpoint = config('filesystems.disks.s3.endpoint');
        $this->url = config('filesystems.disks.s3.url');

        $this->client = new S3Client([
            'version' => 'latest',
            'region'  => config('filesystems.disks.s3.region'),
            'endpoint' => $this->endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key'    => config('filesystems.disks.s3.key'),
                'secret' => config('filesystems.disks.s3.secret'),
            ],
        ]);
    }

    /**
     * Faz upload de um arquivo para o MinIO
     *
     * @param UploadedFile $file Arquivo a ser enviado
     * @param string $directory Diretório onde o arquivo será armazenado
     * @param string|null $filename Nome personalizado do arquivo (opcional)
     * @return array Informações do arquivo enviado
     */
    public function uploadFile(UploadedFile $file, string $directory = 'fotos', string $filename = null): array
    {
        // Gera um nome único para o arquivo se não for fornecido
        if (!$filename) {
            $filename = $this->generateUniqueFilename($file);
        }

        $path = $directory . '/' . $filename;

        try {
            // Fazer upload usando o Storage do Laravel
            Storage::disk('s3')->put($path, file_get_contents($file));

            return [
                'path' => $path,
                'hash' => $filename,
                'bucket' => $this->bucket,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'original_name' => $file->getClientOriginalName()
            ];
        } catch (S3Exception $e) {
            Log::error('Erro ao fazer upload para MinIO: ' . $e->getMessage());
            throw new \Exception('Não foi possível fazer o upload do arquivo: ' . $e->getMessage());
        }
    }

    /**
     * Gera uma URL temporária para acesso ao arquivo
     *
     * @param string $path Caminho do arquivo no bucket
     * @param int $expiration Tempo em minutos para expiração do link
     * @return string URL temporária
     */
    public function getTemporaryUrl(string $path, int $expiration = 5): string
    {
        try {
            $command = $this->client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key'    => $path
            ]);

            $request = $this->client->createPresignedRequest($command, "+{$expiration} minutes");

            return (string) $request->getUri();
        } catch (S3Exception $e) {
            Log::error('Erro ao gerar URL temporária: ' . $e->getMessage());
            throw new \Exception('Não foi possível gerar a URL temporária: ' . $e->getMessage());
        }
    }

    /**
     * Verifica se um arquivo existe no bucket
     *
     * @param string $path Caminho do arquivo
     * @return bool
     */
    public function fileExists(string $path): bool
    {
        try {
            return $this->client->doesObjectExist($this->bucket, $path);
        } catch (S3Exception $e) {
            Log::error('Erro ao verificar existência do arquivo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Exclui um arquivo do bucket
     *
     * @param string $path Caminho do arquivo
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        try {
            if ($this->fileExists($path)) {
                $this->client->deleteObject([
                    'Bucket' => $this->bucket,
                    'Key'    => $path
                ]);
                return true;
            }
            return false;
        } catch (S3Exception $e) {
            Log::error('Erro ao excluir arquivo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Lista arquivos de um diretório
     *
     * @param string $directory Diretório a listar
     * @return array Lista de arquivos
     */
    public function listFiles(string $directory): array
    {
        try {
            $objects = $this->client->listObjects([
                'Bucket' => $this->bucket,
                'Prefix' => $directory
            ]);

            $files = [];

            if (isset($objects['Contents'])) {
                foreach ($objects['Contents'] as $object) {
                    $files[] = [
                        'path' => $object['Key'],
                        'size' => $object['Size'],
                        'last_modified' => $object['LastModified']->format('Y-m-d H:i:s'),
                    ];
                }
            }

            return $files;
        } catch (S3Exception $e) {
            Log::error('Erro ao listar arquivos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Cria um bucket caso não exista
     *
     * @param string $bucket Nome do bucket
     * @return bool
     */
    public function createBucketIfNotExists(string $bucket = null): bool
    {
        if (!$bucket) {
            $bucket = $this->bucket;
        }

        try {
            if (!$this->client->doesBucketExist($bucket)) {
                $this->client->createBucket([
                    'Bucket' => $bucket,
                ]);

                // Configura a política de acesso público (somente leitura)
                $this->client->putBucketPolicy([
                    'Bucket' => $bucket,
                    'Policy' => json_encode([
                        'Version' => '2012-10-17',
                        'Statement' => [
                            [
                                'Sid' => 'PublicRead',
                                'Effect' => 'Allow',
                                'Principal' => '*',
                                'Action' => ['s3:GetObject'],
                                'Resource' => ["arn:aws:s3:::{$bucket}/*"]
                            ]
                        ]
                    ])
                ]);

                return true;
            }
            return true;
        } catch (S3Exception $e) {
            Log::error('Erro ao criar bucket: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Copia um arquivo dentro do bucket
     *
     * @param string $sourcePath Caminho de origem
     * @param string $destinationPath Caminho de destino
     * @return bool
     */
    public function copyFile(string $sourcePath, string $destinationPath): bool
    {
        try {
            $this->client->copyObject([
                'Bucket' => $this->bucket,
                'CopySource' => "{$this->bucket}/{$sourcePath}",
                'Key' => $destinationPath
            ]);

            return true;
        } catch (S3Exception $e) {
            Log::error('Erro ao copiar arquivo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna os metadados de um arquivo
     *
     * @param string $path Caminho do arquivo
     * @return array|null
     */
    public function getFileMetadata(string $path): ?array
    {
        try {
            $result = $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key'    => $path
            ]);

            return [
                'content_type' => $result['ContentType'],
                'content_length' => $result['ContentLength'],
                'last_modified' => $result['LastModified']->format('Y-m-d H:i:s'),
                'etag' => trim($result['ETag'], '"'),
            ];
        } catch (S3Exception $e) {
            Log::error('Erro ao obter metadados do arquivo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Gera um nome de arquivo único baseado no arquivo enviado
     *
     * @param UploadedFile $file
     * @return string
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Remove caracteres especiais do nome do arquivo
        $basename = Str::slug($basename);

        // Gera um hash baseado no nome original, timestamp e um valor aleatório
        $hash = md5($basename . time() . Str::random(8));

        // Retorna o novo nome do arquivo
        return $hash . '.' . $extension;
    }

    /**
     * Gera uma URL pública para o arquivo (não temporária, mas requer que o bucket permita acesso público)
     *
     * @param string $path Caminho do arquivo no bucket
     * @return string URL pública
     */
    public function getPublicUrl(string $path): string
    {
        return "{$this->url}/{$this->bucket}/{$path}";
    }

    /**
     * Faz upload de uma string de conteúdo como arquivo
     *
     * @param string $content Conteúdo a ser salvo
     * @param string $path Caminho onde o arquivo será salvo
     * @param string $contentType Tipo de conteúdo do arquivo
     * @return bool
     */
    public function uploadContent(string $content, string $path, string $contentType = 'text/plain'): bool
    {
        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $path,
                'Body'   => $content,
                'ContentType' => $contentType
            ]);

            return true;
        } catch (S3Exception $e) {
            Log::error('Erro ao fazer upload de conteúdo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Lê o conteúdo de um arquivo
     *
     * @param string $path Caminho do arquivo
     * @return string|null
     */
    public function getFileContent(string $path): ?string
    {
        try {
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $path
            ]);

            return (string) $result['Body'];
        } catch (S3Exception $e) {
            Log::error('Erro ao ler conteúdo do arquivo: ' . $e->getMessage());
            return null;
        }
    }
}

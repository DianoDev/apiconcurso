<?php

namespace App\Models;

use App\Services\MinioService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FotoPessoa extends Model
{
    use HasFactory;

    protected $table = 'foto_pessoa';
    protected $primaryKey = 'fp_id';

    protected $fillable = [
        'pes_id',
        'fp_data',
        'fp_bucket',
        'fp_hash',
    ];

    protected $casts = [
        'fp_data' => 'date',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'pes_id');
    }

    public function getUrlAttribute(): string
    {
        $minioService = app(MinioService::class);
        return $minioService->getTemporaryUrl($this->fp_hash, 5);
    }
}

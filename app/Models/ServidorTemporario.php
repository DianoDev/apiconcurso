<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServidorTemporario extends Model
{
    use HasFactory;

    protected $table = 'servidor_temporario';
    protected $primaryKey = 'pes_id';
    public $incrementing = false;

    protected $fillable = [
        'pes_id',
        'st_data_admissao',
        'st_data_demissao',
    ];

    protected $casts = [
        'st_data_admissao' => 'date',
        'st_data_demissao' => 'date',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'pes_id');
    }
}

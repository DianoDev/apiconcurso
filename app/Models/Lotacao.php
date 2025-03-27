<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lotacao extends Model
{
    use HasFactory;

    protected $table = 'lotacao';
    protected $primaryKey = 'lot_id';

    protected $fillable = [
        'pes_id',
        'unid_id',
        'lot_data_lotacao',
        'lot_data_remocao',
        'lot_portaria',
    ];

    protected $casts = [
        'lot_data_lotacao' => 'date',
        'lot_data_remocao' => 'date',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'pes_id');
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class, 'unid_id');
    }
}

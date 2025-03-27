<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unidade extends Model
{
    use HasFactory;

    protected $table = 'unidade';
    protected $primaryKey = 'unid_id';

    protected $fillable = [
        'unid_nome',
        'unid_sigla',
    ];

    public function enderecos(): BelongsToMany
    {
        return $this->belongsToMany(Endereco::class, 'unidade_endereco', 'unid_id', 'end_id');
    }

    public function lotacoes(): HasMany
    {
        return $this->hasMany(Lotacao::class, 'unid_id');
    }

    public function servidoresEfetivos()
    {
        return $this->lotacoes()
            ->whereNull('lot_data_remocao')
            ->whereHas('pessoa.servidorEfetivo')
            ->with(['pessoa' => function ($query) {
                $query->with(['servidorEfetivo', 'fotos' => function ($query) {
                    $query->latest('fp_data')->limit(1);
                }]);
            }])
            ->get()
            ->pluck('pessoa');
    }

    public function enderecoAtual()
    {
        return $this->enderecos()->first();
    }
}

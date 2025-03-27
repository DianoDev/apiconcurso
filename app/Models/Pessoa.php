<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pessoa extends Model
{
    use HasFactory;

    protected $table = 'pessoa';
    protected $primaryKey = 'pes_id';

    protected $fillable = [
        'pes_nome',
        'pes_data_nascimento',
        'pes_sexo',
        'pes_mae',
        'pes_pai',
    ];

    protected $casts = [
        'pes_data_nascimento' => 'date',
    ];

    protected $appends = ['idade'];

    public function getIdadeAttribute(): int
    {
        return $this->pes_data_nascimento->diffInYears(Carbon::now());
    }

    public function enderecos(): BelongsToMany
    {
        return $this->belongsToMany(Endereco::class, 'pessoa_endereco', 'pes_id', 'end_id');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(FotoPessoa::class, 'pes_id');
    }

    public function servidorEfetivo(): HasOne
    {
        return $this->hasOne(ServidorEfetivo::class, 'pes_id');
    }

    public function servidorTemporario(): HasOne
    {
        return $this->hasOne(ServidorTemporario::class, 'pes_id');
    }

    public function lotacoes(): HasMany
    {
        return $this->hasMany(Lotacao::class, 'pes_id');
    }

    public function lotacaoAtual()
    {
        return $this->lotacoes()->whereNull('lot_data_remocao')->latest('lot_data_lotacao')->first();
    }
}

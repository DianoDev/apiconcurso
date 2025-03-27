<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cidade extends Model
{
    use HasFactory;

    /**
     * A chave primária da tabela.
     *
     * @var string
     */

    protected $table = 'cidade';
    protected $primaryKey = 'cid_id';
    /**
     * Os atributos que podem ser atribuídos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cid_nome',
        'cid_uf',
    ];

    /**
     * Obter os endereços da cidade.
     */
    public function enderecos(): HasMany
    {
        return $this->hasMany(Endereco::class, 'cid_id');
    }
}

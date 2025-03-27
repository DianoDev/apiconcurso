<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Endereco extends Model
{
    use HasFactory;

    /**
     * A chave primária da tabela.
     *
     * @var string
     */
    protected $primaryKey = 'end_id';

    /**
     * Os atributos que podem ser atribuídos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'pes_id',
        'end_id',
    ];

}

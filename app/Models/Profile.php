<?php
// app/Models/Profile.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    // Primary key é o mesmo UUID do users
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'id','tipo','foto_url','cidade','bio','telefone','cpf',
        'data_nascimento','endereco_rua','endereco_numero','endereco_bairro',
        'possui_bag','veiculo','avg_rating','total_reviews',
    ];

    protected $casts = [
        'possui_bag'     => 'boolean',
        'avg_rating'     => 'float',
        'total_reviews'  => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Nome para exibição (usa nome do profile ou do user)
    public function getNomeAttribute($value): string
    {
        return $value ?? $this->user?->name ?? '—';
    }
}

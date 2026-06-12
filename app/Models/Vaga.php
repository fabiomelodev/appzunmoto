<?php
// app/Models/Vaga.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vaga extends Model
{
    use HasUuids;

    protected $fillable = [
        'criador_id','criador_tipo','local','regiao','endereco','cep',
        'data','hora_inicio','hora_fim','valor_diaria','valor_entrega',
        'beneficios','veiculos_aceitos','exige_bag_propria','observacoes',
        'status','reservado_por','lat','lng',
    ];

    protected $casts = [
        'beneficios'       => 'array',
        'veiculos_aceitos'  => 'array',
        'exige_bag_propria' => 'boolean',
        'valor_diaria'     => 'float',
        'valor_entrega'    => 'float',
        'lat'              => 'float',
        'lng'              => 'float',
    ];

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criador_id');
    }

    public function candidaturas(): HasMany
    {
        return $this->hasMany(Candidatura::class, 'vaga_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'vaga_id');
    }
}

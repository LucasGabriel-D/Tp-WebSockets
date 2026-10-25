<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClashMatchLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'clash_match_id',
        'atacante_user_id',
        'bando_atacante',
        'tipo_golpe',
        'danio',
        'rojo_hp_restante',
        'azul_hp_restante',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'danio' => 'integer',
        'rojo_hp_restante' => 'integer',
        'azul_hp_restante' => 'integer',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(ClashMatch::class, 'clash_match_id');
    }

    public function atacante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atacante_user_id');
    }
}

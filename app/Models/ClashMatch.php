<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClashMatch extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sala',
        'rojo_user_id',
        'azul_user_id',
        'rojo_hp',
        'azul_hp',
        'estado',
        'ganador',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rojo_hp' => 'integer',
        'azul_hp' => 'integer',
    ];

    /** HP máximo por jugador. */
    public const int HP_MAXIMO = 1000;

    /** Cache key prefix para el estado en vivo. */
    public const string CACHE_PREFIX = 'clash:';

    /**
     * Obtiene la clave de caché para el estado en vivo de la sala.
     */
    public static function cacheKey(string $sala): string
    {
        return self::CACHE_PREFIX.$sala;
    }

    public function rojoBando(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rojo_user_id');
    }

    public function azulBando(): BelongsTo
    {
        return $this->belongsTo(User::class, 'azul_user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ClashMatchLog::class);
    }

    /**
     * Indica si la partida ya terminó.
     */
    public function haFinalizado(): bool
    {
        return $this->estado === 'finalizado';
    }
}

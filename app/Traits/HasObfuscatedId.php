<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\HashIdService;
use Illuminate\Database\Eloquent\Model;

trait HasObfuscatedId
{
    /**
     * Dapatkan hash ID terenkripsi dari model.
     */
    public function getHashIdAttribute(): string
    {
        return HashIdService::encode((int) $this->getKey());
    }

    /**
     * Alias untuk obfuscated_id
     */
    public function getObfuscatedIdAttribute(): string
    {
        return $this->getHashIdAttribute();
    }

    /**
     * Menemukan model berdasarkan hash ID terenkripsi.
     */
    public static function findByHashId(string $hash): ?self
    {
        $id = HashIdService::decode($hash);

        return $id ? static::find($id) : null;
    }

    /**
     * Query Scope untuk mencari berdasarkan Hash ID terenkripsi.
     */
    public function scopeWhereHashId($query, string $hash)
    {
        $id = HashIdService::decode($hash);

        return $id ? $query->where($this->getQualifiedKeyName(), $id) : $query->whereRaw('1 = 0');
    }

    /**
     * Resolusi otomatis untuk Route Model Binding di Laravel rute.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $id = HashIdService::decode((string) $value);

        return $id ? $this->where($this->getRouteKeyName(), $id)->first() : null;
    }
}

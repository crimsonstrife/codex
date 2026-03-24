<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A system-level API token not associated with any user.
 * Used for trusted server-to-server integrations (e.g. Forge → Codex).
 *
 * @property string   $id
 * @property string   $name
 * @property string   $token      SHA-256 hash of the raw token
 * @property string[] $abilities
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon      $updated_at
 */
class AppToken extends Model
{
    use HasUuids;

    protected $table = 'app_tokens';

    protected $fillable = ['name', 'token', 'abilities'];

    protected $casts = [
        'abilities'    => 'array',
        'last_used_at' => 'datetime',
    ];

    /**
     * Generate a new app token.
     *
     * @param  string   $name
     * @param  string[] $abilities
     * @return array{plaintext: string, token: AppToken}
     */
    public static function generate(string $name, array $abilities = ['*']): array
    {
        $plaintext = Str::random(60);

        $token = static::create([
            'name'      => $name,
            'token'     => hash('sha256', $plaintext),
            'abilities' => $abilities,
        ]);

        return ['plaintext' => $plaintext, 'token' => $token];
    }

    /**
     * Find a token record by its raw (unhashed) value, or null if invalid.
     */
    public static function findByRawToken(string $rawToken): ?static
    {
        return static::where('token', hash('sha256', $rawToken))->first();
    }

    /**
     * Check whether this token has a given ability.
     */
    public function can(string $ability): bool
    {
        return in_array('*', $this->abilities, true)
            || in_array($ability, $this->abilities, true);
    }

    /**
     * Update the last_used_at timestamp without touching updated_at.
     */
    public function touchLastUsed(): void
    {
        $this->timestamps = false;
        $this->last_used_at = now();
        $this->save();
        $this->timestamps = true;
    }
}

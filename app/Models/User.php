<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use InvalidArgumentException;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'jass_id',
        'rol',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => Rol::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            $esSuperadmin = $user->rol === Rol::Superadmin;

            if ($esSuperadmin && $user->jass_id !== null) {
                throw new InvalidArgumentException('Un usuario superadmin no puede pertenecer a una JASS.');
            }

            if (! $esSuperadmin && $user->jass_id === null) {
                throw new InvalidArgumentException('Un usuario con rol distinto de superadmin debe pertenecer a una JASS.');
            }
        });
    }

    public function jass(): BelongsTo
    {
        return $this->belongsTo(Jass::class);
    }

    public function esSuperadmin(): bool
    {
        return $this->rol === Rol::Superadmin;
    }

    public function esPresidente(): bool
    {
        return $this->rol === Rol::Presidente;
    }

    public function esTesorero(): bool
    {
        return $this->rol === Rol::Tesorero;
    }

    public function esVocal(): bool
    {
        return $this->rol === Rol::Vocal;
    }
}

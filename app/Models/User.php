<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $avatar
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function activities(): HasMany
    {
        // Hubungkan riwayat aktivitas agar mudah difilter berdasarkan pemilik akun.
        return $this->hasMany(AccountActivity::class);
    }

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'warehouse_manager';
    public const ROLE_STAFF = 'warehouse_staff';

    // Pasangkan kode role yang dipakai logika aplikasi dengan label untuk antarmuka.
    public const ROLE_LABELS = [
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_MANAGER => 'Manajer Gudang',
        self::ROLE_STAFF => 'Staff Gudang',
    ];

    // Periksa role dengan pencocokan tepat agar akses tidak keliru diberikan.
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    // Admin dan Manajer Gudang sama-sama boleh mengelola data produk.
    public function canManageProducts(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN, self::ROLE_MANAGER);
    }
}

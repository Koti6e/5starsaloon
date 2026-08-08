<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'mobile',
        'role',
        'status',
        'must_change_password',
        'employee_code',
        'profile_photo',
        'specialization',
        'joining_date',
        'employment_type',
        'address',
        'emergency_contact',
        'is_home_service_eligible',
        'shift_start',
        'shift_end',
        'weekly_off',
        'created_by',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function profilePhotoUrl(): ?string
    {
        if (blank($this->profile_photo)) {
            return null;
        }

        if (Str::startsWith($this->profile_photo, ['http://', 'https://', '/'])) {
            return $this->profile_photo;
        }

        return asset('storage/'.$this->profile_photo);
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->join('') ?: 'U';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'joining_date' => 'date',
            'must_change_password' => 'boolean',
            'is_home_service_eligible' => 'boolean',
            'password' => 'hashed',
        ];
    }
}

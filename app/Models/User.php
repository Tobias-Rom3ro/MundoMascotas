<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'position',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Relaciones
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class, 'veterinarian_id');
    }

    public function assignedPqrs()
    {
        return $this->hasMany(Pqr::class, 'assigned_to');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->role($role);
    }

    // Métodos de ayuda para roles
    public function isGeneralManager()
    {
        return $this->hasRole('gerente_general');
    }

    public function isHotelEmployee()
    {
        return $this->hasRole('empleado_hotel');
    }

    public function isClinicAdmin()
    {
        return $this->hasRole('admin_clinica');
    }

    public function isSpaAssistant()
    {
        return $this->hasRole('auxiliar_spa');
    }

    public function canAccessReports()
    {
        return $this->hasAnyRole(['gerente_general', 'empleado_hotel', 'admin_clinica', 'auxiliar_spa']);
    }

    public function canManageUsers()
    {
        return $this->hasRole('gerente_general');
    }

    public function canManagePrices()
    {
        return $this->hasAnyRole(['gerente_general', 'auxiliar_spa']);
    }
}

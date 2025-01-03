<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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
        'username',
        'is_admin',        
        'role',
        'ldap_groups'
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
        'password' => 'hashed',
        'ldap_groups' => 'array'
    ];


    public function isStaff()
    {
        return $this->role === 'staff' || in_array(config('ldap.groups.staff'), $this->ldap_groups ?? []);
    }

    public function isAdmin()
    {
        return $this->is_admin || in_array(config('ldap.groups.admin'), $this->ldap_groups ?? []);
    }

    public function isNormalUser()
    {
        return in_array(config('ldap.groups.user'), $this->ldap_groups ?? []);
    }
}

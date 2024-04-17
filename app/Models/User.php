<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\HasMany;



class User extends Authenticatable implements JWTSubject
{
    use  Notifiable, HasFactory;

    protected $table = 'users';


    protected $fillable=[
        'kode', 'nm_petugas', 'password', 'cabang','role'
    ];
    protected $hidden =[
        'password', 'remember_token', 'created_at', 'updated_at',
    ];


    public function getKeyName()
{
    return 'kode'; // Mengembalikan nama kolom kunci utama
}

    public function getJWTIdentifier()
    {
        return $this->getKey();

    }
    public function getJWTCustomClaims()
    {
        return[];
    }   
}

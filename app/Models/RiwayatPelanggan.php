<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatPelanggan extends Model
{
    protected $table='tr_baca_dt';
    protected $primaryKey='nolangg';

    protected $casts = [
        'nolangg' => 'string',
    ];
    protected $fillable = [
        'nolangg',
        'petugas',
        'periode',
        'beforeUpdate',
        'afterUpdate',
        'update_at',

    ];

    public function statusBaca()
    {
        return $this->belongsTo(StatusBaca::class, 'dt', 'kode'); // model relasi, parent id relasi, id model relasi
    }
   public function rl_petugas()
    {
        return $this->belongsTo(User::class, 'petugas', 'kode'); // model relasi, parent id relasi, id model relasi
    }
   public function statusMeter()
    {
        return $this->belongsTo(StatusMeter::class, 'st', 'kode'); // model relasi, parent id relasi, id model relasi
    }
    public function allStatusMeter()
    {
        return $this->hasMany(StatusMeter::class, 'st', 'st');
    }
    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang', 'kode');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
   protected $table='tr_baca_dt';

//    protected $primary_key = null;
//    protected $primaryKey='nolangg';
   protected $casts = [
    'nolangg' => 'string',
    'ip_entry' => 'string',
];
   protected $fillable =[
    'nolangg',
    'alamat',
    'dism',
    'lalu',
    'periode',
    'st',
    'kini',
    'kt',
    'dt',
    'file',
    'm3',
    'tgl_data',
    'tgl_baca',
    'petugas',
    'cabang',
    'ke',
    'user_entry',
    'stver',
   ];
   public $timestamps=false;

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

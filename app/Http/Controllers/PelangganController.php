<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; // Fix typo in the model namespace

use App\Models\Pelanggan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

use Carbon\Carbon;
use App\Models\RiwayatPelanggan;
use App\Models\StatusMeter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class PelangganController extends Controller
{
    public function index()
    {
        $periode = '202404';
        $data = Pelanggan::with('statusBaca', 
        'rl_petugas', 'statusMeter', 'allStatusMeter', 'Cabang')->where('periode', $periode)->paginate(10);
        return response()->json($data);
    }
    public function cari_data_nolangg($nolangg)
{
    $data = Pelanggan::where('nolangg','like','%'.$nolangg.'%')->get();
    return response()->json($data);
}
public function cariDataNolangg(Request $request)
{
    // Validasi request
    $request->validate([
        'nolangg' => 'required|string', // Pastikan nolangg yang dicari diisi dan berupa string
    ]);

    // Lakukan pencarian data berdasarkan nolangg
    $nolangg = $request->input('nolangg');
    $periodeNow = Carbon::now()->setTimezone('Asia/Jakarta')->format('Ym'); 
    $data = Pelanggan::with('statusBaca', 
    'rl_petugas', 'statusMeter', 'allStatusMeter', 'Cabang')->where('nolangg', 'like', '%' . $nolangg . '%')->where('periode','=',$periodeNow)->where('dt', '=', '0')->get();

    // Kirim respons dengan data yang ditemukan
    return response()->json([
        'data' => $data,
    ]);
}
public function cariDataRiwayat(Request $request)
{
    // Validasi request
    $request->validate([
        'nolangg' => 'required|string', // Pastikan nolangg yang dicari diisi dan berupa string
    ]);

    // Lakukan pencarian data berdasarkan nolangg
    $nolangg = $request->input('nolangg');
    $periodeNow = Carbon::now()->setTimezone('Asia/Jakarta')->format('Ym'); 
    $data = Pelanggan::with('statusBaca', 
    'rl_petugas', 'statusMeter', 'allStatusMeter', 'Cabang')->where('nolangg', 'like', '%' . $nolangg . '%')->where('periode','=',$periodeNow)->get();

    // Kirim respons dengan data yang ditemukan
    return response()->json([
        'data' => $data,
    ]);
}

public function cari_data_dism(Request $request)
{
    $user = User::where('kode',$request->kode)->first();
    $periodeNow = Carbon::now()->setTimezone('Asia/Jakarta')->format('Ym'); 
    $data = Pelanggan::with('statusBaca', 
    'rl_petugas', 'statusMeter', 'allStatusMeter', 'Cabang')->where('dism', 'LIKE', $request->bendel.'%')->where('cabang', '=', $user->cabang,)->where('dt','=', '0')->where('periode','=', $periodeNow)->get();
    return response()->json($data);
}

public function getCheckPelanggan(Request $request)
{
    $periodeNow = Carbon::now()->setTimezone('Asia/Jakarta')->format('Ym');
    $user = User::where('kode', $request->kode)->first();

    if ($user) { 
        $data = Pelanggan::where('nolangg', $request->nolangg)->where('periode','=', $periodeNow)->where('dt', '=', '0')->first();

        if ($data == null) {
            return response()->json([
                'result' => 'Data Tidak Ditemukan',
                'kode' => '0'
            ]);
        } else {
            // Jika cabang pada data yang ditemukan sesuai dengan cabang pengguna
            if ($data->cabang == $user->cabang) {
                return response()->json([
                    'result' => 'Data ditemukan',
                    'kode' => '1'
                ]);
            } else {
                return response()->json([
                    'result' => 'Data tidak ditemukan',
                    'kode' => '0'
                ]);
            }
        }
    }
}
public function getCheckBendel(Request $request)
{
    $bendel = substr($request->bendel, 0, 4);
    $user = User::where('kode', $request->kode)->first();

    if ($user) { 
        $data = Pelanggan::where('dism', 'LIKE', $bendel . '%')->first();

        if ($data == null) {
            return response()->json([
                'result' => 'Data Tidak Ditemukan',
                'kode' => '0'
            ]);
        } else {
            // Jika cabang pada data yang ditemukan sesuai dengan cabang pengguna
            if ($data->cabang == $user->cabang) {
                return response()->json([
                    'result' => 'Data ditemukan dan sesuai dengan cabang user',
                    'kode' => '1'
                ]);
            } else {
                return response()->json([
                    'result' => 'Data ditemukan tetapi tidak sesuai dengan cabang user',
                    'kode' => '0'
                ]);
            }
        }
    } else {
        return response()->json([
            'result' => 'Pengguna tidak ditemukan',
            'kode' => '0'
        ]);
    }
}

public function getCheckCabang(Request $request)
{
    $cabang = $request->cabang;
    $data = Pelanggan::where('cabang', 'LIKE', $cabang. '%')->first();

    if  ($data == null){
            return response()->json(
                [
                    'result' => 'Data Tidak Ditemukan',
                    'kode' => '0'
                ]);
        }else{
            if ($data->cabang == 'cabang') {
                return response()->json(
                    [
                        'result' => 'Data ditemukan dan sesuai dengan cabang user',
                        'kode' => '1'
                    ]
                );
            } 
        }

}
public function getdetailpelanggan(Request $request)
{
    $validator = Validator::make($request->all(), [
        'nolangg' => 'required',
        'periode' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 422);
    }

    // $now = Carbon::now()->setTimezone('Asia/Jakarta');
    // $periodeNow = Carbon::now()->setTimezone('Asia/Jakarta')->format('Ym'); 
    $detail = Pelanggan::with('statusBaca', 
    'rl_petugas', 'statusMeter', 'allStatusMeter', 'Cabang')->where('nolangg','=', $request->nolangg)->where('periode', '=', $request->periode)
    ->first();

    // ->where('periode', '=', $request->periode)
    if ($detail && $detail->image_path) {
        // Ambil URL gambar dari kolom image_path atau file_path
        $imageUrl = $detail->image_path;
        // Tambahkan URL gambar ke respons JSON
        $detail->image_url = $imageUrl;
    }

    return response()->json($detail);
}

public function edit(Request $request, $nolangg)
{ 
    $data = Pelanggan::where('nolangg', $nolangg)->first();
    if(!$data){
        return response()->json(['message' => 'Data Tidak Ditemukan'],404);
    }
    // return response()->json(['message' => $request->all()],422);
    
    $beforeUpdate = $data->toArray(); 
    $petugas = $request->user()->kode; 
     
    $now = Carbon::now()->setTimezone('Asia/Jakarta');
    $data->tgl_baca = $now->toDateString();
    $data->jam_baca = $now->toTimeString();
    $data->periode = Carbon::now()->format('Ym'); 
    $statusBefore = $data->dt; 
    
    // if($data->periode === Carbon::now()->format('Ym')){
    //     return response()->json(['message', 'Anda sudah memiliki data di bulan ini'],422);
    // }
    // return response()->json($request->all());
    $validator= Validator::make($request->all(),[
        'nolangg' => 'sometimes',
        'dism' => 'sometimes',  
        'alamat' => 'sometimes',
        'st' => 'sometimes',
        'kini' => 'sometimes',
        'kt' => 'sometimes',
        // 'file' => 'nullable|image',
    ]);
    if($validator->fails()) {
        return response()->json(['error', $validator->errors()],422);
    } 
    // function get_client_ip() {
    //     $ipaddress = '';
    //     if (isset($_SERVER['HTTP_CLIENT_IP']))
    //         $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    //     else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
    //         $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    //     else if(isset($_SERVER['HTTP_X_FORWARDED']))
    //         $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    //     else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
    //         $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    //     else if(isset($_SERVER['HTTP_FORWARDED']))
    //         $ipaddress = $_SERVER['HTTP_FORWARDED'];
    //     else if(isset($_SERVER['REMOTE_ADDR']))
    //         $ipaddress = $_SERVER['REMOTE_ADDR'];
    //     else
    //         $ipaddress = 'UNKNOWN';
    //     return $ipaddress;
    // }
    // $clientIP = 'https://api.ip2location.io/?key=E8BC4C168D4FC2F961B305F8A2B0B710&ip=140.213.190.5';

        // Mengambil data IP kedalam field Ip_entry
    $publicIp = Http::get('https://api.ipify.org')->body();

    // $getIp = strval($publicIp);
    // $getIp = Str::mask( $publicIp , '*', 4);
   
    // Periksa apakah data sudah ada di periode saat ini
    $existingData = Pelanggan::where('nolangg', $request->nolangg)
    ->where('periode', $data->periode )
    ->first();
    // $existingData->ip_entry = (string) $existingData->ip_entry;
    // if(!$existingData){
    //     return response()->json(['message' => 'Data Tidak Ditemukan'],404);
    // }
    // Jika data sudah ada, lakukan pembaruan
    if ($existingData){
        if ($request->all()) {
            // $file = $request->file('file');
            // $storagePath = 'public/uploads';
            // $filePath = $file->store($storagePath);
            // $fileUrl = url(Storage::url($filePath));
            // $dataFile = $fileUrl;

            $petugas = $request->user()->kode; 
            $user_entry = $request->user()->kode; 
            $pc_entry = $request->user()->nm_petugas; 
            $now = Carbon::now()->setTimezone('Asia/Jakarta');
            $data->tgl_baca = $now->toDateString();
            $m3 = $request->kini - $data->lalu;

            $ke =  Pelanggan::where('nolangg', $request->nolangg)
            ->where('periode', $data->periode )
            ->first();
            $previousKe = $ke->ke;
            $newKe = $previousKe + 1;


            Pelanggan::where('nolangg', $request->nolangg)
            ->where('periode', $data->periode )
            ->update([
                'nolangg' => $request->nolangg,
                'dism' => $request->dism,
                'petugas' => $petugas,
                'tgl_baca' => $data->tgl_baca,
                'jam_baca' => $data->jam_baca,
                'alamat' => $request->alamat,
                'st' => $request->st,
                'dt' => '1' ,
                'kini' => $request->kini, 
                'kt' => $request->kt,
                'm3' => $m3,
                'user_entry' => $user_entry,
                'pc_entry' => $pc_entry,
                'ip_entry' => $publicIp,
                // 'ip_entry' => Crypt::encryptString($publicIp),
                'ke' => $newKe,
                // 'file' =>  $dataFile,

            ]);
            return response()->json([
                'message'=>'Data berhasil disimpan',
                'beforeUpdate' => $beforeUpdate,
                'afterUpdate' => $existingData,
            ],200);
        }
       
    } else {
        // Jika tidak ada, tambahkan data baru
        $dataAfterUpdate = new Pelanggan();
        $dataAfterUpdate->nolangg = trim($request->nolangg);
        $dataAfterUpdate->periode = $data->periode;
        $dataAfterUpdate->dism =  trim($request->dism);
        $dataAfterUpdate->petugas = $petugas;  
        $dataAfterUpdate->tgl_baca = $data->tgl_baca;  
        $dataAfterUpdate->jam_baca = $beforeUpdate['jam_baca']; 
        $dataAfterUpdate->urut = $beforeUpdate['urut'];
        $dataAfterUpdate->lalu = trim($request->lalu);
        $dataAfterUpdate->kini = trim($request->kini);
        $dataAfterUpdate->m3 = $beforeUpdate['m3'];
        $dataAfterUpdate->kt = trim($request->kt);
        $dataAfterUpdate->st = trim($request->st);
        if ($statusBefore != '1') {
            $dataAfterUpdate->dt = '1';
        }
        $dataAfterUpdate->tgl_data = $beforeUpdate['tgl_data']; 
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $storagePath = 'public/uploads';
            $filePath = $file->store($storagePath);
            $fileUrl = url(Storage::url($filePath));
            $dataAfterUpdate->file = $fileUrl;
        }
        $dataAfterUpdate->cabang = $beforeUpdate['cabang'];
        $dataAfterUpdate->user_entry = $beforeUpdate['user_entry'];
        $dataAfterUpdate->pc_entry = $beforeUpdate['pc_entry'];
        $dataAfterUpdate->ip_entry = $beforeUpdate['ip_entry'];
        $dataAfterUpdate->ke = $beforeUpdate['ke'];
        $dataAfterUpdate->tgl_ver = $beforeUpdate['tgl_ver'];
        $dataAfterUpdate->user_ver = $beforeUpdate['user_ver'];
        $dataAfterUpdate->stver = $beforeUpdate['stver'];
        $dataAfterUpdate->catatanver = $beforeUpdate['catatanver'];
        $dataAfterUpdate->kever = $beforeUpdate['kever'];
        $dataAfterUpdate->tgl_transfer = $beforeUpdate['tgl_transfer'];
        $dataAfterUpdate->user_transfer = $beforeUpdate['user_transfer'];
        $dataAfterUpdate->tanggal = $beforeUpdate['tanggal'];
        $dataAfterUpdate->jam_ver = $beforeUpdate['jam_ver'];
        $dataAfterUpdate->longitude = $beforeUpdate['longitude'];
        $dataAfterUpdate->latitude = $beforeUpdate['latitude'];
        $dataAfterUpdate->alamat = trim($request->alamat);
        $dataAfterUpdate->save();
        return response()->json([
            'message'=>'Data berhasil disimpan',
            'beforeUpdate' => $beforeUpdate,
            'afterUpdate' => $dataAfterUpdate,
        ],200);
    }
 
}

public function uploadImage(Request $request, $nolangg)
{
    $validator = Validator::make($request->all(), [
        'file' => 'required|image', // Validasi untuk gambar
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 422);
    }

    $periode = Carbon::now()->format('Ym'); 
    // Cari data pelanggan berdasarkan nomor langganan
    $pelanggan = Pelanggan::where('nolangg', $nolangg)->where('periode', $periode)->first();

    if (!$pelanggan) {
        $pelanggann = Pelanggan::where('nolangg', $nolangg)->first();
        if ($pelanggann) {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $storagePath = 'public/uploads';
                $filePath = $file->store($storagePath);
                $fileUrl = url(Storage::url($filePath));
                $dataFile = $fileUrl;
    
                Pelanggan::where('nolangg', $request->nolangg)
                ->update([
                    'file' =>  $dataFile,
    
                ]);
                return response()->json([
                    'message'=>'Data berhasil disimpan',
                ],200);
            }
        }
        return response()->json(['error' => 'Data pelanggan tidak ditemukan'], 404);
    }else{
        $file = $request->file('file');
        $storagePath = 'public/uploads';
        $filePath = $file->store($storagePath);
        $fileUrl = url(Storage::url($filePath));
          // Perbarui URL gambar pada data pelanggan
        //   $pelanggan->file = $fileUrl;
          $pelanggan = Pelanggan::where('nolangg', $nolangg)->where('periode', $periode)->update([
            'file' =>  $fileUrl,
          ]);
        return response()->json(['message' => 'Gambar berhasil diunggah'], 200);
    }

    
}
public function uploadImageRiwayat(Request $request, $nolangg)
{
    $validator = Validator::make($request->all(), [
        'file' => 'nullable|image', // Validasi untuk gambar
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 422);
    }

    $periode = Carbon::now()->format('Ym'); 
    // Cari data pelanggan berdasarkan nomor langganan
    $pelanggan = Pelanggan::where('nolangg', $nolangg)->where('periode', $periode)->first();

    if (!$pelanggan) {
        $pelanggann = Pelanggan::where('nolangg', $nolangg)->first();
        if ($pelanggann) {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $storagePath = 'public/uploads';
                $filePath = $file->store($storagePath);
                $fileUrl = url(Storage::url($filePath));
                $dataFile = $fileUrl;
    
                Pelanggan::where('nolangg', $request->nolangg)
                ->update([
                    'file' =>  $dataFile,
    
                ]);
                return response()->json([
                    'message'=>'Data berhasil disimpan',
                ],200);
            }
        }
        return response()->json(['error' => 'Data pelanggan tidak ditemukan'], 404);
    }else{
        $file = $request->file('file');
        $storagePath = 'public/uploads';
        $filePath = $file->store($storagePath);
        $fileUrl = url(Storage::url($filePath));
          // Perbarui URL gambar pada data pelanggan
        //   $pelanggan->file = $fileUrl;
          $pelanggan = Pelanggan::where('nolangg', $nolangg)->where('periode', $periode)->update([
            'file' =>  $fileUrl,
          ]);
        return response()->json(['message' => 'Gambar berhasil diunggah'], 200);
    }

    
}
public function riwayat(Request $request)
{
    
    $periodeNow = Carbon::now()->setTimezone('Asia/Jakarta')->format('Ym'); 
    $riwayat = RiwayatPelanggan::with('statusBaca', 
    'rl_petugas', 'statusMeter', 'allStatusMeter', 'Cabang')
    ->where('petugas','=', $request->petugas)
    ->where('periode', '=', $periodeNow)
    ->orderByDesc('tgl_baca')
    ->orderByDesc('jam_baca')
    ->get();

    if (!$riwayat) {
        return response()->json(['message' => 'Riwayat Tidak Ditemukan'], 400);
    }
    $riwayat->transform(function ($item) {
        $item->nolangg = (string) $item->nolangg;
        $item->canEdit = $item->dt === '0' || $item->dt === '1';
        return $item;
    });
    return response()->json($riwayat);
}
public function delete($nolangg)
{
    // Cari data pelanggan berdasarkan id
    $pelanggan = Pelanggan::where('nolangg', $nolangg)->first();
    if ($pelanggan) {
        $pelanggan->delete();

        // Hapus data riwayat pelanggan berdasarkan nolangg
        RiwayatPelanggan::where('nolangg', $nolangg)->delete();

        Pelanggan::where('nolangg', $nolangg)->delete();

        // Kirim respons berhasil
        return response()->json([
            'message' => 'Data berhasil dihapus',
        ]);
    } else {
        // Jika data tidak ditemukan, kirim respons data tidak ditemukan
        return response()->json([
            'message' => 'Data dengan nolangg tersebut tidak ditemukan',
        ], 404);
    }
}
public function getJamBacaAwal(Request $request)
{

    $userCode = auth()->user()->kode;
    // Validasi request
    $request->validate([
        'periode' => 'required|string', 
    ]);

    // Ambil periode dari request
    $periode = $request->input('periode');

    $now = Carbon::now()->setTimezone('Asia/Jakarta');
    $currentDate = $now->toDateString();
    $currentTime = $now->toTimeString();

    // Subquery untuk mendapatkan jam baca paling awal untuk setiap nolangg
    $data = RiwayatPelanggan::select('nolangg', 'tgl_baca', 'jam_baca')
                ->where('periode', $periode)
                ->where('petugas', $userCode) 
                ->whereDate('tgl_baca', $currentDate)
                ->whereTime('jam_baca', '<=', $currentTime)
                ->orderBy('tgl_baca')
                ->orderBy('jam_baca')
                ->get();

    // Kirim respons dengan data jam baca paling awal
    return response()->json([
        'data' => $data,
    ]);
}


}

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

public function cari_data_dism(Request $request)
{
    $user = User::where('kode',$request->kode)->first();

    $data = Pelanggan::with('statusBaca', 
    'rl_petugas', 'statusMeter', 'allStatusMeter', 'Cabang')->where('dism', 'LIKE', $request->bendel.'%')->where('cabang', '=', $user->cabang,)->where('dt','=', '0')->get();
    return response()->json($data);
}

public function getCheckPelanggan(Request $request)
{
    $data = Pelanggan::where('nolangg', '=', $request->nolangg)->first();

    if  ($data == null){
            return response()->json(
                [
                    'result' => 'Data Tidak Ditemukan',
                    'kode' => '0'
                ]);
        }else{
            return response()->json(
                [
                    'result' => 'Data ditemukan',
                    'kode' => '1'
                ]);
        }

    // return response()->json($data);
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

public function simpan_data(Request $request){
    $validator = Validator::make ($request->all(), [
        'nolangg' => 'required',
        'alamat' => 'required',
        'dism' => 'required',
        'lalu' => 'required',
        'dt' => 'required',
        'periode' => 'required',
        'st' => 'required',
        'kini' => 'required',
        'kt' => 'required', 
        'file' => 'required|image',
    ]);
    if($validator->fails()) {
        return response()->json(['error', $validator->errors()],422);
    }
    $file = $request->file('file');

    $storagePath = 'storage/public/uploads';

    $filePath = $file->store($storagePath);

    $fileUrl = url(Storage::url($filePath));
    Pelanggan::create([
        'nolangg' => $request->nolangg,
        'alamat' => $request->alamat,
        'dism' =>  $request->dism,
        'lalu' => $request->lalu,
        'dt' => $request->dt,
        'periode' => $request->periode,
        'st'  => $request->st,
        'kini' => $request->kini,
        'kt' => $request->kt,
        'file' => $fileUrl,
    ]);
    return response()->json(['message'=>'Data berhasil disimpan'],200);



}
public function edit(Request $request, $nolangg)
{ 
    $data = Pelanggan::where('nolangg', $nolangg)->first();
    if(!$data){
        return response()->json(['message' => 'Data Tidak Ditemukan'],404);
    }
   
    $beforeUpdate = $data->toArray(); 
    $data->save();
    $petugas = $request->user()->kode; 
     
    $now = Carbon::now()->setTimezone('Asia/Jakarta');
    $data->tgl_baca = $now->toDateString();
    $data->periode = Carbon::now()->format('Ym'); 
    $statusBefore = $data->dt; 
    
    
    // return response()->json($request->all());
    $validator= Validator::make($request->all(),[
        'nolangg' => 'sometimes',
        'dism' => 'sometimes',  
        'alamat' => 'sometimes',
        'lalu' => 'sometimes',   
        'st' => 'sometimes',
        'kini' => 'sometimes',
        'kt' => 'sometimes',
        'file' => 'nullable|image',
    ]);
    if($validator->fails()) {
        return response()->json(['error', $validator->errors()],422);
    } 

    $dataAfterUpdate = new Pelanggan();
    $dataAfterUpdate->nolangg = trim($request->nolangg);
    $dataAfterUpdate->periode = $data->periode;
    $dataAfterUpdate->dism =  trim($request->dism);
    $dataAfterUpdate->petugas = $petugas;  
    $dataAfterUpdate->tgl_baca = $data->tgl_baca;  
    $dataAfterUpdate->jam_baca = $beforeUpdate['jam_baca']; 
    $dataAfterUpdate->urut = $beforeUpdate['urut'];
    $dataAfterUpdate->lalu = trim($request['lalu']);
    $dataAfterUpdate->kini = trim($request->kini);
    $dataAfterUpdate->m3 = $beforeUpdate['m3'];
    $dataAfterUpdate->kt = trim($request->kt);
    $dataAfterUpdate->st = trim($request->st);
    if ($statusBefore === '0') {
        $dataAfterUpdate->dt = '1';
    }
    $dataAfterUpdate->tgl_data = $beforeUpdate['tgl_data']; 
    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $storagePath = 'storage/public/uploads';
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
// public function edit(Request $request, $nolangg)
// {
//     // return response()->json($request->hasFile('files'));
//     $data = Pelanggan::find($nolangg);
//     if(!$data){
//         return response()->json(['message' => 'Data Tidak Ditemukan'],404);
//     }
//     // $beforeUpdate = $data->toArray();
//     // $dataSebelumUpdate = new Pelanggan();
//     // $dataSebelumUpdate->fill($beforeUpdate);
//     // $dataSebelumUpdate->save();
//     $validator= Validator::make($request->all(),[
//         'nolangg' => 'sometimes',
//         'dism' => 'sometimes',  
//         'periode' => 'sometimes',
//         'alamat' => 'sometimes',
//         'lalu' => 'sometimes',
//         'dt' => 'sometimes',    
//         'st' => 'sometimes',
//         'kini' => 'sometimes',
//         'kt' => 'sometimes',
//         'file' => 'nullable|image',
//     ]);
//     if($validator->fails()) {
//         return response()->json(['error', $validator->errors()],422);
//     }
 
//     $data->update($request->all());
//     $statusBefore = $data->dt; 

//     if ($statusBefore === '0') {
//         $data->dt = '1';
//     }
//     if ($request->hasFile('file')) {
//         $file = $request->file('file');
//         $storagePath = 'storage/public/uploads';
//         $filePath = $file->store($storagePath);
//         $fileUrl = url(Storage::url($filePath));
//         $data->file = $fileUrl;
//     }
//     $afterUpdate = $data->toArray();
//     $petugas = $request->user()->kode;
//     $data->petugas = $petugas;
//     $now = Carbon::now()->setTimezone('Asia/Jakarta');
//     $data->tgl_baca = $now->toDateString();
//     $data->periode = Carbon::now()->format('Ym');
//     $data->save();
    
//     $riwayat = new RiwayatPelanggan();


//     $data->update([
//         'periode' => $afterUpdate['periode'],
//         'dism' => $afterUpdate['dism'],
//         'petugas' => $afterUpdate['petugas'],
//         'tgl_baca' => $afterUpdate['tgl_baca'],
//         'jam_baca' => $afterUpdate['jam_baca'],
//         'urut' => $afterUpdate['urut'],
//         'lalu' => $afterUpdate['lalu'],
//         'kini' => $afterUpdate['kini'],
//         'm3' => $afterUpdate['m3'],
//         'kt' => $afterUpdate['kt'],
//         'st' => $afterUpdate['st'],
//         'dt' => $afterUpdate['dt'],
//         'tgl_data' => $afterUpdate['tgl_data'],
//         'file' => $afterUpdate['file'],
//         'cabang' => $afterUpdate['cabang'],
//         'user_entry' => $afterUpdate['user_entry'],
//         'pc_entry' => $afterUpdate['pc_entry'],
//         'ip_entry' => $afterUpdate['ip_entry'],
//         'ke' => $afterUpdate['ke'],
//         'tgl_ver' => $afterUpdate['tgl_ver'],
//         'user_ver' => $afterUpdate['user_ver'],
//         'stver' => $afterUpdate['stver'],
//         'catatanver' => $afterUpdate['catatanver'],
//         'kever' => $afterUpdate['kever'],
//         'tgl_transfer' => $afterUpdate['tgl_transfer'],
//         'user_transfer' => $afterUpdate['user_transfer'],
//         'tanggal' => $afterUpdate['tanggal'],
//         'jam_ver' => $afterUpdate['jam_ver'],
//         'longitude' => $afterUpdate['longitude'],
//         'latitude' => $afterUpdate['latitude'],
//         'alamat' => $afterUpdate['alamat'],
//     ]);
    

//     return response()->json([
//         'message'=>'Data berhasil disimpan',
//         // 'beforeUpdate' => $beforeUpdate,
//         'fileUrl' => $data->file ?? null,
//         'afterUpdate' => $afterUpdate,
//     ],200);
// }
public function riwayat(Request $request)
{
    // cari tahun dan bulang secara otomatis untuk mengisi periode secara 202403
    $riwayat = RiwayatPelanggan::with('statusBaca', 
    'rl_petugas', 'statusMeter', 'allStatusMeter', 'Cabang')->where('petugas','=', $request->petugas)->where('periode','=', $request->periode)->get();

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
    $pelanggan = Pelanggan::findOrFail($nolangg);
    $pelanggan->delete();

    // Hapus data riwayat pelanggan berdasarkan id pelanggan
    RiwayatPelanggan::where('nolangg','=', $nolangg)->delete();

    // Kirim respons
    return response()->json([
        'message' => 'Data berhasil dihapus',
    ]);
}

}

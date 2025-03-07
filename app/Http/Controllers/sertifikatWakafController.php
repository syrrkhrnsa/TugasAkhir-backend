<?php
namespace App\Http\Controllers;

use App\Models\Sertifikat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class SertifikatWakafController extends Controller
{
    // Menampilkan semua sertifikat
    public function index()
    {
        $sertifikats = Sertifikat::all();
        return response()->json($sertifikats);
    }

    // Menyimpan sertifikat baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'noSertifikat' => 'required|unique:sertifikats',
            'namaWakif' => 'required',
            'lokasi' => 'required',
            'luasTanah' => 'required',
            'fasilitas' => 'required',
            'status' => 'required',
            'dokBastw' => 'nullable|file|mimes:pdf,jpg,png',
            'dokAiw' => 'nullable|file|mimes:pdf,jpg,png',
            'dokSw' => 'nullable|file|mimes:pdf,jpg,png',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sertifikatData = $request->all();
        
        if ($request->hasFile('dokBastw')) {
            $sertifikatData['dokBastw'] = $request->file('dokBastw')->store('documents');
        }
        if ($request->hasFile('dokAiw')) {
            $sertifikatData['dokAiw'] = $request->file('dokAiw')->store('documents');
        }
        if ($request->hasFile('dokSw')) {
            $sertifikatData['dokSw'] = $request->file('dokSw')->store('documents');
        }

        $sertifikat = Sertifikat::create($sertifikatData);

        return response()->json($sertifikat, 201);
    }

    // Menampilkan detail sertifikat
    public function show($id)
    {
        $sertifikat = Sertifikat::findOrFail($id);
        return response()->json($sertifikat);
    }

    // Mengupdate sertifikat
    public function update(Request $request, $id)
    {
        $sertifikat = Sertifikat::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'noSertifikat' => 'required|unique:sertifikats,noSertifikat,' . $id . ',id_sertifikat',
            'namaWakif' => 'required',
            'lokasi' => 'required',
            'luasTanah' => 'required',
            'fasilitas' => 'required',
            'status' => 'required',
            'dokBastw' => 'nullable|file|mimes:pdf,jpg,png',
            'dokAiw' => 'nullable|file|mimes:pdf,jpg,png',
            'dokSw' => 'nullable|file|mimes:pdf,jpg,png',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sertifikatData = $request->all();
        
        if ($request->hasFile('dokBastw')) {
            Storage::delete($sertifikat->dokBastw);
            $sertifikatData['dokBastw'] = $request->file('dokBastw')->store('documents');
        }
        if ($request->hasFile('dokAiw')) {
            Storage::delete($sertifikat->dokAiw);
            $sertifikatData['dokAiw'] = $request->file('dokAiw')->store('documents');
        }
        if ($request->hasFile('dokSw')) {
            Storage::delete($sertifikat->dokSw);
            $sertifikatData['dokSw'] = $request->file('dokSw')->store('documents');
        }

        $sertifikat->update($sertifikatData);

        return response()->json($sertifikat);
    }

    // Menghapus sertifikat
    public function destroy($id)
    {
        $sertifikat = Sertifikat::findOrFail($id);
        Storage::delete([$sertifikat->dokBastw, $sertifikat->dokAiw, $sertifikat->dokSw]);
        $sertifikat->delete();
        return response()->json(['message' => 'Sertifikat berhasil dihapus']);
    }
}

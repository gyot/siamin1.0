<?php

namespace App\Http\Controllers\Api;

use App\Models\Peserta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PesertaController extends BaseApiController
{
    protected $modelClass = Peserta::class;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Peserta::with('kegiatan')->orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_kegiatan' => 'required|exists:kegiatan,id_kegiatan',
            'nama_lengkap' => 'required|string|max:150',
            'nip' => 'sometimes|string|max:30',
            'pangkat' => 'sometimes|string|max:50',
            'gol' => 'sometimes|string|max:50',
            'jabatan' => 'sometimes|string|max:150',
            'no_hp' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:150',
            'npwp_nik' => 'sometimes|string|max:30',
            'tempat_lahir' => 'sometimes|string|max:255',
            'tanggal_lahir' => 'sometimes|date',
            'jenis_kelamin' => [
                'sometimes',
                Rule::in(['laki-laki', 'perempuan', 'Laki-laki', 'Perempuan', 'M', 'F', 'male', 'female']),
            ],
            'nama_instansi' => 'sometimes|string|max:255',
            'alamat_instansi' => 'sometimes|string',
            'kab_kota' => 'sometimes|string|max:100',
            'provinsi' => 'sometimes|string|max:100',
            'telp_instansi' => 'sometimes|string|max:20',
            'email_instansi' => 'sometimes|email|max:150',
            'provider_pulsa' => 'sometimes|string|max:50',
            'nomor_rekening' => 'sometimes|string|max:50',
            'nama_bank' => 'sometimes|string|max:100',
            'no_surat_tugas' => 'sometimes|string|max:255',
            'tanggal_surat_tugas' => 'sometimes|date',
            'peran' => 'sometimes|string|max:100',
            // tanda_tangan is an image file upload
            'tanda_tangan' => 'sometimes|file|image|max:2048',
        ]);

        // Handle signature image upload
        if ($request->hasFile('tanda_tangan')) {
            $path = $request->file('tanda_tangan')->store('signatures', 'public');
            $validated['tanda_tangan'] = $path;
        }

        // Normalize gender to lowercase
        if (isset($validated['jenis_kelamin'])) {
            $gender_map = [
                'M' => 'laki-laki',
                'F' => 'perempuan',
                'male' => 'laki-laki',
                'female' => 'perempuan',
                'laki-laki' => 'laki-laki',
                'perempuan' => 'perempuan',
            ];
            $validated['jenis_kelamin'] = $gender_map[strtolower($validated['jenis_kelamin'])] ?? strtolower($validated['jenis_kelamin']);
        }

        $peserta = Peserta::create($validated);

        return response()->json(['success' => true, 'data' => $peserta], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $peserta = Peserta::with('kegiatan')->findOrFail($id);
            return response()->json(['success' => true, 'data' => $peserta]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Peserta not found'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $peserta = Peserta::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Peserta not found'], 404);
        }

        $validated = $request->validate([
            'id_kegiatan' => 'sometimes|exists:kegiatan,id_kegiatan',
            'nama_lengkap' => 'sometimes|string|max:150',
            'nip' => 'sometimes|string|max:30',
            'pangkat' => 'sometimes|string|max:50',
            'gol' => 'sometimes|string|max:50',
            'jabatan' => 'sometimes|string|max:150',
            'no_hp' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:150',
            'npwp_nik' => 'sometimes|string|max:30',
            'tempat_lahir' => 'sometimes|string|max:255',
            'tanggal_lahir' => 'sometimes|date',
            'jenis_kelamin' => [
                'sometimes',
                Rule::in(['laki-laki', 'perempuan', 'Laki-laki', 'Perempuan', 'M', 'F', 'male', 'female']),
            ],
            'nama_instansi' => 'sometimes|string|max:255',
            'alamat_instansi' => 'sometimes|string',
            'kab_kota' => 'sometimes|string|max:100',
            'provinsi' => 'sometimes|string|max:100',
            'telp_instansi' => 'sometimes|string|max:20',
            'email_instansi' => 'sometimes|email|max:150',
            'provider_pulsa' => 'sometimes|string|max:50',
            'nomor_rekening' => 'sometimes|string|max:50',
            'nama_bank' => 'sometimes|string|max:100',
            'no_surat_tugas' => 'sometimes|string|max:255',
            'tanggal_surat_tugas' => 'sometimes|date',
            'peran' => 'sometimes|string|max:100',
            'tanda_tangan' => 'sometimes|file|image|max:2048',
        ]);

        // Handle signature image upload (delete old if updating)
        if ($request->hasFile('tanda_tangan')) {
            // Delete old signature if exists
            if ($peserta->tanda_tangan && Storage::disk('public')->exists($peserta->tanda_tangan)) {
                Storage::disk('public')->delete($peserta->tanda_tangan);
            }
            $path = $request->file('tanda_tangan')->store('signatures', 'public');
            $validated['tanda_tangan'] = $path;
        }

        // Normalize gender to lowercase
        if (isset($validated['jenis_kelamin'])) {
            $gender_map = [
                'M' => 'laki-laki',
                'F' => 'perempuan',
                'male' => 'laki-laki',
                'female' => 'perempuan',
                'laki-laki' => 'laki-laki',
                'perempuan' => 'perempuan',
            ];
            $validated['jenis_kelamin'] = $gender_map[strtolower($validated['jenis_kelamin'])] ?? strtolower($validated['jenis_kelamin']);
        }

        $peserta->update($validated);

        return response()->json(['success' => true, 'data' => $peserta]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $peserta = Peserta::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Peserta not found'], 404);
        }

        // Delete signature file if exists
        if ($peserta->tanda_tangan && Storage::disk('public')->exists($peserta->tanda_tangan)) {
            Storage::disk('public')->delete($peserta->tanda_tangan);
        }

        $peserta->delete();

        return response()->json(['success' => true, 'message' => 'Peserta deleted successfully']);
    }
}


<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kegiatan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class KegiatanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Kegiatan::orderBy('tanggal_mulai', 'desc')->get();
        return response()->json(["success" => true, "data" => $data]);
    }

    /** import.meta.env.VITE_API_BASE_URL
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kegiatan' => 'sometimes|string|max:255',
            'rincian_kegiatan' => 'sometimes|string',
            'dokumentasi_url' => 'sometimes|url|max:255',
            'materi_url' => 'sometimes|url|max:255',
            'panduan_url' => 'sometimes|url|max:255',
            'laporan_url' => 'sometimes|url|max:255',
            'surat_menyurat_url' => 'sometimes|url|max:255',
            'tanggal_mulai' => 'sometimes|date',
            'tanggal_selesai' => 'sometimes|date|after_or_equal:tanggal_mulai',
            'lokasi' => 'sometimes|string|max:255',
            // flyer must be an uploaded image file
            'flyer' => 'sometimes|file|image|max:2048',
            'peserta_ringkasan' => 'sometimes|string|max:255',
            'total_peserta' => 'sometimes|integer|min:0',
            'metode_pembayaran' => [
                'sometimes',
                Rule::in(['transfer','pulsa','transfer_dan_pulsa','tunai','tidak_dibayar']),
            ],
            'deskripsi' => 'sometimes|string',
            'metode_pelaksanaan' => [
                'sometimes',
                Rule::in(['daring','luring','hybrid']),
            ],
            'status' => [
                'sometimes',
                Rule::in(['draft','berjalan','selesai','dibatalkan']),
            ],
            'created_by' => 'sometimes|exists:users,id_user',
            'id_pegawai' => 'sometimes|nullable|exists:pegawai,id_pegawai',
        ]);

        if ($request->hasFile('flyer')) {
            $path = $request->file('flyer')->store('flyers', 'public');
            $validated['flyer'] = $path;
        }

        $kegiatan = Kegiatan::create($validated);

        return response()->json(["success" => true, "data" => $kegiatan], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $kegiatan = Kegiatan::find($id);
        if (!$kegiatan) {
            return response()->json(["success" => false, "message" => "Kegiatan not found"], 404);
        }
        return response()->json(["success" => true, "data" => $kegiatan]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $kegiatan = Kegiatan::find($id);
        if (!$kegiatan) {
            return response()->json(["success" => false, "message" => "Kegiatan not found"], 404);
        }

        $validated = $request->validate([
            'nama_kegiatan' => 'sometimes|string|max:255',
            'rincian_kegiatan' => 'sometimes|string',
            'dokumentasi_url' => 'sometimes|url|max:255',
            'materi_url' => 'sometimes|url|max:255',
            'panduan_url' => 'sometimes|url|max:255',
            'laporan_url' => 'sometimes|url|max:255',
            'surat_menyurat_url' => 'sometimes|url|max:255',
            'tanggal_mulai' => 'sometimes|date',
            'tanggal_selesai' => 'sometimes|date|after_or_equal:tanggal_mulai',
            'lokasi' => 'sometimes|string|max:255',
            'flyer' => 'sometimes|file|image|max:2048',
            'peserta_ringkasan' => 'sometimes|string|max:255',
            'total_peserta' => 'sometimes|integer|min:0',
            'metode_pembayaran' => [
                'sometimes',
                Rule::in(['transfer','pulsa','transfer_dan_pulsa','tunai','tidak_dibayar']),
            ],
            'deskripsi' => 'sometimes|string',
            'metode_pelaksanaan' => [
                'sometimes',
                Rule::in(['daring','luring','hybrid']),
            ],
            'status' => [
                'sometimes',
                Rule::in(['draft','berjalan','selesai','dibatalkan']),
            ],
            'created_by' => 'sometimes|exists:users,id_user',
            'id_pegawai' => 'sometimes|nullable|exists:pegawai,id_pegawai',
        ]);

        if ($request->hasFile('flyer')) {
            $path = $request->file('flyer')->store('flyers', 'public');
            $validated['flyer'] = $path;
        }

        $kegiatan->update($validated);

        return response()->json(["success" => true, "data" => $kegiatan]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $kegiatan = Kegiatan::find($id);
        if (!$kegiatan) {
            return response()->json(["success" => false, "message" => "Kegiatan not found"], 404);
        }

        $kegiatan->delete();
        return response()->json(["success" => true, "message" => "Deleted successfully"]);
    }
}

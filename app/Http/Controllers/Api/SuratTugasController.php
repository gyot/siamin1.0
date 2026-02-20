<?php

namespace App\Http\Controllers\Api;

use App\Models\SuratTugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SuratTugasController extends BaseApiController
{
    protected $modelClass = SuratTugas::class;
    protected $rules = [
        'id_kegiatan' => 'sometimes|exists:kegiatan,id_kegiatan',
        'nomor_surat' => 'sometimes|string|max:100',
    ];

    public function store(Request $request)
    {
        $validated = $request->validate(array_merge($this->rules, [
            'tanggal_surat' => 'sometimes|date',
            'id_penandatangan' => 'sometimes|exists:pegawai,id_pegawai',
            'status' => ['sometimes', Rule::in(['draft','dikirim','selesai'])],
            'file_surat' => 'sometimes|file|mimes:pdf,doc,docx|max:5120',
        ]));

        if ($request->hasFile('file_surat')) {
            $path = $request->file('file_surat')->store('surat_tugas', 'public');
            $validated['file_surat'] = $path;
        }

        $item = SuratTugas::create($validated);
        $item->load(['kegiatan', 'penandatangan', 'suratTugasPegawais']);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function update(Request $request, $id)
    {
        try {
            $item = SuratTugas::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'SuratTugas not found'], 404);
        }

        $validated = $request->validate(array_merge($this->rules, [
            'tanggal_surat' => 'sometimes|date',
            'id_penandatangan' => 'sometimes|exists:pegawai,id_pegawai',
            'status' => ['sometimes', Rule::in(['draft','dikirim','selesai'])],
            'file_surat' => 'sometimes|file|mimes:pdf,doc,docx|max:5120',
        ]));

        if ($request->hasFile('file_surat')) {
            if ($item->file_surat && Storage::disk('public')->exists($item->file_surat)) {
                Storage::disk('public')->delete($item->file_surat);
            }
            $path = $request->file('file_surat')->store('surat_tugas', 'public');
            $validated['file_surat'] = $path;
        }

        $item->update($validated);
        $item->load(['kegiatan', 'penandatangan', 'suratTugasPegawais']);
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function show($id)
    {
        try {
            $item = SuratTugas::with(['kegiatan', 'penandatangan', 'suratTugasPegawais'])->findOrFail($id);
            return response()->json(['success' => true, 'data' => $item]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'SuratTugas not found'], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $item = SuratTugas::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'SuratTugas not found'], 404);
        }

        if ($item->file_surat && Storage::disk('public')->exists($item->file_surat)) {
            Storage::disk('public')->delete($item->file_surat);
        }

        $item->delete();
        return response()->json(['success' => true, 'message' => 'Deleted successfully']);
    }
}

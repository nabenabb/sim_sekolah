<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\PartnershipDocument;
use App\Models\PklPlacement;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PrivateFileController extends Controller
{
    public function show(string $path): Response
    {
        abort_if(str_contains($path, '..') || str_starts_with($path, '/'), 404);

        $role = auth()->user()->role->name;
        $authorized = match (true) {
            str_starts_with($path, 'surat-masuk/') => $this->canReadIncoming($path, $role),
            str_starts_with($path, 'surat-keluar/') => in_array($role, ['Admin', 'Koordinator TU', 'Kepala Sekolah'], true)
                && OutgoingLetter::withTrashed()->where('file_path', $path)->exists(),
            str_starts_with($path, 'kerja-sama/') => in_array($role, ['Admin', 'Waka Humas', 'Kepala Program Keahlian', 'Kepala Sekolah'], true)
                && PartnershipDocument::withTrashed()->where('file_dokumen', $path)->exists(),
            str_starts_with($path, 'pkl/') => in_array($role, ['Admin', 'Waka Humas'], true)
                && PklPlacement::withTrashed()->where('surat_permohonan', $path)->exists(),
            str_starts_with($path, 'sertifikat/') => in_array($role, ['Admin', 'Waka Humas'], true)
                && Certificate::withTrashed()->where('file_path', $path)->exists(),
            default => false,
        };

        abort_unless($authorized, 404);

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    private function canReadIncoming(string $path, string $role): bool
    {
        $letter = IncomingLetter::withTrashed()->where('file_path', $path)->first();

        if (! $letter) {
            return false;
        }

        return in_array($role, ['Admin', 'Koordinator TU', 'Kepala Sekolah'], true)
            || Disposition::withTrashed()
                ->where('letter_id', $letter->id)
                ->where('assigned_to', auth()->id())
                ->exists();
    }
}

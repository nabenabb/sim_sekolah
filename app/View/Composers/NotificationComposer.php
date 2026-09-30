<?php

namespace App\View\Composers;

use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\PartnershipDocument;
use Illuminate\View\View;

class NotificationComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();
        $items = collect();

        if (! $user) {
            $view->with(['notifItems' => $items, 'notifCount' => 0]);
            return;
        }

        $role = $user->role->name;

        // TU / Admin: surat masuk yang belum didisposisikan
        if (in_array($role, ['Koordinator TU', 'Admin'])) {
            IncomingLetter::whereDoesntHave('dispositions')->latest('tanggal_terima')->take(5)->get()
                ->each(fn ($l) => $items->push([
                    'label' => 'Surat belum didisposisikan: ' . $l->perihal,
                    'url' => route('surat-masuk.index', ['surat' => $l->id]),
                ]));
        }

        // Kepala Sekolah: disposisi kosong, surat keluar menunggu TTD, kerja sama tahap akhir
        if (in_array($role, ['Kepala Sekolah', 'Admin'])) {
            IncomingLetter::whereDoesntHave('dispositions')->latest('tanggal_terima')->take(3)->get()
                ->each(fn ($l) => $items->push([
                    'label' => 'Perlu disposisi: ' . $l->perihal,
                    'url' => route('surat-masuk.index', ['surat' => $l->id]),
                ]));

            OutgoingLetter::where('status', 'menunggu_ttd')->latest('tanggal')->take(3)->get()
                ->each(fn ($s) => $items->push([
                    'label' => 'Menunggu tanda tangan: ' . $s->perihal,
                    'url' => route('surat-keluar.index'),
                ]));

            PartnershipDocument::with(['partner', 'approvals'])
                ->whereIn('status', ['diajukan', 'diverifikasi'])->get()
                ->filter(fn ($d) => optional($d->approvals->firstWhere('status', 'menunggu'))->tahap === 'Persetujuan Kepala Sekolah')
                ->take(3)
                ->each(fn ($d) => $items->push([
                    'label' => 'Kerja sama menunggu persetujuan: ' . $d->partner->nama_perusahaan,
                    'url' => route('mitra-industri.index'),
                ]));
        }

        // Waka Humas: MoU akan berakhir, verifikasi tahap Humas
        if (in_array($role, ['Waka Humas', 'Admin'])) {
            PartnershipDocument::with('partner')
                ->where('status', 'disetujui')
                ->whereDate('berlaku_selesai', '>=', now())
                ->whereDate('berlaku_selesai', '<=', now()->copy()->addDays(30))
                ->take(3)->get()
                ->each(fn ($d) => $items->push([
                    'label' => 'MoU akan berakhir: ' . $d->partner->nama_perusahaan,
                    'url' => route('mitra-industri.index'),
                ]));

            PartnershipDocument::with(['partner', 'approvals'])
                ->whereIn('status', ['diajukan', 'diverifikasi'])->get()
                ->filter(fn ($d) => optional($d->approvals->firstWhere('status', 'menunggu'))->tahap === 'Verifikasi Humas')
                ->take(3)
                ->each(fn ($d) => $items->push([
                    'label' => 'Menunggu verifikasi Anda: ' . $d->partner->nama_perusahaan,
                    'url' => route('mitra-industri.index'),
                ]));
        }

        // Kepala Program Keahlian: verifikasi tahap Kaprog
        if (in_array($role, ['Kepala Program Keahlian', 'Admin'])) {
            PartnershipDocument::with(['partner', 'approvals'])
                ->whereIn('status', ['diajukan', 'diverifikasi'])->get()
                ->filter(fn ($d) => optional($d->approvals->firstWhere('status', 'menunggu'))->tahap === 'Verifikasi Kaprog')
                ->take(5)
                ->each(fn ($d) => $items->push([
                    'label' => 'Menunggu verifikasi Anda: ' . $d->partner->nama_perusahaan,
                    'url' => route('mitra-industri.index'),
                ]));
        }

        // Penerima disposisi (Waka Kurikulum/Kesiswaan/Sapras/Humas): disposisi belum ditindaklanjuti
        if (in_array($role, Disposition::ROLE_PENERIMA)) {
            Disposition::with('letter')
                ->where('assigned_to', $user->id)
                ->where('status', 'belum ditindaklanjuti')
                ->latest()->take(5)->get()
                ->each(fn ($d) => $items->push([
                    'label' => 'Disposisi baru: ' . $d->letter->perihal,
                    'url' => route('disposisi-saya.index'),
                ]));
        }

        $view->with(['notifItems' => $items->take(8), 'notifCount' => $items->count()]);
    }
}

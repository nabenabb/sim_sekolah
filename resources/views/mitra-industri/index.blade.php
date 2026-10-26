@extends('layouts.app')

@section('title', 'Mitra & Kerja Sama Industri')
@section('page-title', 'Mitra & Kerja Sama Industri')
@section('breadcrumb', 'Waka Humas / Mitra Industri')

@section('content')

@php $bolehInput = in_array(auth()->user()->role->name, ['Waka Humas', 'Admin']); @endphp

<div class="card card-pad">
  <div class="card-head">
    <div><h2>Data Mitra Industri</h2><div class="sub">{{ $partners->count() }} mitra terdaftar</div></div>
    @if ($bolehInput)
    <button class="btn btn-primary btn-sm" onclick="openModal('modalMitra')">+ Tambah Mitra / Ajukan Kerja Sama</button>
    @endif
  </div>

  <div class="filter-bar">
    <a href="{{ route('mitra-industri.index', array_filter(['status' => 'semua', 'jurusan' => request('jurusan')])) }}" class="chip {{ $statusFilter === 'semua' ? 'active' : '' }}">Semua</a>
    <a href="{{ route('mitra-industri.index', array_filter(['status' => 'proses', 'jurusan' => request('jurusan')])) }}" class="chip {{ $statusFilter === 'proses' ? 'active' : '' }}">Sedang diproses</a>
    <a href="{{ route('mitra-industri.index', array_filter(['status' => 'arsip', 'jurusan' => request('jurusan')])) }}" class="chip {{ $statusFilter === 'arsip' ? 'active' : '' }}">Arsip</a>

    <form method="GET" action="{{ route('mitra-industri.index') }}" style="display:inline;">
      <input type="hidden" name="status" value="{{ $statusFilter }}">
      <select name="jurusan" class="select-mini" onchange="this.form.submit()">
        <option value="">Semua jurusan</option>
        @foreach ($programKeahlian as $pk)
          <option value="{{ $pk->id }}" {{ request('jurusan') == $pk->id ? 'selected' : '' }}>{{ $pk->nama_jurusan }}</option>
        @endforeach
      </select>
    </form>
  </div>

  <table>
    <thead><tr><th>Nama Perusahaan</th><th>Bidang Usaha</th><th>Jurusan Terkait</th><th>MoU Berakhir</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @forelse ($partners as $partner)
        @php $doc = $partner->latestActiveDocument; @endphp
        <tr>
          <td class="cell-strong">{{ $partner->nama_perusahaan }}</td>
          <td>{{ $partner->bidang_usaha }}</td>
          <td>{{ $partner->programKeahlian->nama_jurusan ?? '—' }}</td>
          <td class="mono">{{ $doc ? $doc->berlaku_selesai->translatedFormat('d M Y') : '—' }}</td>
          <td><span class="pill {{ $partner->computed_status['pill'] }}"><span class="dot"></span>{{ $partner->computed_status['label'] }}</span></td>
          <td style="white-space:nowrap;">
            <a class="link-more" href="#" onclick="openModal('modalLihat{{ $partner->id }}');return false;">Lihat</a>
            @if (in_array(auth()->user()->role->name, ['Admin', 'Waka Humas']))
              &nbsp;·&nbsp;
              <form method="POST" action="{{ route('mitra-industri.destroy', $partner) }}" style="display:inline;"
                    onsubmit="return confirm('Yakin hapus mitra {{ $partner->nama_perusahaan }} dari daftar? Dokumen terkait tetap tersimpan.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="link-more" style="background:none;border:none;padding:0;cursor:pointer;color:var(--danger);">Hapus</button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="6" style="text-align:center;color:var(--ink-faint);padding:24px;">Belum ada mitra industri.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

{{-- ================= PENGAJUAN SEDANG BERJALAN ================= --}}
<div class="card card-pad" style="margin-top:20px;">
  <div class="card-head"><div><h2>Pengajuan Kerja Sama Sedang Berjalan</h2><div class="sub">Tahap verifikasi sampai persetujuan Kepala Sekolah</div></div></div>

  <div class="stack" style="gap:14px;">
    @forelse ($pengajuanBerjalan as $doc)
      @php
        $tahapSekarang = $doc->approvals->firstWhere('status', 'menunggu');
        $roleBoleh = $tahapSekarang ? \App\Models\PartnershipApproval::TAHAP_ROLE[$tahapSekarang->tahap] : null;
        $bolehMemutuskan = $tahapSekarang && auth()->user()->role->name === $roleBoleh;
      @endphp
      <div class="approval-item" style="border:1px solid var(--border);border-radius:var(--radius-card);padding:16px 18px;">
        <div class="spread" style="margin-bottom:10px;">
          <div><b>{{ $doc->partner->nama_perusahaan }}</b> &mdash; {{ $doc->jenis_dokumen }}
            @if ($doc->nomor_dokumen)<span class="mono" style="font-size:11.5px;color:var(--ink-soft);"> · {{ $doc->nomor_dokumen }}</span>@endif
            <div class="cell-sub">Diajukan {{ $doc->created_at->translatedFormat('d M Y') }}</div>
          </div>
          <span class="pill pill-warning"><span class="dot"></span>{{ $tahapSekarang->tahap ?? 'Selesai' }}</span>
        </div>
        <div class="steps">
          @foreach ($doc->approvals as $ap)
            <div class="step {{ $ap->status === 'disetujui' ? 'done' : ($ap === $tahapSekarang ? 'current' : '') }}">
              <div class="node">{{ $ap->status === 'disetujui' ? '✓' : $loop->iteration }}</div>
              <div class="label">{{ $ap->tahap }}</div>
            </div>
          @endforeach
        </div>

        @if ($bolehMemutuskan)
          <div class="row" style="justify-content:flex-end;margin-top:12px;">
            <form method="POST" action="{{ route('mitra-industri.approval.decide', $tahapSekarang->id) }}" style="display:inline;">
              @csrf
              <input type="hidden" name="keputusan" value="ditolak">
              <button type="submit" class="btn btn-ghost btn-sm" onclick="return confirm('Tolak pengajuan ini?')">Tolak</button>
            </form>
            <form method="POST" action="{{ route('mitra-industri.approval.decide', $tahapSekarang->id) }}" style="display:inline;">
              @csrf
              <input type="hidden" name="keputusan" value="disetujui">
              <button type="submit" class="btn btn-primary btn-sm">Setujui Tahap Ini</button>
            </form>
          </div>
        @endif
      </div>
    @empty
      <div class="empty-state"><p>Tidak ada pengajuan yang sedang berjalan.</p></div>
    @endforelse
  </div>
</div>

{{-- ================= MODAL TAMBAH MITRA ================= --}}
<div class="modal-overlay" id="modalMitra">
  <div class="modal-card" style="max-width:680px;">
    <form method="POST" action="{{ route('mitra-industri.store') }}" enctype="multipart/form-data">
      @csrf
      <div class="modal-head">
        <h2>Tambah Mitra &amp; Ajukan Kerja Sama</h2>
        <button type="button" class="modal-close" onclick="closeModal('modalMitra')">&times;</button>
      </div>
      <div class="modal-body">
        <div class="field"><label>Nama Perusahaan</label><input type="text" name="nama_perusahaan" placeholder="mis. PT Nusantara Digital Karya" required></div>
        <div class="field"><label>Alamat Pusat</label><input type="text" name="alamat_pusat" placeholder="Jl. ..." required></div>
        <div class="field-row">
          <div class="field"><label>Bidang Usaha</label><input type="text" name="bidang_usaha" placeholder="mis. Pengembangan Perangkat Lunak" required></div>
          <div class="field">
            <label>Program Keahlian Terkait</label>
            <select name="program_keahlian_id" required>
              @foreach ($programKeahlian as $pk)
                <option value="{{ $pk->id }}">{{ $pk->nama_jurusan }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="field-row">
          <div class="field"><label>Kontak Person</label><input type="text" name="kontak_person" placeholder="Nama & jabatan" required></div>
          <div class="field"><label>No. Telepon</label><input type="text" name="no_telepon" placeholder="08xx-xxxx-xxxx"></div>
        </div>
        <div class="field-row">
          <div class="field">
            <label>Jenis Dokumen</label>
            <select name="jenis_dokumen">
              <option value="MoU">MoU</option>
              <option value="PKS">PKS</option>
              <option value="MoA">MoA</option>
            </select>
          </div>
          <div class="field"><label>Nomor Dokumen</label><input type="text" name="nomor_dokumen" placeholder="mis. 001/MoU/SMKN4/2026"></div>
        </div>
        <div class="field"><label>Ruang Lingkup</label><input type="text" name="ruang_lingkup" placeholder="mis. Guru tamu, PKL"></div>
        <div class="field-row">
          <div class="field"><label>Berlaku Mulai</label><input type="date" name="berlaku_mulai" required></div>
          <div class="field"><label>Berlaku Sampai</label><input type="date" name="berlaku_selesai" required></div>
        </div>
        <div class="field">
          <label>Dokumen Kerja Sama <span class="hint">(PDF, opsional)</span></label>
          <input type="file" name="dokumen" accept=".pdf">
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalMitra')">Batal</button>
        <button type="submit" class="btn btn-primary">Ajukan Verifikasi</button>
      </div>
    </form>
  </div>
</div>

{{-- ================= MODAL DETAIL PER MITRA ================= --}}
@foreach ($partners as $partner)
  <div class="modal-overlay" id="modalLihat{{ $partner->id }}">
    <div class="modal-card" style="max-width:640px;">
      <div class="modal-head">
        <h2>{{ $partner->nama_perusahaan }}</h2>
        <button type="button" class="modal-close" onclick="closeModal('modalLihat{{ $partner->id }}')">&times;</button>
      </div>
      <div class="modal-body">
        <div class="info-list">
          <div class="info-row"><span class="k">Alamat</span><span class="v">{{ $partner->alamat_pusat }}</span></div>
          <div class="info-row"><span class="k">Bidang Usaha</span><span class="v">{{ $partner->bidang_usaha }}</span></div>
          <div class="info-row"><span class="k">Program Keahlian</span><span class="v">{{ $partner->programKeahlian->nama_jurusan ?? '—' }}</span></div>
          <div class="info-row"><span class="k">Kontak</span><span class="v">{{ $partner->kontak_person }} {{ $partner->no_telepon ? '· '.$partner->no_telepon : '' }}</span></div>
        </div>

        <h3 style="font-size:14px;margin:20px 0 10px;">Riwayat Dokumen Kerja Sama</h3>
        @forelse ($partner->documents as $doc)
          <div class="info-row">
            <span class="k">{{ $doc->jenis_dokumen }}@if($doc->nomor_dokumen) <span class="mono" style="font-size:11px;">({{ $doc->nomor_dokumen }})</span>@endif <span class="mono" style="font-size:11px;">{{ $doc->berlaku_mulai->format('d/m/y') }}&ndash;{{ $doc->berlaku_selesai->format('d/m/y') }}</span></span>
            <span class="v">
              @if ($doc->file_dokumen)<a class="link-more" href="{{ route('private-file.show', ['path' => $doc->file_dokumen]) }}" target="_blank">Lihat file</a> &middot; @endif
              {{ ucfirst($doc->status) }}
              @if (in_array(auth()->user()->role->name, ['Admin', 'Waka Humas']))
                &middot;
                <form method="POST" action="{{ route('mitra-industri.dokumen.destroy', $doc) }}" style="display:inline;"
                      onsubmit="return confirm('Yakin hapus dokumen ini dari daftar?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="link-more" style="background:none;border:none;padding:0;cursor:pointer;color:var(--danger);font-size:12px;">Hapus</button>
                </form>
              @endif
            </span>
          </div>
        @empty
          <p style="color:var(--ink-faint);font-size:13px;">Belum ada dokumen.</p>
        @endforelse
      </div>
      <div class="modal-foot">
        @if ($bolehInput)
          <button type="button" class="btn btn-primary" onclick="closeModal('modalLihat{{ $partner->id }}'); openModal('modalPerpanjang{{ $partner->id }}')">+ Ajukan Dokumen Baru / Perpanjang</button>
        @endif
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalLihat{{ $partner->id }}')">Tutup</button>
      </div>
    </div>
  </div>
@endforeach

{{-- ================= MODAL AJUKAN DOKUMEN BARU / PERPANJANG (per mitra) ================= --}}
@if ($bolehInput)
@foreach ($partners as $partner)
  @php
    $terakhir = $partner->documents->sortByDesc('berlaku_selesai')->first();
    $mulaiDefault = $terakhir ? $terakhir->berlaku_selesai->copy()->addDay()->format('Y-m-d') : now()->format('Y-m-d');
  @endphp
  <div class="modal-overlay" id="modalPerpanjang{{ $partner->id }}">
    <div class="modal-card" style="max-width:600px;">
      <form method="POST" action="{{ route('mitra-industri.dokumen.store', $partner->id) }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-head">
          <h2>Ajukan Dokumen Baru &mdash; {{ $partner->nama_perusahaan }}</h2>
          <button type="button" class="modal-close" onclick="closeModal('modalPerpanjang{{ $partner->id }}')">&times;</button>
        </div>
        <div class="modal-body">
          <div class="hint" style="margin-bottom:14px;">Untuk perpanjangan MoU atau dokumen kerja sama baru dengan mitra ini. Pengajuan akan melewati 3 tahap persetujuan lagi.</div>
          <div class="field-row">
            <div class="field">
              <label>Jenis Dokumen</label>
              <select name="jenis_dokumen">
                @foreach (['MoU', 'PKS', 'MoA'] as $jenis)
                  <option value="{{ $jenis }}" {{ optional($terakhir)->jenis_dokumen === $jenis ? 'selected' : '' }}>{{ $jenis }}</option>
                @endforeach
              </select>
            </div>
            <div class="field"><label>Nomor Dokumen</label><input type="text" name="nomor_dokumen" value="{{ optional($terakhir)->nomor_dokumen }}" placeholder="mis. 001/MoU/SMKN4/2026"></div>
          </div>
          <div class="field"><label>Ruang Lingkup</label><input type="text" name="ruang_lingkup" value="{{ optional($terakhir)->ruang_lingkup }}" placeholder="mis. Guru tamu, PKL"></div>
          <div class="field-row">
            <div class="field"><label>Berlaku Mulai</label><input type="date" name="berlaku_mulai" value="{{ $mulaiDefault }}" required></div>
            <div class="field"><label>Berlaku Sampai</label><input type="date" name="berlaku_selesai" required></div>
          </div>
          <div class="field">
            <label>Dokumen <span class="hint">(PDF, opsional)</span></label>
            <input type="file" name="dokumen" accept=".pdf">
          </div>
        </div>
        <div class="modal-foot">
          <button type="button" class="btn btn-ghost" onclick="closeModal('modalPerpanjang{{ $partner->id }}')">Batal</button>
          <button type="submit" class="btn btn-primary">Ajukan Verifikasi</button>
        </div>
      </form>
    </div>
  </div>
@endforeach
@endif

@endsection

@section('scripts')
<script>
  function openModal(id){ document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
  function closeModal(id){ document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
  document.addEventListener('keydown', e=>{ if(e.key==='Escape'){ document.querySelectorAll('.modal-overlay.open').forEach(m=>closeModal(m.id)); } });

  // Dibuka otomatis dari dashboard Humas: /mitra-industri?perpanjang={id mitra}
  @if (request()->filled('perpanjang'))
    document.addEventListener('DOMContentLoaded', () => {
      if (document.getElementById('modalPerpanjang{{ (int) request('perpanjang') }}')) openModal('modalPerpanjang{{ (int) request('perpanjang') }}');
    });
  @endif
</script>
@endsection

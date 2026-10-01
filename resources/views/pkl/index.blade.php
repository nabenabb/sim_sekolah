@extends('layouts.app')

@section('title', 'Data Siswa PKL')
@section('page-title', 'Data Siswa PKL')
@section('breadcrumb', 'Waka Humas / Siswa PKL')
@section('no-error-banner', '1')

@section('content')

<div class="card card-pad">
  <div class="card-head">
    <div><h2>Data Siswa PKL</h2><div class="sub">{{ $jumlahAktif }} siswa sedang aktif PKL</div></div>
    <button class="btn btn-primary btn-sm" onclick="openModal('modalPenempatan')">+ Input Penempatan</button>
  </div>

  <div class="filter-bar">
    <a href="{{ route('pkl.index', array_filter(['status' => 'semua', 'jurusan' => request('jurusan')])) }}" class="chip {{ $status === 'semua' ? 'active' : '' }}">Semua</a>
    <a href="{{ route('pkl.index', array_filter(['status' => 'berjalan', 'jurusan' => request('jurusan')])) }}" class="chip {{ $status === 'berjalan' ? 'active' : '' }}">Berjalan</a>
    <a href="{{ route('pkl.index', array_filter(['status' => 'selesai', 'jurusan' => request('jurusan')])) }}" class="chip {{ $status === 'selesai' ? 'active' : '' }}">Selesai</a>

    <form method="GET" action="{{ route('pkl.index') }}" style="display:inline;">
      <input type="hidden" name="status" value="{{ $status }}">
      <select name="jurusan" class="select-mini" onchange="this.form.submit()">
        <option value="">Semua jurusan</option>
        @foreach ($programKeahlian as $pk)
          <option value="{{ $pk->id }}" {{ request('jurusan') == $pk->id ? 'selected' : '' }}>{{ $pk->nama_jurusan }}</option>
        @endforeach
      </select>
    </form>
  </div>

  <table>
    <thead><tr><th>Nama Siswa</th><th>Industri</th><th>Periode</th><th>Pembimbing</th><th>Nilai</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @forelse ($placements as $p)
        @php
          $pill = match ($p->status) {
            'selesai' => 'pill-success',
            'dibatalkan' => 'pill-danger',
            default => 'pill-warning',
          };
          $label = ucfirst($p->status);
        @endphp
        <tr>
          <td class="cell-strong">{{ $p->student->nama }}<div class="cell-sub">NIS {{ $p->student->nis }} &middot; {{ $p->student->programKeahlian->nama_jurusan ?? '—' }}</div></td>
          <td>{{ $p->partner->nama_perusahaan }}</td>
          <td class="mono">{{ $p->periodeText() }}</td>
          <td>{{ $p->pembimbing->name ?? '—' }}</td>
          <td class="mono">{{ $p->nilai !== null ? $p->nilai + 0 : '—' }}</td>
          <td><span class="pill {{ $pill }}"><span class="dot"></span>{{ $label }}</span></td>
          <td style="white-space:nowrap;">
            <a class="link-more" href="#" onclick="openModal('modalLihat{{ $p->id }}');return false;">Lihat</a>
            @if ($p->status === 'selesai')
              &nbsp;·&nbsp;
              @if ($p->certificate)
                <a class="link-more" href="{{ route('sertifikat.download', $p->certificate->id) }}">Unduh Sertifikat</a>
              @else
                <a class="link-more" href="{{ route('sertifikat.index', ['placement' => $p->id]) }}">Sertifikat</a>
              @endif
            @endif
            @if (in_array(auth()->user()->role->name, ['Admin', 'Waka Humas']))
              &nbsp;·&nbsp;
              <form method="POST" action="{{ route('pkl.destroy', $p) }}" style="display:inline;"
                    onsubmit="return confirm('Yakin hapus data PKL ini dari daftar? Sertifikat terkait tetap tersimpan.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="link-more" style="background:none;border:none;padding:0;cursor:pointer;color:var(--danger);">Hapus</button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center;color:var(--ink-faint);padding:24px;">Belum ada data penempatan PKL.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if ($placements->hasPages())
    <div style="display:flex;justify-content:center;gap:8px;padding:16px;">
      @if ($placements->onFirstPage())
        <span class="btn btn-ghost btn-sm" style="opacity:.4;">&larr; Sebelumnya</span>
      @else
        <a href="{{ $placements->previousPageUrl() }}" class="btn btn-ghost btn-sm">&larr; Sebelumnya</a>
      @endif
      <span style="font-size:12.5px;color:var(--ink-faint);align-self:center;">Hal. {{ $placements->currentPage() }} / {{ $placements->lastPage() }}</span>
      @if ($placements->hasMorePages())
        <a href="{{ $placements->nextPageUrl() }}" class="btn btn-ghost btn-sm">Berikutnya &rarr;</a>
      @else
        <span class="btn btn-ghost btn-sm" style="opacity:.4;">Berikutnya &rarr;</span>
      @endif
    </div>
  @endif
</div>

{{-- ================= MODAL INPUT PENEMPATAN ================= --}}
<div class="modal-overlay {{ $errors->any() ? 'open' : '' }}" id="modalPenempatan">
  <div class="modal-card">
    <form method="POST" action="{{ route('pkl.store') }}" enctype="multipart/form-data">
      @csrf
      <div class="modal-head">
        <h2>Input Penempatan PKL</h2>
        <button type="button" class="modal-close" onclick="closeModal('modalPenempatan')">&times;</button>
      </div>
      <div class="modal-body">
        @if ($errors->any())
          <div style="background:var(--danger-tint);color:var(--danger);border-radius:var(--radius-control);padding:10px 14px;font-size:13px;margin-bottom:14px;">
            {{ $errors->first() }}
          </div>
        @endif

        <div class="hint" style="margin-bottom:14px;">Pastikan surat permohonan penempatan sudah disetujui pihak industri.</div>

        <div class="field-row">
          <div class="field">
            <label>Siswa
              <a href="{{ route('siswa.index') }}" style="font-size:11.5px;font-weight:500;margin-left:6px;color:var(--primary);">+ Kelola data siswa</a>
            </label>
            <input type="text" id="cariSiswa" placeholder="Ketik nama atau NIS untuk filter…"
                   style="width:100%;border:1px solid var(--border-strong);border-radius:var(--radius-control);padding:8px 12px;font:inherit;font-size:13px;background:var(--surface-sunken);margin-bottom:6px;">
            <select name="student_id" id="selectSiswa" required size="6"
                    style="width:100%;border:1px solid var(--border-strong);border-radius:var(--radius-control);padding:6px;font:inherit;font-size:13px;background:var(--surface-sunken);">
              @forelse ($students as $s)
                <option value="{{ $s->id }}"
                        data-text="{{ strtolower($s->nama . ' ' . $s->nis) }}"
                        {{ old('student_id') == $s->id ? 'selected' : '' }}>
                  {{ $s->nama }} — NIS {{ $s->nis }}@if($s->programKeahlian) ({{ $s->programKeahlian->nama_jurusan }})@endif
                </option>
              @empty
                <option value="">Belum ada siswa tersedia — tambah di Data Siswa dulu</option>
              @endforelse
            </select>
            <div class="hint" style="margin-top:4px;">Hanya siswa yang tidak sedang PKL aktif yang ditampilkan.</div>
          </div>
          <div class="field">
            <label>Industri Tujuan</label>
            <select name="partner_id" required>
              @forelse ($partners as $pt)
                <option value="{{ $pt->id }}" {{ old('partner_id') == $pt->id ? 'selected' : '' }}>{{ $pt->nama_perusahaan }}</option>
              @empty
                <option value="">Belum ada mitra industri</option>
              @endforelse
            </select>
          </div>
        </div>
        <div class="field-row">
          <div class="field"><label>Tanggal Mulai</label><input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}" required></div>
          <div class="field"><label>Tanggal Selesai</label><input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}" required></div>
        </div>
        <div class="field-row">
          <div class="field">
            <label>Pembimbing Sekolah</label>
            <select name="pembimbing_id" required>
              @forelse ($pembimbingList as $u)
                <option value="{{ $u->id }}" {{ old('pembimbing_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
              @empty
                <option value="">Belum ada user berperan Guru / Kaprog</option>
              @endforelse
            </select>
          </div>
          <div class="field"><label>Pembimbing Industri</label><input type="text" name="pembimbing_industri" value="{{ old('pembimbing_industri') }}" placeholder="Nama pembimbing di industri"></div>
        </div>
        <div class="field">
          <label>Surat Permohonan Penempatan <span class="hint">(opsional)</span></label>
          <input type="file" name="surat_permohonan" accept=".pdf,.jpg,.jpeg,.png">
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalPenempatan')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Penempatan</button>
      </div>
    </form>
  </div>
</div>

{{-- ================= MODAL DETAIL PER PENEMPATAN ================= --}}
@foreach ($placements as $p)
  <div class="modal-overlay" id="modalLihat{{ $p->id }}">
    <div class="modal-card">
      <div class="modal-head">
        <h2>Detail Penempatan PKL</h2>
        <button type="button" class="modal-close" onclick="closeModal('modalLihat{{ $p->id }}')">&times;</button>
      </div>
      <div class="modal-body">
        <div class="info-list">
          <div class="info-row"><span class="k">Siswa</span><span class="v">{{ $p->student->nama }} &middot; NIS {{ $p->student->nis }}</span></div>
          <div class="info-row"><span class="k">Program Keahlian</span><span class="v">{{ $p->student->programKeahlian->nama_jurusan ?? '—' }}</span></div>
          <div class="info-row"><span class="k">Industri</span><span class="v">{{ $p->partner->nama_perusahaan }}</span></div>
          <div class="info-row"><span class="k">Periode</span><span class="v">{{ $p->periodeText() }}</span></div>
          <div class="info-row"><span class="k">Pembimbing Sekolah</span><span class="v">{{ $p->pembimbing->name ?? '—' }}</span></div>
          <div class="info-row"><span class="k">Pembimbing Industri</span><span class="v">{{ $p->pembimbing_industri ?: '—' }}</span></div>
          <div class="info-row"><span class="k">Nilai Akhir</span><span class="v">{{ $p->nilai !== null ? ($p->nilai + 0) . ' (' . $p->predikat() . ')' : '—' }}</span></div>
          <div class="info-row"><span class="k">Surat Permohonan</span><span class="v">@if($p->surat_permohonan)<a class="link-more" href="{{ asset('storage/'.$p->surat_permohonan) }}" target="_blank">Lihat file</a>@else —@endif</span></div>
        </div>

        @if ($p->status === 'berjalan')
          <form method="POST" action="{{ route('pkl.complete', $p->id) }}" style="margin-top:20px;">
            @csrf
            <div class="field">
              <label>Nilai Akhir (0–100) &mdash; isi untuk menandai PKL selesai</label>
              <input type="number" name="nilai" min="0" max="100" step="0.01" placeholder="mis. 88" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Tandai Selesai</button>
          </form>
          <form method="POST" action="{{ route('pkl.cancel', $p->id) }}" style="margin-top:10px;" onsubmit="return confirm('Batalkan penempatan PKL ini?')">
            @csrf
            <button type="submit" class="btn btn-ghost btn-block">Batalkan Penempatan</button>
          </form>
        @endif
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalLihat{{ $p->id }}')">Tutup</button>
      </div>
    </div>
  </div>
@endforeach


<script>
(function(){
  const cari = document.getElementById('cariSiswa');
  const sel = document.getElementById('selectSiswa');
  if (!cari || !sel) return;
  cari.addEventListener('input', function(){
    const q = this.value.toLowerCase().trim();
    Array.from(sel.options).forEach(function(opt){
      if (!opt.value) { opt.hidden = false; return; }
      const text = (opt.getAttribute('data-text') || opt.textContent).toLowerCase();
      opt.hidden = q !== '' && !text.includes(q);
    });
  });
})();
</script>

@endsection

@section('scripts')
<script>
  function openModal(id){ document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
  function closeModal(id){ document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
  document.addEventListener('keydown', e=>{ if(e.key==='Escape'){ document.querySelectorAll('.modal-overlay.open').forEach(m=>closeModal(m.id)); } });
</script>
@endsection

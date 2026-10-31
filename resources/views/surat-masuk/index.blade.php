@extends('layouts.app')

@section('title', 'Surat Masuk & Disposisi')
@section('page-title', 'Surat Masuk & Disposisi')
@section('breadcrumb', 'Tata Usaha / Surat Masuk')

@section('content')

@php $bolehKelola = in_array(auth()->user()->role->name, ['Koordinator TU', 'Admin']); @endphp

<div class="two-pane">

  {{-- ================= DAFTAR SURAT ================= --}}
  <div class="card list-panel">
    <div class="card-pad" style="padding-bottom:0;">
      <div class="card-head">
        <h2>Daftar Surat Masuk</h2>
        @if ($bolehKelola)
        <button class="btn btn-primary btn-sm" onclick="openModal('modalSuratMasuk')">+ Catat Surat Masuk</button>
        @endif
      </div>

      <form method="GET" action="{{ route('surat-masuk.index') }}" style="margin-bottom:14px;">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari perihal, pengirim, atau no. agenda…"
               style="width:100%;border:1px solid var(--border-strong);border-radius:var(--radius-control);padding:8px 12px;font:inherit;font-size:13px;background:var(--surface-sunken);"
               onchange="this.form.submit()">
      </form>

      <div class="filter-bar">
        <a href="{{ route('surat-masuk.index', array_filter(['status' => 'semua', 'q' => request('q')])) }}" class="chip {{ $status === 'semua' ? 'active' : '' }}">Semua</a>
        <a href="{{ route('surat-masuk.index', array_filter(['status' => 'menunggu', 'q' => request('q')])) }}" class="chip {{ $status === 'menunggu' ? 'active' : '' }}">Menunggu disposisi</a>
        <a href="{{ route('surat-masuk.index', array_filter(['status' => 'diproses', 'q' => request('q')])) }}" class="chip {{ $status === 'diproses' ? 'active' : '' }}">Diproses</a>
        <a href="{{ route('surat-masuk.index', array_filter(['status' => 'arsip', 'q' => request('q')])) }}" class="chip {{ $status === 'arsip' ? 'active' : '' }}">Arsip</a>
      </div>
    </div>

    @forelse ($letters as $letter)
      @php
        $belumAda = $letter->dispositions->isEmpty();
        $statusLabel = $belumAda ? 'Baru' : ($letter->dispositions->every(fn($d) => $d->status === 'selesai') ? 'Selesai' : 'Diproses');
        $pillClass = $statusLabel === 'Selesai' ? 'pill-success' : 'pill-warning';
      @endphp
      <a href="{{ route('surat-masuk.index', array_filter(['surat' => $letter->id, 'status' => $status, 'q' => request('q')])) }}" style="display:block;">
        <div class="list-item {{ $selected && $selected->id === $letter->id ? 'active' : '' }}">
          <div class="top">
            <span class="subj">{{ $letter->perihal }}</span>
            <span class="pill {{ $pillClass }}"><span class="dot"></span>{{ $statusLabel }}</span>
          </div>
          <div class="meta">{{ $letter->pengirim }} &middot; <span class="mono">{{ $letter->no_agenda }}</span> &middot; {{ $letter->tanggal_terima->translatedFormat('d M Y') }}</div>
        </div>
      </a>
    @empty
      <div class="empty-state">
        <div class="ic">&#9636;</div>
        <h3>Belum ada surat masuk</h3>
        <p>Klik "+ Catat Surat Masuk" untuk menambahkan.</p>
      </div>
    @endforelse

    @if ($letters->hasPages())
      <div style="display:flex;justify-content:center;gap:8px;padding:16px;">
        @if ($letters->onFirstPage())
          <span class="btn btn-ghost btn-sm" style="opacity:.4;">&larr; Sebelumnya</span>
        @else
          <a href="{{ $letters->previousPageUrl() }}" class="btn btn-ghost btn-sm">&larr; Sebelumnya</a>
        @endif

        <span style="font-size:12.5px;color:var(--ink-faint);align-self:center;">Hal. {{ $letters->currentPage() }} / {{ $letters->lastPage() }}</span>

        @if ($letters->hasMorePages())
          <a href="{{ $letters->nextPageUrl() }}" class="btn btn-ghost btn-sm">Berikutnya &rarr;</a>
        @else
          <span class="btn btn-ghost btn-sm" style="opacity:.4;">Berikutnya &rarr;</span>
        @endif
      </div>
    @endif
  </div>

  {{-- ================= DETAIL + DISPOSISI ================= --}}
  <div class="stack">
    @if ($selected)
      <div class="card card-pad">
        <div class="card-head">
          <h2>Detail Surat</h2>
          @if ($bolehKelola)
          <div style="display:flex;gap:8px;align-items:center;">
            <button class="btn btn-ghost btn-sm" onclick="openModal('modalEditSurat')">Edit</button>
            <form method="POST" action="{{ route('surat-masuk.destroy', $selected) }}"
                  onsubmit="return confirm('Yakin hapus surat ini dari daftar? Data tidak hilang permanen dan bisa dipulihkan admin.')">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);">Hapus</button>
            </form>
          </div>
          @endif
        </div>
        <div class="info-list">
          <div class="info-row"><span class="k">No. Agenda</span><span class="v mono">{{ $selected->no_agenda }}</span></div>
          <div class="info-row"><span class="k">Asal Pengirim</span><span class="v">{{ $selected->pengirim }}</span></div>
          <div class="info-row"><span class="k">Nomor Surat</span><span class="v mono">{{ $selected->nomor_surat ?? '—' }}</span></div>
          <div class="info-row"><span class="k">Tanggal Surat</span><span class="v">{{ optional($selected->tanggal_surat)->translatedFormat('d M Y') ?? '—' }}</span></div>
          <div class="info-row"><span class="k">Tanggal Diterima</span><span class="v">{{ $selected->tanggal_terima->translatedFormat('d M Y') }}</span></div>
          <div class="info-row"><span class="k">Sifat</span><span class="v">{{ $selected->sifat }}</span></div>
          <div class="info-row">
            <span class="k">File Scan</span>
            <span class="v">
              @if ($selected->file_path)
                <a class="link-more" href="{{ route('private-file.show', ['path' => $selected->file_path]) }}" target="_blank">Lihat lampiran</a>
              @else
                —
              @endif
            </span>
          </div>
        </div>
      </div>

      @if ($selected->dispositions->isNotEmpty())
        <div class="card card-pad">
          <div class="card-head"><h2>Riwayat Disposisi</h2></div>
          <div class="info-list">
            @foreach ($selected->dispositions as $d)
              <div class="info-row" style="align-items:flex-start;">
                <span class="k">{{ $d->assignee->name ?? '—' }}<br><span style="font-size:11.5px;color:var(--ink-faint);">{{ $d->assignee->role->name ?? '' }}</span></span>
                <span class="v" style="text-align:right;">
                  <div style="margin-bottom:6px;font-size:12.5px;">{{ $d->instruksi }}</div>
                  <form method="POST" action="{{ route('surat-masuk.disposisi.updateStatus', $d->id) }}" style="display:inline-flex;gap:6px;align-items:center;">
                    @csrf
                    @method('PATCH')
                    <select name="status" onchange="this.form.submit()" style="font-size:12px;border:1px solid var(--border-strong);border-radius:6px;padding:3px 6px;background:var(--surface-sunken);">
                      <option value="belum ditindaklanjuti" {{ $d->status === 'belum ditindaklanjuti' ? 'selected' : '' }}>Belum ditindaklanjuti</option>
                      <option value="diproses" {{ $d->status === 'diproses' ? 'selected' : '' }}>Diproses</option>
                      <option value="selesai" {{ $d->status === 'selesai' ? 'selected' : '' }}>Selesai</option>
                    </select>
                  </form>
                </span>
              </div>
            @endforeach
          </div>
        </div>
      @endif

      <div class="card card-pad disposition-launch">
        <div>
          <h2>Buat Disposisi</h2>
          <div class="sub">Teruskan surat ini kepada role yang perlu menindaklanjuti.</div>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('modalBuatDisposisi')">Buat Disposisi</button>
      </div>
    @else
      <div class="card card-pad">
        <div class="empty-state">
          <p>Pilih surat di daftar sebelah kiri untuk melihat detail.</p>
        </div>
      </div>
    @endif
  </div>
</div>

@if ($selected)
  @php $dispositionModalOpen = $errors->has('tujuan') || $errors->has('instruksi'); @endphp
  <div class="modal-overlay {{ $dispositionModalOpen ? 'open' : '' }}" id="modalBuatDisposisi">
    <div class="modal-card modal-card--wide" role="dialog" aria-modal="true" aria-labelledby="dispositionModalTitle">
      <form method="POST" action="{{ route('surat-masuk.disposisi.store', $selected->id) }}">
        @csrf
        <div class="modal-head">
          <div>
            <h2 id="dispositionModalTitle">Buat Disposisi</h2>
            <div class="sub">{{ $selected->no_agenda }} · {{ $selected->perihal }}</div>
          </div>
          <button type="button" class="modal-close" onclick="closeModal('modalBuatDisposisi')" aria-label="Tutup">&times;</button>
        </div>
        <div class="modal-body">
          @if ($errors->has('tujuan') || $errors->has('instruksi'))
            <div role="alert" style="background:var(--danger-tint);color:var(--danger);border-radius:var(--radius-control);padding:10px 14px;font-size:13px;margin-bottom:14px;">
              {{ $errors->first() }}
            </div>
          @endif
          <div class="field">
            <label>Diteruskan Kepada</label>
            <div class="checkbox-grid">
              @foreach (\App\Models\Disposition::ROLE_PENERIMA as $roleName)
                <label class="checkbox-item">
                  <input type="checkbox" name="tujuan[]" value="{{ $roleName }}" @checked(in_array($roleName, (array) old('tujuan', []), true))> {{ $roleName }}
                </label>
              @endforeach
            </div>
          </div>
          <div class="field">
            <label>Dengan Hormat Harap</label>
            <div class="checkbox-grid">
              <label class="checkbox-item"><input type="checkbox" name="instruksi[]" value="Tanggapan dan saran" @checked(in_array('Tanggapan dan saran', (array) old('instruksi', []), true))> Tanggapan dan saran</label>
              <label class="checkbox-item"><input type="checkbox" name="instruksi[]" value="Proses lebih lanjut" @checked(in_array('Proses lebih lanjut', (array) old('instruksi', []), true))> Proses lebih lanjut</label>
            </div>
          </div>
          <div class="field">
            <label for="dispositionNote">Catatan</label>
            <textarea id="dispositionNote" name="catatan" placeholder="Tulis arahan tambahan...">{{ old('catatan') }}</textarea>
          </div>
        </div>
        <div class="modal-foot">
          <button type="button" class="btn btn-ghost" onclick="closeModal('modalBuatDisposisi')">Batal</button>
          <button type="submit" class="btn btn-primary">Kirim Disposisi</button>
        </div>
      </form>
    </div>
  </div>
@endif

{{-- ================= MODAL CATAT SURAT BARU ================= --}}
<div class="modal-overlay" id="modalSuratMasuk">
  <div class="modal-card">
    <form method="POST" action="{{ route('surat-masuk.store') }}" enctype="multipart/form-data">
      @csrf
      <div class="modal-head">
        <h2>Catat Surat Masuk Baru</h2>
        <button type="button" class="modal-close" onclick="closeModal('modalSuratMasuk')">&times;</button>
      </div>
      <div class="modal-body">
        <div class="hint" style="margin-bottom:14px;">Nomor agenda dibuat otomatis oleh sistem setelah surat disimpan.</div>

        <div class="field-row">
          <div class="field"><label>Asal Pengirim</label><input type="text" name="pengirim" placeholder="mis. PT Nusantara Digital Karya" required></div>
          <div class="field"><label>Nomor Surat</label><input type="text" name="nomor_surat" placeholder="mis. 021/HRD/IX/2026"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Tanggal Surat</label><input type="date" name="tanggal_surat"></div>
          <div class="field"><label>Tanggal Diterima</label><input type="date" name="tanggal_terima" required></div>
        </div>
        <div class="field"><label>Perihal</label><input type="text" name="perihal" placeholder="mis. Permohonan Kerja Sama PKL" required></div>
        <div class="field-row">
          <div class="field">
            <label>Sifat Surat</label>
            <select name="sifat">
              <option value="Biasa">Biasa</option>
              <option value="Segera">Segera</option>
              <option value="Sangat Segera">Sangat Segera</option>
              <option value="Rahasia">Rahasia</option>
            </select>
          </div>
          <div class="field">
            <label>Scan / Foto Surat <span class="hint">(opsional)</span></label>
            <input type="file" name="file_scan" accept=".jpg,.jpeg,.png,.pdf">
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalSuratMasuk')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Surat</button>
      </div>
    </form>
  </div>
</div>

{{-- ================= MODAL EDIT SURAT ================= --}}
@if ($selected)
<div class="modal-overlay" id="modalEditSurat">
  <div class="modal-card">
    <form method="POST" action="{{ route('surat-masuk.update', $selected->id) }}" enctype="multipart/form-data">
      @csrf
      @method('PUT')
      <div class="modal-head">
        <h2>Edit Surat Masuk</h2>
        <button type="button" class="modal-close" onclick="closeModal('modalEditSurat')">&times;</button>
      </div>
      <div class="modal-body">
        <div class="field-row">
          <div class="field"><label>Asal Pengirim</label><input type="text" name="pengirim" value="{{ $selected->pengirim }}" required></div>
          <div class="field"><label>Nomor Surat</label><input type="text" name="nomor_surat" value="{{ $selected->nomor_surat }}"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Tanggal Surat</label><input type="date" name="tanggal_surat" value="{{ optional($selected->tanggal_surat)->format('Y-m-d') }}"></div>
          <div class="field"><label>Tanggal Diterima</label><input type="date" name="tanggal_terima" value="{{ $selected->tanggal_terima->format('Y-m-d') }}" required></div>
        </div>
        <div class="field"><label>Perihal</label><input type="text" name="perihal" value="{{ $selected->perihal }}" required></div>
        <div class="field-row">
          <div class="field">
            <label>Sifat Surat</label>
            <select name="sifat">
              @foreach (['Biasa', 'Segera', 'Sangat Segera', 'Rahasia'] as $opt)
                <option value="{{ $opt }}" {{ $selected->sifat === $opt ? 'selected' : '' }}>{{ $opt }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label>Ganti Scan/Foto <span class="hint">(opsional)</span></label>
            <input type="file" name="file_scan" accept=".jpg,.jpeg,.png,.pdf">
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalEditSurat')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>
@endif

@endsection

@section('scripts')
<script>
  function openModal(id){ document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
  function closeModal(id){ document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
  document.addEventListener('keydown', e=>{ if(e.key==='Escape'){ document.querySelectorAll('.modal-overlay.open').forEach(m=>closeModal(m.id)); } });
</script>
@endsection

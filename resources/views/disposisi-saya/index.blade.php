@extends('layouts.app')

@section('title', 'Disposisi Saya')
@section('page-title', 'Disposisi Saya')
@section('breadcrumb', auth()->user()->role->name . ' / Disposisi')

@section('content')

<div class="card card-pad">
  <div class="card-head">
    <div><h2>Disposisi untuk Anda</h2><div class="sub">Arahan dari Kepala Sekolah / TU yang perlu ditindaklanjuti</div></div>
  </div>

  <div class="filter-bar">
    <a href="{{ route('disposisi-saya.index', ['status' => 'semua']) }}" class="chip {{ $status === 'semua' ? 'active' : '' }}">Semua</a>
    <a href="{{ route('disposisi-saya.index', ['status' => 'belum']) }}" class="chip {{ $status === 'belum' ? 'active' : '' }}">Belum ditindaklanjuti</a>
    <a href="{{ route('disposisi-saya.index', ['status' => 'diproses']) }}" class="chip {{ $status === 'diproses' ? 'active' : '' }}">Diproses</a>
    <a href="{{ route('disposisi-saya.index', ['status' => 'selesai']) }}" class="chip {{ $status === 'selesai' ? 'active' : '' }}">Selesai</a>
  </div>

  <table>
    <thead><tr><th>No. Agenda</th><th>Perihal</th><th>Instruksi</th><th>Diterima</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @forelse ($dispositions as $d)
        <tr>
          <td class="mono">{{ $d->letter->no_agenda }}</td>
          <td class="cell-strong">{{ $d->letter->perihal }}<div class="cell-sub">{{ $d->letter->pengirim }}</div></td>
          <td style="max-width:260px;">{{ $d->instruksi }}</td>
          <td>{{ $d->created_at->translatedFormat('d M Y') }}</td>
          <td>
            <form method="POST" action="{{ route('disposisi-saya.status', $d->id) }}">
              @csrf
              @method('PATCH')
              <select name="status" onchange="this.form.submit()" style="font-size:12.5px;border:1px solid var(--border-strong);border-radius:6px;padding:4px 8px;background:var(--surface-sunken);">
                <option value="belum ditindaklanjuti" {{ $d->status === 'belum ditindaklanjuti' ? 'selected' : '' }}>Belum ditindaklanjuti</option>
                <option value="diproses" {{ $d->status === 'diproses' ? 'selected' : '' }}>Diproses</option>
                <option value="selesai" {{ $d->status === 'selesai' ? 'selected' : '' }}>Selesai</option>
              </select>
            </form>
          </td>
          <td><a class="link-more" href="#" onclick="openModal('modalSurat{{ $d->id }}');return false;">Lihat surat</a></td>
        </tr>
      @empty
        <tr><td colspan="6" style="text-align:center;color:var(--ink-faint);padding:24px;">Belum ada disposisi untuk Anda.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if ($dispositions->hasPages())
    <div style="display:flex;justify-content:center;gap:8px;padding:16px;">
      @if ($dispositions->onFirstPage())
        <span class="btn btn-ghost btn-sm" style="opacity:.4;">&larr; Sebelumnya</span>
      @else
        <a href="{{ $dispositions->previousPageUrl() }}" class="btn btn-ghost btn-sm">&larr; Sebelumnya</a>
      @endif
      <span style="font-size:12.5px;color:var(--ink-faint);align-self:center;">Hal. {{ $dispositions->currentPage() }} / {{ $dispositions->lastPage() }}</span>
      @if ($dispositions->hasMorePages())
        <a href="{{ $dispositions->nextPageUrl() }}" class="btn btn-ghost btn-sm">Berikutnya &rarr;</a>
      @else
        <span class="btn btn-ghost btn-sm" style="opacity:.4;">Berikutnya &rarr;</span>
      @endif
    </div>
  @endif
</div>

{{-- ================= MODAL DETAIL SURAT PER DISPOSISI ================= --}}
@foreach ($dispositions as $d)
  @php $l = $d->letter; @endphp
  <div class="modal-overlay" id="modalSurat{{ $d->id }}">
    <div class="modal-card">
      <div class="modal-head">
        <h2>Detail Surat &amp; Instruksi</h2>
        <button type="button" class="modal-close" onclick="closeModal('modalSurat{{ $d->id }}')">&times;</button>
      </div>
      <div class="modal-body">
        <div class="info-list">
          <div class="info-row"><span class="k">No. Agenda</span><span class="v mono">{{ $l->no_agenda }}</span></div>
          <div class="info-row"><span class="k">Asal Pengirim</span><span class="v">{{ $l->pengirim }}</span></div>
          <div class="info-row"><span class="k">Nomor Surat</span><span class="v mono">{{ $l->nomor_surat ?? '—' }}</span></div>
          <div class="info-row"><span class="k">Tanggal Surat</span><span class="v">{{ optional($l->tanggal_surat)->translatedFormat('d M Y') ?? '—' }}</span></div>
          <div class="info-row"><span class="k">Tanggal Diterima</span><span class="v">{{ $l->tanggal_terima->translatedFormat('d M Y') }}</span></div>
          <div class="info-row"><span class="k">Sifat</span><span class="v">{{ $l->sifat }}</span></div>
          <div class="info-row"><span class="k">Perihal</span><span class="v">{{ $l->perihal }}</span></div>
          <div class="info-row"><span class="k">Instruksi</span><span class="v">{{ $d->instruksi }}</span></div>
          <div class="info-row"><span class="k">File Scan</span><span class="v">@if($l->file_path)<a class="link-more" href="{{ route('private-file.show', ['path' => $l->file_path]) }}" target="_blank">Lihat lampiran</a>@else —@endif</span></div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalSurat{{ $d->id }}')">Tutup</button>
      </div>
    </div>
  </div>
@endforeach

@endsection

@section('scripts')
<script>
  function openModal(id){ document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
  function closeModal(id){ document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
  document.addEventListener('keydown', e=>{ if(e.key==='Escape'){ document.querySelectorAll('.modal-overlay.open').forEach(m=>closeModal(m.id)); } });
</script>
@endsection

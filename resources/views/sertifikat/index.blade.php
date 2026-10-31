@extends('layouts.app')

@section('title', 'Sertifikat PKL')
@section('page-title', 'Sertifikat PKL')
@section('breadcrumb', 'Waka Humas / Sertifikat PKL')
@section('no-error-banner', '1')

@section('content')

<div class="card card-pad certificate-issue-launch">
  <div>
    <h2>Generate Sertifikat PKL</h2>
    <div class="sub">{{ $eligible->count() }} penempatan siap diterbitkan menjadi sertifikat</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="openModal('modalTerbitSertifikat')" @disabled($eligible->isEmpty())>+ Terbitkan Sertifikat</button>
</div>

@php $issueModalOpen = $errors->any() || $selectedId; @endphp
<div class="modal-overlay {{ $issueModalOpen ? 'open' : '' }}" id="modalTerbitSertifikat">
  <section class="modal-card modal-card--extra-wide" role="dialog" aria-modal="true" aria-labelledby="certificateIssueTitle">
    <div class="modal-head">
      <h2 id="certificateIssueTitle">Terbitkan Sertifikat PKL</h2>
      <button type="button" class="modal-close" onclick="closeModal('modalTerbitSertifikat')" aria-label="Tutup">&times;</button>
    </div>
    <div class="modal-body">
      @if ($errors->any())
        <div role="alert" style="background:var(--danger-tint);color:var(--danger);border-radius:var(--radius-control);padding:10px 14px;font-size:13px;margin-bottom:14px;">
          {{ $errors->first() }}
        </div>
      @endif

      <div class="certificate-issue-grid">
        <form method="POST" action="{{ route('sertifikat.store') }}" class="certificate-issue-form" id="certificateIssueForm">
          @csrf

          <div class="field">
            <label for="placementSelect">Siswa</label>
            <select name="placement_id" id="placementSelect" required>
              @forelse ($eligible as $p)
                <option value="{{ $p->id }}"
                        data-nama="{{ $p->student->nama }}"
                        data-nis="{{ $p->student->nis }}"
                        data-industri="{{ $p->partner->nama_perusahaan }}"
                        data-nilai="{{ ($p->nilai + 0) . ' (' . $p->predikat() . ')' }}"
                        data-periode="{{ $p->periodeText() }}"
                        @selected((string) old('placement_id', $selectedId) === (string) $p->id)>
                  {{ $p->student->nama }} &mdash; NIS {{ $p->student->nis }} (Selesai PKL)
                </option>
              @empty
                <option value="">Belum ada siswa yang siap diterbitkan sertifikatnya</option>
              @endforelse
            </select>
          </div>

          <div class="field-row">
            <div class="field"><label>Industri</label><input type="text" id="f-industri" disabled></div>
            <div class="field"><label>Nilai Akhir</label><input type="text" id="f-nilai" disabled></div>
          </div>
          <div class="field">
            <label>Template Sertifikat</label>
            <select disabled><option>Template Resmi SMKN 4 (Kop + TTD Digital)</option></select>
          </div>
          <div class="field"><label>Nomor Sertifikat</label><input type="text" value="{{ $nomorBerikut }}" class="mono" disabled></div>
          <div class="hint" style="margin-bottom:16px;">Nomor sertifikat &amp; kode QR dibuat otomatis oleh sistem saat diterbitkan.</div>

        </form>

        <section class="certificate-preview-panel" aria-labelledby="certificatePreviewTitle">
          <h3 id="certificatePreviewTitle">Pratinjau Sertifikat</h3>
          <div class="certificate-preview-card">
            <div class="certificate-preview-card__header">
              <div><div>SMKN 4 Tanjungpinang</div><strong>Sertifikat Praktik Kerja Lapangan</strong></div>
              <img src="{{ asset('images/logo-smkn4-tanjungpinang.png') }}" alt="Logo SMKN 4 Tanjungpinang">
            </div>
            <div class="certificate-preview-card__details">
              <div class="info-row"><span class="k">Nama</span><span class="v" id="p-nama">—</span></div>
              <div class="info-row"><span class="k">NIS</span><span class="v" id="p-nis">—</span></div>
              <div class="info-row"><span class="k">Industri</span><span class="v" id="p-industri">—</span></div>
              <div class="info-row"><span class="k">Periode</span><span class="v" id="p-periode">—</span></div>
            </div>
            <div class="certificate-preview-card__footer">
              <div class="mono">{{ $nomorBerikut }}</div>
              <div class="certificate-preview-card__qr" aria-hidden="true"></div>
            </div>
          </div>
          <div class="hint" style="margin-top:10px;">QR asli dibuat saat sertifikat diterbitkan; gambar di atas hanya ilustrasi.</div>
        </section>
      </div>
    </div>
    <div class="modal-foot certificate-issue-actions">
      <button type="button" class="btn btn-ghost" onclick="closeModal('modalTerbitSertifikat')">Batal</button>
      <button type="submit" form="certificateIssueForm" class="btn btn-primary" {{ $eligible->isEmpty() ? 'disabled' : '' }}>Terbitkan Sertifikat (PDF)</button>
    </div>
  </section>
</div>

{{-- ================= DAFTAR SERTIFIKAT TERBIT ================= --}}
<div class="card card-pad" style="margin-top:20px;">
  <div class="card-head">
    <div><h2>Sertifikat yang Sudah Terbit</h2><div class="sub">Unduh PDF atau buka halaman verifikasi publik (yang muncul saat QR di-scan)</div></div>
  </div>
  <table>
    <thead><tr><th>Nomor Sertifikat</th><th>Nama Siswa</th><th>Industri</th><th>Tanggal Terbit</th><th></th></tr></thead>
    <tbody>
      @forelse ($issued as $c)
        <tr>
          <td class="mono">{{ $c->nomor_sertifikat }}</td>
          <td class="cell-strong">{{ $c->placement->student->nama }}</td>
          <td>{{ $c->placement->partner->nama_perusahaan }}</td>
          <td>{{ $c->tanggal_terbit->translatedFormat('d M Y') }}</td>
          <td style="white-space:nowrap;">
            <a class="link-more" href="{{ route('sertifikat.download', $c->id) }}">Unduh PDF</a>
            &nbsp;·&nbsp;
            <a class="link-more" href="{{ route('sertifikat.verify', $c->qr_code) }}" target="_blank">Halaman Verifikasi</a>
            @if (in_array(auth()->user()->role->name, ['Admin', 'Waka Humas']))
              &nbsp;·&nbsp;
              <form method="POST" action="{{ route('sertifikat.destroy', $c) }}" style="display:inline;"
                    onsubmit="return confirm('Yakin hapus sertifikat ini dari daftar?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="link-more" style="background:none;border:none;padding:0;cursor:pointer;color:var(--danger);">Hapus</button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5" style="text-align:center;color:var(--ink-faint);padding:24px;">Belum ada sertifikat yang diterbitkan.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if ($issued->hasPages())
    <div style="display:flex;justify-content:center;gap:8px;padding:16px;">
      @if ($issued->onFirstPage())
        <span class="btn btn-ghost btn-sm" style="opacity:.4;">&larr; Sebelumnya</span>
      @else
        <a href="{{ $issued->previousPageUrl() }}" class="btn btn-ghost btn-sm">&larr; Sebelumnya</a>
      @endif
      <span style="font-size:12.5px;color:var(--ink-faint);align-self:center;">Hal. {{ $issued->currentPage() }} / {{ $issued->lastPage() }}</span>
      @if ($issued->hasMorePages())
        <a href="{{ $issued->nextPageUrl() }}" class="btn btn-ghost btn-sm">Berikutnya &rarr;</a>
      @else
        <span class="btn btn-ghost btn-sm" style="opacity:.4;">Berikutnya &rarr;</span>
      @endif
    </div>
  @endif
</div>

@endsection

@section('scripts')
<script>
  const sel = document.getElementById('placementSelect');

  function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('open');
  }

  function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('open');
  }

  function refreshPreview() {
    if (!sel || !sel.options.length) return;

    const o = sel.options[sel.selectedIndex];
    const d = (o && o.dataset.nama) ? o.dataset : { nama: '—', nis: '—', industri: '', nilai: '', periode: '—' };

    document.getElementById('f-industri').value = d.industri || '';
    document.getElementById('f-nilai').value = d.nilai || '';
    document.getElementById('p-nama').textContent = d.nama;
    document.getElementById('p-nis').textContent = d.nis;
    document.getElementById('p-industri').textContent = d.industri || '—';
    document.getElementById('p-periode').textContent = d.periode;
  }

  sel?.addEventListener('change', refreshPreview);
  refreshPreview();
</script>
@endsection

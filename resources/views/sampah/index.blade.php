@extends('layouts.app')

@section('title', 'Sampah & Pemulihan')
@section('page-title', 'Sampah & Pemulihan')
@section('breadcrumb', 'Admin / Sampah')

@section('content')

<div class="card card-pad">
  <div class="card-head">
    <div>
      <h2>Data yang Sudah Dihapus</h2>
      <div class="sub">Data di sini hanya disembunyikan, belum hilang. Pulihkan kalau terhapus tidak sengaja, atau hapus permanen kalau memang sudah tidak diperlukan.</div>
    </div>
  </div>

  <div class="filter-bar">
    @foreach ($tabs as $key => $label)
      <a href="{{ route('sampah.index', ['jenis' => $key]) }}" class="chip {{ $jenis === $key ? 'active' : '' }}">{{ $label }} ({{ $counts[$key] }})</a>
    @endforeach
  </div>

  <table>
    <thead><tr><th>{{ $config['label'] }}</th><th>Dihapus pada</th><th></th></tr></thead>
    <tbody>
      @forelse ($items as $item)
        <tr>
          <td class="cell-strong">{{ $config['title']($item) }}<div class="cell-sub">{{ $config['sub']($item) }}</div></td>
          <td>{{ $item->deleted_at->translatedFormat('d M Y, H:i') }}</td>
          <td style="white-space:nowrap;">
            <form method="POST" action="{{ route('sampah.restore', ['jenis' => $jenis, 'id' => $item->id]) }}" style="display:inline;">
              @csrf
              <button type="submit" class="link-more" style="background:none;border:none;padding:0;cursor:pointer;color:var(--success);">Pulihkan</button>
            </form>
            &nbsp;·&nbsp;
            <form method="POST" action="{{ route('sampah.force', ['jenis' => $jenis, 'id' => $item->id]) }}" style="display:inline;"
                  onsubmit="return confirm('Hapus PERMANEN? Data dan file terkait tidak bisa dikembalikan lagi.')">
              @csrf
              @method('DELETE')
              <button type="submit" class="link-more" style="background:none;border:none;padding:0;cursor:pointer;color:var(--danger);">Hapus Permanen</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="3" style="text-align:center;color:var(--ink-faint);padding:24px;">Tidak ada {{ strtolower($config['label']) }} di Sampah.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if ($items->hasPages())
    <div style="display:flex;justify-content:center;gap:8px;padding:16px;">
      @if ($items->onFirstPage())
        <span class="btn btn-ghost btn-sm" style="opacity:.4;">&larr; Sebelumnya</span>
      @else
        <a href="{{ $items->previousPageUrl() }}" class="btn btn-ghost btn-sm">&larr; Sebelumnya</a>
      @endif
      <span style="font-size:12.5px;color:var(--ink-faint);align-self:center;">Hal. {{ $items->currentPage() }} / {{ $items->lastPage() }}</span>
      @if ($items->hasMorePages())
        <a href="{{ $items->nextPageUrl() }}" class="btn btn-ghost btn-sm">Berikutnya &rarr;</a>
      @else
        <span class="btn btn-ghost btn-sm" style="opacity:.4;">Berikutnya &rarr;</span>
      @endif
    </div>
  @endif
</div>

@endsection

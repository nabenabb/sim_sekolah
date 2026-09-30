@extends('layouts.app')

@section('title', 'Hasil Pencarian')
@section('page-title', 'Hasil Pencarian')
@section('breadcrumb', $q !== '' ? 'Menampilkan hasil untuk "'.$q.'"' : '')

@section('content')

@if ($q === '')
  <div class="card card-pad">
    <div class="empty-state"><p>Ketik kata kunci di kolom pencarian atas, lalu tekan Enter.</p></div>
  </div>
@elseif (empty($results))
  <div class="card card-pad">
    <div class="empty-state">
      <h3>Tidak ada hasil untuk "{{ $q }}"</h3>
      <p>Coba kata kunci lain, atau pastikan ejaannya benar.</p>
    </div>
  </div>
@else
  <div class="stack" style="gap:16px;">
    @foreach ($results as $judulGrup => $items)
      <div class="card card-pad">
        <div class="card-head"><h2>{{ $judulGrup }}</h2><div class="sub">{{ $items->count() }} hasil</div></div>
        <div class="stack" style="gap:2px;">
          @foreach ($items as $item)
            <a href="{{ $item['url'] }}" style="display:block;text-decoration:none;color:inherit;">
              <div class="list-item">
                <div class="top"><span class="subj">{{ $item['label'] }}</span></div>
                <div class="meta">{{ $item['sub'] }}</div>
              </div>
            </a>
          @endforeach
        </div>
      </div>
    @endforeach
  </div>
@endif

@endsection

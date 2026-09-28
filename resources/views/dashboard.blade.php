@extends('layouts.app')

@section('title', 'Ringkasan')

@section('content')

    <h1>Selamat datang, {{ auth()->user()->name }}</h1>
    <p class="lead">Berikut ringkasan aktivitas surat dan kerja sama hari ini.</p>

    <div class="stat-row">
        <div class="stat">
            <div class="num">—</div>
            <div class="label">Surat masuk belum diproses</div>
        </div>
        <div class="stat">
            <div class="num">—</div>
            <div class="label">Disposisi menunggu tindak lanjut</div>
        </div>
        <div class="stat">
            <div class="num">—</div>
            <div class="label">Dokumen kerja sama akan berakhir</div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">Disposisi terbaru</div>
        <table>
            <thead>
                <tr>
                    <th>Perihal</th>
                    <th>Ditujukan ke</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="3" style="color: var(--slate);">Belum ada data — modul surat masuk belum terhubung.</td>
                </tr>
            </tbody>
        </table>
    </div>

@endsection

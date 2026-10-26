<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>{{ $title }}</title>
<style>
  @page { size: A4 landscape; margin: 24px 28px 38px; }
  body { margin: 0; color: #172B40; font-family: 'DejaVu Sans', sans-serif; font-size: 9px; }
  .masthead { border-bottom: 2px solid #173F66; padding-bottom: 12px; margin-bottom: 14px; }
  .brand { color: #173F66; font-size: 8px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
  h1 { margin: 5px 0 4px; color: #10253D; font-size: 19px; }
  .period { margin: 0; color: #586D82; font-size: 9px; }
  .summary { margin: 12px 0 10px; color: #4B6178; font-size: 8px; }
  table { width: 100%; border-collapse: collapse; table-layout: fixed; }
  thead { display: table-header-group; }
  tr { page-break-inside: avoid; }
  th { padding: 7px 6px; border: 1px solid #C7D5E2; background: #173F66; color: #fff; font-size: {{ count($headers) > 8 ? '7' : (count($headers) > 5 ? '7.5' : '8.5') }}px; text-align: left; }
  td { padding: 6px; border: 1px solid #DCE5EE; color: #263A4D; font-size: {{ count($headers) > 8 ? '6.8' : (count($headers) > 5 ? '7.3' : '8.2') }}px; vertical-align: top; word-wrap: break-word; }
  tbody tr:nth-child(even) { background: #F3F7FB; }
  .row-number { width: 28px; color: #71859A; text-align: center; }
  .empty { padding: 20px; color: #71859A; text-align: center; }
  .footer { position: fixed; right: 0; bottom: -22px; left: 0; border-top: 1px solid #DCE5EE; padding-top: 6px; color: #71859A; font-size: 7px; }
  .footer .right { float: right; }
</style>
</head>
<body>
  <header class="masthead">
    <div class="brand">SIM Sekolah · SMK Negeri 4 Tanjungpinang</div>
    <h1>{{ $title }}</h1>
    <p class="period">Periode {{ $from->translatedFormat('d M Y') }} sampai {{ $to->translatedFormat('d M Y') }}</p>
  </header>

  <div class="summary">{{ number_format(count($rows), 0, ',', '.') }} baris data · Dicetak {{ $generatedAt->translatedFormat('d M Y, H:i') }}</div>

  <table>
    <thead>
      <tr>
        <th class="row-number">No.</th>
        @foreach ($headers as $header)
          <th>{{ \Illuminate\Support\Str::headline($header) }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @forelse ($rows as $index => $row)
        <tr>
          <td class="row-number">{{ $index + 1 }}</td>
          @foreach ($row as $value)
            <td>{{ $value ?? '-' }}</td>
          @endforeach
        </tr>
      @empty
        <tr><td class="empty" colspan="{{ count($headers) + 1 }}">Tidak ada data pada periode ini.</td></tr>
      @endforelse
    </tbody>
  </table>

  <footer class="footer">
    <span>Dokumen laporan dihasilkan oleh SIM Sekolah SMKN 4 Tanjungpinang.</span>
    <span class="right">{{ $title }} · {{ $from->toDateString() }} - {{ $to->toDateString() }}</span>
  </footer>
</body>
</html>

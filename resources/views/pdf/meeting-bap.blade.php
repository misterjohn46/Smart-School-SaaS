<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Rapat - {{ $meeting->meeting_number }}</title>
    <style>
        @page {
            margin: 15mm 15mm 20mm 15mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            color: #1e293b;
            line-height: 1.4;
        }
        .kop {
            text-align: center;
            border-bottom: 2.5px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .kop h1 {
            font-size: 15px;
            margin: 0 0 2px;
            text-transform: uppercase;
            font-weight: bold;
            color: #0f172a;
        }
        .kop h2 {
            font-size: 11px;
            margin: 0 0 2px;
            text-transform: uppercase;
            font-weight: bold;
            color: #334155;
        }
        .kop p {
            font-size: 8.5px;
            margin: 0;
            color: #64748b;
        }
        .judul-dokumen {
            text-align: center;
            margin-bottom: 14px;
        }
        .judul-dokumen h3 {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
            text-decoration: underline;
        }
        .judul-dokumen p {
            font-size: 9px;
            margin: 2px 0 0;
            font-family: monospace;
            color: #475569;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 2.5px 0;
            font-size: 9.5px;
            vertical-align: top;
        }
        .meta-table .label {
            width: 25%;
            color: #475569;
        }
        .meta-table .sep {
            width: 3%;
            text-align: center;
        }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin-top: 10px;
            margin-bottom: 6px;
        }
        .content-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            font-size: 9px;
            margin-bottom: 8px;
        }
        .data-table {
            margin-top: 6px;
            margin-bottom: 8px;
        }
        .data-table th, .data-table td {
            border: 1px solid #94a3b8;
            padding: 3.5px 5px;
            font-size: 8.5px;
        }
        .data-table th {
            background: #f1f5f9;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
        }
        .center {
            text-align: center;
        }
        .badge {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.5px;
        }
        .ttd-box {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .ttd-box td {
            width: 50%;
            text-align: center;
            font-size: 9.5px;
            vertical-align: top;
        }
        .ttd-space {
            height: 48px;
        }
        .signature-img {
            max-height: 26px;
            max-width: 90px;
            display: block;
            margin: 0 auto;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <!-- KOP SURAT RESMI -->
    <div class="kop">
        <h1>{{ $schoolName }}</h1>
        <h2>BERITA ACARA & NOTULEN RAPAT KEDINASAN</h2>
        <p>Sistem Informasi Manajemen Sekolah Terpadu (Paperless Meeting Record)</p>
    </div>

    <!-- JUDUL DAN NOMOR BAP -->
    <div class="judul-dokumen">
        <h3>BERITA ACARA RAPAT</h3>
        <p>Nomor: {{ $meeting->meeting_number }}</p>
    </div>

    <p style="margin-bottom: 8px;">
        Pada hari ini, <strong>{{ $meeting->meeting_date->translatedFormat('l') }}</strong> tanggal <strong>{{ $meeting->meeting_date->translatedFormat('d F Y') }}</strong>, telah diselenggarakan rapat kedinasan dengan rincian sebagai berikut:
    </p>

    <!-- METADATA RAPAT -->
    <table class="meta-table">
        <tr>
            <td class="label">Nama / Agenda Rapat</td>
            <td class="sep">:</td>
            <td><strong>{{ $meeting->title }}</strong></td>
        </tr>
        <tr>
            <td class="label">Jenis Rapat</td>
            <td class="sep">:</td>
            <td>{{ $meeting->type_label }}</td>
        </tr>
        <tr>
            <td class="label">Waktu Pelaksanaan</td>
            <td class="sep">:</td>
            <td>Pukul {{ $meeting->start_time }} WIB {{ $meeting->end_time ? 's/d ' . $meeting->end_time . ' WIB' : 's/d Selesai' }}</td>
        </tr>
        <tr>
            <td class="label">Tempat / Ruang</td>
            <td class="sep">:</td>
            <td>{{ $meeting->location }}</td>
        </tr>
        <tr>
            <td class="label">Pimpinan Rapat</td>
            <td class="sep">:</td>
            <td><strong>{{ $meeting->leader_name ?: ($meeting->leaderUser?->name ?? 'Kepala Sekolah') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Notulis / Sekretaris</td>
            <td class="sep">:</td>
            <td>{{ $meeting->secretary_name ?: ($meeting->secretaryUser?->name ?? 'Staf Tata Usaha') }}</td>
        </tr>
    </table>

    <!-- AGENDA PEMBAHASAN -->
    @if ($meeting->agenda)
    <div class="section-title">I. POKOK BAHASAN & AGENDA</div>
    <div class="content-box">
        {!! nl2br(e($meeting->agenda)) !!}
    </div>
    @endif

    <!-- NOTULEN & JALANNYA RAPAT -->
    @if ($meeting->notes)
    <div class="section-title">II. JALANNYA RAPAT & NOTULEN RESMI</div>
    <div class="content-box" style="white-space: pre-line;">
        {{ $meeting->notes }}
    </div>
    @endif

    <!-- KEPUTUSAN / KESIMPULAN -->
    @if ($meeting->decisions)
    <div class="section-title">III. KEPUTUSAN & KESEPAKATAN RAPAT</div>
    <div class="content-box" style="white-space: pre-line; background: #f0fdf4; border-color: #bbf7d0; font-weight: bold; color: #166534;">
        {{ $meeting->decisions }}
    </div>
    @endif

    <!-- TINDAK LANJUT / ACTION ITEMS -->
    @if (!empty($meeting->action_items) && count($meeting->action_items) > 0)
    <div class="section-title">IV. TINDAK LANJUT (*ACTION ITEMS & PIC*)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 50%;">Tugas / Tindak Lanjut</th>
                <th style="width: 25%;">Penanggung Jawab (PIC)</th>
                <th style="width: 20%;" class="center">Tenggat Waktu</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($meeting->action_items as $idx => $item)
            <tr>
                <td class="center">{{ $idx + 1 }}</td>
                <td>{{ $item['task'] ?? '-' }}</td>
                <td><strong>{{ $item['pic'] ?? '-' }}</strong></td>
                <td class="center">{{ $item['due_date'] ?? 'Segera' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- LAMPIRAN DAFTAR HADIR DENGAN TANDA TANGAN DIGITAL -->
    <div class="section-title" style="margin-top: 14px;">V. LAMPIRAN DAFTAR HADIR PESERTA RAPAT</div>
    <p style="font-size: 8px; color: #64748b; margin: 0 0 4px;">
        Rekapitulasi: Hadir: <strong>{{ $stats['hadir'] }}</strong> orang | Izin: <strong>{{ $stats['izin'] }}</strong> orang | Sakit: <strong>{{ $stats['sakit'] }}</strong> orang | Alpa: <strong>{{ $stats['alpa'] }}</strong> orang. Total: <strong>{{ $stats['total'] }}</strong> peserta.
    </p>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="center">No</th>
                <th style="width: 32%;">Nama Lengkap</th>
                <th style="width: 23%;">Peran / Jabatan</th>
                <th style="width: 12%;" class="center">Kehadiran</th>
                <th style="width: 13%;" class="center">Waktu Presensi</th>
                <th style="width: 15%;" class="center">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendances as $idx => $att)
            <tr>
                <td class="center">{{ $idx + 1 }}</td>
                <td><strong>{{ $att->participant_name }}</strong></td>
                <td>{{ $att->participant_role ?: 'Peserta' }}</td>
                <td class="center badge">
                    @if ($att->status === 'hadir')
                        <span style="color: #166534;">HADIR</span>
                    @elseif ($att->status === 'izin')
                        <span style="color: #b45309;">IZIN</span>
                    @elseif ($att->status === 'sakit')
                        <span style="color: #2563eb;">SAKIT</span>
                    @else
                        <span style="color: #dc2626;">ALPA</span>
                    @endif
                </td>
                <td class="center" style="font-size: 7.5px;">
                    {{ $att->signed_at ? $att->signed_at->format('H:i') . ' WIB' : '-' }}
                </td>
                <td class="center" style="height: 28px; padding: 1px;">
                    @if ($att->signature)
                        <img src="{{ $att->signature }}" class="signature-img" alt="TTD" />
                    @else
                        <span style="color: #94a3b8; font-size: 7px; font-style: italic;">(Belum TTD)</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="center" style="color: #94a3b8; font-style: italic;">Belum ada peserta yang mengisi presensi.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- TANDA TANGAN PENGESAHAN DOKUMEN -->
    <table class="ttd-box">
        <tr>
            <td>
                Mengetahui / Notulis Rapat,
                <div class="ttd-space"></div>
                <strong><u>{{ $meeting->secretary_name ?: ($meeting->secretaryUser?->name ?? 'Sekretaris Rapat') }}</u></strong>
                <br>
                <span style="font-size: 8px; color: #64748b;">Notulis / Staf Administrasi</span>
            </td>
            <td>
                Ditetapkan di {{ $meeting->location }}, {{ $meeting->meeting_date->translatedFormat('d F Y') }}<br>
                Pimpinan Rapat,
                <div class="ttd-space">
                    @if ($meeting->leader_signature)
                        <img src="{{ $meeting->leader_signature }}" class="signature-img" style="max-height: 40px; margin: 4px auto 0;" alt="TTD Pimpinan" />
                    @endif
                </div>
                <strong><u>{{ $meeting->leader_name ?: ($meeting->leaderUser?->name ?? 'Kepala Sekolah') }}</u></strong>
                <br>
                <span style="font-size: 8px; color: #64748b;">Pimpinan Rapat Kedinasan</span>
            </td>
        </tr>
    </table>
</body>
</html>

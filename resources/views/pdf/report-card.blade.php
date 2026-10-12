<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>e-Raport {{ $student->full_name }}</title>
    <style>
        @page { margin: 18mm 15mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        .kop { text-align: center; border-bottom: 2px solid #1e293b; padding-bottom: 8px; margin-bottom: 14px; }
        .kop h1 { font-size: 14px; margin: 0 0 2px; text-transform: uppercase; }
        .kop p { font-size: 9px; margin: 0; color: #475569; }
        .judul { text-align: center; font-size: 12px; font-weight: bold; margin: 0 0 12px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        .identitas td { padding: 2px 0; font-size: 10px; vertical-align: top; }
        .identitas .label { width: 28%; color: #475569; }
        .identitas .sep { width: 3%; }
        .nilai { margin-top: 10px; }
        .nilai th, .nilai td { border: 1px solid #94a3b8; padding: 4px 5px; }
        .nilai th { background: #f1f5f9; font-size: 9px; text-transform: uppercase; }
        .nilai td.c { text-align: center; }
        .nilai tfoot td { background: #f8fafc; font-weight: bold; }
        .badge-tuntas { color: #166534; font-weight: bold; }
        .badge-remedial { color: #b91c1c; font-weight: bold; }
        .blok { margin-top: 12px; border: 1px solid #94a3b8; padding: 7px 9px; }
        .blok h3 { font-size: 10px; margin: 0 0 4px; text-transform: uppercase; color: #475569; }
        .ttd { margin-top: 22px; width: 100%; }
        .ttd td { width: 50%; text-align: center; font-size: 10px; vertical-align: top; }
        .ttd .ruang { height: 48px; }
        .kaki { margin-top: 14px; font-size: 8px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="kop">
        <h1>{{ $schoolName }}</h1>
        <p>Laporan Hasil Belajar Peserta Didik</p>
    </div>

    <p class="judul">Rapor Semester {{ ucfirst($academicYear->semester) }} — Tahun Ajaran {{ $academicYear->name }}</p>

    <table class="identitas">
        <tr>
            <td class="label">Nama Peserta Didik</td><td class="sep">:</td>
            <td><strong>{{ $student->full_name ?? $student->user?->name }}</strong></td>
        </tr>
        <tr>
            <td class="label">NISN</td><td class="sep">:</td>
            <td>{{ $student->nisn ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Rombongan Belajar</td><td class="sep">:</td>
            <td>{{ $student->classroom?->name ?? '-' }}
                @if ($student->classroom?->department)
                    ({{ $student->classroom->department->name }})
                @endif
            </td>
        </tr>
    </table>

    <table class="nilai">
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th>Mata Pelajaran</th>
                <th style="width:7%">KKM</th>
                <th style="width:8%">Tugas</th>
                <th style="width:8%">Harian</th>
                <th style="width:7%">UTS</th>
                <th style="width:7%">UAS</th>
                <th style="width:9%">Nilai Akhir</th>
                <th style="width:8%">Predikat</th>
                <th style="width:11%">Ketuntasan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $i => $row)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $row['name'] }} <span style="color:#94a3b8">({{ $row['code'] }})</span></td>
                    <td class="c">{{ $row['kkm'] }}</td>
                    <td class="c">{{ number_format($row['assignment'], 0) }}</td>
                    <td class="c">{{ number_format($row['quiz'], 0) }}</td>
                    <td class="c">{{ number_format($row['midterm'], 0) }}</td>
                    <td class="c">{{ number_format($row['final'], 0) }}</td>
                    <td class="c"><strong>{{ number_format($row['overall'], 2) }}</strong></td>
                    <td class="c">{{ $row['letter'] }}</td>
                    <td class="c {{ $row['is_passed'] ? 'badge-tuntas' : 'badge-remedial' }}">
                        {{ $row['is_passed'] ? 'Tuntas' : 'Remedial' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="c" colspan="10" style="padding:14px; color:#94a3b8">
                        Belum ada nilai yang diinput untuk tahun ajaran ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($scoredCount > 0)
            <tfoot>
                <tr>
                    <td class="c" colspan="7">Rata-rata Nilai Akhir</td>
                    <td class="c">{{ number_format($average, 2) }}</td>
                    <td class="c" colspan="2">{{ $passedCount }} / {{ $scoredCount }} mapel tuntas</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="blok">
        <h3>Ketidakhadiran</h3>
        <table>
            <tr>
                <td style="width:33%">Sakit: <strong>{{ $remark->attendance_sick ?? 0 }}</strong> hari</td>
                <td style="width:33%">Izin: <strong>{{ $remark->attendance_permission ?? 0 }}</strong> hari</td>
                <td>Tanpa Keterangan: <strong>{{ $remark->attendance_unexcused ?? 0 }}</strong> hari</td>
            </tr>
        </table>
    </div>

    @if ($remark?->extracurriculars)
        <div class="blok">
            <h3>Ekstrakurikuler</h3>
            @foreach ((array) $remark->extracurriculars as $eskul)
                <div>&bull;
                    @if (is_array($eskul))
                        {{ $eskul['name'] ?? '-' }}
                        @isset($eskul['predicate']) — {{ $eskul['predicate'] }} @endisset
                    @else
                        {{ $eskul }}
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="blok">
        <h3>Catatan Wali Kelas</h3>
        <div>{{ $remark->homeroom_notes ?? 'Belum ada catatan wali kelas.' }}</div>
    </div>

    <div class="blok">
        <h3>Keputusan</h3>
        <div>
            @switch($remark->promotion_status ?? 'dalam_proses')
                @case('naik_kelas') Naik ke kelas berikutnya @break
                @case('tinggal_kelas') Tinggal di kelas yang sama @break
                @case('lulus') Dinyatakan LULUS @break
                @case('belum_lulus') Dinyatakan BELUM LULUS @break
                @default Masih dalam proses penetapan
            @endswitch
        </div>
    </div>

    <table class="ttd">
        <tr>
            <td>
                Orang Tua / Wali
                <div class="ruang"></div>
                <div>__________________________</div>
            </td>
            <td>
                {{ $printedAt->translatedFormat('d F Y') }}<br>
                Wali Kelas
                <div class="ruang"></div>
                <div>__________________________</div>
            </td>
        </tr>
    </table>

    <p class="kaki">
        Dokumen ini dicetak dari {{ $schoolName }} pada {{ $printedAt->format('d/m/Y H:i') }} WIB.
    </p>
</body>
</html>

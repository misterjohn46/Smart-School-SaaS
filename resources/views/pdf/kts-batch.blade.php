<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Massal Kartu Tanda Siswa</title>
    @php
        $primary = $template->primary_color ?? '#4f46e5';
        $secondary = $template->secondary_color ?? '#f59e0b';
    @endphp
    <style>
        /*
         | Ukuran kartu mengikuti ISO/IEC 7810 ID-1 (CR-80): 85.6mm x 54mm.
         | Semua satuan sengaja memakai milimeter agar hasil cetak presisi.
         */
        @page { margin: 10mm 9mm; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; }

        .kartu {
            width: 85.6mm;
            height: 54mm;
            border: 0.2mm dashed #cbd5e1;
            float: left;
            margin: 0 2mm 3mm 0;
            position: relative;
            overflow: hidden;
        }
        .kartu-header {
            height: 11mm;
            background: {{ $primary }};
            color: #ffffff;
            padding: 1.8mm 3mm 0;
        }
        .kartu-header .sekolah { font-size: 7pt; font-weight: bold; text-transform: uppercase; }
        .kartu-header .sub { font-size: 5pt; opacity: 0.85; }
        .aksen { height: 1.2mm; background: {{ $secondary }}; }

        .kartu-isi { padding: 2.2mm 3mm; }
        .nama { font-size: 8pt; font-weight: bold; color: #0f172a; }
        .baris { font-size: 6pt; color: #334155; margin-top: 0.8mm; }
        .baris span { color: #64748b; }

        .nisn-box {
            position: absolute;
            left: 3mm;
            bottom: 2.4mm;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 0.3mm;
            color: {{ $primary }};
        }
        .rfid-box {
            position: absolute;
            right: 3mm;
            bottom: 2.4mm;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 5pt;
            color: #94a3b8;
        }
        .chip {
            position: absolute;
            right: 3mm;
            top: 14mm;
            width: 9mm;
            height: 7mm;
            background: {{ $secondary }};
            border-radius: 1mm;
            opacity: 0.85;
        }

        .halaman-kaki {
            clear: both;
            padding-top: 2mm;
            font-size: 6pt;
            color: #94a3b8;
            text-align: center;
        }
        .pemisah { page-break-after: always; }
    </style>
</head>
<body>
    @foreach ($pages as $pageIndex => $cards)
        <div class="{{ ! $loop->last ? 'pemisah' : '' }}">
            @foreach ($cards as $student)
                <div class="kartu">
                    <div class="kartu-header">
                        <div class="sekolah">{{ \Illuminate\Support\Str::limit($schoolName, 34) }}</div>
                        <div class="sub">Kartu Tanda Siswa</div>
                    </div>
                    <div class="aksen"></div>

                    @if ($template->show_rfid_chip ?? true)
                        <div class="chip"></div>
                    @endif

                    <div class="kartu-isi">
                        <div class="nama">{{ \Illuminate\Support\Str::limit($student->full_name ?? $student->user?->name ?? 'Siswa', 30) }}</div>
                        <div class="baris"><span>Kelas:</span> {{ $student->classroom?->name ?? '-' }}</div>
                        <div class="baris"><span>Jurusan:</span> {{ \Illuminate\Support\Str::limit($student->classroom?->department?->name ?? '-', 28) }}</div>
                    </div>

                    <div class="nisn-box">{{ $student->nisn ?: '-' }}</div>
                    <div class="rfid-box">{{ $student->rfid_uid ?: 'RFID belum dipasangkan' }}</div>
                </div>
            @endforeach

            <div class="halaman-kaki">
                Halaman {{ $pageIndex + 1 }} dari {{ $pages->count() }} &bull;
                Ukuran kartu 85,6mm &times; 54mm (ISO CR-80) &bull;
                Dicetak {{ $printedAt->format('d/m/Y H:i') }} WIB
            </div>
        </div>
    @endforeach
</body>
</html>

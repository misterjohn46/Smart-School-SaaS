@php
    $data = $this->getRecentActivities();
    $attendances = $data['attendances'];
    $payments = $data['payments'];
@endphp

<style>
    .sra-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }
    @media (min-width: 1024px) {
        .sra-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    .sra-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
        padding: 1.25rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
    }
    .dark .sra-card {
        background: #0f172a;
        border-color: #1e293b;
    }
    .sra-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 0.875rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .dark .sra-header {
        border-color: #1e293b;
    }
    .sra-icon {
        width: 2.25rem;
        height: 2.25rem;
        min-width: 2.25rem;
        min-height: 2.25rem;
        border-radius: 0.625rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .sra-icon svg {
        width: 1.25rem !important;
        height: 1.25rem !important;
        min-width: 1.25rem !important;
        max-width: 1.25rem !important;
        display: block !important;
    }
    .sra-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f8fafc;
    }
    .dark .sra-item {
        border-color: #1e293b;
    }
    .sra-item:last-child {
        border-bottom: none;
    }
    .sra-avatar {
        width: 2rem;
        height: 2rem;
        min-width: 2rem;
        min-height: 2rem;
        border-radius: 9999px;
        background: #f1f5f9;
        color: #334155;
        font-weight: 700;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .dark .sra-avatar {
        background: #1e293b;
        color: #cbd5e1;
    }
</style>

<div class="sra-grid">
    <!-- Kolom 1: Presensi Tap RFID Terkini -->
    <div class="sra-card">
        <div class="sra-header">
            <div style="display:flex; align-items:center; gap:0.65rem;">
                <span class="sra-icon" style="background:#ecfdf5; color:#059669;">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
                <div>
                    <h4 style="font-size:0.875rem; font-weight:700; color:#0f172a; margin:0;" class="dark:text-white">Aktivitas Presensi Terkini</h4>
                    <p style="font-size:0.75rem; color:#64748b; margin:0.15rem 0 0 0;" class="dark:text-slate-400">Live tap gerbang RFID & presensi mandiri</p>
                </div>
            </div>
            <a href="/admin/attendances" style="font-size:0.75rem; font-weight:600; color:#4f46e5; text-decoration:none;">Lihat Semua</a>
        </div>

        <div style="margin-top:0.5rem;">
            @forelse ($attendances as $att)
                <div class="sra-item">
                    <div style="display:flex; align-items:center; gap:0.65rem;">
                        <div class="sra-avatar">
                            {{ substr($att->user?->name ?? 'S', 0, 1) }}
                        </div>
                        <div>
                            <p style="font-size:0.8125rem; font-weight:600; color:#0f172a; margin:0;" class="dark:text-white">{{ $att->user?->name ?? 'Siswa' }}</p>
                            <p style="font-size:0.7rem; color:#64748b; margin:0.1rem 0 0 0;" class="dark:text-slate-400">{{ $att->device?->device_name ?? $att->device_name ?? 'Gerbang Utama' }} • {{ $att->time_out ? 'Pulang' : 'Masuk' }}</p>
                        </div>
                    </div>
                    @php $lastTap = $att->time_out ?: $att->time_in; @endphp
                    <div style="text-align:right;">
                        <span style="font-size:0.7rem; font-weight:600; padding:0.2rem 0.5rem; border-radius:0.375rem; background:{{ $att->status === 'hadir' ? '#d1fae5' : '#fef3c7' }}; color:{{ $att->status === 'hadir' ? '#065f46' : '#92400e' }};">
                            {{ $att->status === 'hadir' ? 'Hadir Tepat Waktu' : ucfirst($att->status) }}
                        </span>
                        <p style="font-size:0.7rem; color:#94a3b8; font-family:monospace; margin:0.15rem 0 0 0;">{{ $lastTap ? \Carbon\Carbon::parse($lastTap)->format('H:i:s') : '-' }}</p>
                    </div>
                </div>
            @empty
                <div style="padding:2rem 0; text-align:center;">
                    <p style="font-size:0.75rem; color:#94a3b8; margin:0;">Belum ada aktivitas tap kartu presensi hari ini.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Kolom 2: Transaksi Kasir SPP Terkini -->
    <div class="sra-card">
        <div class="sra-header">
            <div style="display:flex; align-items:center; gap:0.65rem;">
                <span class="sra-icon" style="background:#eef2ff; color:#4f46e5;">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v8.25m0-8.25h19.5m0 0v8.25m0-8.25A2.25 2.25 0 0 0 19.5 4.5h-15A2.25 2.25 0 0 0 2.25 6.75m19.5 0v8.25m0 0a2.25 2.25 0 0 1-2.25 2.25H4.5A2.25 2.25 0 0 1 2.25 15" />
                    </svg>
                </span>
                <div>
                    <h4 style="font-size:0.875rem; font-weight:700; color:#0f172a; margin:0;" class="dark:text-white">Penerimaan Kas & SPP Terkini</h4>
                    <p style="font-size:0.75rem; color:#64748b; margin:0.15rem 0 0 0;" class="dark:text-slate-400">Setoran loket kasir & mutasi terverifikasi</p>
                </div>
            </div>
            <a href="/admin/payments" style="font-size:0.75rem; font-weight:600; color:#4f46e5; text-decoration:none;">Lihat Semua</a>
        </div>

        <div style="margin-top:0.5rem;">
            @forelse ($payments as $pay)
                <div class="sra-item">
                    <div style="display:flex; align-items:center; gap:0.65rem;">
                        <div class="sra-avatar" style="background:#ecfdf5; color:#059669;">
                            Rp
                        </div>
                        <div>
                            <p style="font-size:0.8125rem; font-weight:600; color:#0f172a; margin:0;" class="dark:text-white">{{ $pay->studentProfile?->full_name ?? 'Siswa' }}</p>
                            <p style="font-size:0.7rem; color:#64748b; margin:0.1rem 0 0 0;" class="dark:text-slate-400">{{ $pay->payment_number }} • {{ ucfirst($pay->payment_method) }}</p>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <p style="font-size:0.8125rem; font-weight:700; color:#059669; font-family:monospace; margin:0;">Rp {{ number_format((float) $pay->amount, 0, ',', '.') }}</p>
                        <p style="font-size:0.7rem; color:#94a3b8; margin:0.15rem 0 0 0;">{{ $pay->created_at ? $pay->created_at->diffForHumans() : '-' }}</p>
                    </div>
                </div>
            @empty
                <div style="padding:2rem 0; text-align:center;">
                    <p style="font-size:0.75rem; color:#94a3b8; margin:0;">Belum ada transaksi pembayaran kasir masuk.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

@php
    $school = $this->getSchoolData();
@endphp

<style>
    .sh-hero-card {
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
        border-radius: 1.25rem;
        padding: 1.75rem 2rem;
        color: #ffffff;
        box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.35);
        border: 1px solid rgba(99, 102, 241, 0.3);
        position: relative;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }
    .sh-hero-content {
        position: relative;
        z-index: 10;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 1.5rem;
    }
    @media (min-width: 1024px) {
        .sh-hero-content {
            flex-direction: row;
            align-items: center;
        }
    }
    .sh-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.85rem;
        border-radius: 9999px;
        background: rgba(99, 102, 241, 0.18);
        border: 1px solid rgba(129, 140, 248, 0.35);
        font-size: 0.75rem;
        font-weight: 600;
        color: #c7d2fe;
        margin-bottom: 0.5rem;
    }
    .sh-dot {
        width: 0.5rem;
        height: 0.5rem;
        border-radius: 9999px;
        background: #10b981;
        box-shadow: 0 0 8px #10b981;
    }
    .sh-title {
        font-size: 1.75rem;
        font-weight: 800;
        color: #ffffff;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.85rem;
        letter-spacing: -0.02em;
    }
    .sh-icon-box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 3rem;
        height: 3rem;
        min-width: 3rem;
        min-height: 3rem;
        border-radius: 0.85rem;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
    }
    .sh-icon-box svg {
        width: 1.75rem !important;
        height: 1.75rem !important;
        min-width: 1.75rem !important;
        max-width: 1.75rem !important;
        color: #ffffff !important;
        display: block !important;
    }
    .sh-desc {
        font-size: 0.875rem;
        color: #94a3b8;
        margin-top: 0.35rem;
        line-height: 1.4;
    }
    .sh-desc strong {
        color: #ffffff;
    }
    .sh-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    .sh-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.25rem 0.75rem;
        border-radius: 0.5rem;
        background: rgba(15, 23, 42, 0.8);
        border: 1px solid rgba(51, 65, 85, 0.8);
        font-size: 0.75rem;
        color: #cbd5e1;
    }
    .sh-pill svg {
        width: 0.95rem !important;
        height: 0.95rem !important;
        min-width: 0.95rem !important;
        max-width: 0.95rem !important;
        display: inline-block !important;
    }
    .sh-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        align-items: center;
        flex-shrink: 0;
    }
    .sh-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.15rem;
        border-radius: 0.75rem;
        font-size: 0.8125rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        color: #ffffff !important;
        box-sizing: border-box;
    }
    .sh-btn svg {
        width: 1.1rem !important;
        height: 1.1rem !important;
        min-width: 1.1rem !important;
        max-width: 1.1rem !important;
        display: block !important;
    }
    .sh-btn:hover {
        transform: translateY(-2px);
    }
    .sh-btn-emerald {
        background: #059669;
        box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
    }
    .sh-btn-emerald:hover {
        background: #10b981;
    }
    .sh-btn-indigo {
        background: #4f46e5;
        box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
    }
    .sh-btn-indigo:hover {
        background: #6366f1;
    }
    .sh-btn-slate {
        background: #1e293b;
        border: 1px solid #334155;
    }
    .sh-btn-slate:hover {
        background: #334155;
    }
</style>

<div class="sh-hero-card">
    <div class="sh-hero-content">
        <!-- School Identity -->
        <div>
            <div class="sh-badge">
                <span class="sh-dot"></span>
                Smart Digital School Enterprise • Portal Administrasi
            </div>

            <h2 class="sh-title">
                <span class="sh-icon-box">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                    </svg>
                </span>
                {{ $school['school_name'] }}
            </h2>

            <p class="sh-desc">
                Selamat bertugas, <strong>{{ $school['admin_name'] }}</strong>. Seluruh modul akademik, presensi RFID, keuangan SPP, dan e-kantin siap digunakan.
            </p>

            <div class="sh-pills">
                <span class="sh-pill">
                    <svg style="color: #818cf8;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
                    </svg>
                    NPSN: <strong style="color:#ffffff; margin-left:2px;">{{ $school['npsn'] }}</strong>
                </span>

                <span class="sh-pill">
                    <svg style="color: #34d399;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.253M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    T.A: <strong style="color:#ffffff; margin-left:2px;">{{ $school['academic_year'] }}</strong>
                </span>

                <span class="sh-pill">
                    <svg style="color: #fbbf24;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    {{ $school['today_formatted'] }}
                </span>
            </div>
        </div>

        <!-- Action Launchpad -->
        <div class="sh-actions">
            <a href="/finance/pos-kasir" class="sh-btn sh-btn-emerald">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v8.25m0-8.25h19.5m0 0v8.25m0-8.25A2.25 2.25 0 0 0 19.5 4.5h-15A2.25 2.25 0 0 0 2.25 6.75m19.5 0v8.25m0 0a2.25 2.25 0 0 1-2.25 2.25H4.5A2.25 2.25 0 0 1 2.25 15" />
                </svg>
                Loket SPP & Kasir
            </a>

            <a href="/kts/batch-print" class="sh-btn sh-btn-indigo">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                </svg>
                Cetak KTS Digital
            </a>

            <a href="/attendance/gps-face" class="sh-btn sh-btn-slate">
                <svg style="color: #f43f5e;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </svg>
                Presensi GPS
            </a>
        </div>
    </div>
</div>

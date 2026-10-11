@php
    $actions = $this->getQuickActions();
@endphp

<style>
    .sq-section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
        margin-top: 1rem;
    }
    .dark .sq-section-title {
        color: #f8fafc;
    }
    .sq-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 1rem;
    }
    @media (min-width: 768px) {
        .sq-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (min-width: 1200px) {
        .sq-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    .sq-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
        padding: 1.25rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: all 0.2s ease;
    }
    .dark .sq-card {
        background: #0f172a;
        border-color: #1e293b;
    }
    .sq-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
        border-color: #cbd5e1;
    }
    .sq-icon-wrap {
        width: 2.75rem;
        height: 2.75rem;
        min-width: 2.75rem;
        min-height: 2.75rem;
        border-radius: 0.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .sq-icon-wrap svg {
        width: 1.5rem !important;
        height: 1.5rem !important;
        min-width: 1.5rem !important;
        max-width: 1.5rem !important;
        display: block !important;
    }
    .sq-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }
    .sq-badge {
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
    }
    .sq-title {
        font-size: 0.9375rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    .dark .sq-title {
        color: #ffffff;
    }
    .sq-desc {
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 0.35rem;
        line-height: 1.4;
    }
    .dark .sq-desc {
        color: #94a3b8;
    }
    .sq-footer {
        margin-top: 1rem;
        padding-top: 0.75rem;
        border-top: 1px solid #f1f5f9;
    }
    .dark .sq-footer {
        border-color: #1e293b;
    }
    .sq-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.375rem;
        padding: 0.5rem 0.875rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-decoration: none;
        transition: background 0.15s ease;
        color: #ffffff !important;
        box-sizing: border-box;
    }
    .sq-btn svg {
        width: 0.875rem !important;
        height: 0.875rem !important;
        min-width: 0.875rem !important;
        max-width: 0.875rem !important;
    }
</style>

<div>
    <div class="sq-section-title">
        <svg style="width:1.25rem; height:1.25rem; color:#6366f1;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
        </svg>
        Pusat Akses Cepat Layanan Sekolah
    </div>

    <div class="sq-grid">
        @foreach ($actions as $action)
            @php
                $theme = match($action['color']) {
                    'emerald' => [
                        'bg' => '#ecfdf5',
                        'text' => '#059669',
                        'badge_bg' => '#d1fae5',
                        'badge_text' => '#065f46',
                        'btn_bg' => '#059669',
                    ],
                    'indigo' => [
                        'bg' => '#eef2ff',
                        'text' => '#4f46e5',
                        'badge_bg' => '#e0e7ff',
                        'badge_text' => '#3730a3',
                        'btn_bg' => '#4f46e5',
                    ],
                    'rose' => [
                        'bg' => '#fff1f2',
                        'text' => '#e11d48',
                        'badge_bg' => '#ffe4e6',
                        'badge_text' => '#9f1239',
                        'btn_bg' => '#e11d48',
                    ],
                    'amber' => [
                        'bg' => '#fffbeb',
                        'text' => '#d97706',
                        'badge_bg' => '#fef3c7',
                        'badge_text' => '#92400e',
                        'btn_bg' => '#d97706',
                    ],
                    'cyan' => [
                        'bg' => '#ecfeff',
                        'text' => '#0891b2',
                        'badge_bg' => '#cffafe',
                        'badge_text' => '#155e75',
                        'btn_bg' => '#0891b2',
                    ],
                    default => [
                        'bg' => '#faf5ff',
                        'text' => '#9333ea',
                        'badge_bg' => '#f3e8ff',
                        'badge_text' => '#6b21a8',
                        'btn_bg' => '#9333ea',
                    ],
                };
            @endphp

            <div class="sq-card">
                <div>
                    <div class="sq-header">
                        <div class="sq-icon-wrap" style="background-color: {{ $theme['bg'] }}; color: {{ $theme['text'] }};">
                            @if ($action['icon'] === 'banknotes')
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v8.25m0-8.25h19.5m0 0v8.25m0-8.25A2.25 2.25 0 0 0 19.5 4.5h-15A2.25 2.25 0 0 0 2.25 6.75m19.5 0v8.25m0 0a2.25 2.25 0 0 1-2.25 2.25H4.5A2.25 2.25 0 0 1 2.25 15" />
                                </svg>
                            @elseif ($action['icon'] === 'identification')
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
                                </svg>
                            @elseif ($action['icon'] === 'map-pin')
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                            @elseif ($action['icon'] === 'wallet')
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" />
                                </svg>
                            @elseif ($action['icon'] === 'cake')
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-.778.099-1.533.284-2.253" />
                                </svg>
                            @else
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                                </svg>
                            @endif
                        </div>
                        <span class="sq-badge" style="background-color: {{ $theme['badge_bg'] }}; color: {{ $theme['badge_text'] }};">
                            {{ $action['badge'] }}
                        </span>
                    </div>

                    <h4 class="sq-title">{{ $action['title'] }}</h4>
                    <p class="sq-desc">{{ $action['description'] }}</p>
                </div>

                <div class="sq-footer">
                    <a href="{{ $action['url'] }}" class="sq-btn" style="background-color: {{ $theme['btn_bg'] }};">
                        <span>{{ $action['btn_text'] }}</span>
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>

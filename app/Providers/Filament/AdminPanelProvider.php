<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\DashboardOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView; // Tambahan untuk merender CSS kustom
use Filament\View\PanelsRenderHook;        // Tambahan untuk target posisi CSS
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login()
            ->brandName('SIAKAD-ORMAWA')
            ->colors([
                'primary' => \Filament\Support\Colors\Color::Blue, // Mengubah aksen tombol menjadi biru elegan
            ])
            ->font('Poppins')
            ->darkMode(true) // Mengaktifkan fitur dark mode agar pas dengan background gelap
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \Filament\Widgets\AccountWidget::class, // Kotak selamat datang user
                DashboardOverview::class,              // Kotak statistik proposal & LPJ Anda
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Jalankan proses kustomisasi style CSS saat aplikasi memuat panel admin
     */
    public function boot(): void
    {
        // 1. Menyuntikkan CSS untuk layout Split-Screen dengan Batas Gelombang Mulus (Base64 Secured)
        FilamentView::registerRenderHook(
            'panels::head.end',
            fn(): string => Blade::render('
                <style>
                    /* ==========================================================================
                    1. SETTINGAN GLOBAL & ANIMASI
                    ========================================================================== */
                    @keyframes gradientFlow {
                        0% { background-position: 0% 50%; }
                        50% { background-position: 100% 50%; }
                        100% { background-position: 0% 50%; }
                    }

                    /* Teks branding kiri disembunyikan secara default (di mobile tidak muncul) */
                    .custom-left-branding {
                        display: none;
                    }

                    /* ==========================================================================
                    2. SETTINGAN UNTUK MOBILE / TABLET (Default Layar Kecil)
                    ========================================================================== */
                    /* Mengatur container utama di HP agar fleksibel */
                    .fi-simple-layout {
                        display: flex !important;
                        flex-direction: column !important;
                        min-height: 100vh !important;
                        background: #090d16 !important;
                        padding: 1.5rem !important; /* Jarak aman dari pinggiran layar HP */
                        justify-content: center !important;
                    }

                    /* Mengatur Box Form di HP agar memiliki jarak kanan-kiri yang aman (90%) */
                    .fi-simple-layout > .max-w-md,
                    .fi-simple-layout > div:not(.custom-left-branding) {
                        margin: auto !important;
                        width: 100% !important;
                        max-width: 90% !important; /* Kunci agar di HP tidak mentok ke kaca */
                        padding: 0 !important;
                        z-index: 10 !important;
                    }

                    /* Mengubah Card Form bawaan menjadi transparan matte (Glassmorphism) */
                    .fi-simple-main-card {
                        background: rgba(15, 23, 42, 0.5) !important;
                        backdrop-filter: blur(10px) !important;
                        border: 1px solid rgba(255, 255, 255, 0.05) !important;
                        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
                        border-radius: 16px !important;
                        padding: 2rem 1.5rem !important;
                    }

                    /* Memastikan warna text label input Filament cerah dan kontras */
                    .fi-simple-main-card label {
                        color: #e2e8f0 !important;
                    }


                    /* ==========================================================================
                    3. SETTINGAN UNTUK DESKTOP (Layar Lebar >= 1024px)
                    ========================================================================== */
                    @media (min-width: 1024px) {
                        /* Mengubah kontainer utama login menjadi split screen belah dua */
                        .fi-simple-layout {
                            display: grid !important;
                            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                            padding: 0 !important;
                            position: relative !important;
                            overflow: hidden !important;
                        }

                        /* Memaksa pembungkus form login berada pas di grid sisi kanan (Kolom 2) */
                        .fi-simple-layout > .max-w-md,
                        .fi-simple-layout > div:not(.custom-left-branding) {
                            grid-column: 2 !important;
                            max-width: rem !important; /* Lebar form ideal di desktop, tidak ciut & tidak melar */
                            padding: 2.5rem !important;
                        }

                        /* MEMBUAT GELOMBANG DI TENGAH: Memotong background kiri menjadi kurva gelombang organik */
                        .fi-simple-layout::before {
                            content: "" !important;
                            display: block !important;
                            background: linear-gradient(-45deg, #0f172a, #1e3a8a, #0d9488, #111827) !important;
                            background-size: 400% 400% !important;
                            animation: gradientFlow 15s ease infinite !important;

                            position: absolute !important;
                            top: 0 !important;
                            left: 0 !important;
                            height: 100% !important;
                            width: 56% !important; /* Overlap sedikit ke kanan untuk area gelombang */
                            z-index: 1 !important;

                            /* Menggunakan SVG Mask kurva bezier melengkung halus (Aman dari Syntax Error) */
                            -webkit-mask-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIiBwcmVzZXJ2ZUFzcGVjdFJhdGlvPSJub25lIj48cGF0aCBkPSJNMCAwIEw4MCAwIEM5MCAzNSwgOTUgNjUsIDc1IDEwMCBMMCAxMDAgWiIgZmlsbD0iYmxhY2siLz48L3N2Zz4=") !important;
                            mask-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIiBwcmVzZXJ2ZUFzcGVjdFJhdGlvPSJub25lIj48cGF0aCBkPSJNMCAwIEw4MCAwIEM5MCAzNSwgOTUgNjUsIDc1IDEwMCBMMCAxMDAgWiIgZmlsbD0iYmxhY2siLz48L3N2Zz4=") !important;
                            -webkit-mask-size: 100% 100% !important;
                            mask-size: 100% 100% !important;
                        }

                        /* Styling teks judul di sisi kiri (Hanya muncul di desktop) */
                        .custom-left-branding {
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: flex-start;
                            position: absolute;
                            top: 0;
                            left: 0;
                            width: 42%; /* Memastikan teks aman di dalam lekukan gelombang */
                            height: 100%;
                            padding: 5rem;
                            color: #ffffff;
                            text-align: left;
                            pointer-events: none;
                            z-index: 10;
                        }

                        .custom-title {
                            font-size: 1.75rem;
                            font-weight: 800;
                            line-height: 1.4;
                            max-width: 100%;
                            margin-bottom: 1.5rem;
                            letter-spacing: -0.02em;
                            background: linear-gradient(to bottom right, #ffffff, #cbd5e1);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                        }

                        .custom-subtitle {
                            font-size: 0.95rem;
                            font-weight: 500;
                            color: #2dd4bf;
                            text-transform: uppercase;
                            letter-spacing: 0.1em;
                        }
                    }
                </style>
            '),
        );

        // 2. Menyuntikkan elemen Teks Judul dan Sub-judul Skripsi (Hanya dirender di halaman login)
        FilamentView::registerRenderHook(
            'panels::body.start',
            fn(): string => request()->routeIs('filament.admin.auth.login')
                ? Blade::render('
                    <div class="custom-left-branding">
                        <p class="custom-subtitle">
                            Fakultas Ekonomi dan Bisnis • UPS Tegal
                        </p>
                        <h1 class="custom-title">
                            Perancangan Sistem Informasi Pengajuan dan Pelaporan Kegiatan Himpunan Mahasiswa Berbasis Web
                        </h1>
                    </div>
                ')
                : '' // Jika bukan halaman login, berikan string kosong (tidak merender apa-apa)
        );

        // 3. Menambahkan teks petunjuk kecil tepat di atas form login (sisi kanan)
        FilamentView::registerRenderHook(
            'panels::auth.login.form.before',
            fn(): string => Blade::render('
                <div class="text-left mb-6">
                    <h2 class="text-2xl font-black text-white tracking-tight">Selamat Datang</h2>
                    <p class="text-xs text-slate-400 mt-1">Silakan masuk menggunakan akun SIAKAD ORMAWA Anda.</p>
                </div>
            '),
        );
    }
}

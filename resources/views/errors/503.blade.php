<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Sedang Maintenance | LMS Gadai Mulia</title>
    <link rel="icon" href="{{ asset('assets/images/logos/logo-only.png') }}">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #fafaf9; color: #242925; font-family: Arial, Helvetica, sans-serif; -webkit-font-smoothing: antialiased; }
        .page { max-width: 1040px; min-height: 100vh; min-height: 100svh; margin: auto; padding: 36px 48px 24px; display: flex; flex-direction: column; }
        header { display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 600; }
        header img { width: 34px; height: 38px; object-fit: contain; }
        main { flex: 1; display: flex; align-items: center; padding: 80px 0; }
        .content { max-width: 540px; }
        .status { display: flex; align-items: center; gap: 8px; color: #72766f; font-size: 13px; margin: 0 0 24px; }
        .status::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: #b58a36; }
        h1 { margin: 0 0 20px; font-size: clamp(32px, 5vw, 46px); line-height: 1.15; font-weight: 500; letter-spacing: -1.5px; }
        .description { max-width: 460px; margin: 0; font-size: 16px; line-height: 1.8; color: #686e67; }
        .estimate { border-top: 1px solid #dedfd9; border-bottom: 1px solid #dedfd9; margin: 32px 0; padding: 20px 0; }
        .estimate-row { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        h2 { margin: 0; font-size: 14px; font-weight: 400; color: #686e67; }
        .duration { margin: 0; font-size: 18px; font-weight: 600; }
        .note { margin: 10px 0 0; font-size: 12px; line-height: 1.7; color: #767b74; }
        button { border: 1px solid #303e32; border-radius: 5px; background: #303e32; color: white; padding: 12px 20px; font: inherit; font-size: 14px; cursor: pointer; }
        button:hover { background: #233025; }
        button:focus-visible { outline: 2px solid #303e32; outline-offset: 4px; }
        .thanks { margin: 20px 0 0; color: #767b74; font-size: 13px; }
        footer { display: flex; justify-content: space-between; gap: 16px; border-top: 1px solid #e7e8e2; padding-top: 20px; font-size: 11px; line-height: 1.6; color: #858980; }
        @media (max-width: 600px) { .page { padding: 24px; } main { padding: 64px 0; } footer { flex-direction: column; gap: 4px; } }
    </style>
</head>
<body>
    <div class="page">
        <header>
            <img src="{{ asset('assets/images/logos/logo-only.png') }}" alt="" width="34" height="38">
            <span>LMS Gadai Mulia</span>
        </header>
        <main>
            <div class="content">
                <p class="status">Pemeliharaan sistem</p>
                <h1>Kami segera kembali.</h1>
                <p class="description">LMS sedang dalam pemeliharaan untuk menjaga layanan tetap berjalan dengan baik. Anda dapat mengaksesnya kembali setelah proses ini selesai.</p>
                <section class="estimate" aria-labelledby="maintenance-estimate">
                    <div class="estimate-row">
                        <h2 id="maintenance-estimate">Estimasi durasi</h2>
                        <p class="duration">{{ !empty($maintenanceHours) ? 'Sekitar '.$maintenanceHours.' jam' : 'Belum tersedia' }}</p>
                    </div>
                    @if(!empty($maintenanceHours))
                        <p class="note">Sejak pemeliharaan dimulai. Waktu penyelesaian dapat berubah.</p>
                    @else
                        <p class="note">Silakan cek kembali secara berkala untuk informasi terbaru.</p>
                    @endif
                </section>
                <button type="button" onclick="window.location.reload()">Muat ulang halaman</button>
                <p class="thanks">Terima kasih atas kesabaran Anda.</p>
            </div>
        </main>
        <footer>
            <span>&copy; {{ date('Y') }} LMS Gadai Mulia</span>
            <span>503 &middot; Layanan sementara tidak tersedia</span>
        </footer>
    </div>
</body>
</html>
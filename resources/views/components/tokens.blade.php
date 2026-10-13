{{--
    Design tokens BAB 16 — sumber tunggal warna, radius, bayangan, ruang dan gerak.
    Di-include oleh layout utama serta dokumen mandiri (reader & embed) agar
    tidak ada definisi warna yang terduplikasi. Semua komponen memakai var(--…)
    sehingga perubahan tema cukup di satu tempat.
--}}
:root {
    /* Warna institusi */
    --ypl-navy: #002f80;
    --ypl-navy-600: #0046c0;
    --ypl-navy-500: #0a4fc4;
    --ypl-navy-800: #001c55;
    --ypl-navy-900: #000f2e;
    --ypl-red: #c90018;
    --ypl-red-600: #a30014;
    --ypl-red-800: #7a0010;
    --ypl-blue: #2f6BFF;
    --ypl-gray: #d6d6d6;
    --ypl-paper: #faf7f0;
    --ypl-bg: #eef1f4;
    --ypl-ink: #0f172a;
    --ypl-muted: #64748b;

    /* Palet reader (dokumen mandiri) */
    --ypl-reader-stage: #dde2e8;
    --ypl-reader-panel: #f5f1e8;
    --ypl-reader-bar: #0b1330;
    --ypl-reader-toolbar: #1c2431;
    --ypl-reader-dark: #141920;
    --ypl-reader-light: #10182e;
    --ypl-reader-paper: #faf6ec;

    /* Gradien khas */
    --ypl-grad-navy: linear-gradient(135deg, var(--ypl-navy), var(--ypl-navy-600));
    --ypl-grad-red: linear-gradient(135deg, var(--ypl-red-600), var(--ypl-red));

    /* Radius */
    --ypl-radius-sm: .5rem;
    --ypl-radius-md: .8rem;
    --ypl-radius-lg: 1rem;
    --ypl-radius-xl: 1.5rem;
    --ypl-radius-pill: 9999px;

    /* Bayangan */
    --ypl-shadow-sm: 0 2px 20px -12px rgba(0, 30, 80, .45);
    --ypl-shadow-navy: 0 8px 20px -8px rgba(0, 47, 128, .55);
    --ypl-shadow-navy-lg: 0 12px 24px -8px rgba(0, 47, 128, .6);
    --ypl-shadow-red: 0 8px 20px -8px rgba(201, 0, 24, .55);

    /* Fokus (aksesibilitas) */
    --ypl-ring: 0 0 0 3px rgba(0, 47, 128, .15);

    /* Gerak */
    --ypl-transition: .2s;
}

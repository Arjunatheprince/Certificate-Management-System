from pathlib import Path

import create_semhas_ppt as base
from create_semhas_ppt import Slide


base.OUT = Path("ppt_semhas_skripsi_arjuna_final.pptx")


def make_slides() -> list[Slide]:
    slides: list[Slide] = []

    title = "Implementasi Sistem Autentikasi Sertifikat Digital Berbasis QR Code dengan Keamanan SHA-256 dan RBAC"

    s = Slide(title, "Seminar Hasil Skripsi", "Presentasi")
    s.text("Arjuna Pramudya Renaldi\nNPM: 22.403040.19\nProgram Studi Teknik Komputer\nFakultas Teknik\nUniversitas Borneo Tarakan\n2025", 0.78, 1.78, 5.05, 2.55, 19, "334155", fill="F8FAFC", line="D9E2EC", radius=True)
    s.text("Pembimbing I: Dedy Harto, S.T., M.T.\nPembimbing II: Awang Pradana, S.Kom., M.Kom.", 0.78, 4.58, 5.05, 0.92, 16, "475569", fill="FFFFFF", line="CBD5E1", radius=True)
    s.text("Fokus penelitian", 6.35, 1.85, 4.4, 0.45, 22, "FFFFFF", True, fill="0F766E", radius=True)
    s.text("Membangun sistem autentikasi sertifikat digital berbasis web yang menggabungkan QR Code untuk verifikasi cepat, SHA-256 untuk menjaga integritas dokumen, dan RBAC untuk membatasi hak akses pengguna.", 6.35, 2.55, 5.35, 2.05, 22, "334155", fill="F0FDFA", line="99F6E4", radius=True)
    s.metric("Black Box", "60/60", 6.35, 5.30, 2.05, "2563EB")
    s.metric("Security", "14/14", 8.68, 5.30, 2.05, "0F766E")
    s.metric("Likert", "88,46%", 11.01, 5.30, 1.85, "7C3AED")
    slides.append(s)

    s = Slide("Agenda Presentasi", "Alur pembahasan seminar hasil.", "Pembuka")
    s.text("1", 1.0, 2.0, 0.58, 0.58, 18, "FFFFFF", True, fill="0F766E", radius=True)
    s.text("Pendahuluan\nlatar belakang, rumusan masalah, tujuan, batasan, manfaat", 1.8, 1.92, 9.6, 0.82, 20, "334155", fill="F8FAFC", line="CBD5E1", radius=True)
    s.text("2", 1.0, 3.05, 0.58, 0.58, 18, "FFFFFF", True, fill="2563EB", radius=True)
    s.text("Metode penelitian\nR&D, ADDIE, instrumen pengujian, dan teknik analisis data", 1.8, 2.97, 9.6, 0.82, 20, "334155", fill="F8FAFC", line="CBD5E1", radius=True)
    s.text("3", 1.0, 4.10, 0.58, 0.58, 18, "FFFFFF", True, fill="7C3AED", radius=True)
    s.text("Implementasi sistem\nfitur utama, arsitektur Laravel 11, QR Code, SHA-256, dan RBAC", 1.8, 4.02, 9.6, 0.82, 20, "334155", fill="F8FAFC", line="CBD5E1", radius=True)
    s.text("4", 1.0, 5.15, 0.58, 0.58, 18, "FFFFFF", True, fill="DC2626", radius=True)
    s.text("Hasil dan penutup\nblack box, security testing, kuesioner, kesimpulan, dan saran", 1.8, 5.07, 9.6, 0.82, 20, "334155", fill="F8FAFC", line="CBD5E1", radius=True)
    slides.append(s)

    s = Slide("Latar Belakang", "Kebutuhan validasi sertifikat digital yang cepat, akurat, dan aman.", "Pendahuluan")
    s.bullet_box([
        "Sertifikat digunakan sebagai bukti pencapaian, kompetensi, atau keikutsertaan.",
        "Verifikasi konvensional cenderung lambat, manual, dan sulit menjamin keaslian dokumen.",
        "Pemalsuan atau manipulasi sertifikat dapat merugikan institusi dan pemilik sertifikat.",
        "QR Code memungkinkan verifikasi real-time, sedangkan SHA-256 membantu mendeteksi perubahan dokumen.",
        "RBAC diperlukan agar setiap pengguna hanya dapat mengakses fitur sesuai perannya."
    ], 0.78, 1.95, 6.2, 3.8, 21)
    s.text("Solusi yang Diusulkan", 7.35, 2.0, 4.25, 0.45, 22, "FFFFFF", True, fill="0F766E", radius=True)
    s.text("Sistem berbasis Laravel 11 dengan verifikasi QR Code, hash SHA-256, dan Role Based Access Control untuk pengelolaan sertifikat digital yang lebih aman dan terstruktur.", 7.35, 2.72, 4.55, 2.15, 22, "334155", fill="F0FDFA", line="99F6E4", radius=True)
    slides.append(s)

    s = Slide("Rumusan Masalah", "Pertanyaan penelitian yang dijawab melalui implementasi sistem.", "Pendahuluan")
    s.bullet_box([
        "Bagaimana merancang dan mengimplementasikan sistem autentikasi sertifikat digital berbasis QR Code yang dapat memvalidasi keaslian sertifikat secara real-time?",
        "Bagaimana menerapkan keamanan SHA-256 dan RBAC dalam proses penyimpanan, pengelolaan, dan verifikasi sertifikat digital?",
        "Bagaimana menguji sistem agar dapat melakukan autentikasi secara cepat, akurat, dan aman berdasarkan pengujian fungsional, keamanan, dan evaluasi pengguna?"
    ], 0.85, 2.0, 10.8, 3.65, 22)
    slides.append(s)

    s = Slide("Tujuan Penelitian", "Target utama penelitian adalah menghasilkan produk sistem yang dapat diuji.", "Pendahuluan")
    s.bullet_box([
        "Merancang sistem autentikasi sertifikat digital berbasis QR Code dengan keamanan SHA-256 dan RBAC.",
        "Menerapkan SHA-256 sebagai segel digital untuk menjaga keaslian sertifikat.",
        "Menerapkan RBAC agar SuperAdmin, Admin, dan Viewer/Verifikator memiliki hak akses sesuai peran.",
        "Melakukan pengujian fungsional, keamanan berbasis OWASP Top 10 2021, serta evaluasi pengguna melalui kuesioner Likert."
    ], 0.85, 2.0, 10.8, 3.65, 22)
    slides.append(s)

    s = Slide("Batasan dan Manfaat", "Ruang lingkup penelitian dibuat jelas agar pengujian tetap terarah.", "Pendahuluan")
    s.text("Batasan", 0.85, 2.0, 4.9, 0.5, 23, "FFFFFF", True, fill="2563EB", radius=True)
    s.bullet_box([
        "Aplikasi web berjalan pada lingkungan lokal sebagai prototipe.",
        "Database terpusat menggunakan MySQL.",
        "Role dibatasi pada SuperAdmin, Admin, dan Viewer/Verifikator.",
        "Pengujian keamanan fokus pada ancaman umum aplikasi web, bukan keamanan jaringan."
    ], 0.85, 2.7, 5.15, 2.75, 18)
    s.text("Manfaat", 6.55, 2.0, 4.9, 0.5, 23, "FFFFFF", True, fill="0F766E", radius=True)
    s.bullet_box([
        "Pengelolaan sertifikat lebih terpusat dan terdokumentasi.",
        "Verifikasi keaslian sertifikat lebih cepat melalui QR Code.",
        "Perubahan dokumen dapat dideteksi melalui SHA-256.",
        "Kontrol akses dan audit aktivitas mendukung keamanan operasional."
    ], 6.55, 2.7, 5.15, 2.75, 18)
    slides.append(s)

    s = Slide("Landasan Konsep", "Empat konsep utama yang menjadi dasar implementasi.", "Tinjauan")
    s.text("QR Code", 0.75, 2.05, 2.65, 0.62, 22, "FFFFFF", True, fill="2563EB", radius=True)
    s.text("Media verifikasi cepat untuk membawa nomor atau URL sertifikat.", 0.75, 2.9, 2.65, 1.65, 17, "334155", fill="EFF6FF", line="BFDBFE", radius=True)
    s.text("SHA-256", 3.75, 2.05, 2.65, 0.62, 22, "FFFFFF", True, fill="0F766E", radius=True)
    s.text("Hash dokumen untuk memastikan integritas dan mendeteksi manipulasi file.", 3.75, 2.9, 2.65, 1.65, 17, "334155", fill="F0FDFA", line="99F6E4", radius=True)
    s.text("RBAC", 6.75, 2.05, 2.65, 0.62, 22, "FFFFFF", True, fill="7C3AED", radius=True)
    s.text("Pengaturan hak akses berdasarkan peran pengguna dalam sistem.", 6.75, 2.9, 2.65, 1.65, 17, "334155", fill="F5F3FF", line="DDD6FE", radius=True)
    s.text("Laravel 11", 9.75, 2.05, 2.65, 0.62, 22, "FFFFFF", True, fill="DC2626", radius=True)
    s.text("Framework MVC dengan fitur keamanan dan pengelolaan database yang mendukung pengembangan web.", 9.75, 2.9, 2.65, 1.65, 17, "334155", fill="FEF2F2", line="FECACA", radius=True)
    slides.append(s)

    s = Slide("Metode Penelitian", "Penelitian terapan menggunakan pendekatan R&D dan analisis kuantitatif.", "Metode")
    s.text("Jenis penelitian", 0.85, 2.0, 3.3, 0.52, 22, "111827", True, margin=0)
    s.text("Applied Research\nResearch and Development\nPendekatan kuantitatif", 0.82, 2.62, 3.55, 1.75, 20, "334155", fill="F8FAFC", line="CBD5E1", radius=True)
    s.text("Model ADDIE", 4.82, 2.0, 3.3, 0.52, 22, "111827", True, margin=0)
    s.text("Analysis\nDesign\nDevelopment\nImplementation\nEvaluation", 4.78, 2.62, 3.55, 2.32, 20, "334155", fill="F8FAFC", line="CBD5E1", radius=True)
    s.text("Aspek evaluasi", 8.78, 2.0, 3.3, 0.52, 22, "111827", True, margin=0)
    s.text("Black Box Testing\nSecurity Testing\nOWASP Top 10 2021\nKuesioner Skala Likert", 8.75, 2.62, 3.55, 2.05, 20, "334155", fill="F8FAFC", line="CBD5E1", radius=True)
    slides.append(s)

    s = Slide("Tahapan ADDIE", "Setiap tahap menghasilkan output yang mendukung implementasi dan evaluasi.", "Metode")
    steps = [
        ("Analysis", "Analisis masalah, kebutuhan fitur, keamanan, performa, dan teknologi."),
        ("Design", "Perancangan arsitektur MVC, database, UI, alur QR, SHA-256, dan RBAC."),
        ("Development", "Pembangunan sistem Laravel 11, QR Code, hash, middleware, dan activity log."),
        ("Implementation", "Penerapan pada lingkungan Laragon, pengisian data uji, dan pelaksanaan pengujian."),
        ("Evaluation", "Analisis hasil black box, security testing, dan kuesioner Likert.")
    ]
    x = 0.55
    for idx, (head, desc) in enumerate(steps, start=1):
        s.text(str(idx), x, 2.05, 0.55, 0.55, 17, "FFFFFF", True, fill="0F766E", radius=True, margin=70000)
        s.text(head, x, 2.8, 2.28, 0.42, 17, "111827", True, margin=0)
        s.text(desc, x, 3.35, 2.28, 1.55, 14, "475569", fill="F8FAFC", line="CBD5E1", radius=True)
        x += 2.53
    slides.append(s)

    s = Slide("Arsitektur dan Teknologi", "Sistem dikembangkan sebagai aplikasi web berbasis Laravel 11.", "Implementasi")
    s.text("Frontend", 0.85, 2.1, 2.7, 0.62, 21, "FFFFFF", True, fill="2563EB", radius=True)
    s.text("Blade Template\nTailwind / Bootstrap Icons\nHalaman publik dan admin", 0.85, 2.95, 2.7, 1.55, 17, "334155", fill="EFF6FF", line="BFDBFE", radius=True)
    s.text("Backend", 4.05, 2.1, 2.7, 0.62, 21, "FFFFFF", True, fill="0F766E", radius=True)
    s.text("Laravel 11\nPHP 8.2\nMVC, Controller, Middleware", 4.05, 2.95, 2.7, 1.55, 17, "334155", fill="F0FDFA", line="99F6E4", radius=True)
    s.text("Data", 7.25, 2.1, 2.7, 0.62, 21, "FFFFFF", True, fill="7C3AED", radius=True)
    s.text("MySQL\nTabel users, students, certificates, activity logs\nStorage file dan QR", 7.25, 2.95, 2.7, 1.55, 17, "334155", fill="F5F3FF", line="DDD6FE", radius=True)
    s.text("Library", 10.45, 2.1, 2.3, 0.62, 21, "FFFFFF", True, fill="DC2626", radius=True)
    s.text("Endroid QR Code\nQR Reader\nIntervention Image", 10.45, 2.95, 2.3, 1.55, 17, "334155", fill="FEF2F2", line="FECACA", radius=True)
    slides.append(s)

    s = Slide("Alur Verifikasi Sertifikat", "Verifikasi dapat dilakukan melalui input nomor sertifikat atau unggah/scan QR Code.", "Implementasi")
    flow = [
        ("1", "Pengguna memasukkan nomor atau mengunggah QR Code"),
        ("2", "Sistem membaca nomor sertifikat dari input atau QR"),
        ("3", "Data sertifikat dicari pada database"),
        ("4", "Hash SHA-256 file dicek ulang"),
        ("5", "Sistem menampilkan status valid atau tidak valid")
    ]
    x = 0.7
    for num, desc in flow:
        s.text(num, x, 2.15, 0.55, 0.55, 18, "FFFFFF", True, fill="0F766E", radius=True, margin=70000)
        s.text(desc, x, 2.95, 2.12, 1.45, 17, "334155", fill="F8FAFC", line="CBD5E1", radius=True)
        x += 2.48
    s.text("Output utama: data pemilik sertifikat, nomor sertifikat, jenis sertifikat, tanggal terbit, masa berlaku, file sertifikat, dan status integritas dokumen.", 1.1, 5.25, 10.8, 0.75, 18, "475569", margin=0)
    slides.append(s)

    s = Slide("Implementasi Fitur Sistem", "Sistem memiliki dua area utama: halaman publik dan panel admin.", "Implementasi")
    s.text("Halaman Publik", 0.82, 2.0, 3.6, 0.55, 22, "FFFFFF", True, fill="2563EB", radius=True)
    s.bullet_box(["Verifikasi nomor sertifikat", "Unggah gambar QR Code", "Tampilan hasil validasi", "Akses tanpa login"], 0.82, 2.7, 3.75, 2.45, 18)
    s.text("Panel Admin", 4.82, 2.0, 3.6, 0.55, 22, "FFFFFF", True, fill="0F766E", radius=True)
    s.bullet_box(["Login", "Dashboard", "Upload sertifikat", "Daftar dan edit sertifikat", "Pengaturan QR pada dokumen"], 4.82, 2.7, 3.75, 2.45, 18)
    s.text("Panel SuperAdmin", 8.82, 2.0, 3.6, 0.55, 22, "FFFFFF", True, fill="7C3AED", radius=True)
    s.bullet_box(["Kelola user", "Hapus sertifikat", "Activity log", "Kontrol status aktif akun", "Audit aktivitas sistem"], 8.82, 2.7, 3.75, 2.45, 18)
    slides.append(s)

    s = Slide("Implementasi Keamanan", "Keamanan diterapkan pada dokumen, akses pengguna, dan aktivitas sistem.", "Implementasi")
    s.metric("Integritas file", "SHA-256", 0.85, 2.0, 2.55, "0F766E")
    s.metric("Verifikasi cepat", "QR Code", 3.78, 2.0, 2.45, "2563EB")
    s.metric("Akses sistem", "RBAC", 6.58, 2.0, 2.25, "7C3AED")
    s.metric("Audit", "Log", 9.18, 2.0, 2.0, "DC2626")
    s.bullet_box([
        "Hash file dihitung menggunakan SHA-256 dan disimpan pada database.",
        "Digital signature dibentuk dari nomor sertifikat, NPM, dan hash file.",
        "QR Code menyimpan URL verifikasi yang mengarah ke data sertifikat.",
        "Middleware RoleMiddleware membatasi akses berdasarkan peran pengguna.",
        "Activity log mencatat login, logout, upload, edit, hapus, dan pengelolaan user."
    ], 0.9, 3.65, 10.75, 2.15, 20)
    slides.append(s)

    s = Slide("Role Based Access Control", "Hak akses dibedakan untuk menjaga keamanan operasional sistem.", "Implementasi")
    s.text("SuperAdmin", 0.85, 2.0, 3.35, 0.55, 22, "FFFFFF", True, fill="7C3AED", radius=True)
    s.text("Akses penuh: dashboard, sertifikat, upload, edit, hapus, kelola user, dan activity log.", 0.85, 2.75, 3.35, 1.75, 18, "334155", fill="F5F3FF", line="DDD6FE", radius=True)
    s.text("Admin", 4.92, 2.0, 3.35, 0.55, 22, "FFFFFF", True, fill="0F766E", radius=True)
    s.text("Akses operasional: dashboard, daftar sertifikat, upload sertifikat, dan edit sertifikat.", 4.92, 2.75, 3.35, 1.75, 18, "334155", fill="F0FDFA", line="99F6E4", radius=True)
    s.text("Viewer / Verifikator", 8.98, 2.0, 3.35, 0.55, 22, "FFFFFF", True, fill="2563EB", radius=True)
    s.text("Akses terbatas: melihat dashboard atau daftar data dan melakukan verifikasi tanpa mengubah data.", 8.98, 2.75, 3.35, 1.75, 18, "334155", fill="EFF6FF", line="BFDBFE", radius=True)
    slides.append(s)

    s = Slide("Instrumen Pengujian", "Kelayakan sistem diuji dari sisi fungsi, keamanan, dan penerimaan pengguna.", "Pengujian")
    s.text("Black Box Testing", 0.82, 2.0, 3.4, 0.55, 21, "FFFFFF", True, fill="2563EB", radius=True)
    s.text("Menguji kesesuaian input, proses, output, validasi gagal, role, log aktivitas, dan pembatasan request.", 0.82, 2.75, 3.5, 1.9, 18, "334155", fill="EFF6FF", line="BFDBFE", radius=True)
    s.text("Security Testing", 4.87, 2.0, 3.4, 0.55, 21, "FFFFFF", True, fill="0F766E", radius=True)
    s.text("Menguji integritas dokumen, pemalsuan QR, kontrol akses, manipulasi session, dan kategori OWASP Top 10 2021.", 4.87, 2.75, 3.5, 1.9, 18, "334155", fill="F0FDFA", line="99F6E4", radius=True)
    s.text("Kuesioner Likert", 8.92, 2.0, 3.4, 0.55, 21, "FFFFFF", True, fill="7C3AED", radius=True)
    s.text("Mengukur kepuasan, kemudahan penggunaan, keamanan, manfaat, dan kinerja sistem dari perspektif pengguna.", 8.92, 2.75, 3.5, 1.9, 18, "334155", fill="F5F3FF", line="DDD6FE", radius=True)
    slides.append(s)

    s = Slide("Hasil Black Box Testing", "Seluruh fungsi utama sistem berjalan sesuai kebutuhan.", "Hasil")
    s.metric("Total skenario", "60", 0.85, 2.0, 2.45, "2563EB")
    s.metric("Berhasil", "60", 3.65, 2.0, 2.45, "0F766E")
    s.metric("Gagal", "0", 6.45, 2.0, 2.45, "DC2626")
    s.metric("Keberhasilan", "100%", 9.25, 2.0, 2.45, "7C3AED")
    s.bullet_box([
        "Modul diuji meliputi verifikasi manual, QR upload, scan QR kamera, login, dashboard, upload, daftar, edit, hapus, activity log, manajemen user, otorisasi, dan throttle.",
        "Hasil eksekusi otomatis menunjukkan OK dengan 71 tests dan 175 assertions.",
        "Kategori kualitas sistem berdasarkan hasil black box: Sangat Baik."
    ], 0.92, 3.75, 10.75, 2.05, 20)
    slides.append(s)

    s = Slide("Hasil Security Testing", "Pengujian keamanan mengacu pada skenario fitur utama dan OWASP Top 10 2021.", "Hasil")
    s.metric("Total skenario", "14", 0.85, 2.0, 2.45, "2563EB")
    s.metric("Berhasil", "14", 3.65, 2.0, 2.45, "0F766E")
    s.metric("Gagal", "0", 6.45, 2.0, 2.45, "DC2626")
    s.metric("Keamanan", "100%", 9.25, 2.0, 2.45, "7C3AED")
    s.bullet_box([
        "Skenario meliputi integritas dokumen, pemalsuan QR Code, kontrol akses, dan manipulasi session.",
        "Kategori OWASP yang diuji antara lain Broken Access Control, Cryptographic Failures, Injection, Identification and Authentication Failures, serta Software and Data Integrity Failures.",
        "Status keamanan sistem berdasarkan hasil pengujian: Sangat Aman."
    ], 0.92, 3.75, 10.75, 2.05, 20)
    slides.append(s)

    s = Slide("Hasil Kuesioner Skala Likert", "Evaluasi pengguna dilakukan kepada 20 responden.", "Hasil")
    s.metric("SuperAdmin", "3", 0.85, 2.0, 2.25, "7C3AED")
    s.metric("Admin", "7", 3.45, 2.0, 2.25, "0F766E")
    s.metric("Viewer", "10", 6.05, 2.0, 2.25, "2563EB")
    s.metric("Kategori", "Sangat Baik", 8.65, 2.0, 2.9, "DC2626")
    s.text("Total skor: 1641 dari skor maksimal 1855\nPersentase keseluruhan: 88,46%\nRata-rata nilai: 4,42\nInterpretasi: sistem dinilai memenuhi kebutuhan pengguna dari sisi fungsionalitas, kemudahan penggunaan, keamanan, dan kinerja.", 1.0, 3.78, 10.3, 1.7, 22, "334155", fill="F8FAFC", line="CBD5E1", radius=True)
    slides.append(s)

    s = Slide("Pembahasan", "Hasil penelitian menunjukkan sistem mampu menjawab kebutuhan utama.", "Pembahasan")
    s.bullet_box([
        "Pengelolaan sertifikat menjadi lebih terstruktur melalui database, file storage, QR Code, dan activity log.",
        "Verifikasi publik dapat dilakukan tanpa login sehingga memudahkan verifikator eksternal.",
        "SHA-256 membantu mendeteksi perubahan file sertifikat dan menjaga integritas dokumen.",
        "RBAC memisahkan kewenangan SuperAdmin, Admin, dan Viewer/Verifikator.",
        "Kendala utama berada pada sinkronisasi data sertifikat, QR Code, hash, dan pengaturan hak akses antar role."
    ], 0.85, 2.0, 10.8, 3.65, 22)
    slides.append(s)

    s = Slide("Kesimpulan", "Kesimpulan berdasarkan implementasi, pengujian, dan evaluasi pengguna.", "Penutup")
    s.bullet_box([
        "Sistem autentikasi sertifikat digital berhasil diimplementasikan menggunakan Laravel 11 dengan arsitektur MVC.",
        "QR Code, SHA-256, dan RBAC berhasil diterapkan untuk mendukung verifikasi, integritas dokumen, dan kontrol akses.",
        "Black Box Testing menunjukkan tingkat keberhasilan 100% pada 60 skenario.",
        "Security Testing menunjukkan tingkat keamanan 100% pada 14 skenario.",
        "Kuesioner 20 responden memperoleh persentase 88,46% dan masuk kategori Sangat Baik."
    ], 0.85, 2.0, 10.8, 3.65, 22)
    slides.append(s)

    s = Slide("Saran Pengembangan", "Pengembangan berikutnya dapat memperkuat sisi penggunaan dan keamanan.", "Penutup")
    s.bullet_box([
        "Meningkatkan tampilan antarmuka agar lebih modern, responsif, dan nyaman pada perangkat mobile.",
        "Menyempurnakan scan QR Code melalui kamera agar lebih cepat, stabil, dan akurat.",
        "Mempertimbangkan pengembangan aplikasi mobile untuk verifikasi dan pengelolaan sertifikat.",
        "Melakukan deployment production dengan HTTPS.",
        "Menambahkan autentikasi dua faktor untuk Admin dan SuperAdmin.",
        "Memperluas pengujian dengan penetration testing dan jumlah responden yang lebih beragam."
    ], 0.85, 2.0, 10.8, 3.65, 21)
    slides.append(s)

    s = Slide("Terima Kasih", "Seminar Hasil Skripsi", "Penutup")
    s.text("Pertanyaan dan diskusi", 2.05, 2.32, 9.0, 0.85, 38, "FFFFFF", True, fill="0F766E", radius=True)
    s.text("Implementasi Sistem Autentikasi Sertifikat Digital Berbasis QR Code dengan Keamanan SHA-256 dan RBAC", 2.4, 3.75, 8.25, 1.05, 22, "334155", fill="F8FAFC", line="CBD5E1", radius=True)
    s.text("Arjuna Pramudya Renaldi | 22.403040.19", 3.72, 5.08, 5.62, 0.52, 19, "475569", margin=0)
    slides.append(s)

    return slides


if __name__ == "__main__":
    slides = make_slides()
    base.write_pptx(slides)
    print(f"Created {base.OUT} with {len(slides)} slides")

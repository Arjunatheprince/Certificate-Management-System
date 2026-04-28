from pathlib import Path

import create_professional_semhas_ppt as prof
from create_professional_semhas_ppt import Slide, media


prof.OUT = Path("ppt_semhas_skripsi_arjuna_ringkas_profesional.pptx")


def make_slides() -> list[Slide]:
    bgs = prof.ensure_backgrounds()
    slides: list[Slide] = []

    s = Slide("Implementasi Sistem Autentikasi Sertifikat Digital", "Cover", "Berbasis QR Code dengan Keamanan SHA-256 dan RBAC")
    s.image(media("image1.png"), 0.78, 1.55, 1.18, 1.18)
    s.text("Arjuna Pramudya Renaldi\nNPM: 22.403040.19\nProgram Studi Teknik Komputer\nFakultas Teknik\nUniversitas Borneo Tarakan", 2.18, 1.54, 4.9, 1.42, 18, "334155", fill="F8FAFC", line="D6DEE8", radius=True)
    s.text("Seminar Hasil Skripsi\nPembimbing I: Dedy Harto, S.T., M.T.\nPembimbing II: Awang Pradana, S.Kom., M.Kom.", 7.35, 1.54, 4.9, 1.42, 18, "FFFFFF", fill="0F766E", radius=True)
    s.metric("Black Box", "60/60", 1.10, 4.66, 2.20, "2563EB")
    s.metric("Security", "14/14", 3.72, 4.66, 2.20, "0F766E")
    s.metric("Kuesioner", "88,46%", 6.34, 4.66, 2.20, "7C3AED")
    s.metric("Kategori", "Sangat Baik", 8.96, 4.66, 2.65, "DC2626")
    slides.append(s)

    s = Slide("Agenda Presentasi", "Pembuka", "Materi dipadatkan pada bagian yang paling krusial.")
    s.bullet([
        "Latar belakang, rumusan masalah, dan tujuan penelitian",
        "Metode penelitian dan instrumen pengujian",
        "Rancangan inti: use case, DFD, dan ERD",
        "Implementasi fitur dan keamanan: QR Code, SHA-256, RBAC",
        "Hasil pengujian: Black Box, Security Testing, dan Kuesioner",
        "Kesimpulan dan saran pengembangan"
    ], 1.05, 1.95, 10.8, 3.7, 23)
    slides.append(s)

    s = Slide("Masalah dan Solusi", "Pendahuluan", "Sistem dibangun untuk menjawab masalah keaslian dan pengelolaan sertifikat digital.")
    s.text("Masalah utama", 0.86, 1.88, 5.15, 0.52, 22, "FFFFFF", True, fill="2563EB", radius=True, align="ctr")
    s.bullet([
        "Verifikasi sertifikat manual lambat dan sulit diaudit.",
        "Dokumen sertifikat digital rawan dipalsukan atau dimanipulasi.",
        "Pengelolaan akses pengguna perlu dibatasi agar data tidak disalahgunakan."
    ], 0.86, 2.66, 5.25, 2.35, 20)
    s.text("Solusi penelitian", 6.72, 1.88, 5.15, 0.52, 22, "FFFFFF", True, fill="0F766E", radius=True, align="ctr")
    s.bullet([
        "QR Code untuk mempercepat proses verifikasi.",
        "SHA-256 untuk mendeteksi perubahan file sertifikat.",
        "RBAC untuk membatasi akses SuperAdmin, Admin, dan Viewer/Verifikator.",
        "Activity log untuk mendukung audit aktivitas sistem."
    ], 6.72, 2.66, 5.25, 2.35, 20)
    slides.append(s)

    s = Slide("Rumusan Masalah dan Tujuan", "Pendahuluan", "Rumusan dan tujuan diarahkan pada produk sistem yang dapat diuji.")
    s.text("Rumusan Masalah", 0.86, 1.88, 5.15, 0.52, 22, "FFFFFF", True, fill="7C3AED", radius=True, align="ctr")
    s.bullet([
        "Bagaimana membangun sistem autentikasi sertifikat digital berbasis QR Code?",
        "Bagaimana menerapkan SHA-256 dan RBAC untuk keamanan dokumen dan akses?",
        "Bagaimana mengukur kelayakan sistem melalui pengujian fungsi, keamanan, dan pengguna?"
    ], 0.86, 2.66, 5.25, 2.45, 19)
    s.text("Tujuan", 6.72, 1.88, 5.15, 0.52, 22, "FFFFFF", True, fill="0F766E", radius=True, align="ctr")
    s.bullet([
        "Menghasilkan sistem pengelolaan dan verifikasi sertifikat digital.",
        "Menerapkan QR Code, SHA-256, RBAC, dan activity log.",
        "Membuktikan sistem berjalan baik melalui Black Box Testing, Security Testing, dan Kuesioner Likert."
    ], 6.72, 2.66, 5.25, 2.45, 19)
    slides.append(s)

    s = Slide("Metode Penelitian", "Metode", "Penelitian menggunakan R&D model ADDIE dengan evaluasi kuantitatif.")
    s.text("R&D", 0.95, 1.95, 2.00, 0.62, 22, "FFFFFF", True, fill="2563EB", radius=True, align="ctr")
    s.text("Menghasilkan produk sistem yang dirancang, dibangun, diterapkan, dan dievaluasi.", 0.95, 2.84, 2.95, 1.45, 18, "334155", fill="EFF6FF", line="BFDBFE", radius=True)
    s.text("ADDIE", 4.25, 1.95, 2.00, 0.62, 22, "FFFFFF", True, fill="0F766E", radius=True, align="ctr")
    s.text("Analysis, Design, Development, Implementation, Evaluation.", 4.25, 2.84, 2.95, 1.45, 18, "334155", fill="F0FDFA", line="99F6E4", radius=True)
    s.text("Evaluasi", 7.55, 1.95, 2.00, 0.62, 22, "FFFFFF", True, fill="7C3AED", radius=True, align="ctr")
    s.text("Black Box Testing, Security Testing berbasis OWASP, dan Kuesioner Skala Likert.", 7.55, 2.84, 3.35, 1.45, 18, "334155", fill="F5F3FF", line="DDD6FE", radius=True)
    s.bullet(["Subjek evaluasi pengguna: 20 responden, terdiri dari 3 SuperAdmin, 7 Admin, dan 10 Viewer/Verifikator.", "Pengujian otomatis digunakan agar hasil lebih konsisten dan dapat diulang."], 1.0, 5.06, 10.6, 0.95, 18)
    slides.append(s)

    s = Slide("Peran Metode dalam Penelitian", "Metode", "Metode digunakan sebagai alur kerja agar pengembangan sistem terarah dan hasilnya dapat dipertanggungjawabkan.")
    s.text("R&D", 0.85, 1.82, 2.1, 0.55, 21, "FFFFFF", True, fill="2563EB", radius=True, align="ctr")
    s.text("Berperan sebagai pendekatan utama untuk menghasilkan produk berupa sistem autentikasi sertifikat digital, bukan hanya analisis teoritis.", 3.25, 1.82, 8.45, 0.75, 18, "334155", fill="F8FAFC", line="D6DEE8", radius=True)
    s.text("ADDIE", 0.85, 2.90, 2.1, 0.55, 21, "FFFFFF", True, fill="0F766E", radius=True, align="ctr")
    s.text("Berperan sebagai tahapan pengembangan: Analysis untuk kebutuhan, Design untuk rancangan, Development untuk pembuatan sistem, Implementation untuk penerapan, dan Evaluation untuk pengujian.", 3.25, 2.90, 8.45, 0.95, 18, "334155", fill="F8FAFC", line="D6DEE8", radius=True)
    s.text("Kuantitatif", 0.85, 4.18, 2.1, 0.55, 21, "FFFFFF", True, fill="7C3AED", radius=True, align="ctr")
    s.text("Berperan untuk mengukur hasil secara angka melalui persentase keberhasilan Black Box Testing, tingkat keamanan Security Testing, dan skor Kuesioner Likert.", 3.25, 4.18, 8.45, 0.95, 18, "334155", fill="F8FAFC", line="D6DEE8", radius=True)
    s.text("Intinya, metode memastikan sistem dibangun berdasarkan kebutuhan, diuji secara objektif, dan dievaluasi dari sisi pengguna.", 1.05, 5.78, 10.8, 0.42, 18, "475569", margin=0, align="ctr")
    slides.append(s)

    s = Slide("Rancangan Aktor dan Fitur", "Perancangan", "Use case diagram menjadi gambaran utama pembagian fungsi sistem.")
    s.framed_image(media("image8.png"), 0.82, 1.64, 6.1, 5.08, "Use Case Diagram", fit="contain")
    s.bullet([
        "Pengunjung/Verifier: verifikasi manual, scan QR, dan upload QR.",
        "Admin: dashboard, upload sertifikat, daftar sertifikat, edit sertifikat, dan verifikasi admin.",
        "SuperAdmin: seluruh fitur admin ditambah kelola user, hapus sertifikat, dan activity log."
    ], 7.25, 2.05, 4.75, 3.25, 19)
    slides.append(s)

    s = Slide("Aliran Data Sistem", "Perancangan", "DFD Level 1 dipilih karena paling menjelaskan proses sistem secara utuh.")
    s.framed_image(media("image10.png"), 0.72, 1.58, 7.75, 5.18, "DFD Level 1", fit="contain")
    s.bullet([
        "Autentikasi memvalidasi akun dan session.",
        "Upload sertifikat menghasilkan data sertifikat, hash, digital signature, dan QR Code.",
        "Verifikasi mencocokkan nomor/QR dengan database dan mengecek integritas file.",
        "Activity log mencatat aktivitas penting pengguna."
    ], 8.75, 1.95, 3.75, 3.8, 17)
    slides.append(s)

    s = Slide("Struktur Basis Data", "Perancangan", "ERD menunjukkan tabel inti yang menopang proses autentikasi sertifikat.")
    s.framed_image(media("image11.png"), 0.82, 1.58, 6.25, 5.18, "Entity Relationship Diagram", fit="contain")
    s.bullet([
        "users: akun, role, dan status pengguna.",
        "students: identitas pemilik sertifikat.",
        "certificates: data sertifikat, file, QR Code, hash SHA-256, dan digital signature.",
        "activity_logs: riwayat aktivitas untuk audit."
    ], 7.42, 2.0, 4.8, 3.35, 19)
    slides.append(s)

    s = Slide("Implementasi Sistem", "Implementasi", "Tampilan utama dipilih secukupnya agar alur sistem tetap jelas.")
    s.framed_image(media("image24.png"), 0.76, 1.72, 5.6, 2.42, "Verifikasi publik", fit="contain")
    s.framed_image(media("image28.png"), 6.84, 1.72, 5.6, 2.42, "Upload sertifikat dan QR", fit="contain")
    s.framed_image(media("image29.png"), 0.76, 4.50, 5.6, 2.02, "Daftar sertifikat", fit="contain")
    s.framed_image(media("image32.png"), 6.84, 4.50, 5.6, 2.02, "Kelola user", fit="contain")
    slides.append(s)

    s = Slide("Keamanan yang Diimplementasikan", "Implementasi", "Bagian ini menjadi inti kontribusi teknis sistem.")
    s.metric("Dokumen", "SHA-256", 0.92, 1.85, 2.35, "0F766E")
    s.metric("Verifikasi", "QR Code", 3.69, 1.85, 2.35, "2563EB")
    s.metric("Akses", "RBAC", 6.46, 1.85, 2.35, "7C3AED")
    s.metric("Audit", "Log", 9.23, 1.85, 2.35, "DC2626")
    s.bullet([
        "Hash SHA-256 disimpan dan dibandingkan kembali saat sertifikat diverifikasi.",
        "Digital signature dibentuk dari nomor sertifikat, NPM, dan hash file.",
        "QR Code memuat data verifikasi agar proses pengecekan lebih cepat.",
        "RBAC membatasi fitur berdasarkan role pengguna.",
        "Activity log menyimpan jejak aktivitas login, upload, edit, hapus, dan pengelolaan user."
    ], 1.0, 3.72, 10.8, 2.05, 20)
    slides.append(s)

    s = Slide("Hasil Black Box Testing", "Hasil", "Pengujian fungsional memastikan fitur berjalan sesuai kebutuhan.")
    s.metric("Total Skenario", "60", 0.88, 1.86, 2.45, "2563EB")
    s.metric("Berhasil", "60", 3.68, 1.86, 2.45, "0F766E")
    s.metric("Gagal", "0", 6.48, 1.86, 2.45, "DC2626")
    s.metric("Keberhasilan", "100%", 9.28, 1.86, 2.45, "7C3AED")
    s.framed_image(media("image34.png"), 1.05, 3.45, 5.3, 2.28, "Bukti eksekusi pengujian otomatis", fit="contain")
    s.bullet([
        "Cakupan: verifikasi manual/QR, login, dashboard, upload, daftar, edit, hapus, activity log, manajemen user, otorisasi, dan throttle.",
        "Hasil PHPUnit: 71 tests dan 175 assertions."
    ], 6.75, 3.72, 5.05, 1.45, 19)
    slides.append(s)

    s = Slide("Hasil Security Testing", "Hasil", "Security testing menjadi bukti bahwa mekanisme keamanan berjalan.")
    s.metric("Total Skenario", "14", 0.88, 1.78, 2.45, "2563EB")
    s.metric("Berhasil", "14", 3.68, 1.78, 2.45, "0F766E")
    s.metric("Gagal", "0", 6.48, 1.78, 2.45, "DC2626")
    s.metric("Keamanan", "100%", 9.28, 1.78, 2.45, "7C3AED")
    s.framed_image(media("image35.png"), 0.86, 3.16, 3.7, 1.62, "Integritas dokumen", fit="contain")
    s.framed_image(media("image37.png"), 4.86, 3.16, 3.7, 1.62, "Kontrol akses RBAC", fit="contain")
    s.framed_image(media("image43.png"), 8.86, 3.16, 3.35, 1.62, "Software/Data Integrity", fit="contain")
    s.text("Cakupan penting: integritas file, pemalsuan QR, akses di luar role, manipulasi session, SQL injection, login berulang, dan manipulasi data/file.", 1.05, 5.42, 10.8, 0.60, 18, "475569", margin=0)
    slides.append(s)

    s = Slide("Hasil Kuesioner Skala Likert", "Hasil", "Hanya rekap inti ditampilkan agar deck tidak terlalu penuh.")
    s.metric("Responden", "20", 0.95, 1.95, 2.35, "2563EB")
    s.metric("Total Skor", "1641/1855", 3.75, 1.95, 2.65, "0F766E")
    s.metric("Persentase", "88,46%", 6.85, 1.95, 2.35, "7C3AED")
    s.metric("Kategori", "Sangat Baik", 9.65, 1.95, 2.55, "DC2626")
    s.bullet([
        "SuperAdmin: 3 responden",
        "Admin: 7 responden",
        "Viewer/Verifikator: 10 responden",
        "Interpretasi: sistem diterima dengan sangat baik dari sisi fungsi, kemudahan, keamanan, manfaat, dan kinerja."
    ], 1.15, 3.78, 10.3, 1.85, 22)
    slides.append(s)

    s = Slide("Pembahasan Krusial", "Pembahasan", "Hasil pengujian menunjukkan sistem menjawab kebutuhan penelitian.")
    s.bullet([
        "QR Code mempercepat verifikasi karena pengguna tidak perlu mencari data secara manual.",
        "SHA-256 membantu mendeteksi perubahan file sehingga keaslian dokumen lebih terjaga.",
        "RBAC mencegah akses fitur yang tidak sesuai dengan peran pengguna.",
        "Activity log membuat perubahan data lebih mudah diaudit.",
        "Kuesioner menunjukkan sistem dinilai layak digunakan oleh pengguna dengan kategori Sangat Baik."
    ], 1.0, 1.95, 10.8, 3.75, 23)
    slides.append(s)

    s = Slide("Kesimpulan", "Penutup", "Kesimpulan utama disusun sesuai hasil implementasi dan pengujian.")
    s.bullet([
        "Sistem autentikasi sertifikat digital berhasil dibangun menggunakan Laravel 11.",
        "QR Code, SHA-256, dan RBAC berhasil diterapkan sebagai mekanisme verifikasi, integritas, dan kontrol akses.",
        "Black Box Testing menunjukkan keberhasilan 100% pada 60 skenario.",
        "Security Testing menunjukkan keberhasilan 100% pada 14 skenario.",
        "Kuesioner memperoleh nilai 88,46% dengan kategori Sangat Baik."
    ], 1.0, 1.95, 10.8, 3.75, 23)
    slides.append(s)

    s = Slide("Saran Pengembangan", "Penutup", "Saran diarahkan untuk peningkatan sistem pada tahap berikutnya.")
    s.bullet([
        "Meningkatkan tampilan agar lebih responsif dan nyaman pada perangkat mobile.",
        "Menyempurnakan scan QR melalui kamera agar lebih stabil dan akurat.",
        "Melakukan deployment production dengan HTTPS.",
        "Menambahkan Two-Factor Authentication untuk Admin dan SuperAdmin.",
        "Memperluas pengujian keamanan dengan penetration testing."
    ], 1.0, 1.95, 10.8, 3.75, 23)
    slides.append(s)

    s = Slide("Terima Kasih", "Penutup", "Diskusi dan tanya jawab.")
    s.image(media("image1.png"), 1.0, 1.68, 1.25, 1.25)
    s.text("Pertanyaan dan Diskusi", 2.55, 1.72, 7.6, 0.8, 38, "FFFFFF", True, fill="0F766E", radius=True, align="ctr")
    s.text("Implementasi Sistem Autentikasi Sertifikat Digital Berbasis QR Code dengan Keamanan SHA-256 dan RBAC", 2.65, 3.05, 7.5, 0.95, 22, "334155", fill="F8FAFC", line="D6DEE8", radius=True, align="ctr")
    s.text("Arjuna Pramudya Renaldi | 22.403040.19\nProgram Studi Teknik Komputer | Universitas Borneo Tarakan", 3.1, 4.62, 6.6, 0.65, 17, "475569", align="ctr", margin=0)
    slides.append(s)

    section_bg = {
        "Cover": bgs["teal"],
        "Pembuka": bgs["teal"],
        "Pendahuluan": bgs["blue"],
        "Metode": bgs["purple"],
        "Perancangan": bgs["slate"],
        "Implementasi": bgs["teal"],
        "Hasil": bgs["blue"],
        "Pembahasan": bgs["purple"],
        "Penutup": bgs["teal"],
    }
    for slide in slides:
        slide.bg(section_bg.get(slide.section, bgs["teal"]))
    return slides


if __name__ == "__main__":
    slides = make_slides()
    prof.write_pptx(slides)
    print(f"Created {prof.OUT} with {len(slides)} slides")

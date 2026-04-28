from __future__ import annotations

import html
import zipfile
from datetime import datetime, timezone
from pathlib import Path


OUT = Path("ppt_seminar_hasil_sistem_autentikasi_sertifikat.pptx")
SLIDE_W = 13_333_333
SLIDE_H = 7_500_000


def emu(inch: float) -> int:
    return int(inch * 914400)


def esc(text: str) -> str:
    return html.escape(text, quote=False)


class Slide:
    def __init__(self, title: str, subtitle: str | None = None, section: str | None = None):
        self.title = title
        self.subtitle = subtitle
        self.section = section
        self.shapes: list[str] = []
        self._id = 10

    def next_id(self) -> int:
        self._id += 1
        return self._id

    def text(
        self,
        text: str,
        x: float,
        y: float,
        w: float,
        h: float,
        size: int = 24,
        color: str = "1F2937",
        bold: bool = False,
        name: str = "Text",
        fill: str | None = None,
        line: str | None = None,
        radius: bool = False,
        margin: int = 120000,
    ) -> None:
        sid = self.next_id()
        shape = "roundRect" if radius else "rect"
        fill_xml = f'<a:solidFill><a:srgbClr val="{fill}"/></a:solidFill>' if fill else "<a:noFill/>"
        line_xml = f'<a:ln w="12700"><a:solidFill><a:srgbClr val="{line}"/></a:solidFill></a:ln>' if line else "<a:ln><a:noFill/></a:ln>"
        paras = []
        for idx, raw in enumerate(text.split("\n")):
            rpr = f'sz="{size * 100}" b="{1 if bold else 0}"'
            if idx == 0:
                space = ' marL="0"'
            else:
                space = ' marL="0"'
            paras.append(
                f'<a:p><a:pPr{space}/><a:r><a:rPr {rpr} lang="id-ID">'
                f'<a:solidFill><a:srgbClr val="{color}"/></a:solidFill>'
                f'<a:latin typeface="Aptos"/></a:rPr><a:t>{esc(raw)}</a:t></a:r></a:p>'
            )
        self.shapes.append(
            f"""
            <p:sp>
              <p:nvSpPr><p:cNvPr id="{sid}" name="{esc(name)}"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr>
              <p:spPr>
                <a:xfrm><a:off x="{emu(x)}" y="{emu(y)}"/><a:ext cx="{emu(w)}" cy="{emu(h)}"/></a:xfrm>
                <a:prstGeom prst="{shape}"><a:avLst/></a:prstGeom>
                {fill_xml}{line_xml}
              </p:spPr>
              <p:txBody>
                <a:bodyPr wrap="square" lIns="{margin}" tIns="{margin}" rIns="{margin}" bIns="{margin}"/>
                <a:lstStyle/>
                {''.join(paras)}
              </p:txBody>
            </p:sp>
            """
        )

    def bullet_box(self, items: list[str], x: float, y: float, w: float, h: float, size: int = 21) -> None:
        text = "\n".join(f"- {item}" for item in items)
        self.text(text, x, y, w, h, size=size, color="243447")

    def metric(self, label: str, value: str, x: float, y: float, w: float, color: str) -> None:
        self.text(value, x, y, w, 0.62, size=30, color="FFFFFF", bold=True, fill=color, radius=True, margin=90000)
        self.text(label, x, y + 0.66, w, 0.48, size=13, color="475569", fill="F8FAFC", line="D9E2EC", radius=True, margin=80000)

    def divider(self, y: float = 1.24, color: str = "0F766E") -> None:
        self.text("", 0.64, y, 12.05, 0.03, fill=color, line=color)

    def build(self) -> str:
        if self.section:
            self.text(self.section.upper(), 0.64, 0.27, 2.2, 0.35, size=10, color="0F766E", bold=True)
        self.text(self.title, 0.60, 0.56, 11.9, 0.60, size=30, color="111827", bold=True, margin=0)
        if self.subtitle:
            self.text(self.subtitle, 0.64, 1.10, 10.8, 0.42, size=14, color="64748B", margin=0)
        self.divider(1.52)
        body = "".join(self.shapes)
        return f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
       xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
       xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:bg><p:bgPr><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill><a:effectLst/></p:bgPr></p:bg>
    <p:spTree>
      <p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>
      <p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>
      {body}
    </p:spTree>
  </p:cSld>
  <p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr>
</p:sld>"""


def make_slides() -> list[Slide]:
    slides: list[Slide] = []

    s = Slide("Sistem Autentikasi Sertifikat Digital", "Seminar Hasil | Laravel, QR Code, dan SHA-256", "Presentasi")
    s.text("Nama: Arjuna\nNIM: [isi NIM]\nProgram Studi: [isi prodi]\nDosen Pembimbing: [isi nama dosen]", 0.78, 1.9, 5.2, 2.2, 20, "334155", fill="F8FAFC", line="D9E2EC", radius=True)
    s.text("Fokus penelitian:\nMembangun sistem untuk mengelola, memverifikasi, dan menjaga integritas sertifikat digital melalui nomor sertifikat, QR Code, hak akses pengguna, dan pengecekan hash file.", 6.25, 1.9, 5.95, 2.45, 22, "FFFFFF", fill="0F766E", radius=True)
    s.metric("Black box testing", "60/60", 0.78, 5.25, 2.2, "2563EB")
    s.metric("Security testing", "9/9", 3.22, 5.25, 2.2, "0F766E")
    s.metric("OWASP testing", "23/23", 5.66, 5.25, 2.2, "7C3AED")
    s.metric("Keberhasilan", "100%", 8.10, 5.25, 2.2, "DC2626")
    slides.append(s)

    s = Slide("Latar Belakang", "Permasalahan utama berada pada validasi keaslian dokumen sertifikat.", "Pendahuluan")
    s.bullet_box([
        "Sertifikat digital mudah disebarkan, tetapi perlu mekanisme pembuktian keaslian.",
        "Pengecekan manual tanpa sistem berisiko lambat, tidak konsisten, dan sulit diaudit.",
        "QR Code dapat mempercepat verifikasi, sedangkan hash SHA-256 membantu mendeteksi perubahan file.",
        "Hak akses admin dan superadmin dibutuhkan agar pengelolaan data tetap terkontrol."
    ], 0.75, 2.0, 6.0, 3.4)
    s.text("Kebutuhan sistem", 7.20, 2.05, 4.7, 0.45, 22, "111827", True, margin=0)
    s.text("Verifikasi publik\nPengelolaan sertifikat\nKeamanan akses\nRiwayat aktivitas\nValidasi integritas file", 7.15, 2.65, 4.75, 2.9, 22, "FFFFFF", fill="155E75", radius=True)
    slides.append(s)

    s = Slide("Rumusan Masalah dan Tujuan", "Slide ini bisa dipakai untuk menjelaskan arah penelitian secara cepat.", "Pendahuluan")
    s.text("Rumusan masalah", 0.82, 2.0, 5.35, 0.5, 24, "111827", True, margin=0)
    s.bullet_box([
        "Bagaimana membangun sistem autentikasi sertifikat digital berbasis web?",
        "Bagaimana menyediakan verifikasi sertifikat melalui input manual dan QR Code?",
        "Bagaimana menerapkan kontrol akses serta pengujian keamanan pada sistem?"
    ], 0.75, 2.55, 5.65, 2.65)
    s.text("Tujuan penelitian", 6.95, 2.0, 5.35, 0.5, 24, "111827", True, margin=0)
    s.bullet_box([
        "Menghasilkan aplikasi pengelolaan sertifikat digital.",
        "Menyediakan fitur verifikasi yang cepat dan mudah digunakan.",
        "Menerapkan hash SHA-256, autentikasi, otorisasi, CSRF protection, dan rate limiting.",
        "Menguji fungsi dan keamanan sistem secara terukur."
    ], 6.88, 2.55, 5.65, 2.65)
    slides.append(s)

    s = Slide("Metode Pengembangan dan Pengujian", "Pengujian difokuskan pada fungsi utama, keamanan, dan penerimaan pengguna.", "Metodologi")
    s.text("Pengembangan", 0.85, 2.0, 3.5, 0.5, 23, "111827", True, margin=0)
    s.text("Analisis kebutuhan\nPerancangan database dan alur fitur\nImplementasi Laravel\nIntegrasi QR Code dan hash\nPengujian dan evaluasi", 0.78, 2.58, 3.55, 2.85, 19, "334155", fill="F8FAFC", line="CBD5E1", radius=True)
    s.text("Pengujian", 4.85, 2.0, 3.5, 0.5, 23, "111827", True, margin=0)
    s.text("Black box testing\nSecurity testing\nOWASP Top 10 2021\nSession manipulation testing\nKuesioner pengguna", 4.78, 2.58, 3.55, 2.85, 19, "334155", fill="F8FAFC", line="CBD5E1", radius=True)
    s.text("Output", 8.85, 2.0, 3.5, 0.5, 23, "111827", True, margin=0)
    s.text("Aplikasi berjalan\nData sertifikat terkelola\nVerifikasi manual dan QR aktif\nHak akses sesuai role\nHasil pengujian terdokumentasi", 8.78, 2.58, 3.55, 2.85, 19, "334155", fill="F8FAFC", line="CBD5E1", radius=True)
    slides.append(s)

    s = Slide("Arsitektur Sistem", "Sistem dibangun sebagai aplikasi web Laravel dengan modul publik dan modul admin.", "Implementasi")
    s.text("Viewer / Publik", 0.90, 2.15, 2.7, 0.95, 21, "FFFFFF", True, fill="2563EB", radius=True)
    s.text("Verifikasi nomor sertifikat\nUpload gambar QR Code", 0.90, 3.22, 2.7, 1.25, 15, "334155", fill="EFF6FF", line="BFDBFE", radius=True)
    s.text("Aplikasi Laravel", 4.15, 2.15, 3.15, 0.95, 21, "FFFFFF", True, fill="0F766E", radius=True)
    s.text("Controller\nMiddleware role\nValidasi request\nActivity log", 4.15, 3.22, 3.15, 1.25, 15, "334155", fill="F0FDFA", line="99F6E4", radius=True)
    s.text("Database + Storage", 7.85, 2.15, 3.0, 0.95, 21, "FFFFFF", True, fill="7C3AED", radius=True)
    s.text("Users\nStudents\nCertificates\nFile sertifikat dan QR", 7.85, 3.22, 3.0, 1.25, 15, "334155", fill="F5F3FF", line="DDD6FE", radius=True)
    s.text("Admin / Superadmin", 4.15, 5.18, 3.15, 0.65, 19, "FFFFFF", True, fill="DC2626", radius=True)
    s.text("Upload, edit, daftar sertifikat, manajemen user, log aktivitas", 7.55, 5.22, 3.65, 0.55, 15, "475569", margin=0)
    slides.append(s)

    s = Slide("Fitur Utama Sistem", "Fitur dibagi menjadi akses publik, admin, dan superadmin.", "Implementasi")
    s.text("Publik", 0.78, 2.0, 3.6, 0.55, 23, "FFFFFF", True, fill="2563EB", radius=True)
    s.bullet_box(["Verifikasi manual", "Verifikasi QR upload", "Informasi hasil verifikasi", "Pengecekan integritas file"], 0.78, 2.70, 3.6, 2.75)
    s.text("Admin", 4.85, 2.0, 3.6, 0.55, 23, "FFFFFF", True, fill="0F766E", radius=True)
    s.bullet_box(["Dashboard", "Upload sertifikat", "Daftar, cari, dan filter data", "Edit sertifikat", "Verifikasi dari panel admin"], 4.85, 2.70, 3.6, 2.75)
    s.text("Superadmin", 8.92, 2.0, 3.6, 0.55, 23, "FFFFFF", True, fill="7C3AED", radius=True)
    s.bullet_box(["Manajemen user", "Hapus sertifikat", "Log aktivitas", "Kontrol penuh role dan akses"], 8.92, 2.70, 3.6, 2.75)
    slides.append(s)

    s = Slide("Alur Verifikasi Sertifikat", "Pengguna dapat memverifikasi melalui nomor sertifikat atau QR Code.", "Implementasi")
    steps = [
        ("1", "Input nomor / unggah QR"),
        ("2", "Sistem membaca nomor sertifikat"),
        ("3", "Data dicari pada database"),
        ("4", "Hash SHA-256 file dibandingkan"),
        ("5", "Hasil valid/tidak valid ditampilkan"),
    ]
    x = 0.75
    for num, label in steps:
        s.text(num, x, 2.30, 0.56, 0.56, 18, "FFFFFF", True, fill="0F766E", radius=True, margin=75000)
        s.text(label, x, 3.05, 2.05, 1.45, 17, "334155", fill="F8FAFC", line="CBD5E1", radius=True)
        x += 2.45
    s.text("Keluaran", 0.82, 5.38, 1.8, 0.38, 18, "111827", True, margin=0)
    s.text("Data sertifikat valid ditampilkan kepada pengguna. Jika nomor tidak ditemukan, QR tidak terbaca, atau hash file berubah, sistem menampilkan pesan gagal.", 2.25, 5.25, 9.85, 0.8, 17, "475569", margin=0)
    slides.append(s)

    s = Slide("Implementasi Keamanan", "Keamanan diterapkan pada autentikasi, otorisasi, validasi, dan integritas data.", "Implementasi")
    s.metric("Hash integritas", "SHA-256", 0.78, 2.0, 2.55, "0F766E")
    s.metric("Password", "Bcrypt", 3.72, 2.0, 2.25, "2563EB")
    s.metric("Akses", "Role", 6.35, 2.0, 2.15, "7C3AED")
    s.metric("Request", "Throttle", 8.90, 2.0, 2.45, "DC2626")
    s.bullet_box([
        "CSRF token melindungi request perubahan data.",
        "Middleware role membatasi menu dan route admin/superadmin.",
        "Validasi upload membatasi jenis file dan ukuran.",
        "Activity log mencatat upload, edit, hapus, dan aktivitas penting.",
        "Security header dan validasi input mengurangi risiko serangan umum aplikasi web."
    ], 0.82, 3.65, 10.9, 2.35, 21)
    slides.append(s)

    s = Slide("Hasil Black Box Testing", "Pengujian fungsi utama dilakukan pada 60 skenario.", "Hasil")
    s.metric("Total skenario", "60", 0.82, 2.0, 2.4, "2563EB")
    s.metric("Berhasil", "60", 3.55, 2.0, 2.4, "0F766E")
    s.metric("Gagal", "0", 6.28, 2.0, 2.4, "DC2626")
    s.metric("Keberhasilan", "100%", 9.01, 2.0, 2.4, "7C3AED")
    s.bullet_box([
        "Modul yang diuji: verifikasi manual, verifikasi QR, login/logout, dashboard, upload, daftar, edit, hapus, log aktivitas, user, role, dan throttle.",
        "Seluruh fitur utama berjalan sesuai kebutuhan fungsional.",
        "Kondisi berhasil dan gagal diuji untuk memastikan respons sistem sesuai."
    ], 0.85, 3.75, 10.9, 2.05, 21)
    slides.append(s)

    s = Slide("Hasil Security Testing", "Pengujian keamanan dilakukan terhadap akses, session, file, dan request.", "Hasil")
    s.metric("Skenario", "9", 0.82, 2.0, 2.45, "2563EB")
    s.metric("Berhasil", "9", 3.62, 2.0, 2.45, "0F766E")
    s.metric("Gagal", "0", 6.42, 2.0, 2.45, "DC2626")
    s.metric("Status", "Sangat Aman", 9.22, 2.0, 2.75, "7C3AED")
    s.bullet_box([
        "Autentikasi memastikan hanya pengguna valid yang dapat masuk.",
        "Otorisasi mencegah user mengakses fitur di luar role.",
        "Manipulasi session tidak meningkatkan hak akses.",
        "CSRF protection, rate limiting, dan validasi file berjalan sesuai pengujian.",
        "Integrity check mendeteksi perubahan file sertifikat melalui hash."
    ], 0.85, 3.75, 10.9, 2.1, 20)
    slides.append(s)

    s = Slide("Hasil OWASP Security Testing", "Pengujian disesuaikan dengan kategori OWASP Top 10 2021 yang relevan.", "Hasil")
    s.metric("Skenario", "23", 0.82, 2.0, 2.55, "2563EB")
    s.metric("Berhasil", "23", 3.72, 2.0, 2.55, "0F766E")
    s.metric("Gagal", "0", 6.62, 2.0, 2.55, "DC2626")
    s.metric("Keberhasilan", "100%", 9.52, 2.0, 2.55, "7C3AED")
    s.text("Kategori yang diuji", 0.85, 3.65, 3.6, 0.42, 21, "111827", True, margin=0)
    s.bullet_box([
        "Broken Access Control",
        "Cryptographic Failures",
        "Injection",
        "Security Misconfiguration",
        "Identification and Authentication Failures",
        "Software and Data Integrity Failures",
        "Logging and Monitoring Failures"
    ], 0.82, 4.15, 10.8, 1.85, 17)
    slides.append(s)

    s = Slide("Evaluasi Pengguna / Kuesioner", "Bagian ini disiapkan untuk memasukkan hasil rekap kuesioner.", "Hasil")
    s.text("Aspek penilaian", 0.85, 2.0, 3.6, 0.45, 22, "111827", True, margin=0)
    s.bullet_box(["Kemudahan penggunaan", "Tampilan antarmuka", "Kejelasan informasi", "Kecepatan proses", "Keamanan sistem", "Manfaat sistem"], 0.82, 2.55, 4.4, 2.75, 20)
    s.text("Isi data hasil", 6.10, 2.0, 4.2, 0.45, 22, "111827", True, margin=0)
    s.text("Jumlah responden: [isi]\nRata-rata skor: [isi]\nKategori: [isi]\nCatatan utama: [isi ringkasan komentar responden]", 6.05, 2.55, 5.4, 2.3, 21, "334155", fill="F8FAFC", line="CBD5E1", radius=True)
    slides.append(s)

    s = Slide("Dokumentasi Implementasi", "Gunakan slide ini untuk menaruh screenshot saat presentasi.", "Demo")
    s.text("Screenshot 1\nHalaman verifikasi publik", 0.82, 2.1, 3.55, 2.5, 22, "64748B", fill="F8FAFC", line="CBD5E1", radius=True)
    s.text("Screenshot 2\nDashboard admin", 4.82, 2.1, 3.55, 2.5, 22, "64748B", fill="F8FAFC", line="CBD5E1", radius=True)
    s.text("Screenshot 3\nUpload / daftar sertifikat", 8.82, 2.1, 3.55, 2.5, 22, "64748B", fill="F8FAFC", line="CBD5E1", radius=True)
    s.text("Catatan demo: tampilkan alur verifikasi nomor sertifikat, upload QR, login admin, upload sertifikat, serta perbedaan akses admin dan superadmin.", 0.92, 5.25, 10.9, 0.8, 18, "475569", margin=0)
    slides.append(s)

    s = Slide("Kesimpulan", "Hasil pengembangan dan pengujian menunjukkan sistem memenuhi kebutuhan utama.", "Penutup")
    s.bullet_box([
        "Sistem autentikasi sertifikat digital berhasil dibangun menggunakan Laravel.",
        "Verifikasi sertifikat dapat dilakukan melalui nomor sertifikat dan QR Code.",
        "Hash SHA-256 membantu memastikan integritas file sertifikat.",
        "Role admin dan superadmin membatasi akses sesuai tanggung jawab pengguna.",
        "Black box, security testing, dan OWASP testing menunjukkan tingkat keberhasilan 100%."
    ], 0.85, 2.0, 10.7, 3.25, 22)
    slides.append(s)

    s = Slide("Saran Pengembangan", "Beberapa peningkatan dapat dilakukan untuk penelitian atau versi sistem berikutnya.", "Penutup")
    s.bullet_box([
        "Menambahkan scan QR berbasis kamera yang lebih stabil pada berbagai perangkat.",
        "Mengintegrasikan tanda tangan digital berbasis sertifikat elektronik resmi.",
        "Menyediakan dashboard analitik verifikasi dan aktivitas pengguna.",
        "Menambahkan notifikasi masa berlaku sertifikat.",
        "Melakukan deployment production dengan HTTPS, backup terjadwal, dan monitoring."
    ], 0.85, 2.0, 10.7, 3.25, 22)
    slides.append(s)

    s = Slide("Terima Kasih", "Sistem Autentikasi Sertifikat Digital", "Penutup")
    s.text("Pertanyaan dan diskusi", 2.1, 2.4, 8.9, 0.85, 38, "FFFFFF", True, fill="0F766E", radius=True)
    s.text("Kontak: [isi email/nomor]\nRepositori / demo: [isi jika diperlukan]", 3.65, 3.75, 5.8, 1.2, 22, "334155", fill="F8FAFC", line="CBD5E1", radius=True)
    slides.append(s)

    return slides


def content_types(slide_count: int) -> str:
    overrides = "\n".join(
        f'<Override PartName="/ppt/slides/slide{i}.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>'
        for i in range(1, slide_count + 1)
    )
    return f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/>
  <Override PartName="/ppt/presProps.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presProps+xml"/>
  <Override PartName="/ppt/viewProps.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.viewProps+xml"/>
  <Override PartName="/ppt/tableStyles.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.tableStyles+xml"/>
  <Override PartName="/ppt/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>
  <Override PartName="/ppt/slideMasters/slideMaster1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideMaster+xml"/>
  <Override PartName="/ppt/slideLayouts/slideLayout1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/>
  {overrides}
</Types>"""


def presentation_xml(slide_count: int) -> str:
    ids = "\n".join(f'<p:sldId id="{255+i}" r:id="rId{i+1}"/>' for i in range(1, slide_count + 1))
    return f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:presentation xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
                xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
                xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:sldMasterIdLst><p:sldMasterId id="2147483648" r:id="rId1"/></p:sldMasterIdLst>
  <p:sldIdLst>{ids}</p:sldIdLst>
  <p:sldSz cx="{SLIDE_W}" cy="{SLIDE_H}" type="wide"/>
  <p:notesSz cx="6858000" cy="9144000"/>
  <p:defaultTextStyle/>
</p:presentation>"""


def presentation_rels(slide_count: int) -> str:
    rels = ['<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="slideMasters/slideMaster1.xml"/>']
    for i in range(1, slide_count + 1):
        rels.append(f'<Relationship Id="rId{i+1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide{i}.xml"/>')
    rels.extend([
        '<Relationship Id="rId100" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/>',
        '<Relationship Id="rId101" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/presProps" Target="presProps.xml"/>',
        '<Relationship Id="rId102" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/viewProps" Target="viewProps.xml"/>',
        '<Relationship Id="rId103" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/tableStyles" Target="tableStyles.xml"/>',
    ])
    return f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">{''.join(rels)}</Relationships>"""


COMMON_RELS = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="ppt/presentation.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>"""


MASTER = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sldMaster xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
             xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
             xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld><p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr></p:spTree></p:cSld>
  <p:clrMap bg1="lt1" tx1="dk1" bg2="lt2" tx2="dk2" accent1="accent1" accent2="accent2" accent3="accent3" accent4="accent4" accent5="accent5" accent6="accent6" hlink="hlink" folHlink="folHlink"/>
  <p:sldLayoutIdLst><p:sldLayoutId id="2147483649" r:id="rId1"/></p:sldLayoutIdLst>
  <p:txStyles><p:titleStyle/><p:bodyStyle/><p:otherStyle/></p:txStyles>
</p:sldMaster>"""


MASTER_RELS = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="../theme/theme1.xml"/>
</Relationships>"""


LAYOUT = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sldLayout xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
             xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
             xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" type="blank" preserve="1">
  <p:cSld name="Blank"><p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr></p:spTree></p:cSld>
  <p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr>
</p:sldLayout>"""


LAYOUT_RELS = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="../slideMasters/slideMaster1.xml"/>
</Relationships>"""


THEME = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Semhas">
  <a:themeElements>
    <a:clrScheme name="Semhas">
      <a:dk1><a:srgbClr val="111827"/></a:dk1><a:lt1><a:srgbClr val="FFFFFF"/></a:lt1>
      <a:dk2><a:srgbClr val="334155"/></a:dk2><a:lt2><a:srgbClr val="F8FAFC"/></a:lt2>
      <a:accent1><a:srgbClr val="0F766E"/></a:accent1><a:accent2><a:srgbClr val="2563EB"/></a:accent2>
      <a:accent3><a:srgbClr val="7C3AED"/></a:accent3><a:accent4><a:srgbClr val="DC2626"/></a:accent4>
      <a:accent5><a:srgbClr val="F59E0B"/></a:accent5><a:accent6><a:srgbClr val="475569"/></a:accent6>
      <a:hlink><a:srgbClr val="2563EB"/></a:hlink><a:folHlink><a:srgbClr val="7C3AED"/></a:folHlink>
    </a:clrScheme>
    <a:fontScheme name="Aptos"><a:majorFont><a:latin typeface="Aptos Display"/></a:majorFont><a:minorFont><a:latin typeface="Aptos"/></a:minorFont></a:fontScheme>
    <a:fmtScheme name="Semhas"><a:fillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:fillStyleLst><a:lnStyleLst><a:ln w="6350"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:ln></a:lnStyleLst><a:effectStyleLst><a:effectStyle><a:effectLst/></a:effectStyle></a:effectStyleLst><a:bgFillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:bgFillStyleLst></a:fmtScheme>
  </a:themeElements>
</a:theme>"""


def write_pptx(slides: list[Slide]) -> None:
    now = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    with zipfile.ZipFile(OUT, "w", zipfile.ZIP_DEFLATED) as z:
        z.writestr("[Content_Types].xml", content_types(len(slides)))
        z.writestr("_rels/.rels", COMMON_RELS)
        z.writestr("docProps/core.xml", f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>PPT Seminar Hasil Sistem Autentikasi Sertifikat Digital</dc:title>
  <dc:creator>Codex</dc:creator>
  <cp:lastModifiedBy>Codex</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">{now}</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">{now}</dcterms:modified>
</cp:coreProperties>""")
        z.writestr("docProps/app.xml", f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Microsoft PowerPoint</Application><PresentationFormat>Widescreen</PresentationFormat><Slides>{len(slides)}</Slides>
</Properties>""")
        z.writestr("ppt/presentation.xml", presentation_xml(len(slides)))
        z.writestr("ppt/_rels/presentation.xml.rels", presentation_rels(len(slides)))
        z.writestr("ppt/presProps.xml", '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:presentationPr xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"/>')
        z.writestr("ppt/viewProps.xml", '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:viewPr xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"/>')
        z.writestr("ppt/tableStyles.xml", '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><a:tblStyleLst xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" def="{5C22544A-7EE6-4342-B048-85BDC9FD1C3A}"/>')
        z.writestr("ppt/theme/theme1.xml", THEME)
        z.writestr("ppt/slideMasters/slideMaster1.xml", MASTER)
        z.writestr("ppt/slideMasters/_rels/slideMaster1.xml.rels", MASTER_RELS)
        z.writestr("ppt/slideLayouts/slideLayout1.xml", LAYOUT)
        z.writestr("ppt/slideLayouts/_rels/slideLayout1.xml.rels", LAYOUT_RELS)
        for i, slide in enumerate(slides, start=1):
            z.writestr(f"ppt/slides/slide{i}.xml", slide.build())
            z.writestr(f"ppt/slides/_rels/slide{i}.xml.rels", """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>
</Relationships>""")


if __name__ == "__main__":
    slides = make_slides()
    write_pptx(slides)
    print(f"Created {OUT} with {len(slides)} slides")

from __future__ import annotations

import html
import mimetypes
import shutil
import zipfile
from datetime import datetime, timezone
from pathlib import Path
from xml.etree import ElementTree as ET

from PIL import Image
from PIL import ImageDraw, ImageFilter


OUT = Path("ppt_semhas_skripsi_arjuna_profesional.pptx")
MEDIA = Path("tools/skripsi_media")
BG_DIR = Path("tools/ppt_backgrounds")
SLIDE_W_IN = 13.333
SLIDE_H_IN = 7.5
SLIDE_W = 13_333_333
SLIDE_H = 7_500_000


def emu(inch: float) -> int:
    return int(inch * 914400)


def esc(text: str) -> str:
    return html.escape(text, quote=False)


class Slide:
    def __init__(self, title: str, section: str = "", subtitle: str = ""):
        self.title = title
        self.section = section
        self.subtitle = subtitle
        self.shapes: list[str] = []
        self.images: list[dict] = []
        self.bg_path: Path | None = None
        self._id = 20

    def bg(self, path: str | Path) -> None:
        self.bg_path = Path(path)

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
        size: int = 20,
        color: str = "263238",
        bold: bool = False,
        fill: str | None = None,
        line: str | None = None,
        radius: bool = False,
        margin: int = 100000,
        align: str = "l",
        name: str = "Text",
    ) -> None:
        sid = self.next_id()
        shape = "roundRect" if radius else "rect"
        fill_xml = f'<a:solidFill><a:srgbClr val="{fill}"/></a:solidFill>' if fill else "<a:noFill/>"
        line_xml = f'<a:ln w="10000"><a:solidFill><a:srgbClr val="{line}"/></a:solidFill></a:ln>' if line else "<a:ln><a:noFill/></a:ln>"
        paras = []
        for raw in text.split("\n"):
            paras.append(
                f'<a:p><a:pPr algn="{align}"/><a:r><a:rPr sz="{size * 100}" b="{1 if bold else 0}" lang="id-ID">'
                f'<a:solidFill><a:srgbClr val="{color}"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr>'
                f"<a:t>{esc(raw)}</a:t></a:r></a:p>"
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
              <p:txBody><a:bodyPr wrap="square" lIns="{margin}" tIns="{margin}" rIns="{margin}" bIns="{margin}"/><a:lstStyle/>{''.join(paras)}</p:txBody>
            </p:sp>
            """
        )

    def bullet(self, items: list[str], x: float, y: float, w: float, h: float, size: int = 20) -> None:
        self.text("\n".join(f"- {item}" for item in items), x, y, w, h, size=size, color="374151")

    def pill(self, text: str, x: float, y: float, w: float, color: str = "0F766E") -> None:
        self.text(text, x, y, w, 0.38, 12, "FFFFFF", True, fill=color, radius=True, margin=65000, align="ctr")

    def metric(self, label: str, value: str, x: float, y: float, w: float, color: str) -> None:
        self.text(value, x, y, w, 0.64, 28, "FFFFFF", True, fill=color, radius=True, align="ctr", margin=65000)
        self.text(label, x, y + 0.70, w, 0.45, 12, "475569", fill="F8FAFC", line="D6DEE8", radius=True, align="ctr", margin=65000)

    def image(self, path: str | Path, x: float, y: float, w: float, h: float, fit: str = "contain", line: str = "D6DEE8") -> None:
        p = Path(path)
        sid = self.next_id()
        with Image.open(p) as im:
            iw, ih = im.size
        box_ratio = w / h
        img_ratio = iw / ih
        if fit == "cover":
            dw, dh = (h * img_ratio, h) if img_ratio > box_ratio else (w, w / img_ratio)
        else:
            dw, dh = (w, w / img_ratio) if img_ratio > box_ratio else (h * img_ratio, h)
        ox = x + (w - dw) / 2
        oy = y + (h - dh) / 2
        self.images.append({"path": p, "sid": sid, "x": ox, "y": oy, "w": dw, "h": dh, "line": line})

    def framed_image(self, path: str | Path, x: float, y: float, w: float, h: float, caption: str = "", fit: str = "contain") -> None:
        self.text("", x, y, w, h, fill="FFFFFF", line="D6DEE8", radius=True, margin=0)
        # Keep images inside a generous frame. This is intentionally conservative:
        # seminar diagrams are more valuable when readable than when aggressively cropped.
        self.image(path, x + 0.12, y + 0.12, w - 0.24, h - (0.50 if caption else 0.24), fit=fit)
        if caption:
            self.text(caption, x + 0.10, y + h - 0.33, w - 0.20, 0.25, 10, "64748B", align="ctr", margin=10000)

    def header_footer(self, idx: int, total: int) -> None:
        self.text("", 0, 0, SLIDE_W_IN, 0.13, fill="0F766E", line="0F766E", margin=0)
        if self.section:
            self.pill(self.section.upper(), 0.58, 0.35, 1.85)
        self.text(self.title, 0.58, 0.76, 10.9, 0.52, 27, "111827", True, margin=0)
        if self.subtitle:
            self.text(self.subtitle, 0.60, 1.23, 10.7, 0.28, 12, "64748B", margin=0)
        self.text(f"{idx:02d}/{total:02d}", 11.95, 6.92, 0.75, 0.24, 10, "64748B", align="r", margin=0)
        self.text("Seminar Hasil Skripsi | Teknik Komputer UBT", 0.58, 6.92, 5.2, 0.24, 10, "64748B", margin=0)

    def build(self, idx: int, total: int) -> tuple[str, list[dict]]:
        self.header_footer(idx, total)
        bg_xml = ""
        bg_imgs = []
        if self.bg_path:
            bg_sid = 2
            bg_xml = f"""
                <p:pic>
                  <p:nvPicPr><p:cNvPr id="{bg_sid}" name="{esc(self.bg_path.name)}"/><p:cNvPicPr/><p:nvPr/></p:nvPicPr>
                  <p:blipFill><a:blip r:embed="rId2"/><a:stretch><a:fillRect/></a:stretch></p:blipFill>
                  <p:spPr>
                    <a:xfrm><a:off x="0" y="0"/><a:ext cx="{SLIDE_W}" cy="{SLIDE_H}"/></a:xfrm>
                    <a:prstGeom prst="rect"><a:avLst/></a:prstGeom>
                    <a:ln><a:noFill/></a:ln>
                  </p:spPr>
                </p:pic>
                """
            bg_imgs.append({"path": self.bg_path, "background": True})
        pic_xml = []
        for i, img in enumerate(self.images, start=1):
            rid = f"rId{i + (2 if self.bg_path else 1)}"
            pic_xml.append(
                f"""
                <p:pic>
                  <p:nvPicPr><p:cNvPr id="{img['sid']}" name="{esc(img['path'].name)}"/><p:cNvPicPr/><p:nvPr/></p:nvPicPr>
                  <p:blipFill><a:blip r:embed="{rid}"/><a:stretch><a:fillRect/></a:stretch></p:blipFill>
                  <p:spPr>
                    <a:xfrm><a:off x="{emu(img['x'])}" y="{emu(img['y'])}"/><a:ext cx="{emu(img['w'])}" cy="{emu(img['h'])}"/></a:xfrm>
                    <a:prstGeom prst="rect"><a:avLst/></a:prstGeom>
                    <a:ln w="6350"><a:solidFill><a:srgbClr val="{img['line']}"/></a:solidFill></a:ln>
                  </p:spPr>
                </p:pic>
                """
            )
        xml = f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
       xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
       xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:bg><p:bgPr><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill><a:effectLst/></p:bgPr></p:bg>
    <p:spTree>
      <p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>
      <p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>
      {bg_xml}
      {''.join(self.shapes)}
      {''.join(pic_xml)}
    </p:spTree>
  </p:cSld>
  <p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr>
</p:sld>"""
        return xml, bg_imgs + self.images


def make_bg(path: Path, accent: tuple[int, int, int], deep: tuple[int, int, int], variant: int) -> None:
    w, h = 1920, 1080
    img = Image.new("RGB", (w, h), "white")
    px = img.load()
    for y in range(h):
        for x in range(w):
            t = (x / w * 0.55) + (y / h * 0.45)
            base = int(255 - 18 * t)
            px[x, y] = (base, min(255, base + 3), min(255, base + 7))

    overlay = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    d = ImageDraw.Draw(overlay)

    def iso_cube(cx: int, cy: int, s: int, alpha: int) -> None:
        top = [(cx, cy - s // 2), (cx + s, cy), (cx, cy + s // 2), (cx - s, cy)]
        left = [(cx - s, cy), (cx, cy + s // 2), (cx, cy + s + s // 2), (cx - s, cy + s)]
        right = [(cx + s, cy), (cx, cy + s // 2), (cx, cy + s + s // 2), (cx + s, cy + s)]
        d.polygon(left, fill=(deep[0], deep[1], deep[2], alpha))
        d.polygon(right, fill=(accent[0], accent[1], accent[2], alpha + 15))
        d.polygon(top, fill=(255, 255, 255, min(170, alpha + 60)))
        d.line(top + [top[0]], fill=(180, 195, 210, alpha + 30), width=2)
        d.line(left + [left[0]], fill=(180, 195, 210, alpha), width=2)
        d.line(right + [right[0]], fill=(180, 195, 210, alpha), width=2)

    # Angled formal bands and 3D blocks, placed away from the main reading area.
    d.polygon([(w * 0.70, 0), (w, 0), (w, h), (w * 0.84, h)], fill=(*accent, 28))
    d.polygon([(0, h * 0.78), (w * 0.30, h), (0, h)], fill=(*deep, 20))
    d.line([(110, 150), (650, 70), (1100, 150)], fill=(*accent, 38), width=3)
    d.line([(1200, 890), (1700, 820), (1910, 920)], fill=(*deep, 38), width=3)

    if variant % 3 == 0:
        cubes = [(1660, 145, 86, 38), (1785, 220, 58, 34), (1510, 870, 62, 30)]
    elif variant % 3 == 1:
        cubes = [(1580, 175, 72, 36), (1745, 130, 48, 30), (1370, 910, 72, 28)]
    else:
        cubes = [(1680, 785, 80, 34), (1800, 865, 52, 30), (1460, 145, 66, 28)]
    for cx, cy, s, a in cubes:
        iso_cube(cx, cy, s, a)

    # Subtle grid lines add structure without making the slide busy.
    for x in range(120, w, 160):
        d.line([(x, 0), (x + 240, h)], fill=(15, 118, 110, 10), width=1)
    for y in range(90, h, 150):
        d.line([(0, y), (w, y - 100)], fill=(37, 99, 235, 8), width=1)

    overlay = overlay.filter(ImageFilter.GaussianBlur(0.15))
    img = Image.alpha_composite(img.convert("RGBA"), overlay)
    img.save(path)


def ensure_backgrounds() -> dict[str, Path]:
    BG_DIR.mkdir(parents=True, exist_ok=True)
    specs = {
        "teal": ((15, 118, 110), (17, 94, 89)),
        "blue": ((37, 99, 235), (30, 64, 175)),
        "purple": ((124, 58, 237), (91, 33, 182)),
        "red": ((220, 38, 38), (153, 27, 27)),
        "slate": ((71, 85, 105), (30, 41, 59)),
    }
    result = {}
    for i, (name, (accent, deep)) in enumerate(specs.items()):
        path = BG_DIR / f"bg_{name}.png"
        make_bg(path, accent, deep, i)
        result[name] = path
    return result


def media(name: str) -> Path:
    return MEDIA / name


def make_slides() -> list[Slide]:
    slides: list[Slide] = []
    bgs = ensure_backgrounds()

    s = Slide("Implementasi Sistem Autentikasi Sertifikat Digital", "Cover", "Berbasis QR Code dengan Keamanan SHA-256 dan RBAC")
    s.image(media("image1.png"), 0.75, 1.55, 1.25, 1.25)
    s.text("Arjuna Pramudya Renaldi\nNPM: 22.403040.19\nProgram Studi Teknik Komputer\nFakultas Teknik\nUniversitas Borneo Tarakan", 2.20, 1.58, 4.95, 1.55, 18, "334155", fill="F8FAFC", line="D6DEE8", radius=True)
    s.text("Seminar Hasil Skripsi\nPembimbing I: Dedy Harto, S.T., M.T.\nPembimbing II: Awang Pradana, S.Kom., M.Kom.\nTahun 2025", 7.45, 1.58, 4.7, 1.55, 18, "FFFFFF", fill="0F766E", radius=True)
    s.metric("Black Box", "60/60", 1.2, 4.62, 2.15, "2563EB")
    s.metric("Security", "14/14", 3.72, 4.62, 2.15, "0F766E")
    s.metric("Kuesioner", "88,46%", 6.24, 4.62, 2.15, "7C3AED")
    s.metric("Kategori", "Sangat Baik", 8.76, 4.62, 2.75, "DC2626")
    slides.append(s)

    s = Slide("Agenda Presentasi", "Pembuka", "Bagian dibuat ringkas mengikuti alur seminar hasil.")
    agenda = [
        ("Pendahuluan", "Latar belakang, rumusan masalah, tujuan, batasan, manfaat"),
        ("Metode", "R&D, ADDIE, instrumen pengujian, teknik analisis"),
        ("Perancangan", "Kerangka, flowchart, use case, DFD, ERD"),
        ("Implementasi", "Fitur sistem, QR Code, SHA-256, RBAC"),
        ("Hasil", "Black box, security testing, kuesioner Likert"),
        ("Penutup", "Kesimpulan dan saran pengembangan"),
    ]
    y = 1.86
    for i, (head, body) in enumerate(agenda, start=1):
        color = ["0F766E", "2563EB", "7C3AED", "DC2626", "0891B2", "475569"][i - 1]
        s.text(str(i), 0.95, y + 0.03, 0.45, 0.45, 15, "FFFFFF", True, fill=color, radius=True, align="ctr", margin=55000)
        s.text(head, 1.62, y, 2.15, 0.34, 18, "111827", True, margin=0)
        s.text(body, 3.75, y, 7.8, 0.34, 16, "475569", margin=0)
        y += 0.72
    slides.append(s)

    s = Slide("Latar Belakang", "Pendahuluan", "Masalah utama penelitian adalah keaslian, integritas, dan efisiensi verifikasi sertifikat.")
    s.bullet([
        "Sertifikat merupakan bukti formal pencapaian, kompetensi, atau keikutsertaan.",
        "Verifikasi manual membutuhkan waktu dan belum menjamin keabsahan dokumen secara akurat.",
        "Sertifikat digital membutuhkan mekanisme untuk mencegah pemalsuan dan manipulasi file.",
        "QR Code mempercepat akses verifikasi, SHA-256 menjaga integritas dokumen, dan RBAC mengatur hak akses."
    ], 0.72, 1.85, 6.0, 3.4, 21)
    s.text("Arah solusi", 7.15, 1.95, 4.3, 0.46, 22, "FFFFFF", True, fill="0F766E", radius=True, align="ctr")
    s.text("Sistem web Laravel 11 untuk pengelolaan dan autentikasi sertifikat digital yang terstruktur, aman, cepat, serta mudah diverifikasi oleh pihak internal maupun eksternal.", 7.15, 2.68, 4.5, 1.95, 22, "334155", fill="F0FDFA", line="99F6E4", radius=True)
    slides.append(s)

    s = Slide("Rumusan Masalah dan Tujuan", "Pendahuluan", "Rumusan dibuat langsung terhubung dengan output sistem.")
    s.text("Rumusan Masalah", 0.78, 1.86, 5.2, 0.5, 22, "FFFFFF", True, fill="2563EB", radius=True, align="ctr")
    s.bullet([
        "Bagaimana membangun sistem autentikasi sertifikat digital berbasis QR Code?",
        "Bagaimana menerapkan SHA-256 dan RBAC untuk keamanan data dan akses?",
        "Bagaimana menguji fungsi, keamanan, dan penerimaan pengguna terhadap sistem?"
    ], 0.78, 2.62, 5.35, 2.4, 18)
    s.text("Tujuan Penelitian", 6.78, 1.86, 5.2, 0.5, 22, "FFFFFF", True, fill="0F766E", radius=True, align="ctr")
    s.bullet([
        "Menghasilkan sistem pengelolaan dan verifikasi sertifikat digital.",
        "Menerapkan QR Code, SHA-256, dan RBAC pada sistem.",
        "Mengevaluasi sistem melalui black box, security testing, dan kuesioner Likert."
    ], 6.78, 2.62, 5.35, 2.4, 18)
    slides.append(s)

    s = Slide("Batasan dan Manfaat", "Pendahuluan", "Ruang lingkup menjaga penelitian tetap fokus pada prototipe sistem.")
    s.text("Batasan", 0.78, 1.86, 5.2, 0.5, 22, "FFFFFF", True, fill="7C3AED", radius=True, align="ctr")
    s.bullet([
        "Sistem berjalan sebagai aplikasi web pada lingkungan localhost.",
        "Database terpusat menggunakan MySQL.",
        "Role tetap: SuperAdmin, Admin, Viewer/Verifikator.",
        "Keamanan difokuskan pada aplikasi web, bukan keamanan jaringan."
    ], 0.78, 2.62, 5.35, 2.6, 18)
    s.text("Manfaat", 6.78, 1.86, 5.2, 0.5, 22, "FFFFFF", True, fill="0F766E", radius=True, align="ctr")
    s.bullet([
        "Pengelolaan sertifikat lebih terpusat dan terdokumentasi.",
        "Verifikasi keaslian lebih cepat menggunakan QR Code.",
        "Manipulasi file dapat dideteksi melalui SHA-256.",
        "Hak akses dan audit aktivitas lebih jelas melalui RBAC dan log."
    ], 6.78, 2.62, 5.35, 2.6, 18)
    slides.append(s)

    s = Slide("Kerangka Pemikiran", "Perancangan", "Kerangka menunjukkan hubungan masalah, teknologi, implementasi, dan hasil yang diharapkan.")
    s.framed_image(media("image4.png"), 1.15, 1.68, 10.95, 4.95, "Gambar 2.1 Kerangka Pemikiran")
    slides.append(s)

    s = Slide("Metode Penelitian", "Metode", "Penelitian menggunakan R&D dengan model ADDIE dan pendekatan kuantitatif.")
    s.text("Research and Development", 0.80, 1.88, 3.65, 0.55, 21, "FFFFFF", True, fill="2563EB", radius=True, align="ctr")
    s.text("Penelitian terapan untuk menghasilkan produk sistem autentikasi sertifikat digital yang dapat digunakan dan diuji secara praktis.", 0.80, 2.72, 3.65, 1.35, 18, "334155", fill="EFF6FF", line="BFDBFE", radius=True)
    s.text("Model ADDIE", 4.82, 1.88, 3.65, 0.55, 21, "FFFFFF", True, fill="0F766E", radius=True, align="ctr")
    s.text("Analysis, Design, Development, Implementation, dan Evaluation sebagai tahapan pengembangan sistem.", 4.82, 2.72, 3.65, 1.35, 18, "334155", fill="F0FDFA", line="99F6E4", radius=True)
    s.text("Analisis Kuantitatif", 8.84, 1.88, 3.65, 0.55, 21, "FFFFFF", True, fill="7C3AED", radius=True, align="ctr")
    s.text("Data dianalisis dari persentase keberhasilan black box, tingkat keamanan, dan skor kuesioner Likert.", 8.84, 2.72, 3.65, 1.35, 18, "334155", fill="F5F3FF", line="DDD6FE", radius=True)
    s.bullet(["Instrumen: lembar black box, script security testing, script OWASP, dan kuesioner skala Likert.", "Responden kuesioner: 20 orang, terdiri dari 3 SuperAdmin, 7 Admin, dan 10 Viewer/Verifikator."], 1.15, 5.0, 10.5, 0.9, 18)
    slides.append(s)

    s = Slide("Flowchart SuperAdmin", "Perancangan", "SuperAdmin memiliki kendali penuh terhadap data, user, dan audit aktivitas.")
    s.framed_image(media("image5.png"), 0.8, 1.62, 4.65, 5.1, "Gambar 3.1 Flowchart SuperAdmin")
    s.bullet([
        "Login sebagai pengguna dengan hak tertinggi.",
        "Mengakses dashboard, manajemen user, daftar sertifikat, activity log, dan pengaturan keamanan.",
        "Melakukan tambah, edit, nonaktifkan, atau hapus user sesuai kebutuhan.",
        "Melihat audit trail untuk memantau seluruh aktivitas sistem."
    ], 6.0, 2.0, 5.6, 3.6, 20)
    slides.append(s)

    s = Slide("Flowchart Admin", "Perancangan", "Admin berfokus pada pengelolaan sertifikat dan verifikasi internal.")
    s.framed_image(media("image6.png"), 0.9, 1.62, 4.8, 5.1, "Gambar 3.2 Flowchart Admin")
    s.bullet([
        "Login menggunakan akun admin aktif.",
        "Mengakses dashboard monitoring dan daftar sertifikat.",
        "Mengunggah sertifikat, mengatur QR Code, serta memperbarui data sertifikat.",
        "Melakukan verifikasi manual atau QR dari panel admin."
    ], 6.18, 2.05, 5.5, 3.35, 20)
    slides.append(s)

    s = Slide("Flowchart User / Verifikator", "Perancangan", "User difokuskan pada proses verifikasi tanpa pengelolaan data.")
    s.framed_image(media("image7.png"), 0.95, 1.62, 4.75, 5.1, "Gambar 3.3 Flowchart User")
    s.bullet([
        "Mengakses halaman verifikasi publik.",
        "Memasukkan nomor sertifikat atau memindai/mengunggah QR Code.",
        "Sistem mencocokkan data sertifikat dan memeriksa integritas file.",
        "Hasil validasi ditampilkan sebagai informasi keaslian sertifikat."
    ], 6.15, 2.05, 5.55, 3.35, 20)
    slides.append(s)

    s = Slide("Use Case Diagram", "Perancangan", "Use case menunjukkan pembagian fungsi untuk Pengunjung, Admin, dan SuperAdmin.")
    s.framed_image(media("image8.png"), 0.85, 1.60, 5.5, 5.12, "Gambar 3.4 Use Case Diagram")
    s.bullet([
        "Pengunjung melakukan verifikasi manual, scan QR, atau upload QR tanpa login.",
        "Admin mengelola sertifikat: dashboard, upload, daftar, edit, dan verifikasi admin.",
        "SuperAdmin memiliki akses tambahan untuk manajemen user, hapus sertifikat, dan activity log."
    ], 6.78, 2.05, 5.25, 3.05, 20)
    slides.append(s)

    s = Slide("DFD Level 0", "Perancangan", "DFD level 0 menggambarkan sistem sebagai satu proses utama.")
    s.framed_image(media("image9.png"), 1.0, 1.82, 5.2, 4.2, "Gambar 3.5 DFD Level 0")
    s.bullet([
        "Entitas eksternal: SuperAdmin, Admin Fakultas/Jurusan, dan Verifier.",
        "Admin mengirimkan data sertifikat dan dokumen ke sistem.",
        "Verifier mengirim permintaan verifikasi melalui QR Code atau nomor sertifikat.",
        "Sistem memberikan hasil verifikasi dan informasi sertifikat."
    ], 6.65, 2.0, 5.2, 3.45, 20)
    slides.append(s)

    s = Slide("DFD Level 1", "Perancangan", "DFD level 1 memecah sistem menjadi proses-proses utama.")
    s.framed_image(media("image10.png"), 0.65, 1.58, 7.2, 5.15, "Gambar 3.6 DFD Level 1")
    s.bullet([
        "Autentikasi memvalidasi email, password, status aktif akun, dan session.",
        "Upload sertifikat menyimpan file, QR Code, hash SHA-256, dan digital signature.",
        "Verifikasi sertifikat mencocokkan nomor/QR dengan database dan integritas file.",
        "Activity log menyimpan jejak aktivitas sebagai audit trail."
    ], 8.15, 1.95, 4.25, 3.7, 17)
    slides.append(s)

    s = Slide("Entity Relationship Diagram", "Perancangan", "ERD menunjukkan struktur data utama yang menopang sistem.")
    s.framed_image(media("image11.png"), 0.85, 1.62, 5.7, 5.1, "Gambar 3.7 ERD")
    s.bullet([
        "users menyimpan data akun dan role pengguna.",
        "students menyimpan identitas pemilik sertifikat.",
        "certificates menyimpan data sertifikat, file, hash, digital signature, dan QR Code.",
        "activity_logs mencatat aktivitas pengguna untuk kebutuhan audit."
    ], 6.95, 2.05, 5.2, 3.45, 20)
    slides.append(s)

    s = Slide("Arsitektur dan Teknologi", "Implementasi", "Implementasi memanfaatkan Laravel 11 dan beberapa library pendukung.")
    s.text("Laravel 11 + PHP 8.2", 0.85, 1.90, 3.2, 0.55, 20, "FFFFFF", True, fill="0F766E", radius=True, align="ctr")
    s.text("MVC, routing, controller, middleware, validasi request, dan pengelolaan session.", 0.85, 2.68, 3.2, 1.15, 17, "334155", fill="F0FDFA", line="99F6E4", radius=True)
    s.text("MySQL", 4.25, 1.90, 2.35, 0.55, 20, "FFFFFF", True, fill="2563EB", radius=True, align="ctr")
    s.text("Menyimpan users, students, certificates, dan activity logs.", 4.25, 2.68, 2.35, 1.15, 17, "334155", fill="EFF6FF", line="BFDBFE", radius=True)
    s.text("QR + Image", 6.80, 1.90, 2.75, 0.55, 20, "FFFFFF", True, fill="7C3AED", radius=True, align="ctr")
    s.text("Endroid QR Code, QR Reader, dan Intervention Image untuk QR serta penempelan pada dokumen.", 6.80, 2.68, 2.75, 1.15, 17, "334155", fill="F5F3FF", line="DDD6FE", radius=True)
    s.text("Security", 9.75, 1.90, 2.55, 0.55, 20, "FFFFFF", True, fill="DC2626", radius=True, align="ctr")
    s.text("SHA-256, bcrypt, CSRF protection, rate limiting, validasi upload, dan RBAC.", 9.75, 2.68, 2.55, 1.15, 17, "334155", fill="FEF2F2", line="FECACA", radius=True)
    s.text("Alur teknis singkat", 1.0, 4.85, 2.4, 0.35, 18, "111827", True, margin=0)
    s.bullet(["Admin upload sertifikat -> sistem membuat hash SHA-256 dan QR Code.", "Viewer/verifikator melakukan input nomor atau QR -> sistem mencocokkan data dan memvalidasi integritas file.", "Setiap aktivitas penting dicatat di activity log."], 1.0, 5.25, 10.6, 0.9, 17)
    slides.append(s)

    s = Slide("Implementasi Antarmuka Publik dan Admin", "Implementasi", "Gambar ditampilkan lebih lega agar alur sistem mudah dipahami saat presentasi.")
    s.framed_image(media("image24.png"), 0.72, 1.72, 5.65, 2.55, "Halaman verifikasi publik", fit="contain")
    s.framed_image(media("image25.png"), 6.78, 1.72, 5.65, 2.55, "Halaman login admin", fit="contain")
    s.framed_image(media("image26.png"), 0.72, 4.55, 5.65, 2.05, "Dashboard SuperAdmin", fit="contain")
    s.framed_image(media("image28.png"), 6.78, 4.55, 5.65, 2.05, "Upload sertifikat dan pengaturan QR", fit="contain")
    slides.append(s)

    s = Slide("Implementasi Pengelolaan Data", "Implementasi", "Fitur pengelolaan data ditampilkan terpisah agar tidak terlalu padat.")
    s.framed_image(media("image29.png"), 0.72, 1.74, 5.65, 2.45, "Daftar sertifikat SuperAdmin", fit="contain")
    s.framed_image(media("image31.png"), 6.78, 1.74, 5.65, 2.45, "Edit sertifikat", fit="contain")
    s.framed_image(media("image32.png"), 0.72, 4.50, 5.65, 2.05, "Kelola user", fit="contain")
    s.framed_image(media("image33.png"), 6.78, 4.50, 5.65, 2.05, "Activity log", fit="contain")
    slides.append(s)

    s = Slide("Implementasi Keamanan Sistem", "Implementasi", "Tiga mekanisme utama bekerja bersama untuk menjaga autentikasi dan integritas.")
    s.metric("Integritas", "SHA-256", 0.95, 1.95, 2.35, "0F766E")
    s.metric("Akses cepat", "QR Code", 3.72, 1.95, 2.35, "2563EB")
    s.metric("Kontrol akses", "RBAC", 6.49, 1.95, 2.35, "7C3AED")
    s.metric("Audit", "Log", 9.26, 1.95, 2.35, "DC2626")
    s.bullet([
        "SHA-256 menghitung nilai hash file sertifikat dan membandingkannya saat verifikasi.",
        "Digital signature dibentuk dari nomor sertifikat, NPM, dan hash file.",
        "QR Code menyimpan URL atau nomor sertifikat untuk mempercepat proses validasi.",
        "RBAC membatasi akses berdasarkan SuperAdmin, Admin, dan Viewer/Verifikator.",
        "Activity log mencatat aktivitas penting untuk kebutuhan pemantauan dan audit."
    ], 1.05, 3.75, 10.8, 2.05, 20)
    slides.append(s)

    s = Slide("Alur Verifikasi Sertifikat", "Implementasi", "Alur ini menjadi inti proses autentikasi sertifikat digital.")
    steps = [
        ("1", "Input nomor sertifikat atau QR Code"),
        ("2", "Sistem membaca nomor sertifikat"),
        ("3", "Data dicari di database"),
        ("4", "Hash file divalidasi ulang"),
        ("5", "Status valid/tidak valid ditampilkan"),
    ]
    x = 0.72
    for num, text in steps:
        s.text(num, x, 2.00, 0.52, 0.52, 17, "FFFFFF", True, fill="0F766E", radius=True, align="ctr", margin=60000)
        s.text(text, x, 2.74, 2.1, 1.42, 17, "334155", fill="F8FAFC", line="D6DEE8", radius=True)
        x += 2.45
    s.framed_image(media("image23.png"), 1.15, 4.72, 4.2, 1.62, "Contoh hasil verifikasi valid", fit="cover")
    s.text("Jika nomor tidak ditemukan, QR tidak terbaca, role tidak sesuai, atau hash file berubah, sistem menolak proses dan menampilkan pesan gagal.", 5.85, 4.86, 5.95, 1.0, 19, "475569", margin=0)
    slides.append(s)

    s = Slide("Instrumen dan Cakupan Pengujian", "Pengujian", "Pengujian memastikan sistem layak secara fungsi, keamanan, dan penerimaan pengguna.")
    s.text("Black Box Testing", 0.85, 1.90, 3.55, 0.55, 20, "FFFFFF", True, fill="2563EB", radius=True, align="ctr")
    s.text("60 skenario fitur utama: verifikasi, login, dashboard, upload, daftar, edit, hapus, log, user, role, throttle.", 0.85, 2.70, 3.55, 1.75, 18, "334155", fill="EFF6FF", line="BFDBFE", radius=True)
    s.text("Security Testing", 4.88, 1.90, 3.55, 0.55, 20, "FFFFFF", True, fill="0F766E", radius=True, align="ctr")
    s.text("14 skenario: integritas file, pemalsuan QR, kontrol akses, session, dan OWASP Top 10 2021.", 4.88, 2.70, 3.55, 1.75, 18, "334155", fill="F0FDFA", line="99F6E4", radius=True)
    s.text("Kuesioner Likert", 8.91, 1.90, 3.55, 0.55, 20, "FFFFFF", True, fill="7C3AED", radius=True, align="ctr")
    s.text("20 responden: 3 SuperAdmin, 7 Admin, dan 10 Viewer/Verifikator untuk mengukur kepuasan dan kemudahan.", 8.91, 2.70, 3.55, 1.75, 18, "334155", fill="F5F3FF", line="DDD6FE", radius=True)
    s.framed_image(media("image34.png"), 3.0, 4.90, 7.4, 1.32, "Hasil eksekusi script pengujian otomatis", fit="cover")
    slides.append(s)

    s = Slide("Hasil Black Box Testing", "Hasil", "Seluruh fitur utama berjalan sesuai output yang diharapkan.")
    s.metric("Total skenario", "60", 0.85, 1.88, 2.4, "2563EB")
    s.metric("Berhasil", "60", 3.62, 1.88, 2.4, "0F766E")
    s.metric("Gagal", "0", 6.39, 1.88, 2.4, "DC2626")
    s.metric("Keberhasilan", "100%", 9.16, 1.88, 2.4, "7C3AED")
    s.framed_image(media("image34.png"), 0.95, 3.42, 5.55, 2.5, "PHPUnit: 71 tests, 175 assertions", fit="cover")
    s.bullet([
        "Modul diuji meliputi verifikasi manual, QR upload, scan kamera, login/logout, dashboard, upload, daftar, edit, hapus, log aktivitas, manajemen user, otorisasi, dan pembatasan request.",
        "Kategori kualitas: Sangat Baik."
    ], 6.85, 3.65, 5.0, 1.7, 19)
    slides.append(s)

    s = Slide("Hasil Security Testing", "Hasil", "Pengujian keamanan menunjukkan seluruh skenario dapat ditangani.")
    s.metric("Total skenario", "14", 0.85, 1.85, 2.4, "2563EB")
    s.metric("Berhasil", "14", 3.62, 1.85, 2.4, "0F766E")
    s.metric("Gagal", "0", 6.39, 1.85, 2.4, "DC2626")
    s.metric("Keamanan", "100%", 9.16, 1.85, 2.4, "7C3AED")
    s.framed_image(media("image35.png"), 0.82, 3.25, 3.65, 1.65, "Integritas dokumen", fit="contain")
    s.framed_image(media("image36.png"), 4.72, 3.25, 3.65, 1.65, "Pemalsuan QR Code", fit="contain")
    s.framed_image(media("image37.png"), 8.62, 3.25, 3.65, 1.65, "Kontrol akses RBAC", fit="contain")
    s.text("Kategori keamanan: Sangat Aman. Skenario meliputi integritas dokumen, QR palsu, akses di luar role, manipulasi session, SQL injection, login berulang, dan manipulasi data.", 1.08, 5.42, 10.9, 0.65, 18, "475569", margin=0)
    slides.append(s)

    s = Slide("Bukti Security Testing Lanjutan", "Hasil", "Screenshot tambahan melengkapi dokumentasi pengujian keamanan berbasis OWASP.")
    s.framed_image(media("image38.png"), 0.65, 1.72, 3.0, 1.68, "Manipulasi session", fit="contain")
    s.framed_image(media("image39.png"), 3.88, 1.72, 3.0, 1.68, "Broken Access Control", fit="contain")
    s.framed_image(media("image40.png"), 7.11, 1.72, 3.0, 1.68, "Cryptographic Failures", fit="contain")
    s.framed_image(media("image41.png"), 10.34, 1.72, 2.35, 1.68, "Injection", fit="contain")
    s.framed_image(media("image42.png"), 1.55, 4.12, 3.2, 1.78, "Authentication Failures", fit="contain")
    s.framed_image(media("image43.png"), 5.10, 4.12, 3.2, 1.78, "Software/Data Integrity", fit="contain")
    s.text("Seluruh skenario tambahan berstatus berhasil dan digunakan sebagai bukti bahwa sistem menolak akses tidak sah, input berbahaya, login berulang, serta manipulasi data/file.", 8.62, 4.28, 3.55, 1.28, 16, "475569", fill="F8FAFC", line="D6DEE8", radius=True)
    slides.append(s)

    s = Slide("Hasil Kuesioner Skala Likert", "Hasil", "Evaluasi pengguna menunjukkan sistem diterima dengan kategori Sangat Baik.")
    s.metric("Responden", "20", 0.85, 1.88, 2.2, "2563EB")
    s.metric("Skor", "1641/1855", 3.42, 1.88, 2.5, "0F766E")
    s.metric("Persentase", "88,46%", 6.27, 1.88, 2.5, "7C3AED")
    s.metric("Rata-rata", "4,42", 9.12, 1.88, 2.2, "DC2626")
    s.framed_image(media("image44.png"), 0.92, 3.45, 3.55, 1.95, "Form SuperAdmin", fit="cover")
    s.framed_image(media("image45.png"), 4.82, 3.45, 3.55, 1.95, "Form Admin", fit="cover")
    s.framed_image(media("image46.png"), 8.72, 3.45, 3.55, 1.95, "Form Viewer", fit="cover")
    s.text("Interpretasi: sistem memenuhi kebutuhan pengguna dari segi fungsionalitas, kemudahan penggunaan, keamanan, dan kinerja.", 1.10, 5.82, 10.8, 0.45, 18, "475569", margin=0)
    slides.append(s)

    s = Slide("Dokumentasi Pengisian Kuesioner", "Hasil", "Dokumentasi menunjukkan proses uji coba dan pengisian kuesioner oleh responden.")
    s.framed_image(media("image47.jpeg"), 0.78, 1.78, 2.8, 2.15, "Responden uji coba", fit="cover")
    s.framed_image(media("image48.jpeg"), 3.78, 1.78, 2.8, 2.15, "Penggunaan sistem", fit="cover")
    s.framed_image(media("image54.jpeg"), 6.78, 1.78, 2.8, 2.15, "Pendampingan pengisian", fit="cover")
    s.framed_image(media("image55.jpeg"), 9.78, 1.78, 2.8, 2.15, "Evaluasi responden", fit="cover")
    s.bullet([
        "Responden mencoba fitur sesuai peran masing-masing.",
        "Kuesioner digunakan untuk menilai kemudahan, tampilan, informasi, keamanan, manfaat, dan kinerja sistem.",
        "Hasil akhir memperoleh kategori Sangat Baik."
    ], 1.05, 4.75, 10.7, 1.25, 19)
    slides.append(s)

    s = Slide("Pembahasan Hasil", "Pembahasan", "Hasil pengujian mendukung hipotesis penelitian.")
    s.bullet([
        "Sistem mampu menggantikan proses verifikasi manual menjadi digital melalui nomor sertifikat dan QR Code.",
        "SHA-256 berhasil membantu menjaga integritas dokumen dengan mendeteksi perubahan file.",
        "RBAC berhasil membatasi akses fitur berdasarkan SuperAdmin, Admin, dan Viewer/Verifikator.",
        "Activity log mendukung audit karena mencatat aktivitas penting pengguna.",
        "Tantangan utama berada pada sinkronisasi data sertifikat, QR Code, hash, dan pembatasan akses agar tetap konsisten."
    ], 0.92, 1.95, 10.9, 3.65, 22)
    slides.append(s)

    s = Slide("Kesimpulan", "Penutup", "Kesimpulan ditarik dari implementasi, pengujian, dan evaluasi pengguna.")
    s.bullet([
        "Sistem autentikasi sertifikat digital berhasil diimplementasikan menggunakan Laravel 11 dengan arsitektur MVC.",
        "QR Code, SHA-256, dan RBAC berhasil diterapkan sebagai mekanisme verifikasi, integritas dokumen, dan kontrol akses.",
        "Black Box Testing menghasilkan tingkat keberhasilan 100% pada 60 skenario.",
        "Security Testing menghasilkan tingkat keamanan 100% pada 14 skenario.",
        "Kuesioner 20 responden memperoleh nilai 88,46% dengan kategori Sangat Baik."
    ], 0.92, 1.95, 10.9, 3.65, 22)
    slides.append(s)

    s = Slide("Saran Pengembangan", "Penutup", "Saran diarahkan untuk peningkatan penggunaan dan keamanan sistem.")
    s.bullet([
        "Meningkatkan antarmuka agar lebih modern, responsif, dan nyaman digunakan pada perangkat mobile.",
        "Menyempurnakan scan QR Code melalui kamera agar lebih cepat, stabil, dan akurat.",
        "Mempertimbangkan pengembangan aplikasi mobile untuk verifikasi sertifikat.",
        "Melakukan deployment pada server produksi dengan HTTPS.",
        "Menambahkan Two-Factor Authentication untuk Admin dan SuperAdmin.",
        "Memperluas security testing dengan penetration testing dan responden yang lebih beragam."
    ], 0.92, 1.95, 10.9, 3.65, 21)
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
        "Tinjauan": bgs["purple"],
        "Metode": bgs["purple"],
        "Perancangan": bgs["slate"],
        "Implementasi": bgs["teal"],
        "Pengujian": bgs["blue"],
        "Hasil": bgs["blue"],
        "Pembahasan": bgs["purple"],
        "Penutup": bgs["teal"],
    }
    for slide in slides:
        slide.bg(section_bg.get(slide.section, bgs["teal"]))

    return slides


def content_types(slide_count: int, media_files: list[Path]) -> str:
    overrides = "\n".join(
        f'<Override PartName="/ppt/slides/slide{i}.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>'
        for i in range(1, slide_count + 1)
    )
    defaults = {
        "rels": "application/vnd.openxmlformats-package.relationships+xml",
        "xml": "application/xml",
    }
    for p in media_files:
        ext = p.suffix.lower().lstrip(".")
        if ext in ("jpg", "jpeg"):
            defaults[ext] = "image/jpeg"
        elif ext == "png":
            defaults[ext] = "image/png"
    default_xml = "\n".join(f'<Default Extension="{k}" ContentType="{v}"/>' for k, v in defaults.items())
    return f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  {default_xml}
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/>
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
    rels.append('<Relationship Id="rId100" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/>')
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
<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="UBT Formal">
  <a:themeElements>
    <a:clrScheme name="UBT Formal">
      <a:dk1><a:srgbClr val="111827"/></a:dk1><a:lt1><a:srgbClr val="FFFFFF"/></a:lt1>
      <a:dk2><a:srgbClr val="334155"/></a:dk2><a:lt2><a:srgbClr val="F8FAFC"/></a:lt2>
      <a:accent1><a:srgbClr val="0F766E"/></a:accent1><a:accent2><a:srgbClr val="2563EB"/></a:accent2>
      <a:accent3><a:srgbClr val="7C3AED"/></a:accent3><a:accent4><a:srgbClr val="DC2626"/></a:accent4>
      <a:accent5><a:srgbClr val="0891B2"/></a:accent5><a:accent6><a:srgbClr val="475569"/></a:accent6>
      <a:hlink><a:srgbClr val="2563EB"/></a:hlink><a:folHlink><a:srgbClr val="7C3AED"/></a:folHlink>
    </a:clrScheme>
    <a:fontScheme name="Aptos"><a:majorFont><a:latin typeface="Aptos Display"/></a:majorFont><a:minorFont><a:latin typeface="Aptos"/></a:minorFont></a:fontScheme>
    <a:fmtScheme name="UBT Formal"><a:fillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:fillStyleLst><a:lnStyleLst><a:ln w="6350"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:ln></a:lnStyleLst><a:effectStyleLst><a:effectStyle><a:effectLst/></a:effectStyle></a:effectStyleLst><a:bgFillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:bgFillStyleLst></a:fmtScheme>
  </a:themeElements>
</a:theme>"""


def write_pptx(slides: list[Slide]) -> None:
    rendered = []
    used_media: list[Path] = []
    for i, slide in enumerate(slides, start=1):
        xml, imgs = slide.build(i, len(slides))
        rendered.append((xml, imgs))
        used_media.extend(img["path"] for img in imgs)
    unique_media = []
    seen = set()
    for p in used_media:
        key = p.resolve()
        if key not in seen:
            seen.add(key)
            unique_media.append(p)

    now = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    media_name_map = {p.resolve(): f"media{i}{p.suffix.lower()}" for i, p in enumerate(unique_media, start=1)}

    with zipfile.ZipFile(OUT, "w", zipfile.ZIP_DEFLATED) as z:
        z.writestr("[Content_Types].xml", content_types(len(slides), unique_media))
        z.writestr("_rels/.rels", COMMON_RELS)
        z.writestr("docProps/core.xml", f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>PPT Seminar Hasil Skripsi Arjuna Profesional</dc:title><dc:creator>Codex</dc:creator><cp:lastModifiedBy>Codex</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">{now}</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">{now}</dcterms:modified>
</cp:coreProperties>""")
        z.writestr("docProps/app.xml", f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Microsoft PowerPoint</Application><PresentationFormat>Widescreen</PresentationFormat><Slides>{len(slides)}</Slides>
</Properties>""")
        z.writestr("ppt/presentation.xml", presentation_xml(len(slides)))
        z.writestr("ppt/_rels/presentation.xml.rels", presentation_rels(len(slides)))
        z.writestr("ppt/theme/theme1.xml", THEME)
        z.writestr("ppt/slideMasters/slideMaster1.xml", MASTER)
        z.writestr("ppt/slideMasters/_rels/slideMaster1.xml.rels", MASTER_RELS)
        z.writestr("ppt/slideLayouts/slideLayout1.xml", LAYOUT)
        z.writestr("ppt/slideLayouts/_rels/slideLayout1.xml.rels", LAYOUT_RELS)
        for p in unique_media:
            z.write(p, f"ppt/media/{media_name_map[p.resolve()]}")
        for i, (xml, imgs) in enumerate(rendered, start=1):
            z.writestr(f"ppt/slides/slide{i}.xml", xml)
            rels = ['<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>']
            for j, img in enumerate(imgs, start=1):
                name = media_name_map[img["path"].resolve()]
                rels.append(f'<Relationship Id="rId{j+1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/{name}"/>')
            z.writestr(f"ppt/slides/_rels/slide{i}.xml.rels", f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">{''.join(rels)}</Relationships>""")


if __name__ == "__main__":
    if not MEDIA.exists():
        raise SystemExit("Run tools/extract_skripsi_media.py first")
    slides = make_slides()
    write_pptx(slides)
    print(f"Created {OUT} with {len(slides)} slides")

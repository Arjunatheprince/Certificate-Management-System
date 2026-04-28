import zipfile
from pathlib import Path
from xml.etree import ElementTree as ET

from PIL import Image


src = Path("skripsi arjuna.docx")
out = Path("tools/skripsi_media")
out.mkdir(parents=True, exist_ok=True)

z = zipfile.ZipFile(src)
for name in z.namelist():
    if name.startswith("word/media/"):
        (out / Path(name).name).write_bytes(z.read(name))

ns = {
    "w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main",
    "a": "http://schemas.openxmlformats.org/drawingml/2006/main",
    "r": "http://schemas.openxmlformats.org/officeDocument/2006/relationships",
}
rels = ET.fromstring(z.read("word/_rels/document.xml.rels"))
relmap = {el.attrib["Id"]: el.attrib["Target"] for el in rels}
root = ET.fromstring(z.read("word/document.xml"))

items = []
for p in root.findall(".//w:body/w:p", ns):
    text = "".join(t.text or "" for t in p.findall(".//w:t", ns)).strip()
    embeds = []
    for blip in p.findall(".//a:blip", ns):
        rid = blip.attrib.get(f"{{{ns['r']}}}embed")
        if rid and rid in relmap:
            embeds.append(Path(relmap[rid]).name)
    if text or embeds:
        items.append((text, embeds))

report = []
for i, (text, embeds) in enumerate(items):
    for img in embeds:
        previous = [items[j][0] for j in range(max(0, i - 5), i) if items[j][0]]
        next_items = [items[j][0] for j in range(i + 1, min(len(items), i + 7)) if items[j][0]]
        path = out / img
        try:
            im = Image.open(path)
            size = f"{im.width}x{im.height}"
        except Exception:
            size = "?"
        report.append(
            f"{img}\t{size}\n"
            f"  prev: {' | '.join(previous[-4:])[:700]}\n"
            f"  text: {text[:250]}\n"
            f"  next: {' | '.join(next_items[:5])[:800]}"
        )

Path("tools/skripsi_media_report.txt").write_text("\n\n".join(report), encoding="utf-8")
print("media extracted", len(list(out.iterdir())), "report items", len(report))

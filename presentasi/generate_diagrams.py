#!/usr/bin/env python3
"""
Generator presentasi PowerPoint: ERD, DFD, dan Use Case Diagram
untuk "YPL Smart Digital Library" (Lokon Dokumen Digital).

Jalankan:  /opt/homebrew/bin/python3.11 presentasi/generate_diagrams.py
Output:    presentasi/ERD-DFD-UseCase.pptx
"""

from pptx import Presentation
from pptx.util import Inches, Pt, Emu
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from pptx.enum.shapes import MSO_SHAPE, MSO_CONNECTOR
from pptx.oxml.ns import qn

# ─────────────────────────── Palet & Ukuran ───────────────────────────
NAVY = RGBColor(0x00, 0x2F, 0x80)
NAVY_DARK = RGBColor(0x00, 0x1C, 0x55)
RED = RGBColor(0xC9, 0x00, 0x18)
WHITE = RGBColor(0xFF, 0xFF, 0xFF)
BG = RGBColor(0xEE, 0xF1, 0xF4)
SLATE = RGBColor(0x33, 0x41, 0x55)
SLATE_LIGHT = RGBColor(0x64, 0x74, 0x8B)
GREEN = RGBColor(0x16, 0xA3, 0x4A)
VIOLET = RGBColor(0x7C, 0x3A, 0xED)
AMBER = RGBColor(0xD9, 0x77, 0x06)
SKY = RGBColor(0x02, 0x84, 0xC7)
GREY_LINE = RGBColor(0x94, 0xA3, 0xB8)
TEAL = RGBColor(0x0F, 0x76, 0x6E)

SW, SH = 13.333, 7.5  # inci (16:9)

prs = Presentation()
prs.slide_width = Inches(SW)
prs.slide_height = Inches(SH)
BLANK = prs.slide_layouts[6]


# ─────────────────────────── Helper dasar ───────────────────────────
def slide():
    s = prs.slides.add_slide(BLANK)
    bg = s.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, prs.slide_height)
    bg.fill.solid()
    bg.fill.fore_color.rgb = BG
    bg.line.fill.background()
    bg.shadow.inherit = False
    return s


def box(s, x, y, w, h, text="", fill=WHITE, line=NAVY, tcolor=SLATE,
        size=11, bold=False, shape=MSO_SHAPE.ROUNDED_RECTANGLE,
        line_w=1.0, align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE, radius=None):
    sh = s.shapes.add_shape(shape, Inches(x), Inches(y), Inches(w), Inches(h))
    if fill is None:
        sh.fill.background()
    else:
        sh.fill.solid()
        sh.fill.fore_color.rgb = fill
    if line is None:
        sh.line.fill.background()
    else:
        sh.line.color.rgb = line
        sh.line.width = Pt(line_w)
    sh.shadow.inherit = False
    if radius is not None and shape == MSO_SHAPE.ROUNDED_RECTANGLE:
        try:
            sh.adjustments[0] = radius
        except Exception:
            pass
    tf = sh.text_frame
    tf.word_wrap = True
    tf.margin_left = Pt(4); tf.margin_right = Pt(4)
    tf.margin_top = Pt(2); tf.margin_bottom = Pt(2)
    tf.vertical_anchor = anchor
    p = tf.paragraphs[0]
    p.alignment = align
    run = p.add_run()
    run.text = text
    run.font.size = Pt(size)
    run.font.bold = bold
    run.font.color.rgb = tcolor
    run.font.name = "Calibri"
    return sh


def label(s, x, y, w, h, text, size=11, bold=False, color=SLATE,
          align=PP_ALIGN.LEFT, anchor=MSO_ANCHOR.TOP):
    tb = s.shapes.add_textbox(Inches(x), Inches(y), Inches(w), Inches(h))
    tf = tb.text_frame
    tf.word_wrap = True
    tf.vertical_anchor = anchor
    tf.margin_left = Pt(2); tf.margin_right = Pt(2)
    tf.margin_top = Pt(1); tf.margin_bottom = Pt(1)
    lines = text.split("\n")
    for i, ln in enumerate(lines):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.alignment = align
        run = p.add_run()
        run.text = ln
        run.font.size = Pt(size)
        run.font.bold = bold
        run.font.color.rgb = color
        run.font.name = "Calibri"
    return tb


def title_bar(s, kicker, title):
    bar = s.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, Inches(1.05))
    bar.fill.solid(); bar.fill.fore_color.rgb = NAVY
    bar.line.fill.background(); bar.shadow.inherit = False
    accent = s.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, Inches(1.05),
                                prs.slide_width, Inches(0.06))
    accent.fill.solid(); accent.fill.fore_color.rgb = RED
    accent.line.fill.background(); accent.shadow.inherit = False
    label(s, 0.55, 0.10, 12.2, 0.35, kicker, size=11, bold=True,
          color=RGBColor(0x9F, 0xB4, 0xD8))
    label(s, 0.55, 0.36, 12.2, 0.6, title, size=24, bold=True, color=WHITE)


def connector(s, x1, y1, x2, y2, color=GREY_LINE, width=1.5, arrow=True,
              dash=None):
    c = s.shapes.add_connector(MSO_CONNECTOR.STRAIGHT, Inches(x1), Inches(y1),
                               Inches(x2), Inches(y2))
    c.line.color.rgb = color
    c.line.width = Pt(width)
    if dash:
        c.line.dash_style = dash
    if arrow:
        ln = c.line._get_or_add_ln()
        tail = ln.makeelement(qn('a:tailEnd'),
                              {'type': 'triangle', 'w': 'med', 'len': 'med'})
        ln.append(tail)
    return c


def elbow(s, x1, y1, x2, y2, color=GREY_LINE, width=1.5, arrow=True, mid=None):
    """Konektor siku (vertikal-horizontal-vertikal) memakai dua garis."""
    mx = mid if mid is not None else (x1 + x2) / 2
    connector(s, x1, y1, mx, y1, color, width, arrow=False)
    connector(s, mx, y1, mx, y2, color, width, arrow=False)
    connector(s, mx, y2, x2, y2, color, width, arrow=arrow)


# ─────────────────────────── Entity card (ERD) ───────────────────────────
def entity(s, x, y, w, header_h, rows, title, color=NAVY, row_h=0.26):
    h = header_h + len(rows) * row_h
    hdr = box(s, x, y, w, header_h, title, fill=color, line=color,
              tcolor=WHITE, size=12, bold=True, shape=MSO_SHAPE.RECTANGLE)
    # body putih
    body = box(s, x, y + header_h, w, len(rows) * row_h, "", fill=WHITE,
               line=color, shape=MSO_SHAPE.RECTANGLE)
    for i, row in enumerate(rows):
        txt, key = row
        col = NAVY if key == "PK" else (RED if key == "FK" else SLATE)
        b = key == "PK" or key == "FK"
        label(s, x + 0.08, y + header_h + i * row_h, w - 0.16, row_h,
              txt, size=9, bold=b, color=col, anchor=MSO_ANCHOR.MIDDLE)
    return h


# ═══════════════════════════════════════════════════════════════════
#  SLIDE 1 — Judul
# ═══════════════════════════════════════════════════════════════════
s = slide()
band = s.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, prs.slide_height)
band.fill.gradient()
band.fill.gradient_stops[0].color.rgb = NAVY_DARK
band.fill.gradient_stops[1].color.rgb = NAVY
band.fill.gradient_angle = 45
band.line.fill.background(); band.shadow.inherit = False

stripe = s.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.9), Inches(2.15), Inches(1.6), Inches(0.09))
stripe.fill.solid(); stripe.fill.fore_color.rgb = RED
stripe.line.fill.background(); stripe.shadow.inherit = False

label(s, 0.9, 2.35, 11.5, 1.4,
      "Perancangan Basis Data & Aliran Informasi", size=40, bold=True, color=WHITE)
label(s, 0.9, 3.75, 11.5, 0.8,
      "ERD • DFD • Use Case Diagram", size=26, bold=True,
      color=RGBColor(0x9F, 0xB4, 0xD8))
label(s, 0.9, 4.85, 11.5, 1.2,
      "YPL Smart Digital Library — Lokon Dokumen Digital\n"
      "Yayasan Pendidikan Lokon  ·  Tomohon, Sulawesi Utara",
      size=15, color=WHITE)
label(s, 0.9, 6.6, 11.5, 0.4, "Dokumen ini disusun untuk keperluan presentasi akademik.",
      size=11, color=RGBColor(0x9F, 0xB4, 0xD8))

# ═══════════════════════════════════════════════════════════════════
#  SLIDE 2 — Ringkasan Sistem
# ═══════════════════════════════════════════════════════════════════
s = slide()
title_bar(s, "PENGANTAR", "Ringkasan Sistem")

cards = [
    ("📚", "Perpustakaan Digital", "Dokumen PDF & Living Books dengan\nkategori, tag, dan versi terkontrol."),
    ("🔗", "Connected Knowledge", "Relasi antar dokumen, sistem, agenda,\ndan FAQ membentuk knowledge graph."),
    ("🏛️", "Governance", "Lifecycle dokumen: draft → review →\napproval → active → archived."),
    ("👥", "Komunitas & Peran", "Anggota, Staff, Admin, Super Admin\ndengan kontrol akses berlapis."),
    ("💡", "Ask YPL & Rekomendasi", "Pencarian cerdas, tanya-jawab bersitasi,\ndan rekomendasi dokumen."),
    ("🛠️", "Layanan & Notifikasi", "Pengajuan layanan, notifikasi in-app,\ndan analitik operasional."),
]
cx, cy, cw, ch = 0.55, 1.45, 3.95, 1.72
for i, (icon, t, d) in enumerate(cards):
    r, c = divmod(i, 3)
    x = cx + c * (cw + 0.22)
    y = cy + r * (ch + 0.28)
    box(s, x, y, cw, ch, "", fill=WHITE, line=RGBColor(0xD6, 0xD6, 0xD6), radius=0.06)
    box(s, x + 0.22, y + 0.22, 0.7, 0.7, icon, fill=NAVY, line=None, tcolor=WHITE,
        size=20, shape=MSO_SHAPE.ROUNDED_RECTANGLE, radius=0.25)
    label(s, x + 1.05, y + 0.22, cw - 1.2, 0.4, t, size=13, bold=True, color=NAVY)
    label(s, x + 1.05, y + 0.66, cw - 1.2, 0.9, d, size=10, color=SLATE_LIGHT)

label(s, 0.55, 6.75, 12.2, 0.4,
      "Stack: Laravel 12 · PHP 8.5 · MySQL · Blade + Tailwind CSS · Gemini LLM (opsional)",
      size=11, bold=True, color=SLATE_LIGHT)

# ═══════════════════════════════════════════════════════════════════
#  SLIDE 3 — ERD (1) Identitas & Peran
# ═══════════════════════════════════════════════════════════════════
s = slide()
title_bar(s, "ENTITY RELATIONSHIP DIAGRAM · 1/3", "ERD — Identitas, Peran & Pengguna")
label(s, 0.55, 1.18, 12.2, 0.35,
      "Titik pusat otorisasi: satu pengguna berperan, satu dokumen punya owner/approver/PIC.",
      size=11, color=SLATE_LIGHT)

entity(s, 0.6, 1.75, 3.2, 0.42, [
    ("id  (PK)", "PK"), ("name", ""), ("email  (UK)", ""), ("role  → roles.slug", "FK"),
    ("audience", ""), ("ypl_verified", ""),
], "users", color=NAVY)

entity(s, 4.55, 1.75, 3.2, 0.42, [
    ("id  (PK)", "PK"), ("slug  (UK)", ""), ("name", ""), ("priority", ""),
    ("scopes  (json)", ""), ("homepage  (json)", ""),
], "roles", color=VIOLET)

entity(s, 8.5, 1.75, 4.2, 0.42, [
    ("id  (PK)", "PK"), ("user_id  (FK → users)", "FK"), ("requested_role", ""),
    ("status", ""), ("reviewed_by  (FK → users)", "FK"), ("reason", ""),
], "role_requests", color=TEAL)

entity(s, 4.55, 4.4, 3.9, 0.42, [
    ("id  (PK)", "PK"), ("user_id  (FK)", "FK"), ("type", ""),
    ("status", ""), ("handler_id  (FK → users)", "FK"),
], "service_requests", color=AMBER)

# connector users -> role_requests (1:N)
connector(s, 3.8, 2.9, 8.5, 2.9, color=SLATE_LIGHT, arrow=True)
label(s, 7.0, 2.62, 2.9, 0.3, "1 : N  pengajuan peran", size=9, bold=True, color=SLATE)
# users -> service_requests
elbow(s, 2.2, 3.85, 5.3, 4.4, color=SLATE_LIGHT, mid=3.35)
label(s, 2.3, 4.05, 3.0, 0.3, "1 : N  pengajuan layanan", size=9, bold=True, color=SLATE)
# users -> roles
connector(s, 3.8, 2.2, 4.55, 2.2, color=SLATE_LIGHT, arrow=True)
label(s, 3.86, 1.9, 1.6, 0.3, "N : 1  slug", size=9, bold=True, color=SLATE)

legend = box(s, 8.5, 4.4, 4.2, 1.55, "", fill=WHITE, line=RGBColor(0xD6, 0xD6, 0xD6))
label(s, 8.72, 4.55, 3.9, 0.3, "KETERANGAN", size=10, bold=True, color=NAVY)
label(s, 8.72, 4.9, 3.9, 1.0,
      "PK  Primary Key      FK  Foreign Key      UK  Unique\n"
      "→  arah relasi    1 : N  satu-ke-banyak\n"
      "Peran disimpan sebagai slug (dinamis dari tabel roles).",
      size=9, color=SLATE)

# ═══════════════════════════════════════════════════════════════════
#  SLIDE 4 — ERD (2) Dokumen & Knowledge
# ═══════════════════════════════════════════════════════════════════
s = slide()
title_bar(s, "ENTITY RELATIONSHIP DIAGRAM · 2/3", "ERD — Dokumen, Versi & Knowledge Graph")
label(s, 0.55, 1.18, 12.2, 0.35,
      "Dokumen sebagai hub: kategori, tag, versi (audit), dan relasi ke sistem/agenda/FAQ.",
      size=11, color=SLATE_LIGHT)

entity(s, 0.55, 1.72, 3.3, 0.42, [
    ("id  (PK)", "PK"), ("name", ""), ("slug  (UK)", ""),
    ("code", ""), ("collection", ""),
], "categories", color=TEAL)

entity(s, 4.35, 1.72, 4.6, 0.42, [
    ("id  (PK)", "PK"), ("user_id  (FK → users)", "FK"),
    ("approver_id / pic_id  (FK)", "FK"), ("category_id  (FK)", "FK"),
    ("title / slug (UK)", ""), ("doc_type / status", ""),
    ("visibility / audience", ""), ("review_at / effective_at", ""),
], "documents", color=NAVY)

entity(s, 9.45, 1.72, 3.25, 0.42, [
    ("id  (PK)", "PK"), ("name", ""), ("slug", ""),
    ("color", ""),
], "tags", color=VIOLET)

entity(s, 4.35, 4.65, 2.2, 0.42, [
    ("document_id (FK)", "FK"), ("tag_id (FK)", "FK"),
], "document_tag", color=VIOLET)

entity(s, 6.85, 4.65, 2.55, 0.42, [
    ("id (PK)", "PK"), ("document_id (FK)", "FK"),
    ("version", ""), ("from_status / to_status", ""), ("snapshot (json)", ""),
], "document_versions", color=NAVY)

entity(s, 0.55, 4.65, 3.3, 0.42, [
    ("id (PK)", "PK"), ("document_id (FK)", "FK"),
    ("related_id (FK→doc)", "FK"), ("system_id / event_id / faq_id", "FK"),
    ("relation_type", ""),
], "document_relations", color=RED)

# connector kategori -> documents
connector(s, 3.85, 2.6, 4.35, 2.6, color=SLATE_LIGHT, arrow=True)
label(s, 3.9, 2.3, 1.0, 0.3, "1 : N", size=9, bold=True, color=SLATE)
# documents -> tags via pivot
elbow(s, 8.95, 3.5, 10.4, 3.5, color=SLATE_LIGHT, mid=9.2, arrow=True)
label(s, 8.9, 3.2, 1.6, 0.3, "M : N (tag)", size=9, bold=True, color=SLATE)
connector(s, 4.0, 5.35, 4.35, 5.35, color=SLATE_LIGHT, arrow=True)
label(s, 4.05, 5.05, 1.0, 0.3, "1 : N", size=9, bold=True, color=SLATE)
# documents -> versions
elbow(s, 6.45, 3.5, 8.1, 4.65, color=SLATE_LIGHT, mid=7.5)
label(s, 6.6, 3.55, 2.2, 0.3, "1 : N  versi", size=9, bold=True, color=SLATE)
# documents -> relations
elbow(s, 5.1, 3.5, 2.2, 4.65, color=SLATE_LIGHT, mid=3.0)
label(s, 2.35, 3.55, 2.6, 0.3, "1 : N  relasi keluar", size=9, bold=True, color=SLATE)

label(s, 0.55, 6.9, 12.2, 0.4,
      "document_relations bersifat polimorfik: satu baris menunjuk tepat satu target (dokumen / sistem / agenda / FAQ).",
      size=10, bold=True, color=SLATE_LIGHT)

# ═══════════════════════════════════════════════════════════════════
#  SLIDE 5 — ERD (3) Interaksi, Entitas Knowledge & Komunitas
# ═══════════════════════════════════════════════════════════════════
s = slide()
title_bar(s, "ENTITY RELATIONSHIP DIAGRAM · 3/3", "ERD — Interaksi, Knowledge & Komunitas")

entity(s, 0.55, 1.5, 3.1, 0.4, [
    ("id (PK)", "PK"), ("document_id (FK)", "FK"),
    ("system_id (FK)", "FK"), ("relation_type", ""),
], "systems", color=GREEN)

entity(s, 3.95, 1.5, 3.1, 0.4, [
    ("id (PK)", "PK"), ("title", ""),
    ("category / starts_at", ""), ("visibility", ""),
], "events", color=VIOLET)

entity(s, 7.35, 1.5, 3.1, 0.4, [
    ("id (PK)", "PK"), ("question", ""),
    ("category", ""), ("visibility", ""),
], "faqs", color=SKY)

entity(s, 10.75, 1.5, 1.95, 0.4, [
    ("id (PK)", "PK"), ("name / slug", ""),
], "units", color=TEAL)

entity(s, 0.55, 3.95, 3.1, 0.4, [
    ("id (PK)", "PK"), ("user_id (FK)", "FK"),
    ("document_id (FK)", "FK"), ("created_at", ""),
], "bookmarks / likes", color=AMBER)

entity(s, 3.95, 3.95, 3.1, 0.4, [
    ("id (PK)", "PK"), ("user_id (FK)", "FK"),
    ("document_id (FK)", "FK"), ("page / percent / body", ""),
], "reading_positions / notes", color=AMBER)

entity(s, 7.35, 3.95, 3.1, 0.4, [
    ("id (PK)", "PK"), ("user_id (FK)", "FK"),
    ("document_id (FK)", "FK"), ("action / meta (json)", ""),
], "activity_logs", color=SLATE)

entity(s, 10.75, 3.95, 1.95, 0.4, [
    ("id (PK)", "PK"),
    ("notifiable_id", ""), ("read_at", ""),
], "notifications", color=RED)

# knowledge entities -> document_relations
for x in (1.35, 4.75, 8.15):
    connector(s, x, 3.1, x - 0.0, 3.95, color=SLATE_LIGHT, arrow=False)
label(s, 0.55, 3.15, 12.2, 0.3,
      "systems · events · faqs  ──▶  document_relations (target relasi, on delete: set null)",
      size=9, bold=True, color=SLATE, align=PP_ALIGN.CENTER)

box(s, 10.75, 5.15, 1.95, 1.2, "contacts\n(unit_id FK)", fill=WHITE,
    line=TEAL, tcolor=SLATE, size=9)
connector(s, 11.7, 2.75, 11.7, 5.15, color=SLATE_LIGHT, arrow=True)
label(s, 11.75, 3.9, 1.4, 0.3, "1 : N", size=9, bold=True, color=SLATE)

label(s, 0.55, 6.75, 12.2, 0.5,
      "Tabel framework (cache, jobs, sessions, migrations, password_resets) tidak ditampilkan — fokus domain aplikasi.",
      size=10, color=SLATE_LIGHT)

# ═══════════════════════════════════════════════════════════════════
#  SLIDE 6 — DFD Level 0 (Context Diagram)
# ═══════════════════════════════════════════════════════════════════
s = slide()
title_bar(s, "DATA FLOW DIAGRAM · LEVEL 0", "DFD Konteks — Sistem & Entitas Eksternal")

# Proses pusat
box(s, 5.0, 2.85, 3.4, 1.8,
    "0\n\nSistem\nYPL Smart Digital Library",
    fill=NAVY, line=NAVY_DARK, tcolor=WHITE, size=15, bold=True, radius=0.08)

# Entitas eksternal
box(s, 0.5, 1.5, 2.5, 1.0, "Tamu /\nPengguna Publik", fill=WHITE, line=NAVY, tcolor=NAVY, size=12, bold=True)
box(s, 0.5, 4.6, 2.5, 1.0, "Anggota\n(login)", fill=WHITE, line=NAVY, tcolor=NAVY, size=12, bold=True)
box(s, 10.35, 1.5, 2.5, 1.0, "Staff /\nAdmin", fill=WHITE, line=RED, tcolor=RED, size=12, bold=True)
box(s, 10.35, 4.6, 2.5, 1.0, "Gemini LLM\n(API eksternal)", fill=WHITE, line=VIOLET, tcolor=VIOLET, size=12, bold=True)

def flow(s, x1, y1, x2, y2, text, color=SLATE):
    connector(s, x1, y1, x2, y2, color=color, arrow=True)
    label(s, min(x1, x2), (y1 + y2) / 2 - 0.42, abs(x2 - x1), 0.4, text,
          size=9, bold=True, color=SLATE, align=PP_ALIGN.CENTER)

# Tamu <-> sistem
flow(s, 3.0, 1.85, 5.0, 3.1, "permintaan halaman,\npencarian")
flow(s, 5.0, 3.4, 3.0, 2.3, "konten publik,\nhasil pencarian")
# Anggota <-> sistem
flow(s, 3.0, 5.0, 5.0, 4.1, "login, bookmark, like,\ncatatan, pengajuan")
flow(s, 5.0, 4.5, 3.0, 5.2, "dokumen terautorisasi,\nnotifikasi")
# Sistem <-> Staff/Admin
flow(s, 8.4, 3.1, 10.35, 2.0, "konten dokumen,\nhasil olah")
flow(s, 10.35, 2.35, 8.4, 3.4, "unggah/kelola dokumen,\npengaturan")
# Sistem <-> Gemini
flow(s, 8.4, 4.1, 10.35, 5.0, "pertanyaan +\nkonteks kutipan")
flow(s, 10.35, 4.7, 8.4, 4.5, "jawaban\n(narasi)")

# Data store tunggal (representasi)
box(s, 5.15, 5.5, 3.1, 0.85, "Basis Data MySQL\n(documents · users · relations)",
    fill=BG, line=SLATE_LIGHT, tcolor=SLATE, size=10, bold=True)
connector(s, 6.7, 4.65, 6.7, 5.5, color=SLATE_LIGHT, arrow=False)

# ═══════════════════════════════════════════════════════════════════
#  SLIDE 7 — DFD Level 1
# ═══════════════════════════════════════════════════════════════════
s = slide()
title_bar(s, "DATA FLOW DIAGRAM · LEVEL 1", "DFD Rincian — Proses Utama & Data Store")

procs = [
    ("1.0", "Autentikasi &\nAkun", 0.6, 1.5, NAVY),
    ("2.0", "Manajemen\nDokumen", 3.6, 1.5, NAVY),
    ("3.0", "Penelusuran &\nKnowledge", 6.6, 1.5, NAVY),
    ("4.0", "Interaksi\nPembaca", 0.6, 3.5, TEAL),
    ("5.0", "Layanan &\nPermintaan", 3.6, 3.5, TEAL),
    ("6.0", "Governance &\nNotifikasi", 6.6, 3.5, TEAL),
    ("7.0", "Analitik &\nPelaporan", 9.6, 3.5, RED),
]
for num, name, x, y, col in procs:
    box(s, x, y, 2.7, 1.3, f"{num}\n{name}", fill=col, line=None,
        tcolor=WHITE, size=12, bold=True, radius=0.12)

# Data stores di bawah
stores = [
    ("D1  users / roles", 0.6, GREEN),
    ("D2  documents", 3.6, GREEN),
    ("D3  document_versions", 6.6, GREEN),
    ("D4  document_relations", 9.6, GREEN),
    ("D5  bookmarks / likes / notes", 0.6, SKY),
    ("D6  service_requests", 3.6, SKY),
    ("D7  activity_logs / notifications", 6.6, SKY),
    ("D8  settings", 9.6, SKY),
]
for name, x, col in stores[0:4]:
    box(s, x, 5.35, 2.7, 0.5, name, fill=WHITE, line=col, tcolor=SLATE,
        size=9.5, bold=True, shape=MSO_SHAPE.RECTANGLE)
for name, x, col in stores[4:8]:
    box(s, x, 6.05, 2.7, 0.5, name, fill=WHITE, line=col, tcolor=SLATE,
        size=9.5, bold=True, shape=MSO_SHAPE.RECTANGLE)

# Panah proses -> data store
for x in (1.95, 4.95):
    connector(s, x, 2.8, x, 5.35, color=SLATE_LIGHT, arrow=True)
connector(s, 7.95, 2.8, 7.95, 5.35, color=SLATE_LIGHT, arrow=True)
connector(s, 10.95, 2.8, 10.95, 5.35, color=SLATE_LIGHT, arrow=True)
for x in (1.95, 4.95, 7.95, 10.95):
    connector(s, x, 4.8, x, 6.05, color=SLATE_LIGHT, arrow=True)

label(s, 0.6, 1.15, 12.2, 0.3,
      "Setiap proses membaca/menulis ke data store terkait (D1–D8). Aliran antar-proses diringkas agar diagram tetap terbaca.",
      size=10, color=SLATE_LIGHT)

# ═══════════════════════════════════════════════════════════════════
#  SLIDE 8 — Use Case Overview (aktor)
# ═══════════════════════════════════════════════════════════════════
s = slide()
title_bar(s, "USE CASE DIAGRAM · AKTOR", "Aktor & Hak Akses Sistem")

actors = [
    ("🧑‍💻", "Tamu / Publik", "Tanpa login", [
        "Lihat beranda & bookshelf", "Telusuri & cari dokumen publik",
        "Lihat governance & agenda", "Daftar akun baru"], NAVY),
    ("👤", "Anggota", "Login terverifikasi", [
        "Bookmark, like, catatan baca", "Ask YPL & rekomendasi",
        "Ajukan layanan & peran", "Terima notifikasi"], TEAL),
    ("🧑‍🔧", "Staff", "Pengelola konten", [
        "Unggah / tulis dokumen", "Kelola versi & relasi",
        "Transisi lifecycle", "Analitik operasional"], GREEN),
    ("🛡️", "Admin", "Pengelola yayasan", [
        "Kelola kategori & pengguna", "Setujui peningkatan peran",
        "Lihat log aktivitas", "Kelola permintaan layanan"], AMBER),
    ("👑", "Super Admin", "Pemilik sistem", [
        "Pengaturan web & fitur", "Kelola peran (RBAC)",
        "Kelola semua akun", "Kontrol penuh sistem"], RED),
]
cw, ch = 2.42, 4.7
for i, (icon, name, sub, cases, col) in enumerate(actors):
    x = 0.4 + i * (cw + 0.13)
    y = 1.55
    box(s, x, y, cw, ch, "", fill=WHITE, line=RGBColor(0xD6, 0xD6, 0xD6), radius=0.04)
    box(s, x, y, cw, 0.95, f"{icon}\n{name}", fill=col, line=None, tcolor=WHITE,
        size=13, bold=True, radius=0.04)
    label(s, x + 0.1, y + 1.0, cw - 0.2, 0.3, sub, size=9, bold=True,
          color=SLATE_LIGHT, align=PP_ALIGN.CENTER)
    rows = "\n".join("• " + c for c in cases)
    label(s, x + 0.14, y + 1.35, cw - 0.28, ch - 1.5, rows, size=9.5, color=SLATE)

label(s, 0.4, 6.45, 12.5, 0.4,
      "Aktor tersusun hierarkis: hak akses Staff ⊃ Anggota, Admin ⊃ Staff, Super Admin ⊃ Admin.",
      size=10, bold=True, color=SLATE_LIGHT)

# ═══════════════════════════════════════════════════════════════════
#  SLIDE 9 — Use Case Diagram (oval + aktor)
# ═══════════════════════════════════════════════════════════════════
s = slide()
title_bar(s, "USE CASE DIAGRAM", "Diagram Use Case Utama")

# sistem boundary
bound = s.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(2.55), Inches(1.35),
                           Inches(8.25), Inches(5.75))
bound.fill.solid(); bound.fill.fore_color.rgb = WHITE
bound.line.color.rgb = RGBColor(0xD6, 0xD6, 0xD6); bound.line.width = Pt(1)
bound.shadow.inherit = False
label(s, 2.7, 1.4, 8.0, 0.3, "Sistem YPL Smart Digital Library", size=11, bold=True, color=SLATE)

def usecase(cx, cy, text, w=2.15, h=0.72):
    box(s, cx, cy, w, h, text, fill=RGBColor(0xEF, 0xF4, 0xFF), line=NAVY,
        tcolor=NAVY, size=9.5, bold=True, shape=MSO_SHAPE.OVAL)

# use cases (kolom dalam boundary)
uc = [
    ("Login / Daftar", 3.2, 1.95),
    ("Telusuri & Cari Dokumen", 3.2, 2.95),
    ("Baca Dokumen (Reader)", 3.2, 3.95),
    ("Bookmark / Like / Catatan", 3.2, 4.95),
    ("Ask YPL (tanya-jawab)", 6.55, 1.95),
    ("Kelola Dokumen & Versi", 6.55, 2.95),
    ("Kelola Relasi Knowledge", 6.55, 3.95),
    ("Transisi Lifecycle", 6.55, 4.95),
    ("Ajukan Layanan", 5.65, 5.85),
    ("Notifikasi & Dashboard", 3.2, 5.85),
]
for t, x, y in uc:
    usecase(x, y, t)

# Aktor sederhana (ikon + label)
def actor(x, y, icon, name):
    box(s, x, y, 1.5, 0.75, f"{icon}\n{name}", fill=WHITE, line=NAVY,
        tcolor=NAVY, size=10, bold=True, shape=MSO_SHAPE.ROUNDED_RECTANGLE)

actor(0.45, 2.4, "🧑‍💻", "Tamu")
actor(0.45, 4.3, "👤", "Anggota")
actor(11.1, 2.4, "🧑‍🔧", "Staff")
actor(11.1, 4.3, "🛡️", "Admin /\nSuper Admin")

# Relasi aktor -> use case (garis)
connector(s, 1.95, 2.75, 3.2, 2.3, color=GREY_LINE, arrow=False)
connector(s, 1.95, 5.0, 3.2, 5.3, color=GREY_LINE, arrow=False)
connector(s, 1.95, 4.6, 3.2, 4.1, color=GREY_LINE, arrow=False)
connector(s, 11.1, 2.75, 8.7, 2.3, color=GREY_LINE, arrow=False)
connector(s, 11.1, 4.6, 8.7, 4.1, color=GREY_LINE, arrow=False)
connector(s, 11.1, 3.0, 8.7, 3.0, color=GREY_LINE, arrow=False)

# include
connector(s, 5.35, 2.3, 6.55, 2.3, color=RED, width=1.2, arrow=True)
label(s, 5.3, 2.0, 1.3, 0.3, "«include»", size=8, bold=True, color=RED)

label(s, 0.45, 6.85, 12.5, 0.4,
      "Relasi «include»: setiap use case terlindungi membutuhkan Login/Daftar terlebih dahulu.",
      size=10, color=SLATE_LIGHT)

# ═══════════════════════════════════════════════════════════════════
#  SLIDE 10 — Penutup
# ═══════════════════════════════════════════════════════════════════
s = slide()
band = s.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, prs.slide_height)
band.fill.gradient()
band.fill.gradient_stops[0].color.rgb = NAVY_DARK
band.fill.gradient_stops[1].color.rgb = NAVY
band.fill.gradient_angle = 45
band.line.fill.background(); band.shadow.inherit = False

stripe = s.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.9), Inches(2.6), Inches(1.6), Inches(0.09))
stripe.fill.solid(); stripe.fill.fore_color.rgb = RED
stripe.line.fill.background(); stripe.shadow.inherit = False

label(s, 0.9, 2.8, 11.5, 1.0, "Kesimpulan", size=34, bold=True, color=WHITE)
label(s, 0.9, 3.9, 11.5, 2.2,
      "•  ERD: dokumen sebagai pusat dengan relasi first-class membentuk knowledge graph.\n"
      "•  DFD: aliran data terstruktur dari entitas eksternal hingga ke data store MySQL.\n"
      "•  Use Case: 5 aktor dengan hak akses berlapis dan kontrol otorisasi yang jelas.\n"
      "•  Desain mendukung audit (versi dokumen) dan akses permission-aware.",
      size=15, color=WHITE)
label(s, 0.9, 6.6, 11.5, 0.5, "Terima kasih  ·  Yayasan Pendidikan Lokon",
      size=14, bold=True, color=RGBColor(0x9F, 0xB4, 0xD8))

# ─────────────────────────── Simpan ───────────────────────────
out = "presentasi/ERD-DFD-UseCase.pptx"
prs.save(out)
print(f"OK  {out}  ({len(prs.slides.__iter__.__self__._sldIdLst)} slides)")

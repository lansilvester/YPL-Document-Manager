#!/usr/bin/env python3
"""ERD YPL Smart Digital Library — digambar dari skema MySQL aktual.

Sumber: information_schema (kolom -> /tmp/schema.txt, FK -> /tmp/fks.txt).
Output: presentasi/ERD — YPL Smart Digital Library (LOSNITO).png
"""
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch, FancyArrowPatch

NAVY, RED, GREEN, AMBER, SLATE = "#002F80", "#C90018", "#16652E", "#92600A", "#334155"
HDR = {"dokumen": NAVY, "orang": RED, "interaksi": GREEN, "layanan": AMBER, "sistem": SLATE}

# (kolom, baris) manual — hub users & documents di tengah agar garis FK pendek.
POS = {
    "roles": (0, 0), "users": (1, 0), "role_requests": (2, 0), "contacts": (3, 0), "units": (4, 0),
    "categories": (0, 1), "tags": (1, 1), "documents": (2, 1), "systems": (3, 1), "events": (4, 1),
    "document_versions": (0, 2), "document_relations": (1, 2), "document_tag": (2, 2),
    "faqs": (3, 2), "service_requests": (4, 2),
    "bookmarks": (0, 3), "likes": (1, 3), "reading_notes": (2, 3),
    "reading_positions": (3, 3), "reading_prefs": (4, 3),
    "activity_logs": (0, 4), "notifications": (1, 4), "settings": (2, 4),
}
DOMAIN = {
    "documents": "dokumen", "document_versions": "dokumen", "document_relations": "dokumen",
    "document_tag": "dokumen", "categories": "dokumen", "tags": "dokumen",
    "users": "orang", "roles": "orang", "role_requests": "orang",
    "contacts": "orang", "units": "orang",
    "bookmarks": "interaksi", "likes": "interaksi", "reading_notes": "interaksi",
    "reading_positions": "interaksi", "reading_prefs": "interaksi",
    "service_requests": "layanan", "events": "layanan", "faqs": "layanan", "systems": "layanan",
    "activity_logs": "sistem", "notifications": "sistem", "settings": "sistem",
}
ID_LABEL = {"dokumen": "DOKUMEN", "orang": "ORANG & AKSES", "interaksi": "INTERAKSI PEMBACA",
            "layanan": "LAYANAN & AGENDA", "sistem": "SISTEM"}

# --- baca skema ---
cols = {}  # tabel -> [(nama, key)]
for line in open("/tmp/schema.txt"):
    p = line.rstrip("\n").split("\t")
    if len(p) < 5 or p[0] not in POS:
        continue
    cols.setdefault(p[0], []).append((p[1], p[4]))
fks = []  # (sumber, kolom, target)
for line in open("/tmp/fks.txt"):
    p = line.rstrip("\n").split("\t")
    if len(p) == 3 and p[0] in POS and p[2] in POS:
        fks.append((p[0], p[1], p[2]))

PRIORITY = {"id": 0}
def col_sort(c):
    n, k = c
    if k == "PRI":
        return (0, n)
    if n.endswith("_id"):
        return (1, n)
    return (2, n)

MAXCOLS = 8
box_cols = {}
for t, cl in cols.items():
    s = sorted(cl, key=col_sort)
    shown = s[:MAXCOLS]
    box_cols[t] = (shown, len(s) - len(shown))

# --- kanvas ---
NC, NR = 5, 5
XGAP, YGAP = 0.35, 0.55
BW, BH = 3.1, 2.15
W = NC * BW + (NC + 1) * XGAP
H = NR * BH + (NR + 1) * YGAP + 1.3  # ruang judul

def xy(c, r):
    x = XGAP + c * (BW + XGAP)
    y = 1.3 + YGAP + (NR - 1 - r) * (BH + YGAP)
    return x, y

fig, ax = plt.subplots(figsize=(W, H), dpi=150)
ax.set_xlim(0, W)
ax.set_ylim(0, H)
ax.axis("off")
fig.patch.set_facecolor("white")

ax.text(W / 2, H - 0.75, "ERD — YPL Smart Digital Library (LOSNITO)", ha="center",
        fontsize=22, fontweight="bold", color=NAVY)
ax.text(W / 2, H - 1.05, "Digambar dari skema MySQL aktual  ·  garis = foreign key  ·  PK/FK ditandai",
        ha="center", fontsize=10, color="#64748B")

centers = {}
for t, (c, r) in POS.items():
    x, y = xy(c, r)
    cx, cy = x + BW / 2, y + BH / 2
    centers[t] = (cx, cy)
    dom = DOMAIN[t]
    ax.add_patch(FancyBboxPatch((x, y), BW, BH, boxstyle="round,pad=0.02,rounding_size=0.12",
                                facecolor="white", edgecolor=HDR[dom], linewidth=2.2))
    ax.add_patch(FancyBboxPatch((x, y + BH - 0.42), BW, 0.42,
                                boxstyle="round,pad=0.02,rounding_size=0.12",
                                facecolor=HDR[dom], edgecolor=HDR[dom], linewidth=0))
    ax.text(cx, y + BH - 0.21, t, ha="center", va="center", fontsize=10.5,
            fontweight="bold", color="white", family="monospace")
    shown, more = box_cols.get(t, ([], 0))
    yy = y + BH - 0.62
    for name, key in shown:
        tag, col = "", "#334155"
        if key == "PRI":
            tag, col = "  [PK]", "#002F80"
        elif name.endswith("_id"):
            tag, col = "  [FK]", "#C90018"
        ax.text(x + 0.12, yy, name + tag, ha="left", va="center", fontsize=8.2,
                color=col, family="monospace", fontweight="bold" if tag else "normal")
        yy -= 0.185
    if more > 0:
        ax.text(x + 0.12, yy, f"+{more} kolom…", ha="left", va="center",
                fontsize=8, color="#94A3B8", style="italic")

drawn = set()
for src, col, tgt in fks:
    if src == tgt or (src, tgt) in drawn:
        continue
    drawn.add((src, tgt))
    x1, y1 = centers[src]
    x2, y2 = centers[tgt]
    ax.add_patch(FancyArrowPatch((x1, y1), (x2, y2), arrowstyle="-",
                                 color="#B6C2D2", linewidth=0.9,
                                 shrinkA=BH / 2 - 0.25, shrinkB=BH / 2 - 0.25,
                                 connectionstyle="arc3,rad=0.06", zorder=1))

# legenda domain
lx = XGAP
for i, (d, label) in enumerate(ID_LABEL.items()):
    ax.add_patch(FancyBboxPatch((lx, 0.28), 0.32, 0.32, boxstyle="round,pad=0.01,rounding_size=0.06",
                                facecolor=HDR[d], edgecolor=HDR[d], linewidth=0))
    ax.text(lx + 0.4, 0.44, label, ha="left", va="center", fontsize=9.5,
            fontweight="bold", color="#334155")
    lx += 0.4 + len(label) * 0.115 + 0.45
ax.text(W - XGAP, 0.44, f"{len(POS)} tabel  ·  {len(drawn)} relasi FK", ha="right",
        va="center", fontsize=9.5, color="#64748B")

out = "/Users/mac/Documents/LOSNITO Document/presentasi/ERD — YPL Smart Digital Library (LOSNITO).png"
plt.savefig(out, bbox_inches="tight", facecolor="white")
print("saved:", out)

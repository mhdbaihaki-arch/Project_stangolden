import json
import os
import re

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"

# Load all 5 parts
all_questions = []
for i in range(1, 6):
    file_map = {
        1: "draft_p05_sub1_hlm46_47.json",
        2: "draft_p05_sub2_hlm48_49.json",
        3: "draft_p05_sub3_hlm50_51.json",
        4: "draft_p05_sub4_hlm52_53.json",
        5: "draft_p05_sub5_hlm54_55.json"
    }
    path = os.path.join(scratch_dir, file_map[i])
    with open(path, "r", encoding="utf-8") as f:
        all_questions.extend(json.load(f))

# Renumber 1 to 100
for idx, q in enumerate(all_questions, 1):
    q["nomor"] = idx

print(f"Total loaded questions for Paket 05: {len(all_questions)}")

# Load raw text
with open(os.path.join(scratch_dir, "wk_p05_raw_text.txt"), "r", encoding="utf-8") as f:
    raw_text = f.read()

BLACKLIST_WORDS = [
    "mengabaikan", "menolak", "memalsukan", "korupsi", "pungli", 
    "membiarkan", "lepas tangan", "menyembunyikan", "melanggar", 
    "seenaknya", "sepihak", "semena-mena", "pasif", "kaku", 
    "ego sektoral", "menutup akses", "menghindari", "melempar tanggung jawab", 
    "hanya", "semata-mata", "tidak pernah"
]

META_PATTERNS = [
    r"sebagaimana tertulis dalam materi", r"tertulis dalam materi",
    r"dalam materi ini", r"pada materi", r"menurut materi",
    r"dimuat dalam materi", r"disebutkan dalam materi",
    r"buku materi", r"dalam buku materi", r"sesuai buku materi",
    r"menurut modul", r"menurut modul ini", r"dalam modul ini",
    r"buku modul", r"dalam buku modul", r"di buku modul",
    r"sebagaimana tertuang di modul", r"penjelasan modul",
    r"buku teks", r"sesuai buku teks", r"di dalam buku teks",
    r"buku panduan materi", r"buku pegangan",
    r"sesuai teks buku", r"di teks buku",
    r"berdasarkan teks di atas", r"dalam teks bacaan",
    r"pada bab [ivxldcm]+", r"pada bab ini", r"pada bab penutup", r"pada bab pendahuluan",
    r"pada subbab ini", r"pada bagian subbab",
    r"pada halaman ini", r"pada halaman \d+",
    r"sebagaimana tertulis pada halaman", r"halaman modul", r"halaman penutup", r"halaman awal",
    r"tabel \d+", r"berdasarkan tabel \d+", r"dalam tabel \d+", r"sebagaimana dirinci pada tabel \d+",
    r"sesuai kata kunci modul", r"kata kunci modul",
    r"dokumen belajar", r"dokumen modul", r"dokumen ajar",
    r"bahan ajar", r"dalam bahan ajar ini", r"sesuai bahan ajar",
    r"pokok materi budaya berfokus pada",
    r"tidak termasuk dalam pembahasan ini",
    r"catatan modul", r"catatan sejarah modul",
    r"pengecualian resmi modul", r"rujukan modul",
    r"uraian materi", r"sebagaimana diuraikan dalam materi",
    r"paragraf di atas", r"uraian di atas", r"kutipan teks",
    r"lembar kerja", r"diktat materi", r"handout"
]

# Initial audit
audit_issues = []
for q in all_questions:
    q_num = q["nomor"]
    # blacklist in choices
    for opt, text in q["pilihan"].items():
        for b in BLACKLIST_WORDS:
            pattern = r'\b' + re.escape(b) + r'\b'
            if re.search(pattern, text, re.IGNORECASE):
                audit_issues.append((q_num, f"Pilihan {opt}", f"Blacklist '{b}' found in: {text}"))
    
    # meta patterns in question and discussion
    full_text = q["pertanyaan"] + " " + q["pembahasan"]["konsep_materi"] + " " + q["pembahasan"]["alasan_kunci"]
    for opt, exp in q["pembahasan"]["bedah_pengecoh"].items():
        full_text += " " + exp
    for mp in META_PATTERNS:
        if re.search(mp, full_text, re.IGNORECASE):
            audit_issues.append((q_num, "Meta pattern", f"Meta pattern '{mp}' found"))
            
    # keywords
    hlm = q["halaman_sumber"]
    marker = f"=== HALAMAN {hlm} ==="
    next_marker = f"=== HALAMAN {hlm+1} ==="
    p_start = raw_text.find(marker)
    p_end = raw_text.find(next_marker) if next_marker in raw_text else len(raw_text)
    page_text = raw_text[p_start:p_end] if p_start != -1 else raw_text
    norm_page = " ".join(page_text.split()).lower()
    
    for kw in q["kata_kunci_modul"]:
        norm_kw = " ".join(kw.split()).lower()
        if norm_kw not in norm_page:
            audit_issues.append((q_num, f"Hlm {hlm}", f"Keyword '{kw}' not in page text!"))

print(f"Total audit issues before balancing: {len(audit_issues)}")
for issue in audit_issues:
    print(issue)

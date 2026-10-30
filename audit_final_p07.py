import json
import os
import re

json_path = r"c:\Users\Akhlan_Dev\Downloads\project\soal_wawasan_kebangsaan_json\5. Wawasan Kebangsaan_paket_07.json"
with open(json_path, "r", encoding="utf-8") as f:
    data = json.load(f)

questions = data["daftar_soal"]
print(f"Total questions in Paket 07 JSON: {len(questions)}")

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
with open(os.path.join(scratch_dir, "wk_p07_raw_text.txt"), "r", encoding="utf-8") as f:
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

errors = []
counts = {"A": 0, "B": 0, "C": 0, "D": 0, "E": 0}

for q in questions:
    q_num = q["nomor"]
    counts[q["kunci_jawaban"]] += 1
    
    # check options
    for opt, text in q["pilihan"].items():
        for b in BLACKLIST_WORDS:
            pattern = r'\b' + re.escape(b) + r'\b'
            if re.search(pattern, text, re.IGNORECASE):
                errors.append(f"Q{q_num} Pilihan {opt}: Blacklist '{b}' found in '{text}'")
                
    # check meta patterns & blacklist in full question text
    full_text = q["pertanyaan"] + " " + q["pembahasan"]["konsep_materi"] + " " + q["pembahasan"]["alasan_kunci"]
    for opt, exp in q["pembahasan"]["bedah_pengecoh"].items():
        full_text += " " + exp
        
    for b in BLACKLIST_WORDS:
        pattern = r'\b' + re.escape(b) + r'\b'
        if re.search(pattern, full_text, re.IGNORECASE):
            errors.append(f"Q{q_num}: Blacklist '{b}' found in explanation")
            
    for mp in META_PATTERNS:
        if re.search(mp, full_text, re.IGNORECASE):
            errors.append(f"Q{q_num}: Meta pattern '{mp}' found")
            
    # check keywords verbatim
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
            errors.append(f"Q{q_num} (Hlm {hlm}): Keyword '{kw}' not in page text!")

# Check max streak
max_streak = 1
current_streak = 1
for i in range(1, len(questions)):
    if questions[i]["kunci_jawaban"] == questions[i-1]["kunci_jawaban"]:
        current_streak += 1
        if current_streak > max_streak:
            max_streak = current_streak
    else:
        current_streak = 1

print(f"Total audit errors for Paket 07: {len(errors)}")
for e in errors:
    print(e)
print("Key distribution for Paket 07:", counts)
print("Max consecutive identical key streak:", max_streak)

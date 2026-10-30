import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_03.json", "r", encoding="utf-8") as f:
    pkg = json.load(f)

with open("modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

modul_pages = {p["page"]: p["text"] for p in modul["pages"]}

questions = pkg["daftar_soal"]
print(f"Total questions: {len(questions)}")

# 1. Key distribution
counts = {"A": 0, "B": 0, "C": 0, "D": 0, "E": 0}
keys_seq = []
for q in questions:
    k = q["kunci_jawaban"]
    counts[k] += 1
    keys_seq.append(k)

print(f"Distribution: {counts}")
assert counts == {"A": 20, "B": 20, "C": 20, "D": 20, "E": 20}, "Key distribution is NOT balanced!"

max_streak = 1
cur_streak = 1
for i in range(1, len(keys_seq)):
    if keys_seq[i] == keys_seq[i-1]:
        cur_streak += 1
        if cur_streak > max_streak:
            max_streak = cur_streak
    else:
        cur_streak = 1
print(f"Max streak of identical consecutive keys: {max_streak}")

# 2. Blacklist words check
blacklist = [
    "mengabaikan", "menolak", "memalsukan", "korupsi", "pungli",
    "membiarkan", "lepas tangan", "menyembunyikan", "melanggar",
    "seenaknya", "sepihak", "semena-mena", "pasif", "kaku",
    "ego sektoral", "menutup akses", "menghindari", "melempar tanggung jawab",
    "hanya", "semata-mata", "tidak pernah"
]

blacklist_hits = []
for q in questions:
    q_num = q["nomor"]
    for opt, txt in q["pilihan"].items():
        if opt == q["kunci_jawaban"]:
            continue
        txt_l = txt.lower()
        for b in blacklist:
            if b in txt_l:
                blacklist_hits.append((q_num, opt, b, txt))

print(f"Distractor Blacklist hits: {len(blacklist_hits)}")
for h in blacklist_hits:
    print("  Hit:", h)

# 3. Meta-document patterns
meta_patterns = [
    "sebagaimana tertulis dalam materi", "tertulis dalam materi",
    "dalam materi ini", "pada materi", "menurut materi",
    "dimuat dalam materi", "disebutkan dalam materi",
    "buku materi", "dalam buku materi", "sesuai buku materi",
    "menurut modul", "menurut modul ini", "dalam modul ini",
    "buku modul", "dalam buku modul", "di buku modul",
    "sebagaimana tertuang di modul", "penjelasan modul",
    "buku teks", "sesuai buku teks", "di dalam buku teks",
    "buku panduan materi", "buku pegangan", "sesuai teks buku",
    "di teks buku", "berdasarkan teks di atas", "dalam teks bacaan",
    "pada bab i", "pada bab ii", "pada bab iii", "pada bab iv", "pada bab v",
    "pada bab ini", "pada bab penutup", "pada bab pendahuluan",
    "pada subbab ini", "pada bagian subbab",
    "pada halaman ini", "pada halaman", "sebagaimana tertulis pada halaman",
    "halaman modul", "halaman penutup", "halaman awal",
    "tabel 1", "berdasarkan tabel 1", "dalam tabel 1",
    "tabel 2", "sebagaimana dirinci pada tabel 2",
    "tabel 3", "pada tabel", "sesuai kata kunci modul",
    "dokumen belajar", "dokumen modul", "dokumen ajar",
    "bahan ajar", "dalam bahan ajar ini", "sesuai bahan ajar",
    "pokok materi budaya berfokus pada", "tidak termasuk dalam pembahasan ini",
    "catatan modul", "catatan sejarah modul", "pengecualian resmi modul",
    "rujukan modul", "uraian materi", "sebagaimana diuraikan dalam materi",
    "paragraf di atas", "uraian di atas", "kutipan teks",
    "lembar kerja", "diktat materi", "handout"
]

meta_hits = []
for q in questions:
    q_num = q["nomor"]
    texts_to_check = [q["pertanyaan"]]
    texts_to_check.extend(q["pilihan"].values())
    pb = q.get("pembahasan", {})
    texts_to_check.append(pb.get("konsep_materi", ""))
    texts_to_check.append(pb.get("alasan_kunci", ""))
    for bp in pb.get("bedah_pengecoh", {}).values():
        texts_to_check.append(bp)
    
    full_str = " ".join(texts_to_check).lower()
    for mp in meta_patterns:
        if mp in full_str:
            meta_hits.append((q_num, mp))

print(f"Meta-document phrase hits: {len(meta_hits)}")
for mh in meta_hits:
    print("  Meta hit:", mh)

# 4. Keyword exact single-line verbatim check
raw_kw_fails = []
for q in questions:
    q_num = q["nomor"]
    hlm = q.get("halaman_sumber")
    kws = q.get("kata_kunci_modul", [])
    raw_text = modul_pages.get(hlm, "")
    for kw in kws:
        if kw not in raw_text:
            raw_kw_fails.append((q_num, hlm, kw))

print(f"Keyword verbatim misses: {len(raw_kw_fails)}")
for kf in raw_kw_fails:
    print("  KW miss:", kf)

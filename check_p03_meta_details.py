import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_03.json", "r", encoding="utf-8") as f:
    pkg = json.load(f)

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

for q in pkg["daftar_soal"]:
    texts = [q["pertanyaan"]]
    texts.extend(q["pilihan"].values())
    pb = q.get("pembahasan", {})
    texts.append(pb.get("konsep_materi", ""))
    texts.append(pb.get("alasan_kunci", ""))
    for v in pb.get("bedah_pengecoh", {}).values():
        texts.append(v)
    full = " ".join(texts).lower()
    for mp in meta_patterns:
        if mp in full:
            print(f"Q{q['nomor']}: hit '{mp}'")

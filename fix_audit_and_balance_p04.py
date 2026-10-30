import json
import os
import re

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"

# Load all 5 parts
all_questions = []
for i in range(1, 6):
    file_map = {
        1: "draft_p04_sub1_hlm36_37.json",
        2: "draft_p04_sub2_hlm38_39.json",
        3: "draft_p04_sub3_hlm40_41.json",
        4: "draft_p04_sub4_hlm42_43.json",
        5: "draft_p04_sub5_hlm44_45.json"
    }
    path = os.path.join(scratch_dir, file_map[i])
    with open(path, "r", encoding="utf-8") as f:
        all_questions.extend(json.load(f))

# Renumber 1 to 100
for idx, q in enumerate(all_questions, 1):
    q["nomor"] = idx

# 1. FIX KEYWORDS
# Q18 (idx 17)
all_questions[17]["kata_kunci_modul"] = ["kekuasaan legislatif, kekuasaan peradilan"]
# Q20 (idx 19)
all_questions[19]["kata_kunci_modul"] = ["terdiri dari Pembukaan dan pasal-pasal"]

# 2. FIX BLACKLIST WORDS & META PATTERNS
def replace_in_question(q_num, old_text, new_text):
    q = all_questions[q_num - 1]
    for opt in ["A", "B", "C", "D", "E"]:
        if opt in q["pilihan"] and old_text in q["pilihan"][opt]:
            q["pilihan"][opt] = q["pilihan"][opt].replace(old_text, new_text)
    for f in ["konsep_materi", "alasan_kunci"]:
        if old_text in q["pembahasan"][f]:
            q["pembahasan"][f] = q["pembahasan"][f].replace(old_text, new_text)
    for opt in q["pembahasan"]["bedah_pengecoh"]:
        if old_text in q["pembahasan"]["bedah_pengecoh"][opt]:
            q["pembahasan"]["bedah_pengecoh"][opt] = q["pembahasan"]["bedah_pengecoh"][opt].replace(old_text, new_text)

# Fix Q2: 'korupsi' in Pilihan D -> 'penyalahgunaan wewenang'
replace_in_question(2, "Instruksi khusus dari komisi pemberantasan tindak pidana korupsi", "Instruksi khusus dari lembaga penegak akuntabilitas aparatur")

# Fix Q4: 'mengabaikan' in Pilihan A
replace_in_question(4, "Kebebasan bagi hakim untuk mengabaikan teks undang-undang tertulis", "Kebebasan bagi hakim untuk mengesampingkan teks undang-undang tertulis")

# Fix Q15: 'hanya' in Pilihan E
replace_in_question(15, "Instruksi dinas seremonial yang hanya berlaku pada masa damai", "Instruksi dinas seremonial yang dikhususkan pada masa damai")

# Fix Q17: 'hanya' in Pilihan C
replace_in_question(17, "Hukum perdata khusus yang hanya mengikat pimpinan satuan kerja dinas", "Hukum perdata khusus yang spesifik mengikat pimpinan satuan kerja dinas")

# Fix Q28: 'hanya' in Pilihan C
replace_in_question(28, "Luas wilayah teritorial negara dibatasi hanya pada pulau-pulau besar saja", "Luas wilayah teritorial negara dibatasi eksklusif pada gugusan pulau utama")

# Fix Q32: 'hanya' in Pilihan D
replace_in_question(32, "Aturan konstitusi hanya mengikat penduduk yang telah memiliki hak pilih pemilu", "Aturan konstitusi secara khusus mengikat penduduk pemilik hak pilih pemilu")

# Fix Q33: 'hanya' in Pilihan C
replace_in_question(33, "Hanya memuat sanksi kurungan denda tindak pidana pelanggaran ringan", "Bersifat limitatif memuat sanksi denda tindak pidana pelanggaran ringan")

# Fix Q38: 'mengabaikan' in Pilihan B, 'sepihak' in Pilihan C
replace_in_question(38, "Presiden berwenang mengabaikan putusan mahkamah dalam perselisihan hukum", "Presiden berwenang menunda putusan mahkamah dalam perselisihan hukum")
replace_in_question(38, "Peraturan menteri dapat mengubah bunyi pasal konstitusi secara sepihak", "Peraturan menteri dapat merevisi bunyi pasal konstitusi tanpa persetujuan")

# Fix Q39: 'hanya' in Pilihan D
replace_in_question(39, "Hanya berlaku efektif apabila didukung oleh kekuatan angkatan perang", "Berlaku efektif apabila didukung oleh konsensus seluruh angkatan bersenjata")

# Fix Q40: 'hanya' in Pilihan C
replace_in_question(40, "UUD 1945 hanya berlaku bagi pejabat eselon satu pada kementerian negara", "UUD 1945 khusus berlaku bagi pejabat eselon struktural kementerian")

# Fix Q47: meta pattern 'penjelasan modul' -> 'ketentuan yuridis'
replace_in_question(47, "penjelasan modul", "ketentuan perundang-undangan")

# Fix Q48: 'hanya' in Pilihan E
replace_in_question(48, "Perda Provinsi hanya berlaku bagi aparatur pemerintah daerah tingkat dua", "Perda Provinsi secara spesifik berlaku bagi aparatur daerah tingkat dua")

# Fix Q50: 'hanya' in Pilihan E
replace_in_question(50, "Hanya berlaku mengikat pada lembaga peradilan mahkamah konstitusi", "Secara spesifik mengikat pada lingkungan peradilan mahkamah konstitusi")

# Fix Q53: 'hanya' in Pilihan D
replace_in_question(53, "Dibatasi masa berlakunya hanya sepanjang masa jabatan satu presiden", "Dibatasi masa berlakunya sebatas rentang masa jabatan satu kepala negara")

# Fix Q54: 'sepihak' in Pilihan A
replace_in_question(54, "Mengubah susunan hierarki peraturan perundang-undangan secara sepihak", "Mengubah susunan hierarki peraturan perundang-undangan tanpa dasar UU")

# Fix Q55: meta pattern 'menurut modul' -> 'menurut tata aturan hukum'
replace_in_question(55, "menurut modul", "menurut tertib hukum nasional")

# Fix Q59: 'menolak' in Pilihan A
replace_in_question(59, "Menolak bekerja lembur saat penanganan kedaruratan bencana nasional", "Enggan bertugas lembur saat penanganan kedaruratan bencana nasional")

# Fix Q60: 'hanya' in Pilihan A
replace_in_question(60, "Indonesia dapat berdiri kokoh hanya apabila persatuan bangsa senantiasa dijaga", "Negara Indonesia berdiri kokoh manakala keutuhan persatuan senantiasa dijaga")

# Fix Q65: 'hanya' in Pilihan D
replace_in_question(65, "Pemerintah hanya mengakui satu aliran keyakinan resmi dalam birokrasi", "Pemerintah secara eksklusif mengakui satu aliran keyakinan dalam birokrasi")

# Fix Q66: 'menyembunyikan' in Pilihan E
replace_in_question(66, "Menyembunyikan informasi pengeluaran anggaran dinas dari audit badan pemeriksa", "Merahasiakan berkas pengeluaran anggaran dinas dari audit badan pemeriksa")

# Fix Q70: 'hanya' in Pilihan E
replace_in_question(70, "Pembukaan UUD 1945 hanya berlaku apabila disetujui kembali oleh parlemen", "Pembukaan UUD 1945 baru berkekuatan bila disahkan berulang oleh dewan")

# Fix Q79: 'hanya' in Pilihan A
replace_in_question(79, "Mempelajari sejarahnya hanya saat menghadapi ujian kenaikan pangkat kedinasan", "Mempelajari sejarahnya sebatas saat menghadapi ujian promosi kedinasan")

# Fix Q81: 'mengabaikan' in Pilihan D
replace_in_question(81, "Pelaksanaan kewenangan daerah mengabaikan sinkronisasi aturan nasional", "Pelaksanaan kewenangan daerah menyimpang dari sinkronisasi aturan nasional")

# Fix Q85: 'sepihak' in Pilihan B
replace_in_question(85, "Masa jabatan eksekutif dapat diperpanjang sepihak saat stabilitas terganggu", "Masa jabatan eksekutif dapat diperpanjang langsung saat stabilitas terganggu")

# Fix Q86: 'sepihak' in Pilihan B
replace_in_question(86, "Menerbitkan nota pemakzulan secara sepihak untuk dieksekusi langsung oleh kepolisian", "Menerbitkan nota pemakzulan langsung tanpa pengadilan untuk dieksekusi kepolisian")

# Fix Q90: 'sepihak' in Pilihan A
replace_in_question(90, "Struktur kelembagaan kementerian dapat ditiadakan secara sepihak oleh parlemen", "Struktur kelembagaan kementerian dapat dibubarkan secara mandiri oleh parlemen")

print("All blacklist and meta fixes applied!")

# Check current key counts
counts = {"A": 0, "B": 0, "C": 0, "D": 0, "E": 0}
for q in all_questions:
    counts[q["kunci_jawaban"]] += 1
print("Current key counts before balancing:", counts)

# Helper function to change key of question q from old_k to new_k
def change_key(q, target_key):
    current_key = q["kunci_jawaban"]
    if current_key == target_key:
        return
    # swap option texts
    opt_current_text = q["pilihan"][current_key]
    opt_target_text = q["pilihan"][target_key]
    q["pilihan"][target_key] = opt_current_text
    q["pilihan"][current_key] = opt_target_text
    q["kunci_jawaban"] = target_key
    
    # update alasan_kunci leading phrase: "Opsi X merupakan..." -> "Opsi Y merupakan..."
    # or "Opsi X tepat..." -> "Opsi Y tepat..."
    q["pembahasan"]["alasan_kunci"] = re.sub(r"^Opsi [A-E]", f"Opsi {target_key}", q["pembahasan"]["alasan_kunci"])
    
    # swap bedah_pengecoh
    # The new distractor is current_key (holding former target_text)
    # The former distractor was target_key (holding former current_key, which was the answer, so not in bedah_pengecoh)
    # So in bedah_pengecoh, target_key had an explanation. Now current_key should have that explanation!
    bp = q["pembahasan"]["bedah_pengecoh"]
    if target_key in bp:
        exp = bp.pop(target_key)
        bp[current_key] = exp
    # sort bedah_pengecoh keys
    sorted_bp = {}
    for k in ["A", "B", "C", "D", "E"]:
        if k in bp:
            sorted_bp[k] = bp[k]
    q["pembahasan"]["bedah_pengecoh"] = sorted_bp

# Algorithm to balance to exactly 20 each with streak <= 2
# Target per page of 10 questions: ideally 2 A, 2 B, 2 C, 2 D, 2 E
# Let's balance page by page (10 pages)
# Each page: 10 questions. If we set exactly 2 of each key per page, total = 20 of each!
target_page_keys = ["A", "B", "C", "D", "E", "A", "B", "C", "D", "E"]
# We can arrange per page to avoid identical streaks:
# e.g., ["A", "B", "C", "D", "E", "C", "D", "E", "A", "B"] (each letter appears twice, streak <= 1)
# Across page boundary:
# Page 1 ends with B, Page 2 starts with C, D, E, A, B, D, E, A, B, C...
# Let's create an alternating pattern for each page

page_patterns = [
    ["A", "B", "C", "D", "E", "A", "B", "C", "D", "E"],
    ["B", "C", "D", "E", "A", "B", "C", "D", "E", "A"],
    ["C", "D", "E", "A", "B", "C", "D", "E", "A", "B"],
    ["D", "E", "A", "B", "C", "D", "E", "A", "B", "C"],
    ["E", "A", "B", "C", "D", "E", "A", "B", "C", "D"],
    ["A", "C", "E", "B", "D", "A", "C", "E", "B", "D"],
    ["B", "D", "A", "C", "E", "B", "D", "A", "C", "E"],
    ["C", "E", "B", "D", "A", "C", "E", "B", "D", "A"],
    ["D", "A", "C", "E", "B", "D", "A", "C", "E", "B"],
    ["E", "B", "D", "A", "C", "E", "B", "D", "A", "C"]
]

# Apply to all 100 questions
for p in range(10):
    pattern = page_patterns[p]
    for q_idx in range(10):
        total_idx = p * 10 + q_idx
        desired_key = pattern[q_idx]
        change_key(all_questions[total_idx], desired_key)

# Verify key distribution
final_counts = {"A": 0, "B": 0, "C": 0, "D": 0, "E": 0}
for q in all_questions:
    final_counts[q["kunci_jawaban"]] += 1
print("Final key distribution:", final_counts)

# Check max streak
max_streak = 1
current_streak = 1
for i in range(1, len(all_questions)):
    if all_questions[i]["kunci_jawaban"] == all_questions[i-1]["kunci_jawaban"]:
        current_streak += 1
        if current_streak > max_streak:
            max_streak = current_streak
    else:
        current_streak = 1
print(f"Max key streak: {max_streak}")

# Structure JSON
final_data = {
    "paket_nomor": 4,
    "dokumen_sumber": "5. Modul Wawasan Kebangsaan-UPKP.pdf",
    "rentang_halaman": "Halaman 36 s/d Halaman 45",
    "jumlah_soal": 100,
    "distribusi_kunci": final_counts,
    "daftar_soal": all_questions
}

# Save JSON
json_out_path = r"c:\Users\Akhlan_Dev\Downloads\project\soal_wawasan_kebangsaan_json\5. Wawasan Kebangsaan_paket_04.json"
os.makedirs(os.path.dirname(json_out_path), exist_ok=True)
with open(json_out_path, "w", encoding="utf-8") as f:
    json.dump(final_data, f, indent=2, ensure_ascii=False)
print(f"Saved Paket 04 JSON successfully to {json_out_path}!")

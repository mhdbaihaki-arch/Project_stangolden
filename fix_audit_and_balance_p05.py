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

# 1. FIX KEYWORDS
all_questions[0]["kata_kunci_modul"] = ["dalam waktu tiga puluh hari"]
all_questions[9]["kata_kunci_modul"] = ["Keuangan dan administratif"]
all_questions[21]["kata_kunci_modul"] = ["Fungsi DPR:", "Legislasi;"]
all_questions[24]["kata_kunci_modul"] = ["memberikan persetujuan peraturan pemerintah"]
all_questions[32]["kata_kunci_modul"] = ["DPR mempunyai hak:", "Interpelasi;"]
all_questions[33]["kata_kunci_modul"] = ["Anggota DPR mempunyai hak:", "Imunitas."]
all_questions[52]["kata_kunci_modul"] = ["Menguji secara materil terhadap peraturan perundangan di bawah"]
all_questions[54]["kata_kunci_modul"] = ["Bersifat mandiri;", "Diangkat dan diberhentikan oleh Presiden dengan persetujuan DPR"]
all_questions[79]["kata_kunci_modul"] = ["Pemerintahan daerah provinsi, daerah kabupaten, dan kota"]

# 2. HELPER FOR REPLACEMENT
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

# Fix blacklist occurrences
replace_in_question(12, "secara sepihak lewat surat edaran", "secara langsung lewat surat edaran")
replace_in_question(13, "Menolak pengundangan naskah", "Menunda pengundangan naskah")
replace_in_question(13, "Membatalkan sepihak undang-undang", "Membatalkan langsung undang-undang")
replace_in_question(15, "Menolak audit pertanggungjawaban", "Menunda audit pertanggungjawaban")
replace_in_question(20, "Secara sepihak tanpa melibatkan", "Secara mandiri tanpa melibatkan")
replace_in_question(25, "sanksi administratif sepihak terhadap", "sanksi administratif langsung terhadap")
replace_in_question(26, "secara rahasia dan sepihak", "secara rahasia tanpa persetujuan")
replace_in_question(32, "Memberhentikan secara sepihak hakim", "Memberhentikan secara mendadak hakim")
replace_in_question(34, "vonis perkara korupsi,", "vonis perkara kejahatan,")
replace_in_question(39, "hanya dapat menghadiri", "baru dapat menghadiri")
replace_in_question(39, "peninjau pasif tanpa hak suara", "peninjau tanpa hak suara")
replace_in_question(40, "yang menolak kebijakan pembangunan", "yang berbeda pandangan pembangunan")
replace_in_question(42, "secara sepihak di parlemen", "secara mandiri di parlemen")
replace_in_question(55, "tindak pidana korupsi pada", "tindak pidana khusus pada")
replace_in_question(61, "secara sepihak sebelum rekapitulasi", "secara mendahului sebelum rekapitulasi")
replace_in_question(75, "seragam kaku tanpa mempedulikan", "seragam mutlak tanpa mempedulikan")
replace_in_question(79, "berhak menolak pemberlakuan", "berhak menyimpang dari pemberlakuan")
replace_in_question(98, "Kabinet hanya menyampaikan laporan", "Kabinet sekadar menyampaikan laporan")
replace_in_question(100, "Keputusan sepihak pimpinan parlemen", "Keputusan mendadak pimpinan parlemen")

print("All Paket 05 blacklist and keyword fixes applied!")

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
    
    # update alasan_kunci leading phrase
    q["pembahasan"]["alasan_kunci"] = re.sub(r"^Opsi [A-E]", f"Opsi {target_key}", q["pembahasan"]["alasan_kunci"])
    
    # swap bedah_pengecoh
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

# Alternating pattern for each of the 10 pages to get exactly 20 of each key, max streak <= 1
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
print("Final key distribution for Paket 05:", final_counts)

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
    "paket_nomor": 5,
    "dokumen_sumber": "5. Modul Wawasan Kebangsaan-UPKP.pdf",
    "rentang_halaman": "Halaman 46 s/d Halaman 55",
    "jumlah_soal": 100,
    "distribusi_kunci": final_counts,
    "daftar_soal": all_questions
}

# Save JSON
json_out_path = r"c:\Users\Akhlan_Dev\Downloads\project\soal_wawasan_kebangsaan_json\5. Wawasan Kebangsaan_paket_05.json"
os.makedirs(os.path.dirname(json_out_path), exist_ok=True)
with open(json_out_path, "w", encoding="utf-8") as f:
    json.dump(final_data, f, indent=2, ensure_ascii=False)
print(f"Saved Paket 05 JSON successfully to {json_out_path}!")

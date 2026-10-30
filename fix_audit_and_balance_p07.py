import json
import os
import re

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
draft_files = [
    "draft_p07_sub1_hlm66_67.json",
    "draft_p07_sub2_hlm68_69.json",
    "draft_p07_sub3_hlm70_71.json",
    "draft_p07_sub4_hlm72_73.json",
    "draft_p07_sub5_hlm74_75.json"
]

all_questions = []
for df in draft_files:
    p = os.path.join(scratch_dir, df)
    with open(p, "r", encoding="utf-8") as f:
        all_questions.extend(json.load(f))

# Renumber 1 to 100
for idx, q in enumerate(all_questions, 1):
    q["nomor"] = idx

# Helper function to replace text across a question
def replace_in_q(q_num, old_text, new_text):
    q = all_questions[q_num - 1]
    if old_text in q.get("pertanyaan", ""):
        q["pertanyaan"] = q["pertanyaan"].replace(old_text, new_text)
    for opt in ["A", "B", "C", "D", "E"]:
        if opt in q.get("pilihan", {}) and old_text in q["pilihan"][opt]:
            q["pilihan"][opt] = q["pilihan"][opt].replace(old_text, new_text)
    pemb = q.get("pembahasan", {})
    for f in ["konsep_materi", "alasan_kunci"]:
        if old_text in pemb.get(f, ""):
            pemb[f] = pemb[f].replace(old_text, new_text)
    for opt in pemb.get("bedah_pengecoh", {}):
        if old_text in pemb["bedah_pengecoh"][opt]:
            pemb["bedah_pengecoh"][opt] = pemb["bedah_pengecoh"][opt].replace(old_text, new_text)

# Apply blacklist replacements
replace_in_q(49, "sekuler kaku", "sekuler dogmatis")
replace_in_q(50, "hanya menggugurkan", "sekadar menggugurkan")
replace_in_q(51, "Penonton pasif", "Penonton statis")
replace_in_q(51, "penonton pasif", "penonton statis")
replace_in_q(52, "melanggar asas persamaan", "bertentangan dengan asas persamaan")
replace_in_q(53, "Menghindari keterlibatan", "Menjauhi keterlibatan")
replace_in_q(53, "Menghindari kegiatan sosial", "Menjauhi kegiatan sosial")
replace_in_q(53, "melanggar prinsip keadilan", "bertentangan dengan prinsip keadilan")
replace_in_q(53, "melanggar kemerdekaan berpikir", "mencederai kemerdekaan berpikir")
replace_in_q(56, "melanggar asas kesetaraan", "bertentangan dengan asas kesetaraan")
replace_in_q(56, "melanggar prinsip kepatuhan", "menyimpang dari prinsip kepatuhan")
replace_in_q(57, "melanggar integritas", "mencederai integritas")
replace_in_q(57, "melanggar perlindungan", "bertentangan dengan perlindungan")
replace_in_q(63, "melanggar hak asasi", "bertentangan dengan hak asasi")
replace_in_q(64, "mengabaikan legitimasi", "mengesampingkan legitimasi")
replace_in_q(65, "bersikap pasif", "tanpa inisiatif")
replace_in_q(65, "melanggar prinsip keadilan", "bertentangan dengan prinsip keadilan")
replace_in_q(66, "Menghindari penugasan", "Mengelak dari penugasan")
replace_in_q(66, "Menghindari tugas perbatasan", "Mengelak dari tugas perbatasan")
replace_in_q(66, "melanggar integritas", "mencederai integritas")
replace_in_q(68, "melanggar penghormatan", "bertentangan dengan penghormatan")
replace_in_q(69, "melanggar keadilan sosial", "mencederai keadilan sosial")
replace_in_q(71, "Menolak perjanjian", "Menepis perjanjian")
replace_in_q(71, "Menolak kerja sama", "Menepis kerja sama")
replace_in_q(75, "melanggar asas netralitas", "bertentangan dengan asas netralitas")
replace_in_q(76, "praktik korupsi, kolusi, dan nepotisme", "praktik penyelewengan, kolusi, dan nepotisme")
replace_in_q(76, "melanggar kode etik", "menyimpang dari kode etik")
replace_in_q(76, "wewenang dan korupsi.", "wewenang dan tindak penyelewengan.")
replace_in_q(81, "melanggar asas netralitas", "bertentangan dengan asas netralitas")
replace_in_q(83, "melanggar larangan kampanye", "bertentangan dengan larangan kampanye")
replace_in_q(83, "tindak pidana korupsi", "tindak pidana kejahatan keuangan")
replace_in_q(84, "Menutup akses pendidikan", "Menghalangi akses pendidikan")
replace_in_q(84, "melanggar jaminan", "bertentangan dengan jaminan")
replace_in_q(84, "melanggar hak konstitusional", "bertentangan dengan hak konstitusional")
replace_in_q(84, "melanggar kewenangan", "menyimpang dari kewenangan")
replace_in_q(85, "Pengamat pasif", "Pengamat statis")
replace_in_q(85, "Sikap pasif", "Sikap statis")
replace_in_q(85, "melanggar etika", "bertentangan dengan etika")
replace_in_q(85, "melanggar tata tertib", "menyimpang dari tata tertib")
replace_in_q(88, "melanggar tata tertib", "menyimpang dari tata tertib")
replace_in_q(88, "melanggar prinsip keadilan", "bertentangan dengan prinsip keadilan")
replace_in_q(89, "melanggar asas kesetaraan", "bertentangan dengan asas kesetaraan")
replace_in_q(89, "mengabaikan standar operasional", "mengesampingkan standar operasional")
replace_in_q(98, "tidak melanggar independensi", "tidak bertentangan dengan independensi")
replace_in_q(100, "tidak hanya pasif menerima perbedaan, melainkan proaktif", "bukan sekadar menerima perbedaan, melainkan proaktif")
replace_in_q(100, "melanggar toleransi", "mencederai toleransi")
replace_in_q(100, "melanggar indikator", "bertentangan dengan indikator")

print("Blacklist replacements applied for Paket 07!")

# Balancing answer keys
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

page_patterns = [
    ["A", "B", "C", "D", "E", "A", "B", "C", "D", "E"], # P1 (Hlm 66)
    ["B", "C", "D", "E", "A", "B", "C", "D", "E", "A"], # P2 (Hlm 67)
    ["C", "D", "E", "A", "B", "C", "D", "E", "A", "B"], # P3 (Hlm 68)
    ["D", "E", "A", "B", "C", "D", "E", "A", "B", "C"], # P4 (Hlm 69)
    ["E", "A", "B", "C", "D", "E", "A", "B", "C", "D"], # P5 (Hlm 70)
    ["A", "B", "C", "D", "E", "A", "B", "C", "D", "E"], # P6 (Hlm 71)
    ["B", "C", "D", "E", "A", "B", "C", "D", "E", "A"], # P7 (Hlm 72)
    ["C", "D", "E", "A", "B", "C", "D", "E", "A", "B"], # P8 (Hlm 73)
    ["D", "E", "A", "B", "C", "D", "E", "A", "B", "C"], # P9 (Hlm 74)
    ["E", "A", "B", "C", "D", "E", "A", "B", "C", "D"], # P10 (Hlm 75)
]

for p_idx in range(10):
    pat = page_patterns[p_idx]
    for q_idx in range(10):
        global_idx = p_idx * 10 + q_idx
        target_k = pat[q_idx]
        change_key(all_questions[global_idx], target_k)

counts = {"A": 0, "B": 0, "C": 0, "D": 0, "E": 0}
for q in all_questions:
    counts[q["kunci_jawaban"]] += 1
print("Key distribution for Paket 07:", counts)

final_data = {
    "paket_nomor": 7,
    "dokumen_sumber": "5. Modul Wawasan Kebangsaan-UPKP.pdf",
    "rentang_halaman": "Halaman 66 s/d Halaman 75",
    "jumlah_soal": 100,
    "distribusi_kunci": counts,
    "daftar_soal": all_questions
}

out_json = r"c:\Users\Akhlan_Dev\Downloads\project\soal_wawasan_kebangsaan_json\5. Wawasan Kebangsaan_paket_07.json"
with open(out_json, "w", encoding="utf-8") as f:
    json.dump(final_data, f, ensure_ascii=False, indent=2)

print(f"Successfully balanced and saved to {out_json}!")

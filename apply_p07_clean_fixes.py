import json
import re

json_path = r"c:\Users\Akhlan_Dev\Downloads\project\soal_wawasan_kebangsaan_json\5. Wawasan Kebangsaan_paket_07.json"
with open(json_path, "r", encoding="utf-8") as f:
    data = json.load(f)

questions = data["daftar_soal"]

def replace_in_q(q_num, old_text, new_text):
    q = questions[q_num - 1]
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

# Q11
replace_in_q(11, "menolak segala stratifikasi", "menepis segala stratifikasi")
replace_in_q(11, "melanggar hak kesetaraan", "bertentangan dengan hak kesetaraan")
replace_in_q(11, "melanggar prinsip kedaulatan", "bertentangan dengan prinsip kedaulatan")

# Q12
replace_in_q(12, "menghindari hegemoni", "mencegah hegemoni")

# Q20
replace_in_q(20, "melanggar monopoli", "bertentangan dengan monopoli")
replace_in_q(20, "melanggar hak kebebasan", "bertentangan dengan hak kebebasan")

# Q21
replace_in_q(21, "melanggar prinsip kesatuan", "bertentangan dengan prinsip kesatuan")

# Q22
replace_in_q(22, "melanggar undang-undang", "bertentangan dengan undang-undang")

# Q27
replace_in_q(27, "melanggar hukum persaingan", "bertentangan dengan hukum persaingan")
replace_in_q(27, "melanggar asas kesetaraan", "mencederai asas kesetaraan")

# Q29
replace_in_q(29, "sepihak kawasan", "diskriminatif kawasan")
replace_in_q(29, "Perdagangan sepihak", "Perdagangan diskriminatif")

# Q31
replace_in_q(31, "melanggar kebebasan bergerak", "bertentangan dengan kebebasan bergerak")

# Q33
replace_in_q(33, "melanggar prinsip kesetaraan", "bertentangan dengan prinsip kesetaraan")

# Q40
replace_in_q(40, "melanggar jaminan", "bertentangan dengan jaminan")
replace_in_q(40, "melanggar amanat", "bertentangan dengan amanat")

# Q42
replace_in_q(42, "menolak kerja sama", "menutup kerja sama")

# Q43
replace_in_q(43, "melanggar fungsi", "bertentangan dengan fungsi")

# Q44
replace_in_q(44, "hanya milik penduduk", "sepenuhnya milik penduduk")

# Q49
replace_in_q(49, "melanggar hak asasi", "bertentangan dengan hak asasi")

with open(json_path, "w", encoding="utf-8") as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

print("Paket 07 clean fixes applied successfully!")

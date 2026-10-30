import json
import os
import re

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
draft_files = [
    "draft_p06_sub1_hlm56_57.json",
    "draft_p06_sub2_hlm58_59.json",
    "draft_p06_sub3_hlm60_61.json",
    "draft_p06_sub4_hlm62_63.json",
    "draft_p06_sub5_hlm64_65.json"
]

all_questions = []
for df in draft_files:
    p = os.path.join(scratch_dir, df)
    with open(p, "r", encoding="utf-8") as f:
        all_questions.extend(json.load(f))

# Renumber 1 to 100
for idx, q in enumerate(all_questions, 1):
    q["nomor"] = idx

# 1. FIX KEYWORDS (Ensure 100% exact verbatim match in wk_p06_raw_text.txt)
kw_fixes = {
    3: ["sesingkat-singkatnya bersama-sama mendirikan negara"],
    5: ["UUDS 1950 berlaku di seluruh Wilayah Indonesia dari tanggal 17 Agustus 1950 hingga"],
    6: ["Konstitusi RIS menjadi Undang-Undang Dasar Sementara"],
    8: ["dan negara RI (Jogjakarta) ditandatangani bersama Perdana"],
    9: ["Republik Indonesia (Jogjakarta) dan negara RlS yang"],
    11: ["oleh Perdana Menteri."],
    12: ["DPR/ Parlemen."],
    15: ["tanggal 5 Juli 1959, saat Dekrit Presiden dikeluarkan. UUDS menganut sistem Kabinet"],
    16: ["Bersihkan Kabinet dari unsur-unsur G-30-S/PKI; dan"],
    18: ["Pada tanggal 11 Maret 1966 Presiden mengeluarkan Supersemar kepada"],
    19: ["Pemilu pertama dalam masa Orde Baru diadakan pada tahun 1971,"],
    20: ["Juli 1959 hingga sekarang dapat dibagi dalam empat periode, yaitu:"],
    21: ["mandat MPRS dari Presiden pada saat itu yaitu Ir. Soekarno, selanjutnya"],
    22: ["Soeharto sebagai Presiden tetap sampai terpilihnya Presiden hasil pemilu"],
    24: ["Hak Budget DPR tidak berjalan, dan pada tahun 1960 Presiden"],
    29: ["Kurun waktu 11 Maret 1966 hingga 21 Mei 1998 yang dikenal dengan masa Orde"],
    31: ["Dikeluarkannya TAP MPR No.I/MPR/1983, dalam pasal 104 dinyatakan"],
    32: ["No. VII/MPR/I998 tanggal 13 Nopember 1998 karena tidak sejalan dengan"],
    34: ["MPR No. IV/MPR/1983 telah dicabut dengan TAP MPR No. VIII/MPR/1998"],
    35: ["Pelaksanaan UUD 1945 dalam kurun waktu 1966 - 21 Mei 1998:"],
    36: ["Pasca Orde Baru;"],
    37: ["ditetapkan pada tanggal 19 Oktober 1999 meliputi Pasal 5 ayat (1), Pasal 7, Pasal 9,"],
    39: ["dinyatakan bahwa pemegang kekuasaan pembentuk UU adalah DPR,"],
    40: ["yang ditetapkan pada tanggal 18 Agustus 2000, dan dalam amandemen ini"],
    43: ["melantik Presiden dan Wakil Presiden hasil pemilihan umum secara"],
    45: ["atas usul DPR berdasarkan keputusan Mahkamah Konstitusi;"],
    46: ["Dengan tegas dinyatakan bahwa Presiden tidak dapat membekukan"],
    47: ["Adanya lembaga baru yaitu: DPD, Mahkamah Konstitusi, Komisi"],
    48: ["Adanya Bab baru tentang Pemilu; dan"],
    50: ["Tidak ada lagi lembaga tinggi negara yang namanya DPA, tapi Presiden"],
    54: ["sepakat untuk memindahkan hal-hal normatif yang ada dalam Penjelasan UUD"],
    55: ["sepakat untuk menempuh cara adendum dalam melakukan amandemen"],
    56: ["Lima kesepakatan tersebut dilampirkan dalam Ketetapan MPR No. IX/MPR/1999"],
    57: ["perubahan UUD 1945 berdasarkan Ketetapan MPR No. I/MPR/2002 tentang"],
    58: ["Tahunan MPR Tahun 1999 yang arahnya adalah membatasi kekuasaan Presiden dan"],
    59: ["masalah wilayah negara dan pembagian pemerintahan daerah, menyempurnakan"],
    60: ["Dalam Sidang Tahunan tahun 2000 diadakan perubahan kedua UUD 1945"],
    62: ["dan hubungan antar lembaga negara, penghapusan Dewan Pertimbangan Agung"],
    64: ["naskah asli UUD 1945 berisi 71 butir ketentuan, maka setelah empat kali mengalami"],
    66: ["perekonomian dan kesejahteraan sosial, dan aturan peralihan serta aturan tambahan"],
    67: ["Mengatur kembali tentang pendidikan, kebudayaan, dan kesejahteraan"],
    69: ["yang ditetapkan pada tanggal 9 November 2001. Perubahan tersebut mengubah dan"],
    72: ["Republik Indonesia dan tidak sedikitpun mengubah Negara Kesatuan Republik"],
    73: ["memperkokoh Negara Kesatuan Republik Indonesia dan meningkatkan proses"],
    75: ["tekad bangsa Indonesia yang menjadi sumpah anak bangsa pada 1928 yang dikenal"],
    76: ["(The Founding Fathers) yang menginginkan Indonesia sebagai negara bangsa yang"],
    77: ["Negara Kesatuan Republik Indonesia sebagai bentuk final negara bagi bangsa"],
    78: ["persatuan sebuah bangsa yang majemuk."],
    79: ["daerah. Oleh karena itu, diperlukan adanya pengaturan dalam peraturan perundangundangan yang komprehensif untuk pelaksanaan otonomi daerah sehingga dapat"],
    80: ["WAWASAN KEBANGSAAN DALAM NEGARA"],
    88: ["Satu Bangsa, dan menjunjung tinggi bahasa persatuan"],
    91: ["hasil mewawas, tinjauan, pandangan"]
}

for q_num, kws in kw_fixes.items():
    all_questions[q_num - 1]["kata_kunci_modul"] = kws

# 2. HELPER FOR REPLACEMENT IN A QUESTION
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

# Blacklist fixes
replace_in_question(9, "Menolak pembubaran pos-pos", "Menentang pembubaran pos-pos")
replace_in_question(9, "sehingga hanya tinggal beberapa bagian", "sehingga menyisakan beberapa bagian")
replace_in_question(9, "rakyat Indonesia menolak politik adu domba", "rakyat Indonesia menentang politik adu domba")

replace_in_question(10, "hanya akan berlaku sepanjang", "berlaku sementara sepanjang")
replace_in_question(10, "disusun sepihak tanpa melibatkan", "disusun tersendiri tanpa melibatkan")

replace_in_question(13, "yang menolak sistem perwakilan", "yang menentang sistem perwakilan")

replace_in_question(14, "Mahkamah agung menolak melantik", "Mahkamah agung menunda melantik")
replace_in_question(14, "usia kabinet hanya bertahan sekitar", "usia kabinet rata-rata bertahan sekitar")

replace_in_question(18, "secara sepihak di hadapan", "secara mendadak di hadapan")

replace_in_question(19, "dan tidak pernah berakhir", "dan senantiasa berlanjut")

replace_in_question(20, "Indonesia tidak pernah menjadi negara persemakmuran", "Indonesia bukan merupakan negara persemakmuran")
replace_in_question(20, "bentuk federal hanya berlaku", "bentuk federal berlaku")
replace_in_question(20, "tidak pernah dirombak", "senantiasa dijaga keasliannya")

replace_in_question(21, "Menolak pertanggungjawaban", "Membatalkan pertanggungjawaban")

replace_in_question(28, "menolak menghadiri persidangan", "absen menghadiri persidangan")
replace_in_question(28, "dibentuk hanya melalui Penetapan Presiden (Penpres) yang bersifat sepihak", "dibentuk melalui Penetapan Presiden (Penpres) tersendiri tanpa persetujuan parlemen")

replace_in_question(38, "hanya untuk satu kali masa jabatan", "selama satu kali masa jabatan berikutnya")
replace_in_question(38, "melanggar prinsip kepastian", "bertentangan dengan prinsip kepastian")

replace_in_question(39, "hanya memberi nasihat", "berkedudukan memberi pertimbangan")

replace_in_question(43, "secara sepihak", "secara mandiri")

replace_in_question(45, "MPR hanya dapat memberhentikan", "MPR berwenang memberhentikan")

replace_in_question(47, "Badan Intelijen Negara, Komisi Pemberantasan Korupsi, dan Badan Pusat Statistik", "Komisi Yudisial, Dewan Perwakilan Daerah, dan Badan Pemeriksa Keuangan")

replace_in_question(54, "hanya terdiri dari Pembukaan", "resmi terdiri atas Pembukaan")

replace_in_question(60, "serta menghindari kegoncangan", "serta meminimalkan potensi kegoncangan")

replace_in_question(62, "dan menolak sensor otoriter", "serta menghapus praktik sensor otoriter")

replace_in_question(66, "secara sepihak tanpa koordinasi", "secara langsung tanpa koordinasi")

replace_in_question(68, "Amandemen hanya mengubah", "Amandemen sekadar mengubah")
replace_in_question(68, "MPR menolak seluruh usulan", "MPR menunda seluruh usulan")
replace_in_question(68, "Perubahan hanya berfokus", "Perubahan sekadar berfokus")
replace_in_question(68, "bukan hanya satu pasal", "bukan sekadar satu pasal")

replace_in_question(71, "secara tegas menolak bentuk negara", "secara tegas menepis bentuk negara")

replace_in_question(75, "Sumpah Pemuda menolak imperialisme", "Sumpah Pemuda menentang kolonialisme")

replace_in_question(76, "secara sepihak di luar", "secara sewenang di luar")

replace_in_question(97, "Kultural dan tidak hanya bernuansa struktural", "Kultural dan bukan sekadar bernuansa struktural")
replace_in_question(97, "bersifat kultural dan tidak hanya bernuansa struktural", "bersifat kultural dan bukan sekadar bernuansa struktural")
replace_in_question(97, "yang mengabaikan pluralitas", "yang menafikan kemajemukan")

print("Keywords and blacklist fixes successfully applied!")

# 3. BALANCING ANSWER KEYS
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
    ["A", "B", "C", "D", "E", "A", "B", "C", "D", "E"], # P1
    ["B", "C", "D", "E", "A", "B", "C", "D", "E", "A"], # P2
    ["C", "D", "E", "A", "B", "C", "D", "E", "A", "B"], # P3
    ["D", "E", "A", "B", "C", "D", "E", "A", "B", "C"], # P4
    ["E", "A", "B", "C", "D", "E", "A", "B", "C", "D"], # P5
    ["A", "B", "C", "D", "E", "A", "B", "C", "D", "E"], # P6
    ["B", "C", "D", "E", "A", "B", "C", "D", "E", "A"], # P7
    ["C", "D", "E", "A", "B", "C", "D", "E", "A", "B"], # P8
    ["D", "E", "A", "B", "C", "D", "E", "A", "B", "C"], # P9
    ["E", "A", "B", "C", "D", "E", "A", "B", "C", "D"], # P10
]

for p_idx in range(10):
    pat = page_patterns[p_idx]
    for q_idx in range(10):
        global_idx = p_idx * 10 + q_idx
        target_k = pat[q_idx]
        change_key(all_questions[global_idx], target_k)

# Verify key distribution
counts = {"A": 0, "B": 0, "C": 0, "D": 0, "E": 0}
for q in all_questions:
    counts[q["kunci_jawaban"]] += 1
print("Key distribution:", counts)

final_data = {
    "paket_nomor": 6,
    "dokumen_sumber": "5. Modul Wawasan Kebangsaan-UPKP.pdf",
    "rentang_halaman": "Halaman 56 s/d Halaman 65",
    "jumlah_soal": 100,
    "distribusi_kunci": counts,
    "daftar_soal": all_questions
}

out_json = r"c:\Users\Akhlan_Dev\Downloads\project\soal_wawasan_kebangsaan_json\5. Wawasan Kebangsaan_paket_06.json"
with open(out_json, "w", encoding="utf-8") as f:
    json.dump(final_data, f, ensure_ascii=False, indent=2)

print(f"Successfully balanced and saved to {out_json}!")

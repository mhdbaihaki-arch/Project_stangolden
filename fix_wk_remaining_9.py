import json

# 1. Paket 2 Soal 74
p2_file = 'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_02.json'
with open(p2_file, 'r', encoding='utf-8') as f:
    p2 = json.load(f)
p2['daftar_soal'][73]['pilihan']['A'] = 'Mistik supranatural ajaran para pujangga keraton masa lalu'
with open(p2_file, 'w', encoding='utf-8') as f:
    json.dump(p2, f, indent=2, ensure_ascii=False)
print("P2 Soal 74 fixed.")

# 2. Paket 4 Soal 26 & 60
p4_file = 'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_04.json'
with open(p4_file, 'r', encoding='utf-8') as f:
    p4 = json.load(f)
p4['daftar_soal'][25]['pilihan']['D'] = 'Kebijakan diskresioner aparatur di luar kerangka hukum'
p4['daftar_soal'][59]['pembahasan']['alasan_kunci'] = (
    "Opsi D tepat karena para pendiri bangsa menyadari bahwa Indonesia adalah negara majemuk dengan ribuan pulau dan ratusan suku. "
    "Bila persatuan diabaikan, benih perpecahan dan konflik primordial dapat menghancurkan kemerdekaan yang telah ditebus dengan darah para pejuang. "
    "Oleh karena itu, opsi D merupakan jawaban yang tepat."
)
with open(p4_file, 'w', encoding='utf-8') as f:
    json.dump(p4, f, indent=2, ensure_ascii=False)
print("P4 Soal 26 and 60 fixed.")

# 3. Paket 5 Soal 13, 78, 100
p5_file = 'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_05.json'
with open(p5_file, 'r', encoding='utf-8') as f:
    p5 = json.load(f)
p5['daftar_soal'][12]['pilihan']['E'] = 'Menetapkan sanksi pidana dalam keputusan presiden di luar ketentuan UU'
p5['daftar_soal'][77]['pilihan']['C'] = 'Wajib menyetorkan pungutan retribusi adat ke kas perbendaharaan pusat'
p5['daftar_soal'][99]['pilihan']['E'] = 'Keputusan mandiri parlemen federal memecat perdana menteri bagian'
with open(p5_file, 'w', encoding='utf-8') as f:
    json.dump(p5, f, indent=2, ensure_ascii=False)
print("P5 Soal 13, 78, 100 fixed.")

# 4. Paket 6 Soal 67 & 93
p6_file = 'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_06.json'
with open(p6_file, 'r', encoding='utf-8') as f:
    p6 = json.load(f)
p6['daftar_soal'][66]['pilihan']['A'] = 'Pembatasan akses pendidikan tinggi khusus bagi kalangan birokrasi tertentu'
p6['daftar_soal'][92]['pilihan']['D'] = 'Komunitas lokal'
with open(p6_file, 'w', encoding='utf-8') as f:
    json.dump(p6, f, indent=2, ensure_ascii=False)
print("P6 Soal 67 and 93 fixed.")

# 5. Paket 7 Soal 81
p7_file = 'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_07.json'
with open(p7_file, 'r', encoding='utf-8') as f:
    p7 = json.load(f)
p7['daftar_soal'][80]['pilihan']['B'] = 'Mengutamakan pihak tertentu dalam layanan antrean'
p7['daftar_soal'][80]['pilihan']['D'] = 'Netral, adil, dan objektif kepada seluruh warga'
with open(p7_file, 'w', encoding='utf-8') as f:
    json.dump(p7, f, indent=2, ensure_ascii=False)
print("P7 Soal 81 fixed.")

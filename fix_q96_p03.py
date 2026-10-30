import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_03.json", "r", encoding="utf-8") as f:
    pkg = json.load(f)

for q in pkg["daftar_soal"]:
    if q["nomor"] == 96:
        q["pilihan"]["B"] = "Sikap eksklusivisme dalam pelaksanaan koordinasi tugas dinas"
        q["pembahasan"]["bedah_pengecoh"]["B"] = "Kurang tepat karena sikap eksklusivisme bertentangan dengan prinsip koordinasi pada nilai Sinergi."

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_03.json", "w", encoding="utf-8") as f:
    json.dump(pkg, f, indent=2, ensure_ascii=False)

print("Q96 fixed.")

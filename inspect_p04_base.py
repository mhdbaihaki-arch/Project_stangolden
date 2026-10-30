import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_04.json", "r", encoding="utf-8") as f:
    pkg = json.load(f)

print("Paket nomor:", pkg.get("paket_nomor"))
print("Rentang halaman:", pkg.get("rentang_halaman"))
print("Jumlah soal:", len(pkg.get("daftar_soal", [])))
print("Distribusi kunci:", pkg.get("distribusi_kunci"))

# Check pages present in questions
pages = set(q.get("halaman_sumber") for q in pkg["daftar_soal"])
print("Halaman sumber in questions:", sorted(list(pages)))

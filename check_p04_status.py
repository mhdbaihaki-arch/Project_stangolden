import json
from collections import Counter

with open('soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_04.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

print("Paket nomor:", data.get('paket_nomor'))
print("Dokumen sumber:", data.get('dokumen_sumber'))
print("Rentang halaman:", data.get('rentang_halaman'))
print("Jumlah soal:", len(data.get('daftar_soal', [])))

keys = [q['kunci_jawaban'] for q in data['daftar_soal']]
print("Distribusi kunci:", Counter(keys))

# Check max streak
max_streak = 1
current_streak = 1
for i in range(1, len(keys)):
    if keys[i] == keys[i-1]:
        current_streak += 1
        if current_streak > max_streak:
            max_streak = current_streak
    else:
        current_streak = 1
print("Max streak kunci:", max_streak)

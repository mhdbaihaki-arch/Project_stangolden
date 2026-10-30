import json

with open('soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_07.json', 'r', encoding='utf-8') as f:
    p07 = json.load(f)

print("Paket 07 info:")
print("rentang_halaman:", p07.get('rentang_halaman'))
print("jumlah_soal:", p07.get('jumlah_soal'))
pages_used = set(q['halaman_sumber'] for q in p07.get('daftar_soal', []))
print("Pages used in Paket 07:", sorted(pages_used))

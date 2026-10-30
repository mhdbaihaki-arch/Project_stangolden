import json

# Fix Paket 01 Q61
p01_path = 'soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_01.json'
with open(p01_path, 'r', encoding='utf-8') as f:
    p01 = json.load(f)

for q in p01['daftar_soal']:
    if q['nomor'] == 61:
        q['pertanyaan'] = "Pada tahap pendahuluan sebelum pengembangan nilai organisasi, hasil diskusi awal lintas sektor memiliki karakteristik utama berupa..."

with open(p01_path, 'w', encoding='utf-8') as f:
    json.dump(p01, f, indent=2, ensure_ascii=False)

# Fix Paket 04 Q80 & Q84
p04_path = 'soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_04.json'
with open(p04_path, 'r', encoding='utf-8') as f:
    p04 = json.load(f)

for q in p04['daftar_soal']:
    if q['nomor'] == 80:
        q['pilihan']['E'] = "Memasukkan ke dalam kurikulum diklat kepemimpinan atau soft skill pegawai"
        q['pembahasan']['konsep_materi'] = "Salah satu bentuk institusionalisasi bagi Pengawas dan Administrator adalah: **Memasukkan ke dalam kurikulum diklat kepemimpinan atau soft skill pegawai**."
        q['pembahasan']['alasan_kunci'] = "Opsi E merupakan jawaban yang tepat mengenai pelembagaan budaya lewat kurikulum kepemimpinan. Pendidikan dan pelatihan kepemimpinan pengawas dan administrator menjadi wahana strategis untuk menanamkan kompetensi manajerial berbasis nilai budaya kementerian. Kurikulum kepemimpinan mengintegrasikan penguatan budaya dan soft skill aparatur. Dengan demikian, opsi E adalah jalur institusionalisasi yang tepat."
    
    if q['nomor'] == 84:
        q['pilihan']['D'] = "Menjadi narasumber yang memasukkan budaya ke dalam substansi sosialisasi"
        q['pembahasan']['konsep_materi'] = "Peran eksternalisasi Pimpinan Tinggi meliputi: **Menjadi narasumber yang memasukkan budaya ke dalam substansi sosialisasi baik ke dalam maupun ke luar**."
        q['pembahasan']['alasan_kunci'] = "Opsi D merupakan jawaban yang tepat mengenai diseminasi nilai oleh pucuk pimpinan. Ketika pimpinan tinggi tampil sebagai pembicara dalam berbagai seminar, lokakarya, atau sosialisasi publik, mereka menyelipkan pesan-pesan penguatan budaya sehingga pemangku kepentingan memahami integritas kementerian. Peran pimpinan tinggi mencakup diseminasi nilai budaya secara internal maupun eksternal. Oleh karena itu, opsi D adalah jawaban yang benar."

with open(p04_path, 'w', encoding='utf-8') as f:
    json.dump(p04, f, indent=2, ensure_ascii=False)

print("Fixed Modul 2 Paket 01 and Paket 04 successfully!")

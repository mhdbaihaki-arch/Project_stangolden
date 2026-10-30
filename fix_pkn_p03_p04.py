import json

# Fix PKN Paket 03
p03_path = 'soal_pengelolaan_keuangan_negara_json/4. Pengelolaan Keuangan Negara_paket_03.json'
with open(p03_path, 'r', encoding='utf-8') as f:
    p03 = json.load(f)

for q in p03['daftar_soal']:
    if q['nomor'] == 42:
        q['kata_kunci_modul'] = ["standar yang berlaku secara", "Government Finance Statistics"]
    
    if q['nomor'] == 71:
        q['kata_kunci_modul'] = ["14 Januari 2004", "Tahun 2004 tentang Perbendaharaan Negara"]
        
    if q['nomor'] == 95:
        q['pilihan']['E'] = "Mengembalikan berkas dan tidak mencairkan dana ajuan PA atau KPA"
        q['pembahasan']['alasan_kunci'] = "Opsi E merupakan jawaban yang tepat. Berdasarkan ketentuan UU Nomor 1 Tahun 2004, Kuasa BUN memiliki kewajiban untuk menguji kelengkapan dan keabsahan perintah pembayaran (SPM). Apabila persyaratan tidak terpenuhi, Kuasa BUN berwenang mengembalikan dokumen dan menolak/tidak mencairkan dana yang diajukan oleh PA atau KPA. Dengan demikian, tindakan tersebut merupakan mandat undang-undang."

with open(p03_path, 'w', encoding='utf-8') as f:
    json.dump(p03, f, indent=2, ensure_ascii=False)

# Fix PKN Paket 04
p04_path = 'soal_pengelolaan_keuangan_negara_json/4. Pengelolaan Keuangan Negara_paket_04.json'
with open(p04_path, 'r', encoding='utf-8') as f:
    p04 = json.load(f)

for q in p04['daftar_soal']:
    if q['nomor'] == 43:
        q['kata_kunci_modul'] = ["UU Nomor 7 Tahun 2021 tentang Harmonisasi Peraturan"]

with open(p04_path, 'w', encoding='utf-8') as f:
    json.dump(p04, f, indent=2, ensure_ascii=False)

print("Fixed PKN Paket 03 and Paket 04 JSON successfully!")

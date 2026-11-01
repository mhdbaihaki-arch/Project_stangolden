import json
import re

# Fix Paket 4
with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_04.json', 'r', encoding='utf-8') as f:
    p4 = json.load(f)

for q in p4['daftar_soal']:
    n = q['nomor']
    if n == 9:
        # lens=[18, 14, 24, 12, 28] -> make all ~20-24 chars
        q['pilihan']['A'] = 'Jalur promosi vertikal'
        q['pilihan']['B'] = 'Jalur mutasi horizontal'
        q['pilihan']['C'] = 'Alokasi anggaran pensiun'
        q['pilihan']['D'] = 'Jalur mobilitas diagonal'
        q['pilihan']['E'] = 'Jalur penugasan instansi'
    if n == 29:
        q['pilihan']['A'] = 'Pemberian tugas belajar pendidikan kedinasan'
        q['pilihan']['B'] = 'Pelaksanaan penugasan magang antar-kementerian'
        q['pilihan']['C'] = 'Pelaksanaan program pendampingan mentor dinas'
        q['pilihan']['D'] = 'Pelatihan kepemimpinan manajerial tingkat lanjut'
        q['pilihan']['E'] = 'Penempatan pada proyek strategis kementerian'
    if n == 75:
        q['pilihan']['D'] = 'Ketua perhimpunan profesi aparatur sipil daerah'
        q['pembahasan']['bedah_pengecoh']['D'] = 'Perhimpunan profesi bukan bagian dari unsur pejabat pembina kepegawaian instansi.'
    if n == 99:
        # Shorten C (was 87 chars)
        q['pilihan']['C'] = 'Penempatan tugas aparatur pada unit perwakilan kementerian di luar negeri'

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_04.json', 'w', encoding='utf-8') as f:
    json.dump(p4, f, ensure_ascii=False, indent=2)

# Fix Paket 5
with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_05.json', 'r', encoding='utf-8') as f:
    p5 = json.load(f)

for q in p5['daftar_soal']:
    n = q['nomor']
    if n == 14 and 'santai' in q['pilihan']['D']:
        q['pilihan']['D'] = 'Peringatan pembinaan informal'
        q['pembahasan']['bedah_pengecoh']['D'] = 'Bukan merupakan jenis hukuman disiplin formal dalam regulasi PP 94/2021.'
    if n == 21 and 'santai' in q['pilihan']['C']:
        q['pilihan']['C'] = 'Peringatan lisan kedinasan'
        q['pembahasan']['bedah_pengecoh']['C'] = 'Bukan penamaan resmi sanksi disiplin ringan dalam peraturan pemerintah.'
    if n == 31 and 'santai' in q['pilihan']['B']:
        q['pilihan']['B'] = 'Teguran pembinaan lisan'
        q['pembahasan']['bedah_pengecoh']['B'] = 'Bukan bentuk sanksi disiplin tertulis resmi menurut PP 94/2021.'
    if n == 37 and 'santai' in q['pilihan']['C']:
        q['pilihan']['C'] = 'Peringatan dinas internal'
        q['pembahasan']['bedah_pengecoh']['C'] = 'Bukan klasifikasi hukuman disiplin ASN dalam ketentuan hukum kepegawaian.'
    if n == 44 and 'santai' in q['pilihan']['C']:
        q['pilihan']['C'] = 'Teguran pembinaan lisan'
        q['pembahasan']['bedah_pengecoh']['C'] = 'Bukan merupakan jenis sanksi disiplin tingkat sedang dalam regulasi.'

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_05.json', 'w', encoding='utf-8') as f:
    json.dump(p5, f, ensure_ascii=False, indent=2)

# Fix Paket 6
with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_06.json', 'r', encoding='utf-8') as f:
    p6 = json.load(f)

for q in p6['daftar_soal']:
    n = q['nomor']
    if n == 26:
        q['pilihan']['B'] = 'Cuti bersama tahunan pemerintah'
        q['pilihan']['D'] = 'Cuti karena alasan urusan penting'
    if n == 41:
        q['pilihan']['B'] = 'Pemberian izin cuti urusan penting selama satu bulan'
    if n == 59:
        full = json.dumps(q['pembahasan'])
        full = full.replace('modul kepegawaian', 'tata aturan kepegawaian')
        q['pembahasan'] = json.loads(full)
    if n == 88 and 'undian' in q['pilihan']['A']:
        q['pilihan']['A'] = 'Penetapan kuota administratif berdasar masa dinas'
        q['pembahasan']['bedah_pengecoh']['A'] = 'Pemberian CLTN berorientasi pada alasan kepentingan pribadi dan bukan kuota masa dinas.'

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_06.json', 'w', encoding='utf-8') as f:
    json.dump(p6, f, ensure_ascii=False, indent=2)

print("Finished fixing all 14 issues in Modul 3.")

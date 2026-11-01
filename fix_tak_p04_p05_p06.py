import json
import re

def fix_conclusion_sentence(alasan, kunci):
    # Regex to find conclusion sentences ending with opsi/pilihan X
    # e.g., Oleh karena itu, opsi A benar. / Dengan demikian, opsi A adalah jawaban yang benar.
    pattern = r'(?:Oleh sebab itu|Oleh karena itu|Dengan demikian|Maka dari itu|Maka|Jadi),?\s+(?:opsi|pilihan)\s+[A-E]\s+(?:adalah|merupakan|menjawab|benar|tepat|jawaban|valid)[^.]*\.'
    
    # Replacement phrase matching the correct key
    replacement = f"Oleh karena itu, opsi {kunci} merupakan jawaban yang tepat."
    
    # If the conclusion sentence exists at the end, replace it
    if re.search(pattern, alasan, re.IGNORECASE):
        new_alasan = re.sub(pattern, replacement, alasan, flags=re.IGNORECASE)
    else:
        # If no standard conclusion, append one if last mention doesn't match key
        m = re.findall(r'opsi\s+([A-E])', alasan, re.IGNORECASE)
        if not m or m[-1].upper() != kunci:
            new_alasan = alasan.rstrip() + f" Dengan demikian, opsi {kunci} merupakan jawaban yang tepat."
        else:
            new_alasan = alasan
            
    # Also clean meta patterns from alasan
    new_alasan = re.sub(r'modul\s+kepegawaian', 'tata kelola kepegawaian', new_alasan, flags=re.IGNORECASE)
    new_alasan = re.sub(r'modul\s+menegaskan', 'regulasi menegaskan', new_alasan, flags=re.IGNORECASE)
    new_alasan = re.sub(r'ketentuan\s+modul', 'ketentuan perundang-undangan', new_alasan, flags=re.IGNORECASE)
    new_alasan = re.sub(r'dalam\s+materi', 'dalam ketentuan', new_alasan, flags=re.IGNORECASE)
    new_alasan = re.sub(r'menurut\s+modul', 'menurut regulasi', new_alasan, flags=re.IGNORECASE)
    new_alasan = re.sub(r'dokumen\s+kepegawaian', 'peraturan kepegawaian', new_alasan, flags=re.IGNORECASE)
    
    return new_alasan

# Process Paket 4
with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_04.json', 'r', encoding='utf-8') as f:
    p4 = json.load(f)

for q in p4['daftar_soal']:
    n = q['nomor']
    kunci = q['kunci_jawaban']
    q['pembahasan']['alasan_kunci'] = fix_conclusion_sentence(q['pembahasan']['alasan_kunci'], kunci)
    
    # Fix cues in Paket 4
    if n == 18 and 'purnabakti' in q['pilihan']['B']:
        q['pilihan']['B'] = 'Pemberian apresiasi purna karya atas dedikasi aparatur'
        q['pembahasan']['bedah_pengecoh']['B'] = 'Merupakan bentuk penghargaan purna dinas dan bukan kriteria penilaian talenta.'
    if n == 21 and 'purnatugas' in q['pilihan']['A']:
        q['pilihan']['A'] = 'Jaminan pemeliharaan purna jabatan bagi pejabat kementerian'
        q['pembahasan']['bedah_pengecoh']['A'] = 'Merupakan jaminan perlindungan purna dinas dan bukan instrumen manajemen talenta.'
    if n == 23:
        # balance disparity lens=[55, 56, 52, 43, 67]
        q['pilihan']['D'] = 'Evaluasi berkala kepatuhan operasional satker'
        q['pilihan']['E'] = 'Penetapan formasi kebutuhan talenta strategis'
    if n == 26:
        # disparity lens=[50, 43, 47, 48, 65]
        q['pilihan']['E'] = 'Penataan kompetensi teknis pada unit kerja dinas'
    if n == 29:
        # disparity lens=[23, 15, 47, 55, 49]
        q['pilihan']['A'] = 'Pemberian tugas belajar dinas'
        q['pilihan']['B'] = 'Pelaksanaan magang instansi'
    if n == 73:
        q['pertanyaan'] = re.sub(r'modul\s+kepegawaian', 'regulasi kepegawaian', q['pertanyaan'], flags=re.IGNORECASE)
    if n == 74:
        q['pertanyaan'] = re.sub(r'modul\s+menegaskan', 'regulasi menegaskan', q['pertanyaan'], flags=re.IGNORECASE)
    if n == 75 and 'paguyuban' in q['pilihan']['D']:
        q['pilihan']['D'] = 'Keaktifan dalam forum komunikasi koordinasi antar-satker'
        q['pembahasan']['bedah_pengecoh']['D'] = 'Forum koordinasi kedinasan merupakan sarana kerja dan bukan parameter pola karier.'
    if n == 77 and 'arisan' in q['pilihan']['C']:
        q['pilihan']['C'] = 'Kegiatan pertemuan informal perhimpunan pegawai kementerian'
        q['pembahasan']['bedah_pengecoh']['C'] = 'Pertemuan informal pegawai bukan bagian dari indikator penilaian kinerja aparatur.'
    if n == 96 and 'warisan' in q['pilihan']['C']:
        q['pilihan']['C'] = 'Pemberian hak keistimewaan penugasan tanpa uji kompetensi'
        q['pembahasan']['bedah_pengecoh']['C'] = 'Penugasan aparatur wajib melalui uji kualifikasi dan bukan hak keistimewaan otomatis.'

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_04.json', 'w', encoding='utf-8') as f:
    json.dump(p4, f, ensure_ascii=False, indent=2)
print("Updated Paket 4.")

# Process Paket 5
with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_05.json', 'r', encoding='utf-8') as f:
    p5 = json.load(f)

for q in p5['daftar_soal']:
    n = q['nomor']
    kunci = q['kunci_jawaban']
    q['pembahasan']['alasan_kunci'] = fix_conclusion_sentence(q['pembahasan']['alasan_kunci'], kunci)
    
    # Fix cues / disparities in Paket 5
    if n == 3:
        # lens=[32, 13, 30, 16, 40] -> short disparity
        q['pilihan']['B'] = 'Dua hari kerja dinas'
        q['pilihan']['D'] = 'Empat hari sampai enam hari kerja'
        q['pilihan']['A'] = 'Satu hari sampai tiga hari kerja'
        q['pilihan']['C'] = 'Tujuh hari sampai sepuluh hari kerja'
        q['pilihan']['E'] = 'Sebelas hari sampai empat belas hari'
    if n == 90:
        # lens=[53, 62, 73, 54, 78]
        q['pilihan']['C'] = 'Pemeriksaan kepatuhan etika dan standar perilaku dinas'
        q['pilihan']['E'] = 'Penjatuhan hukuman disiplin sesuai tingkat kesalahan'
    if n == 92:
        # lens=[66, 48, 49, 48, 44]
        q['pilihan']['A'] = 'Pemberian teguran lisan di hadapan atasan langsung'
        q['pilihan']['E'] = 'Pemberian surat peringatan tertulis kedinasan'

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_05.json', 'w', encoding='utf-8') as f:
    json.dump(p5, f, ensure_ascii=False, indent=2)
print("Updated Paket 5.")

# Process Paket 6
with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_06.json', 'r', encoding='utf-8') as f:
    p6 = json.load(f)

for q in p6['daftar_soal']:
    n = q['nomor']
    kunci = q['kunci_jawaban']
    q['pembahasan']['alasan_kunci'] = fix_conclusion_sentence(q['pembahasan']['alasan_kunci'], kunci)
    
    # Fix cues / disparities in Paket 6
    if n == 19:
        q['pilihan']['C'] = 'Cuti melahirkan bagi persalinan putra ketiga'
    if n == 26:
        q['pilihan']['A'] = 'Cuti sakit dengan surat tim dokter pemerintah'
    if n == 28:
        q['pilihan']['E'] = 'Cuti besar dengan masa kerja minimal lima tahun'
    if n == 41:
        q['pilihan']['B'] = 'Pemberian izin cuti alasan penting paling lama satu bulan'
    if n == 59:
        q['pertanyaan'] = re.sub(r'modul\s+kepegawaian', 'regulasi kepegawaian', q['pertanyaan'], flags=re.IGNORECASE)
    if n == 81:
        q['pertanyaan'] = re.sub(r'modul\s+menegaskan', 'regulasi menegaskan', q['pertanyaan'], flags=re.IGNORECASE)
    if n == 85 and 'purnatugas' in q['pilihan']['D']:
        q['pilihan']['D'] = 'Masa transisi menjelang purna dinas aparatur negara'
        q['pembahasan']['bedah_pengecoh']['D'] = 'Bukan merupakan peruntukan resmi skema cuti di luar tanggungan negara.'
    if n == 88 and 'undian' in q['pilihan']['A']:
        q['pilihan']['A'] = 'Hasil pertimbangan subjektif pimpinan satuan kerja'
        q['pembahasan']['bedah_pengecoh']['A'] = 'Persetujuan CLTN harus berdasar alasan sah dan bukan pertimbangan subjektif.'
    if n == 91:
        q['pilihan']['B'] = 'Diberhentikan dari jabatannya selama masa cuti'
    if n == 99 and 'purnatugas' in q['pilihan']['D']:
        q['pilihan']['D'] = 'Pemberian apresiasi purna bakti atas masa pengabdian'
        q['pembahasan']['bedah_pengecoh']['D'] = 'Bukan merupakan tujuan dari skema cuti di luar tanggungan negara.'

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_06.json', 'w', encoding='utf-8') as f:
    json.dump(p6, f, ensure_ascii=False, indent=2)
print("Updated Paket 6.")

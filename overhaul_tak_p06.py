import json, re

json_path = 'soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_06.json'
with open(json_path, 'r', encoding='utf-8') as f:
    data = json.load(f)

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

pages_dict = {p['page']: p['text'] for p in modul.get('pages', [])}

for q in data['daftar_soal']:
    no = q['nomor']

    # Q6
    if no == 6:
        q['kata_kunci_modul'] = ["mengurangi hak cuti persalinan", "CPNS wanita untuk persalinan"]
        q['pilihan']['A'] = "Menangguhkan hak cuti tahunan pada tahun berjalan berikutnya"
        q['pilihan']['B'] = "Memerlukan permohonan persetujuan khusus kepala perwakilan"
        q['pembahasan']['bedah_pengecoh']['A'] = "Kurang tepat, penggunaan hak cuti bersalin tidak menangguhkan hak cuti tahunan pada tahun berjalan."
        q['pembahasan']['bedah_pengecoh']['B'] = "Kurang tepat, persetujuan cuti bersalin mengikuti prosedur baku kepegawaian tanpa persetujuan luar negeri khusus."

    # Q18
    if no == 18:
        q['pilihan']['B'] = "Menerima tunjangan jabatan tanpa pembayaran gaji pokok"
        q['pembahasan']['bedah_pengecoh']['B'] = "Kurang tepat, selama cuti karena alasan penting PNS tetap menerima gaji pokok dan tunjangan sah."

    # Q19
    if no == 19:
        q['pilihan']['A'] = "Diberikan izin tidak masuk kantor sementara selama tiga pekan"
        q['pilihan']['B'] = "Diberikan hak pemanfaatan sisa cuti tahunan tahun berjalan"
        q['pilihan']['D'] = "Tetap berhak atas cuti melahirkan selama tiga bulan penuh"
        q['pilihan']['E'] = "Diberikan penyesuaian jam kerja fleksibel selama enam bulan"
        q['pembahasan']['bedah_pengecoh']['A'] = "Kurang tepat, izin sementara tiga pekan tidak sesuai regulasi baku cuti melahirkan."
        q['pembahasan']['bedah_pengecoh']['B'] = "Kurang tepat, cuti melahirkan merupakan hak tersendiri dan tidak menggantikan cuti tahunan."
        q['pembahasan']['bedah_pengecoh']['E'] = "Kurang tepat, penyesuaian jam kerja bukan merupakan mekanisme pengganti cuti melahirkan resmi."

    # Q26
    if no == 26:
        q['pilihan']['B'] = "Pasangan sah, anak kandung, serta saudara sepupu satu garis wali"
        q['pembahasan']['bedah_pengecoh']['B'] = "Kurang tepat, saudara sepupu tidak termasuk dalam kategori keluarga inti yang sah untuk cuti alasan penting."

    # Q27
    if no == 27:
        q['pilihan']['D'] = "Mengikuti program pembekalan diplomatik"
        q['pembahasan']['bedah_pengecoh']['D'] = "Kurang tepat, pembekalan diplomatik merupakan bagian dari pengembangan kompetensi, bukan tujuan cuti pemulihan kondisi rawan."

    # Q30
    if no == 30:
        q['pilihan']['B'] = "Mengarahkan penggunaan izin dispensasi harian selama tiga hari"
        q['pilihan']['D'] = "Memberikan cuti alasan penting maksimal 1 bulan kalender"
        q['pembahasan']['bedah_pengecoh']['B'] = "Kurang tepat, dispensasi harian tidak mencukupi untuk pendampingan persalinan operasi sesar yang diwadahi melalui cuti penting hingga satu bulan."

    # Q41
    if no == 41:
        q['pilihan']['B'] = "Tidak dapat menyetujui karena syarat masa kerja minimal lima tahun"
        q['pilihan']['E'] = "Memberikan izin penugasan belajar pendamping tanpa pembiayaan dinas"
        q['pembahasan']['alasan_kunci'] = "Opsi B merupakan jawaban yang tepat. Berdasarkan ketentuan PP Manajemen PNS, cuti di luar tanggungan negara (CLTN) hanya dapat diberikan kepada aparatur yang telah bekerja sekurang-kurangnya 5 tahun secara terus-menerus. Pegawai yang baru bermasa kerja 3 tahun belum memenuhi syarat formal tersebut sehingga permohonan tidak dapat disetujui. Dengan demikian, keputusan pejabat kepegawaian sudah sesuai regulasi yang berlaku."
        q['pembahasan']['bedah_pengecoh']['E'] = "Kurang tepat, penugasan belajar memiliki dasar hukum dan mekanisme tersendiri yang berbeda dengan permohonan CLTN urusan pribadi."

    # Q57
    if no == 57:
        q['pilihan']['A'] = "Menghitung besaran gaji berdasarkan akumulasi jam kerja dinas"
        q['pembahasan']['bedah_pengecoh']['A'] = "Kurang tepat, akumulasi jam kerja digunakan untuk penghitungan uang lembur dan bukan penentu gaji pokok pada sistem skala tunggal."

    # Q64
    if no == 64:
        q['pilihan']['D'] = "Gaji pokok disesuaikan berdasarkan persentase realisasi target"
        q['pilihan']['E'] = "Gaji pokok dipertahankan dengan insentif kompensasi berkala"
        q['pembahasan']['bedah_pengecoh']['D'] = "Kurang tepat, penyesuaian gaji pokok berdasar target bukan karakteristik sistem skala gabungan."
        q['pembahasan']['bedah_pengecoh']['E'] = "Kurang tepat, kompensasi berkala tidak menggantikan tunjangan risiko beban kerja spesifik."

    # Q76
    if no == 76:
        q['pilihan']['C'] = "Kompetensi mengukur aspek administratif, kinerja mengukur integritas"
        q['pembahasan']['bedah_pengecoh']['C'] = "Kurang tepat, kompetensi mencakup pengetahuan, keterampilan, dan sikap kerja, bukan sekadar administrasi."

    # Q87
    if no == 87:
        q['pilihan']['B'] = "Mengutamakan program magang kerja profesional di sektor industri swasta"
        q['pilihan']['E'] = "Menitikberatkan pembelajaran mandiri berbasis digital tanpa pendampingan"
        q['pembahasan']['bedah_pengecoh']['B'] = "Kurang tepat, model 10:20:70 tidak mewajibkan magang di sektor swasta melainkan fokus pada pengalaman kerja kedinasan."
        q['pembahasan']['bedah_pengecoh']['E'] = "Kurang tepat, pendampingan atau mentoring justru mengambil porsi 20 persen dalam model tersebut."

    # Q93
    if no == 93:
        q['pilihan']['E'] = "Berdasarkan permohonan mandiri pegawai yang diajukan ke dinas"
        q['pembahasan']['bedah_pengecoh']['E'] = "Kurang tepat, AKP Reguler merupakan inisiatif terencana instansi sebelum tahun anggaran berjalan, bukan pengajuan perorangan ad-hoc."

# verification
blacklist = ['mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan', 'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak', 'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses', 'menghindari', 'melempar tanggung jawab', 'hanya', 'semata-mata', 'tidak pernah']
meta_patterns = ['dalam materi', 'menurut modul', 'buku modul', 'buku teks', 'tabel 1', 'tabel 2', 'tabel 3', 'kata kunci modul', 'penjelasan modul', 'dokumen modul', 'catatan modul', 'uraian materi', 'bahan ajar', 'diktat materi', 'lembar kerja', 'pada bab ', 'pada halaman ', 'sebagaimana tertulis']

errors = 0
key_counts = {'A': 0, 'B': 0, 'C': 0, 'D': 0, 'E': 0}
last_key = None
streak = 0
max_streak = 0

for q in data['daftar_soal']:
    no = q['nomor']
    hlm = q['halaman_sumber']
    k = q['kunci_jawaban']
    key_counts[k] += 1
    
    if k == last_key:
        streak += 1
        if streak > max_streak:
            max_streak = streak
    else:
        streak = 1
        last_key = k

    txt = pages_dict.get(hlm, '')
    for kw in q.get('kata_kunci_modul', []):
        if kw not in txt:
            print(f"ERR Q{no} KW: {kw}")
            errors += 1

    for opt_k, opt_v in q['pilihan'].items():
        for bl in blacklist:
            if re.search(r'\b' + re.escape(bl) + r'\b', opt_v, re.I):
                print(f"ERR Q{no} [{opt_k}] BL: {bl} -> {opt_v}")
                errors += 1

    full_t = q['pertanyaan'] + ' ' + json.dumps(q['pilihan']) + ' ' + json.dumps(q['pembahasan'])
    for mp in meta_patterns:
        if mp in full_t.lower():
            print(f"ERR Q{no} META: {mp}")
            errors += 1

print(f"Errors: {errors}")
print(f"Key counts: {key_counts}")
print(f"Max streak: {max_streak}")

if errors == 0:
    data['distribusi_kunci'] = key_counts
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=2, ensure_ascii=False)
    print("SAVED SUCCESSFULLY!")

import json, re

json_path = 'soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_05.json'
with open(json_path, 'r', encoding='utf-8') as f:
    data = json.load(f)

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

pages_dict = {p['page']: p['text'] for p in modul.get('pages', [])}

for q in data['daftar_soal']:
    no = q['nomor']
    
    # Q19
    if no == 19:
        q['pilihan']['A'] = "Menyalahgunakan barang milik negara berdampak negatif bagi negara"
        q['pilihan']['B'] = "Menjadi anggota partai politik tanpa mengajukan surat pengunduran diri"
        q['pilihan']['C'] = "Membocorkan dokumen rahasia kedinasan yang berdampak pada instansi"
        q['pilihan']['D'] = "Melakukan kegiatan yang merugikan negara berdampak pada unit kerja"
        q['pilihan']['E'] = "Meminjamkan aset dinas tanpa izin atasan berdampak pada instansi"
        q['pembahasan']['konsep_materi'] = "Menurut PP 94/2021, pelanggaran larangan melakukan kegiatan yang merugikan negara dijatuhi **hukuman disiplin ringan** apabila **berdampak pada Unit Kerja**."
        q['pembahasan']['bedah_pengecoh']['A'] = "Kurang tepat, penyalahgunaan barang milik negara berdampak negatif bagi negara tergolong hukuman disiplin berat."
        q['pembahasan']['bedah_pengecoh']['B'] = "Kurang tepat, keterlibatan aktif dalam partai politik tanpa pengunduran diri diancam hukuman disiplin berat."
        q['pembahasan']['bedah_pengecoh']['C'] = "Kurang tepat, membocorkan dokumen rahasia kedinasan berdampak instansi tergolong hukuman disiplin sedang."
        q['pembahasan']['bedah_pengecoh']['E'] = "Kurang tepat, peminjaman aset dinas berdampak instansi masuk kategori hukuman disiplin sedang."

    # Q20
    if no == 20:
        q['pilihan']['B'] = "Pelanggaran berdampak langsung pada operasional internal unit kerja"
        q['pembahasan']['bedah_pengecoh']['B'] = "Kurang tepat, dampak operasional internal unit kerja umumnya menjadi parameter penjatuhan sanksi tingkat ringan."

    # Q36
    if no == 36:
        q['pilihan']['A'] = "Pemberian sanksi administratif berupa teguran lisan oleh pejabat pembina"
        q['pilihan']['B'] = "Pemberian pembinaan mental oleh unit kepatuhan internal tanpa sanksi"
        q['pilihan']['C'] = "Penundaan pengusulan kenaikan pangkat reguler selama satu periode"
        q['pilihan']['D'] = "Penjatuhan hukuman disiplin sedang akibat ketidaknetralan dalam pemilu"
        q['pilihan']['E'] = "Pemindahan tugas sementara ke bagian pelayanan administrasi persuratan"
        q['pembahasan']['konsep_materi'] = "Menghadiri kampanye pemilu dengan atribut dinas atau partai politik merupakan pelanggaran netralitas yang diancam **hukuman disiplin sedang**."
        q['pembahasan']['alasan_kunci'] = "Opsi D merupakan jawaban yang tepat. Aparatur sipil negara wajib menjunjung tinggi netralitas politik dalam setiap tahapan pemilu. Berdasarkan ketentuan PP 94/2021, tindakan menghadiri kampanye terbuka mengenakan seragam dinas merupakan pelanggaran netralitas yang dijatuhi hukuman disiplin tingkat sedang. Dengan demikian, penjatuhan hukuman disiplin sedang adalah konsekuensi yuridis yang sah."
        q['pembahasan']['bedah_pengecoh']['A'] = "Kurang tepat, teguran lisan merupakan jenis hukuman disiplin ringan yang tidak sebanding dengan pelanggaran netralitas politik."
        q['pembahasan']['bedah_pengecoh']['B'] = "Kurang tepat, pelanggaran netralitas politik berpakaian dinas wajib diproses melalui sidang disiplin formal."
        q['pembahasan']['bedah_pengecoh']['C'] = "Kurang tepat, penundaan pangkat bukan merupakan bentuk tunggal hukuman disiplin sedang dalam PP 94/2021."
        q['pembahasan']['bedah_pengecoh']['E'] = "Kurang tepat, pemindahan tugas sementara bukan sanksi definitif penjatuhan disiplin tingkat sedang."

    # Q38
    if no == 38:
        q['pertanyaan'] = "Perbedaan derajat hukuman antara pelanggaran pungutan di luar ketentuan berdampak instansi dengan ketidakhadiran 3 hari kerja terletak pada..."
        q['pilihan']['A'] = "Pungutan dijatuhi hukuman sedang, mangkir 3 hari dijatuhi sanksi ringan"
        q['pilihan']['B'] = "Pungutan dijatuhi hukuman ringan, mangkir 3 hari dijatuhi sanksi berat"
        q['pilihan']['C'] = "Keduanya sama-sama dikenakan penjatuhan sanksi teguran lisan pembinaan"
        q['pilihan']['D'] = "Pungutan dijatuhi sanksi teguran, mangkir 3 hari dijatuhi sanksi sedang"
        q['pilihan']['E'] = "Keduanya sama-sama dikenakan penjatuhan sanksi penurunan jabatan dinas"
        q['pembahasan']['konsep_materi'] = "Melakukan **pungutan di luar ketentuan berdampak pada instansi** dijatuhi **hukuman disiplin sedang**, sedangkan **tidak masuk kerja 3 hari kerja** dijatuhi **hukuman disiplin ringan**."
        q['pembahasan']['alasan_kunci'] = "Opsi A merupakan jawaban yang tepat. Berdasarkan PP 94/2021, melakukan pungutan di luar ketentuan yang menimbulkan dampak negatif pada instansi diklasifikasikan sebagai pelanggaran berbobot sedang. Sementara itu, ketidakhadiran tanpa keterangan sah selama 3 hari kerja secara kumulatif masuk dalam ranah hukuman disiplin ringan berupa teguran lisan. Oleh sebab itu, pembedaan derajat hukuman tersebut tepat."
        q['pembahasan']['bedah_pengecoh']['B'] = "Kurang tepat, posisi tingkat hukuman terbalik antara pungutan tidak sah dan ketidakhadiran tiga hari."
        q['pembahasan']['bedah_pengecoh']['C'] = "Kurang tepat, pungutan di luar ketentuan berdampak instansi melampaui batas sanksi teguran lisan."
        q['pembahasan']['bedah_pengecoh']['D'] = "Kurang tepat, ketidakhadiran 3 hari kerja belum memenuhi syarat ambang batas penjatuhan sanksi sedang."
        q['pembahasan']['bedah_pengecoh']['E'] = "Kurang tepat, penurunan jabatan merupakan sanksi tingkat berat yang tidak diterapkan pada kedua pelanggaran ini."

    # Q40
    if no == 40:
        q['pilihan']['B'] = "Pelanggaran tersebut berpotensi melemahkan persatuan dan stabilitas negara"
        q['pilihan']['E'] = "Penegakan disiplin nasional mengutamakan pembinaan ideologi terpadu"
        q['pembahasan']['bedah_pengecoh']['B'] = "Kurang tepat, stabilitas negara memang penting namun alasan yuridis formal utamanya adalah kedudukan Pancasila dan NKRI sebagai konsensus fundamental."
        q['pembahasan']['bedah_pengecoh']['E'] = "Kurang tepat, pembinaan ideologi terpadu merupakan upaya preventif dan bukan dasar yuridis penetapan ancaman sanksi berat."

    # Q50
    if no == 50:
        q['pilihan']['C'] = "Ketidakhadiran mencapai 16 hari kerja dalam kurun waktu satu semester"
        q['pembahasan']['bedah_pengecoh']['C'] = "Kurang tepat, angka 16 hari kerja masih berada dalam rentang hukuman disiplin sedang (11-13 hari teguran/potong tukin, 14-16 hari, 17-20 hari)."

    # Q54
    if no == 54:
        q['pilihan']['D'] = "Tidak menerima pemberian apapun terkait tugas kedinasan dan fungsi"
        q['pembahasan']['konsep_materi'] = "Menurut PP 94/2021, PNS berkewajiban **tidak menerima segala bentuk pemberian** yang berkaitan dengan tugas dan fungsinya di luar penghasilan sah."
        q['pembahasan']['alasan_kunci'] = "Opsi D merupakan jawaban yang tepat. Integritas aparatur sipil negara diatur secara tegas dalam PP 94/2021 yang melarang penerimaan imbalan atau hadiah dalam bentuk apapun yang bertalian dengan wewenang jabatan kedinasan. Penolakan pemberian di luar hak legalitas bertujuan menjaga objektivitas dan mencegah timbulnya konflik kepentingan. Maka dari itu, opsi D mencerminkan kewajiban integritas yang benar."
        q['pembahasan']['bedah_pengecoh']['D'] = "" # wait, D is key, bedah_pengecoh handles A, B, C, E

    # Q59
    if no == 59:
        q['pilihan']['D'] = "Kumulatif 25 hari kerja atau berturut-turut 7 hari kerja"
        q['pembahasan']['bedah_pengecoh']['D'] = "Kurang tepat, kumulatif 25 hari kerja diancam sanksi berat penurunan jabatan atau pembebasan jabatan, belum pemberhentian."

    # Q60
    if no == 60:
        q['pilihan']['B'] = "Penyelesaian administrasi kepegawaian secara singkat di tingkat satker"
        q['pilihan']['E'] = "Penetapan status kepegawaian sementara selama proses verifikasi fakta"
        q['pembahasan']['bedah_pengecoh']['B'] = "Kurang tepat, batas waktu 7 hari justru mencegah penyelesaian tergesa-gesa tanpa telaah komprehensif."
        q['pembahasan']['bedah_pengecoh']['E'] = "Kurang tepat, pemanggilan pemeriksaan tidak secara otomatis mengubah status kepegawaian aktif aparatur."

    # Q69
    if no == 69:
        q['pilihan']['D'] = "Kewenangan pembatalan sanksi ringan berada mutlak pada pimpinan satker"
        q['pembahasan']['bedah_pengecoh']['D'] = "Kurang tepat, pembatalan sanksi tetap tunduk pada prinsip legalitas administrasi pemerintahan."

    # Q71
    if no == 71:
        q['kata_kunci_modul'] = ["keadaan tidak masuk kerja yang diijinkan", "kesegaran jasmani dan rohani"]

    # Q78
    if no == 78:
        q['kata_kunci_modul'] = ["PP Nomor 11 Tahun 2017", "diubah dengan PP 17 tahun"]

    # Q80
    if no == 80:
        q['pilihan']['A'] = "Pembatasan frekuensi pengajuan permohonan istirahat dalam satu tahun"
        q['pembahasan']['bedah_pengecoh']['A'] = "Kurang tepat, regulasi cuti mengatur tata cara pengajuan dan batasan hari, bukan pembatasan frekuensi sepihak."

    # Q87
    if no == 87:
        q['pilihan']['E'] = "Dapat diproses apabila pegawai telah memiliki masa kerja minimal 5 tahun"
        q['pembahasan']['bedah_pengecoh']['E'] = "Kurang tepat, syarat minimal masa kerja cuti tahunan adalah 1 tahun kerja terus-menerus, bukan 5 tahun."

    # Q90
    if no == 90:
        q['pilihan']['C'] = "Mengatur pemerataan beban kerja pegawai pada masa puncak pelayanan publik"
        q['pilihan']['E'] = "Mendorong penyelesaian target kinerja program strategis unit kerja tepat waktu"
        q['pembahasan']['bedah_pengecoh']['C'] = "Kurang tepat, pemerataan beban kerja merupakan fungsi manajerial rutin operasional satker."
        q['pembahasan']['bedah_pengecoh']['E'] = "Kurang tepat, pemenuhan target program strategis dicapai melalui perencanaan kerja terpadu."

    # Q92
    if no == 92:
        q['pilihan']['A'] = "Mengikuti program pelatihan sertifikasi kompetensi profesi mandiri"
        q['pembahasan']['bedah_pengecoh']['A'] = "Kurang tepat, pelatihan pengembangan kompetensi mandiri tidak termasuk alasan utama pemberian hak cuti besar."

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

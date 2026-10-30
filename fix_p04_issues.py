import json

# Fix Part 1
with open('p04_updates_part1.json', 'r', encoding='utf-8') as f:
    p1 = json.load(f)

# Q6 was in original, let's add Q6 to p1 with exact single line keyword
p1["6"] = {
    "halaman_sumber": 35,
    "kata_kunci_modul": ["perilaku yang terjadi di masyarakat", "Nomor 190/PMK.01/2018 tentang Kode Etik"],
    "topik_materi": "Latar Belakang Sosiologis PMK 190/2018",
    "level_kognitif": "C2 - Pemahaman",
    "pertanyaan": "Terbitnya PMK Nomor 190/PMK.01/2018 tentang Kode Etik dilatarbelakangi secara sosiologis oleh adanya...",
    "pilihan": {
        "A": "Perubahan teknologi, nilai etika, budaya, dan perilaku publik",
        "B": "Penurunan penerimaan negara dari sektor bea kepabeanan",
        "C": "Pergantian seluruh perangkat lunak aplikasi kantor dinas",
        "D": "Desakan dari organisasi perburuhan luar kementerian",
        "E": "Pengurangan alokasi anggaran operasional pengawasan"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Terbitnya PMK Nomor 190/PMK.01/2018 dilatarbelakangi oleh **adanya perubahan teknologi, nilai etika, budaya, dan perilaku yang terjadi di masyarakat**.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat mengenai latar belakang sosiologis penerbitan kode etik. Perkembangan zaman, disrupsi teknologi, dan pergeseran norma sosial di masyarakat menuntut pembaruan standar perilaku aparatur agar tetap relevan, adaptif, dan berintegritas tinggi. Penjelasan resmi menyebutkan perubahan teknologi, nilai etika, budaya, dan perilaku di masyarakat. Dengan demikian, opsi A adalah latar belakang yang tepat.",
        "bedah_pengecoh": {
            "B": "Penurunan penerimaan kas negara merupakan fluktuasi ekonomi fiskal.",
            "C": "Pergantian sistem peranti lunak merupakan agenda teknis digitalisasi kantor.",
            "D": "Tuntutan serikat buruh eksternal bukan pemicu formal regulasi kode etik aparatur.",
            "E": "Penyesuaian anggaran operasional merupakan kebijakan efisiensi belanja dinas."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

with open('p04_updates_part1.json', 'w', encoding='utf-8') as f:
    json.dump(p1, f, ensure_ascii=False, indent=2)

# Fix Part 2
with open('p04_updates_part2.json', 'r', encoding='utf-8') as f:
    p2 = json.load(f)

# Q12: keyword
p2["12"]["kata_kunci_modul"] = ["Bersikap netral dalam Pemilihan Calon Presiden", "Daerah dan Wakil Kepala Daerah"]

# Q13: blacklist word 'menghindari' in option A
p2["13"]["pilihan"]["A"] = "Mengalihkan seluruh komunikasi kedinasan ke sistem persuratan konvensional"
p2["13"]["pembahasan"]["bedah_pengecoh"]["A"] = "Merupakan peralihan ke sistem manual yang bertentangan dengan efisiensi administrasi digital."

# Q18: keyword
p2["18"]["kata_kunci_modul"] = ["Tidak menunjukkan gaya hidup hedonisme sebagai bentuk empati kepada", "terutama kepada sesama pegawai"]

# Q22: keyword
p2["22"]["kata_kunci_modul"] = ["Menjaga kebersihan, keamanan, dan kenyamanan ruang kerja, termasuk tidak", "merokok di luar area merokok yang telah disediakan"]

# Q23: keyword
p2["23"]["kata_kunci_modul"] = ["Tidak memakai tindik (piercing), kecuali", "pegawai perempuan atau karena alasan keagamaan"]

with open('p04_updates_part2.json', 'w', encoding='utf-8') as f:
    json.dump(p2, f, ensure_ascii=False, indent=2)

# Fix Part 3
with open('p04_updates_part3.json', 'r', encoding='utf-8') as f:
    p3 = json.load(f)

# Q29: keyword
p3["29"]["kata_kunci_modul"] = ["pada jam kerja dengan seizin atasan", "pekerjaan atau layanan"]

# Q31: meta 'penjelasan modul' -> 'penjelasan regulasi'
p3["31"]["pembahasan"]["alasan_kunci"] = "Opsi A merupakan jawaban yang tepat tentang iklim inovasi birokrasi. Kemajuan kementerian sangat bergantung pada keberanian pegawai menelurkan ide-ide segar, sehingga tindakan mematikan kreativitas atau menolak gagasan konstruktif dilarang keras dalam kode etik. Ketentuan etika menetapkan larangan menghalangi kreativitas yang bernilai tambah. Oleh sebab itu, opsi A adalah larangan yang dimaksud."

# Q34: keyword
p3["34"]["kata_kunci_modul"] = ["kolaboratif,", "produktif, dan inovatif dengan tetap menjunjung tinggi etika dan integritas"]

with open('p04_updates_part3.json', 'w', encoding='utf-8') as f:
    json.dump(p3, f, ensure_ascii=False, indent=2)

# Fix Part 4
with open('p04_updates_part4.json', 'r', encoding='utf-8') as f:
    p4 = json.load(f)

# Q40: keyword
p4["40"]["kata_kunci_modul"] = ["bagian yang melekat dan", "melembaga"]

# Q41: meta 'penjelasan modul'
p4["41"]["pembahasan"]["alasan_kunci"] = "Opsi A merupakan jawaban yang tepat tentang strategi penularan nilai ke masyarakat dan mitra kerja. Eksternalisasi memproyeksikan citra positif integritas dan profesionalitas kementerian melalui keteladanan aparatur saat berinteraksi dan melayani pemangku kepentingan. Ketentuan etika menegaskan tujuannya adalah pemahaman budaya oleh pihak luar kementerian. Maka dari itu, opsi A adalah strategi yang dimaksud."

# Q44: blacklist word 'korupsi' in option A
p4["44"]["pilihan"]["A"] = "Ketua Komisi Aparatur Sipil Negara Republik Indonesia"
p4["44"]["pembahasan"]["bedah_pengecoh"]["A"] = "KASN merupakan komite pengawas sistem merit nasional di luar kementerian."

# Q46: keyword
p4["46"]["kata_kunci_modul"] = ["Sikap Dasar: senantiasa amanah, jujur, disiplin, konsisten, dapat dipercaya, militan", "dalam bekerja"]

# Q51: meta 'penjelasan modul'
p4["51"]["pembahasan"]["alasan_kunci"] = "Opsi A merupakan jawaban yang tepat tentang kompetensi eksekusi dan komunikasi profesional. Kecepatan tanpa akurasi membahayakan keuangan negara, sedangkan akurasi tanpa komunikasi efektif menimbulkan kebuntuan koordinasi, sehingga keduanya harus berpadu seimbang. Pedoman kerja merumuskan komunikasi efektif serta perhatian pada kecepatan dan akurasi. Dengan demikian, opsi A adalah perilaku yang tepat."

# Q54: meta 'penjelasan modul'
p4["54"]["pembahasan"]["alasan_kunci"] = "Opsi D merupakan jawaban yang tepat tentang kepemimpinan kolaboratif aparatur. Perbedaan latar belakang dan sudut pandang tidak dipandang sebagai ancaman perpecahan, melainkan dikelola secara bijak sebagai sumber kekayaan gagasan untuk menghasilkan solusi terbaik. Pedoman perilaku menegaskan pengelolaan perbedaan sebagai peluang solusi terbaik. Dengan demikian, opsi D adalah perilaku yang tepat."

# Q58: meta 'penjelasan modul'
p4["58"]["pembahasan"]["alasan_kunci"] = "Opsi C merupakan jawaban yang tepat sesuai rumusan Sikap Dasar nilai Kesempurnaan dalam KMK 429. Keberhasilan menuju kesempurnaan menuntut pegawai tanggap menangkap peluang dari perubahan lingkungan, melahirkan ide pembaruan, menjaga transparansi, serta berani mengoreksi kelemahan sistem secara objektif. Pedoman etika merinci efektif memanfaatkan perubahan, inovatif, transparan, dan korektif. Dengan demikian, opsi C adalah rumusan yang tepat."

# Q59: keyword & meta 'penjelasan modul'
p4["59"]["kata_kunci_modul"] = ["Mendorong partisipasi aktif untuk mencari peluang perbaikan", "sistem secara lebih komprehensif"]
p4["59"]["pembahasan"]["alasan_kunci"] = "Opsi D merupakan jawaban yang tepat tentang partisipasi inovasi aparatur. Perbaikan sistem yang berdaya tahan lama tidak bisa dilakukan sepihak oleh pimpinan, melainkan membutuhkan pelibatan seluruh anggota tim dalam mengidentifikasi titik lemah dan menciptakan terobosan tata kelola. Panduan perilaku menetapkan keharusan mendorong partisipasi aktif untuk perbaikan komprehensif. Oleh sebab itu, opsi D adalah perilaku yang tepat."

# Q60: keyword
p4["60"]["kata_kunci_modul"] = ["Mendorong partisipasi aktif untuk mencari peluang perbaikan", "dan mengimplementasikan cara-cara baru/inovatif"]

# Q65: meta 'penjelasan modul'
p4["65"]["pembahasan"]["alasan_kunci"] = "Opsi E merupakan jawaban yang tepat mengenai standar mutu perencanaan kerja. Rencana kerja yang efektif harus menjawab tantangan nyata organisasi (relevan) dan dikaji secara mendalam dari segi sumber daya serta mitigasi risiko (matang) agar dapat dieksekusi dengan sukses. Pedoman kerja menetapkan karakteristik rencana yang relevan dan matang. Dengan demikian, opsi E adalah rumusan yang benar."

with open('p04_updates_part4.json', 'w', encoding='utf-8') as f:
    json.dump(p4, f, ensure_ascii=False, indent=2)

# Fix Part 5
with open('p04_updates_part5.json', 'r', encoding='utf-8') as f:
    p5 = json.load(f)

# Q67: blacklist word 'korupsi' in option E
p5["67"]["pilihan"]["E"] = "Aparat penegak hukum pada bidang penanganan sengketa perdata"
p5["67"]["pembahasan"]["bedah_pengecoh"]["E"] = "Aparat penegak hukum perdata menangani sengketa kebendaan hukum luar instansi."

# Q98: meta 'penjelasan modul'
p5["98"]["pembahasan"]["alasan_kunci"] = "Opsi C merupakan jawaban yang tepat mengenai fokus nilai Pelayanan di bab penutup. Keberhasilan pelayanan aparatur tidak diukur dari persepsi internal birokrasi, melainkan dari tingkat kepuasan, kepercayaan, dan kemudahan yang dirasakan secara transparan oleh para pemangku kepentingan. Ketentuan etika menegaskan pemenuhan kepuasan pemangku kepentingan secara transparan. Oleh karena itu, opsi C adalah jawaban yang benar."

with open('p04_updates_part5.json', 'w', encoding='utf-8') as f:
    json.dump(p5, f, ensure_ascii=False, indent=2)

print("Successfully fixed all flagged issues in parts 1-5!")

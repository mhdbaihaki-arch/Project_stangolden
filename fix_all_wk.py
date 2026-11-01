import json
import re

# 1. FIX PAKET 01
p1_file = 'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_01.json'
with open(p1_file, 'r', encoding='utf-8') as f:
    p1 = json.load(f)

# Soal 20: A
p1['daftar_soal'][19]['pilihan']['A'] = 'Menghapuskan capaian positif kepemimpinan masa sebelumnya'
# Soal 23: C
p1['daftar_soal'][22]['pilihan']['C'] = 'Empat Konsensus Dasar Berbangsa dan Bernegara'
# Soal 36: E
p1['daftar_soal'][35]['pilihan']['E'] = 'Panitia Perancang Pengalihan Aset Rampasan Perang'
# Soal 42: A-E balanced
p1['daftar_soal'][41]['pilihan'] = {
    'A': 'Fungsi ketahanan pertahanan militer doktriner',
    'B': 'Fungsi sosiologis, etis, serta filosofis kenegaraan',
    'C': 'Fungsi pengelolaan moneter kawasan regional',
    'D': 'Fungsi peradilan dalam adjudikasi perkara',
    'E': 'Fungsi pengawasan pada otoritas bursa efek'
}
# Soal 46: A
p1['daftar_soal'][45]['pilihan']['A'] = 'Kedudukan hukum dan landasan yuridis yang kokoh'
# Soal 80: E
p1['daftar_soal'][79]['pilihan']['E'] = 'Undang-Undang Nomor 12 Tahun 2011'
# Soal 90: B
p1['daftar_soal'][89]['pilihan']['B'] = 'Sanksi sosial informal berupa teguran lingkungan'

with open(p1_file, 'w', encoding='utf-8') as f:
    json.dump(p1, f, indent=2, ensure_ascii=False)
print("Paket 01 WK updated.")

# 2. FIX PAKET 02
p2_file = 'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_02.json'
with open(p2_file, 'r', encoding='utf-8') as f:
    p2 = json.load(f)

# Soal 2: alasan
p2['daftar_soal'][1]['pembahasan']['alasan_kunci'] = (
    "Opsi E merupakan jawaban yang tepat. Nilai kerohanian bersifat abstrak dan imaterial sehingga tidak memiliki satuan matematis. "
    "Secara hakiki, sarana pengukur nilai rohani terletak pada kedalaman budi nurani manusia yang merefleksikan kebaikan dan kepatutan batin."
)
# Soal 4: alasan
p2['daftar_soal'][3]['pembahasan']['alasan_kunci'] = (
    "Opsi C merupakan jawaban yang tepat. Fungsi aksiologis nilai berperan sebagai penggerak fundamental perilaku manusia. "
    "Sebelum melakukan suatu tindakan nyata, manusia selalu mendasarkan keputusannya pada tata nilai yang dianutnya sebagai alasan pembenar moral."
)
# Soal 24: alasan
p2['daftar_soal'][23]['pembahasan']['alasan_kunci'] = (
    "Opsi E merupakan jawaban yang tepat. Ideologi kenegaraan berakar kuat pada sanubari bangsa yang paling dalam. "
    "Ia menampung cita-cita keadilan, kemerdekaan, dan kemakmuran yang dirindukan oleh seluruh rakyat sejak masa penjajahan."
)
# Soal 42: alasan
p2['daftar_soal'][41]['pembahasan']['alasan_kunci'] = (
    "Opsi C merupakan jawaban yang tepat. Tonggak integrasi kedaulatan wilayah nusantara tercatat dalam era keemasan Kerajaan Sriwijaya (abad ke-7) dan Majapahit (abad ke-14). "
    "Keduanya membuktikan kemampuan bangsa menyelenggarakan ketatanegaraan berdaulat."
)
# Soal 67: D & alasan
p2['daftar_soal'][66]['pilihan']['D'] = 'Persatuan Indonesia serta Kemanusiaan Adil dan Beradab'
p2['daftar_soal'][66]['pembahasan']['alasan_kunci'] = (
    "Opsi D merupakan jawaban yang tepat. Secara historis, kedua frasa tersebut menjadi rujukan penting yang sangat mirip dengan rumusan Sila Kedua dan Sila Ketiga dalam Pembukaan UUD 1945 pasca pengesahan PPKI."
)
# Soal 97: alasan
p2['daftar_soal'][96]['pembahasan']['alasan_kunci'] = (
    "Opsi C merupakan jawaban yang tepat. Fakta konstitusional membuktikan bahwa BPUPKI merupakan rahim historis lahirnya rumusan Pancasila. "
    "Dari pertukaran gagasan di forum inilah dasar filsafat negara Indonesia merdeka dirajut hingga menjadi konsensus nasional."
)

with open(p2_file, 'w', encoding='utf-8') as f:
    json.dump(p2, f, indent=2, ensure_ascii=False)
print("Paket 02 WK updated.")

# 3. FIX PAKET 03
p3_file = 'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_03.json'
with open(p3_file, 'r', encoding='utf-8') as f:
    p3 = json.load(f)

p3['daftar_soal'][38]['pertanyaan'] = 'Prinsip kepemimpinan musyawarah pada Sila Keempat Pembukaan UUD 1945 berbunyi...'
p3['daftar_soal'][38]['pilihan'] = {
    'A': 'Kedaulatan rakyat berlandaskan permusyawaratan dewan',
    'B': 'Demokrasi perwakilan berdasar pemufakatan majelis',
    'C': 'Hikmat kebijaksanaan dalam permusyawaratan/perwakilan',
    'D': 'Kepemimpinan perwakilan berdasarkan suara terbanyak',
    'E': 'Permusyawaratan gotong royong segenap rakyat negara'
}

with open(p3_file, 'w', encoding='utf-8') as f:
    json.dump(p3, f, indent=2, ensure_ascii=False)
print("Paket 03 WK updated.")

# 4. FIX PAKET 04
p4_file = 'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_04.json'
with open(p4_file, 'r', encoding='utf-8') as f:
    p4 = json.load(f)

# Soal 14: D
p4['daftar_soal'][13]['pilihan']['D'] = 'Kelompok kerabat tertentu yang memiliki relasi kedinasan'
# Soal 26: D
p4['daftar_soal'][25]['pilihan']['D'] = 'Kebijakan diskresioner sepihak di luar kerangka hukum'
# Soal 34: A, C
p4['daftar_soal'][33]['pilihan']['A'] = 'Bebas memuat materi yang bertentangan dengan undang-undang'
p4['daftar_soal'][33]['pilihan']['C'] = 'Dapat mengesampingkan pasal hak asasi demi percepatan modal'
# Soal 37: E
p4['daftar_soal'][36]['pilihan']['E'] = 'UU Nomor 12 Tahun 2011 juncto UU Nomor 13 Tahun 2022'
# Soal 39: A
p4['daftar_soal'][38]['pilihan']['A'] = 'Membentuk kelompok kedaerahan eksklusif di lingkungan kantor'
# Soal 42: A
p4['daftar_soal'][41]['pilihan']['A'] = 'UUD 1945, Tap MPR, UU/Perppu, PP, Perpres, Perda Prov, Perda Kab/Kota'
# Soal 44: A, B, C, D
p4['daftar_soal'][43]['pilihan']['A'] = 'Peraturan baru otomatis membatalkan seluruh undang-undang dasar'
p4['daftar_soal'][43]['pilihan']['B'] = 'Keputusan kepala desa dapat mengesampingkan peraturan pemerintah'
p4['daftar_soal'][43]['pilihan']['C'] = 'Peraturan yang lebih tinggi derajatnya mengalahkan yang rendah'
p4['daftar_soal'][43]['pilihan']['D'] = 'Peraturan daerah berkedudukan tertinggi dalam batas otonominya'
# Soal 54: A
p4['daftar_soal'][53]['pilihan']['A'] = 'Mengubah hierarki tata urutan peraturan di luar undang-undang'
# Soal 83: C
p4['daftar_soal'][82]['pilihan']['C'] = 'Kekuasaan tertinggi di tangan rakyat dan tidak lagi di tangan MPR'
# Soal 86: A, B, C, D, E
p4['daftar_soal'][85]['pilihan'] = {
    'A': 'Meminta fatwa hukum Mahkamah Agung terkait pemakzulan Presiden',
    'B': 'Menerbitkan nota pemakzulan langsung untuk dieksekusi kepolisian',
    'C': 'Menyelenggarakan referendum nasional guna meminta mandat pemilih',
    'D': 'Mengajukan permintaan ke MK guna memeriksa dan memutus pendapat DPR',
    'E': 'Membentuk komite etik guna memberhentikan Presiden dari jabatannya'
}
# Soal 87: E
p4['daftar_soal'][86]['pilihan']['E'] = 'Keputusan perombakan kabinet di luar persetujuan komisi terkait di DPR'
# Soal 88: C
p4['daftar_soal'][87]['pilihan']['C'] = 'Menteri diangkat dan diberhentikan serta bertanggung jawab ke Presiden'
# Soal 90: B
p4['daftar_soal'][89]['pilihan']['B'] = 'Bentuk Negara Kesatuan Republik Indonesia tidak dapat diubah'
# Soal 93: C, E
p4['daftar_soal'][92]['pilihan']['C'] = 'Perwira militer aktif beserta pimpinan partai pemenang pemilu'
p4['daftar_soal'][92]['pilihan']['E'] = 'Gubernur seluruh provinsi dan delegasi rektor perguruan tinggi'

with open(p4_file, 'w', encoding='utf-8') as f:
    json.dump(p4, f, indent=2, ensure_ascii=False)
print("Paket 04 WK updated.")

# 5. FIX PAKET 05
p5_file = 'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_05.json'
with open(p5_file, 'r', encoding='utf-8') as f:
    p5 = json.load(f)

p5_replaces = {
    (1, 'A'): 'Selambat-lambatnya tiga puluh hari dari dua paket calon terbanyak',
    (1, 'B'): 'Paling lambat empat belas hari kerja sejak pengunduran diri',
    (1, 'C'): 'Maksimal enam puluh hari kalender sejak penetapan panitia',
    (1, 'D'): 'Dalam tenggat sembilan puluh hari setelah verifikasi berkas',
    (2, 'B'): 'Kepala pemerintahan (eksekutif dan legislatif) dan kepala negara',
    (4, 'D'): 'Menjabat lima tahun dan dapat dipilih kembali untuk satu kali',
    (5, 'C'): 'Mahkamah Agung mengeksekusi pemberhentian tanpa sidang majelis',
    (5, 'E'): 'Diberhentikan MPR atas usul DPR berdasar putusan Mahkamah Konstitusi',
    (8, 'C'): 'Menteri Luar Negeri, Dalam Negeri, dan Pertahanan bersama-sama',
    (9, 'D'): 'Usul perubahan pasal diajukan sekurang-kurangnya 1/3 anggota MPR',
    (12, 'C'): 'Menetapkan PP untuk menjalankan undang-undang sebagaimana mestinya',
    (16, 'B'): 'Memegang kekuasaan tertinggi atas Angkatan Darat, Laut, dan Udara',
    (17, 'C'): 'Menyatakan perang dan membuat perdamaian atas persetujuan DPR',
    (18, 'D'): 'Mengangkat duta dan konsul dengan memperhatikan pertimbangan DPR',
    (19, 'E'): 'Memberi grasi dan rehabilitasi dengan pertimbangan Mahkamah Agung',
    (20, 'A'): 'Memberi amnesti dan abolisi dengan memperhatikan pertimbangan DPR',
    (24, 'A'): 'Membentuk undang-undang yang dibahas bersama Presiden',
    (25, 'B'): 'Membahas dan memberi persetujuan atau penolakan atas Perppu',
    (26, 'E'): 'Mengalihkan belanja pendidikan menjadi pos bantuan modal swasta',
    (28, 'E'): 'Mengawasi pelaksanaan UU, APBN, serta kebijakan pemerintah',
    (30, 'B'): 'Menyetujui usulan pengangkatan dan pemberhentian anggota KY',
    (32, 'E'): 'Memilih tiga calon hakim konstitusi dan mengajukannya ke Presiden',
    (37, 'C'): 'Provinsi kepulauan diberi kuota perwakilan dua kali lipat daratan',
    (37, 'D'): 'Jumlah kursi dewan ditentukan besaran kontribusi pajak ke APBN',
    (37, 'E'): 'Jumlah anggota DPD setiap provinsi sama dan berkeadilan merata',
    (38, 'A'): 'Jumlah seluruh anggota DPD paling banyak 1/3 jumlah anggota DPR',
    (40, 'C'): 'Pemberhentian anggota DPD diatur syarat dan tata caranya dalam UU',
    (41, 'D'): 'Membatalkan peraturan daerah secara mandiri tanpa izin menteri',
    (41, 'E'): 'Mengajukan RUU bidang otonomi dan perimbangan keuangan ke DPR',
    (42, 'A'): 'Membahas RUU bidang kedaerahan bersama DPR sebelum dengan pemerintah',
    (43, 'B'): 'Mengawasi pelaksanaan undang-undang mengenai otonomi daerah bagi DPR',
    (44, 'C'): 'Memberikan pertimbangan kepada DPR dalam pemilihan anggota BPK',
    (45, 'D'): 'Pelaksana kekuasaan kehakiman yang merdeka demi hukum dan keadilan',
    (46, 'B'): 'Lembaga banding pertama tindak pidana pelanggaran pilkada daerah',
    (46, 'E'): 'Pengadilan tertinggi bagi semua peradilan yang merdeka dan mandiri',
    (47, 'A'): 'Calon Hakim Agung diusulkan Komisi Yudisial kepada DPR untuk disetujui',
    (47, 'B'): 'Calon dipilih melalui musyawarah tertutup oleh seluruh hakim agung',
    (47, 'C'): 'Presiden mengangkat langsung para hakim tinggi tanpa dewan',
    (47, 'D'): 'Komisi ASN menyelenggarakan tes komputerisasi serentak daerah',
    (48, 'B'): 'Ketua dan Wakil Ketua MA dipilih dari dan oleh Hakim Agung',
    (48, 'D'): 'Pimpinan MA disahkan melalui dengar pendapat komisi kepolisian',
    (50, 'A'): 'RUU kepegawaian dinas intelijen dan penegakan hukum peradilan',
    (50, 'C'): 'Rancangan Perppu tentang status keadaan bahaya perang nasional',
    (50, 'E'): 'Nota kesepahaman antara kementerian negara dengan swasta asing',
    (51, 'A'): 'Memutus permohonan PK pada tingkat pertama dan terakhir',
    (51, 'C'): 'Meninjau keabsahan konstitusional undang-undang terhadap UUD',
    (52, 'C'): 'Memutus kasasi putusan pengadilan tingkat banding semua peradilan',
    (52, 'D'): 'Mengesahkan kepengurusan organisasi advokat nasional administratif',
    (53, 'E'): 'Menguji materiil peraturan di bawah undang-undang terhadap UU',
    (55, 'C'): 'Sub-bagian teknis kepaniteraan peradilan lingkungan mahkamah agung',
    (55, 'D'): 'Mandiri, serta diangkat dan diberhentikan Presiden atas persetujuan DPR',
    (58, 'E'): 'Sembilan hakim konstitusi yang diusulkan Presiden, DPR, dan MA',
    (59, 'B'): 'Menguji undang-undang terhadap Undang-Undang Dasar Negara 1945',
    (60, 'D'): 'Memutus sengketa kewenangan lembaga negara yang diberikan UUD',
    (66, 'B'): 'Menyerahkan hasil audit keuangan negara kepada DPR, DPD, dan DPRD',
    (68, 'C'): 'Kolektif konsensus, tertutup perwakilan, dan saat kas APBN surplus',
    (71, 'C'): 'Mengatur urusan pemerintahan berdasar asas otonomi dan pembantuan',
    (73, 'A'): 'Kepala daerah diangkat mutlak oleh menteri atas dasar loyalitas',
    (73, 'B'): 'Gubernur, Bupati, Walikota dipilih demokratis sesuai undang-undang',
    (73, 'C'): 'Jabatan kepala daerah diwariskan kepada garis keluarga penguasa',
    (73, 'E'): 'Pemilihan kepala daerah ditentukan melalui lelang kontribusi modal',
    (74, 'B'): 'Seluruh urusan publik wajib diputuskan terpusat di kementerian',
    (74, 'C'): 'Pemerintah pusat melimpahkan wewenang militer dan peradilan ke daerah',
    (74, 'D'): 'Menjalankan otonomi seluasnya, kecuali urusan yang ditentukan pusat',
    (75, 'B'): 'Standardisasi tata kelola seragam tanpa mempedulikan budaya lokal',
    (76, 'B'): 'Daerah istimewa dibebaskan dari kewajiban mematuhi konstitusi',
    (76, 'C'): 'Negara mengakui satuan pemda yang bersifat khusus atau istimewa',
    (77, 'E'): 'Negara mengakui kesatuan masyarakat adat beserta hak tradisionalnya',
    (79, 'A'): 'Tata kelola daerah ditentukan kesepakatan asosiasi kepala desa',
    (79, 'D'): 'Susunan dan tata cara pemerintahan daerah diatur dalam undang-undang',
    (79, 'E'): 'Birokrasi daerah berhak menyimpang dari regulasi presiden',
    (80, 'A'): 'NKRI dibagi atas daerah provinsi, dan provinsi dibagi kabupaten/kota',
    (80, 'B'): 'Indonesia merupakan federasi dari negara-negara bagian otonom',
    (80, 'C'): 'Wilayah nasional disatukan dalam satu karesidenan kendali presiden',
    (80, 'E'): 'Batas teritorial daerah ditetapkan berdasar persebaran modal swasta',
    (81, 'D'): 'Kurun waktu pertama dari 18 Agustus 1945 hingga 27 Desember 1949',
    (82, 'A'): 'PPKI menetapkan Komite Nasional pembantu Presiden jalankan negara',
    (83, 'C'): 'Sistem Presidensial menurut UUD 1945 diubah menjadi Parlementer',
    (84, 'E'): 'Berlaku Konstitusi RIS dari 27 Desember 1949 hingga 17 Agustus 1950',
    (85, 'B'): 'Negara bagian RI ibukota Jogjakarta yang tetap berlakukan UUD 1945',
    (86, 'D'): 'Sejak 17 Agustus 1950 hingga 5 Juli 1959 berlaku naskah UUDS 1950',
    (87, 'A'): 'Dekrit Presiden 5 Juli 1959 dasar hukum UUD 1945 kurun waktu kedua',
    (88, 'C'): 'Memusatkan upaya pertahankan kemerdekaan dari agresi kolonial',
    (89, 'E'): 'Diberlakukan pasal IV Aturan Peralihan UUD 1945 dalam kekuasaan',
    (91, 'B'): 'Peradilan kehakiman dipimpin langsung oleh komandan militer',
    (91, 'C'): 'Kedaulatan diserahkan kepada komisi jasa baik perserikatan bangsa',
    (91, 'D'): 'Presiden dilarang membentuk kabinet sebelum izin komite asing',
    (91, 'E'): 'Sebelum MPR/DPR/DPA dibentuk, kekuasaan dijalankan Presiden dan KNIP',
    (92, 'B'): 'Kekuasaan Presiden sangat besar meliputi kewenangan semua lembaga',
    (92, 'D'): 'Parlemen memegang kendali pencopotan dwitunggal proklamator',
    (93, 'D'): 'Maklumat Wakil Presiden No. X tanggal 16 Oktober 1945 oleh Hatta',
    (94, 'A'): 'Memegang kekuasaan legislatif dan ikut menetapkan haluan negara',
    (95, 'C'): 'Usul Badan Pekerja KNIP (BPKNIP) pada tanggal 11 Nopember 1945',
    (98, 'D'): 'Para menteri dan perdana menteri bertanggung jawab kepada KNIP',
    (99, 'A'): 'Berlangsung delapan bulan sejak 27 Desember 1949 hingga 17 Agustus 1950',
    (99, 'C'): 'Berjalan tiga dasawarsa sebelum diamandemen oleh dewan perwakilan',
    (99, 'D'): 'Mencapai sepuluh tahun transisi di bawah komisi perserikatan bangsa',
    (100, 'A'): 'Instruksi militer asing agar seluruh daerah lebur ke persemakmuran',
    (100, 'B'): 'Kegagalan perjanjian utang luar negeri dengan perbankan eropa',
    (100, 'C'): 'Keinginan negara bagian RIS bentukan Belanda bergabung kembali ke RI',
    (100, 'D'): 'Penolakan mahkamah internasional atas keanggotaan federasi',
    (100, 'E'): 'Keputusan sepihak parlemen federal memecat perdana menteri bagian'
}

for (q_no, opt_let), new_val in p5_replaces.items():
    p5['daftar_soal'][q_no - 1]['pilihan'][opt_let] = new_val

with open(p5_file, 'w', encoding='utf-8') as f:
    json.dump(p5, f, indent=2, ensure_ascii=False)
print("Paket 05 WK updated.")

# 6. FIX PAKET 06
p6_file = 'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_06.json'
with open(p6_file, 'r', encoding='utf-8') as f:
    p6 = json.load(f)

p6_replaces = {
    (1, 'A'): 'Pembubaran dan penggabungan negara bagian diatur pasal 43 dan 49 RIS',
    (1, 'C'): 'Konstitusi federal melarang pembubaran wilayah tanpa izin Belanda',
    (1, 'E'): 'Pemerintah federal berhak membekukan dewan perwakilan daerah',
    (5, 'E'): 'Pada 17 Agustus 1950 negara federal RIS berubah menjadi NKRI',
    (6, 'A'): 'Menyusun rancangan perubahan Konstitusi RIS menjadi UUDS 1950',
    (9, 'D'): 'Sebagian besar wilayah telah bergabung ke RI Negara Bagian',
    (10, 'B'): 'Aturan hukum dapat dibatalkan sewaktu-waktu oleh komisi PBB',
    (10, 'C'): 'Naskah konstitusi disusun tanpa melibatkan tokoh pergerakan',
    (10, 'E'): 'UUDS 1950 dibentuk sembari menunggu hasil kerja Konstituante',
    (12, 'C'): 'Menteri dipimpin Perdana Menteri bertanggung jawab ke parlemen',
    (13, 'C'): 'Sistem feodalistik dengan aristokrasi pemegang kekuasaan',
    (15, 'A'): 'Dasar berlakunya UUD 1945 kurun kedua adalah Dekrit 5 Juli 1959',
    (16, 'B'): 'Bubarkan PKI, bersihkan kabinet, dan perbaiki ekonomi (Tritura)',
    (16, 'D'): 'Pemilihan langsung, penghapusan DPD, dan revisi undang-undang agraria',
    (16, 'E'): 'Pembekuan dewan daerah, privatisasi BUMN, dan pinjaman luar negeri',
    (18, 'D'): 'Presiden menerbitkan Supersemar kepada Letjen Soeharto',
    (20, 'A'): 'Kurun waktu kedua berlakunya UUD 1945 dibagi empat periode',
    (21, 'C'): 'Menarik mandat MPRS dari Ir. Soekarno dan mengangkat pejabat Presiden',
    (22, 'D'): 'Mengangkat Jenderal Soeharto sebagai Presiden sampai hasil pemilu',
    (26, 'C'): 'Ketua lembaga tinggi negara dijadikan menteri pembantu Presiden',
    (27, 'D'): 'Kekuasaan lembaga kepresidenan sangat dominan dibanding lainnya',
    (29, 'A'): 'Kurun waktu 11 Maret 1966 hingga 21 Mei 1998 (masa Orde Baru)',
    (30, 'A'): 'Pasangan calon presiden dipilih langsung rakyat lewat pemilu',
    (30, 'C'): 'Presiden ditunjuk bergiliran oleh pimpinan dewan daerah',
    (30, 'D'): 'Kepala pemerintahan diangkat mahkamah berdasar uji kompetensi',
    (30, 'E'): 'Suksesi kepemimpinan diserahkan kesepakatan dewan perbankan',
    (31, 'D'): 'Pasal 104 TAP MPR I/1983 yang berketetapan mempertahankan UUD 1945',
    (34, 'B'): 'Pencabutan lewat TAP MPR VIII/1998 karena bertentangan Pasal 37 UUD',
    (43, 'B'): 'Melantik Presiden dan Wakil Presiden hasil pemilihan umum langsung',
    (45, 'A'): 'MPR berwenang memberhentikan Presiden atas kehendak pimpinan komisi',
    (45, 'D'): 'MPR memberhentikan Presiden atas usul DPR berdasar putusan MK',
    (46, 'E'): 'Ditegaskan bahwa Presiden tidak dapat membekukan/membubarkan DPR',
    (50, 'D'): 'Lembaga DPA ditiadakan, diganti Dewan Pertimbangan bentukan Presiden',
    (54, 'D'): 'Memindahkan hal-hal normatif dalam Penjelasan UUD ke pasal-pasal',
    (55, 'C'): 'Menyusun piagam konstitusi darurat tanpa melampirkan teks UUD asli',
    (56, 'A'): 'TAP MPR No. IX/MPR/1999 tentang Penugasan Badan Pekerja MPR',
    (56, 'B'): 'Surat keputusan kementerian kehakiman tentang tim perumus regulasi',
    (58, 'C'): 'Membatasi wewenang Presiden dan memperkuat fungsi legislatif DPR',
    (60, 'B'): 'Perubahan naskah melalui musyawarah tertutup antar pimpinan fraksi',
    (60, 'E'): 'Perubahan UUD 1945 bertahap pada Sidang Tahunan MPR 1999–2002',
    (64, 'E'): 'Naskah asli berisi 71 butir ketentuan, setelah diubah menjadi 199',
    (65, 'A'): 'Aturan Peralihan Pasal I, II, III dan Aturan Tambahan Pasal I, II',
    (67, 'C'): 'Pendidikan dan kebudayaan termasuk anggaran minimal 20% APBN',
    (69, 'E'): 'Ketentuan Pemilihan Umum yang disahkan pada 9 November 2001',
    (72, 'D'): 'Tidak mengubah bentuk Negara Kesatuan Republik Indonesia',
    (75, 'B'): 'Sumpah Pemuda 1928 sebagai ikrar persatuan kebangsaan anak bangsa',
    (76, 'C'): 'Penghargaan cita-cita pendiri bangsa mewujudkan negara bersatu',
    (79, 'A'): 'Perlu pengaturan undang-undang komprehensif bagi otonomi daerah',
    (79, 'C'): 'Seluruh pejabat daerah diangkat dari aparatur kementerian pusat'
}

for (q_no, opt_let), new_val in p6_replaces.items():
    p6['daftar_soal'][q_no - 1]['pilihan'][opt_let] = new_val

with open(p6_file, 'w', encoding='utf-8') as f:
    json.dump(p6, f, indent=2, ensure_ascii=False)
print("Paket 06 WK updated.")

# 7. FIX PAKET 07
p7_file = 'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_07.json'
with open(p7_file, 'r', encoding='utf-8') as f:
    p7 = json.load(f)

# Soal 28: D
p7['daftar_soal'][27]['pilihan']['D'] = 'Kecintaan mendalam terhadap budaya adiluhung bangsa'
# Soal 81: B
p7['daftar_soal'][80]['pilihan']['B'] = 'Mengutamakan pihak tertentu dalam antrean layanan publik'
# Soal 100: B
p7['daftar_soal'][99]['pilihan']['B'] = 'Membentuk kelompok eksklusif di lingkungan kantor satker'

with open(p7_file, 'w', encoding='utf-8') as f:
    json.dump(p7, f, indent=2, ensure_ascii=False)
print("Paket 07 WK updated.")

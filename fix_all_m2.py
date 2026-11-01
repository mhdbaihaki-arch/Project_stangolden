import json
import re

# 1. FIX PAKET 02
p2_file = 'soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_02.json'
with open(p2_file, 'r', encoding='utf-8') as f:
    p2 = json.load(f)

# Soal 11: D
p2['daftar_soal'][10]['pilihan']['D'] = 'Surat Edaran Menpan RB Nomor 20 Tahun 2021'
# Soal 16: D
p2['daftar_soal'][15]['pilihan']['D'] = 'Mempunyai core values dan semboyan kerja yang sama'
# Soal 20: D
p2['daftar_soal'][19]['pilihan']['D'] = 'Melakukan perbaikan tiada henti secara berkesinambungan'
# Soal 25: D
p2['daftar_soal'][24]['pilihan']['D'] = 'Memegang teguh ideologi dasar negara dan konstitusi'
# Soal 43: D
p2['daftar_soal'][42]['pilihan']['D'] = 'Pelayanan dan Adaptif'
# Soal 51: E
p2['daftar_soal'][50]['pilihan']['E'] = 'Undang-Undang Nomor 17 Tahun 2003 tentang Keuangan'
# Soal 55: B
p2['daftar_soal'][54]['pilihan']['B'] = 'Memiliki sangka baik dan saling menghormati'
# Soal 59: C
p2['daftar_soal'][58]['pilihan']['C'] = 'Bekerja dengan hati dan dedikasi yang tinggi'
# Soal 65: A, B, D
p2['daftar_soal'][64]['pilihan']['A'] = 'Melayani berorientasi pada kepuasan para pemangku'
p2['daftar_soal'][64]['pilihan']['B'] = 'Memiliki sangka baik dan saling percaya antarsesama'
p2['daftar_soal'][64]['pilihan']['D'] = 'Bersikap proaktif dan cepat tanggap melayani'

with open(p2_file, 'w', encoding='utf-8') as f:
    json.dump(p2, f, indent=2, ensure_ascii=False)
print("Paket 02 M2 updated.")

# 2. FIX PAKET 03
p3_file = 'soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_03.json'
with open(p3_file, 'r', encoding='utf-8') as f:
    p3 = json.load(f)

# Soal 40: Alasan mentions B vs key E
p3['daftar_soal'][39]['pembahasan']['alasan_kunci'] = (
    "Opsi E merupakan jawaban yang tepat karena perbaikan berkelanjutan menuntut sikap keterbukaan atas gagasan baru "
    "sekaligus keberanian melahirkan terobosan proses baru. Kedua perilaku utama tersebut saling melengkapi dalam mendorong "
    "perbaikan berkesinambungan. Oleh karena itu, opsi E memuat kedua perilaku utama yang tepat."
)
# Soal 46: C
p3['daftar_soal'][45]['pilihan']['C'] = 'Kekayaan materiil milik sanak kerabat pegawai'
# Soal 76: A
p3['daftar_soal'][75]['pilihan']['A'] = 'Disiplin apel pagi, olahraga bersama, dan senam pagi'

with open(p3_file, 'w', encoding='utf-8') as f:
    json.dump(p3, f, indent=2, ensure_ascii=False)
print("Paket 03 M2 updated.")

# 3. FIX PAKET 04
p4_file = 'soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_04.json'
with open(p4_file, 'r', encoding='utf-8') as f:
    p4 = json.load(f)

# Soal 40: meta in alasan
p4['daftar_soal'][39]['pembahasan']['alasan_kunci'] = (
    "Opsi E merupakan jawaban yang tepat mengenai strategi pelembagaan budaya. Institusionalisasi memastikan nilai-nilai "
    "organisasi didukung oleh kerangka regulasi yang kuat serta penegakan disiplin dan pemberian apresiasi yang nyata bagi pegawai. "
    "Pelembagaan nilai melalui regulasi dan sistem penghargaan berfungsi sebagai pilar institusionalisasi. Oleh sebab itu, opsi E adalah jawaban yang benar."
)
# Soal 64: meta in alasan
p4['daftar_soal'][63]['pembahasan']['alasan_kunci'] = (
    "Opsi D merupakan jawaban yang tepat sesuai kaidah nilai Kesempurnaan. Data dan informasi bukan sekadar arsip statistik pasif, "
    "melainkan amunisi evaluasi kritis untuk menemukan kelemahan proses bisnis dan menyempurnakannya tanpa henti (continuous improvement). "
    "Tujuan utama evaluasi data adalah perbaikan pekerjaan secara terus menerus. Oleh karena itu, opsi D adalah tujuan yang tepat."
)
# Soal 66: A, E
p4['daftar_soal'][65]['pilihan']['A'] = 'Surat Edaran Menteri Keuangan Nomor SE-15/MK.1/2022'
p4['daftar_soal'][65]['pilihan']['E'] = 'Peraturan Presiden Nomor 17 Tahun 2022'
# Soal 72: meta in alasan
p4['daftar_soal'][71]['pembahasan']['alasan_kunci'] = (
    "Opsi B merupakan jawaban yang tepat mengenai wujud eksternalisasi kreatif bagi pegawai pemula. Mahasiswa dan CPNS "
    "diberdayakan keterampilannya untuk memproduksi konten kreatif seperti video dan grafis budaya guna menyebarkan nilai-nilai luhur "
    "Kemenkeu ke masyarakat luas. Dalam panduan peran pelaksana pemula dirinci tugas desain grafis atau video sosialisasi eksternal. Oleh karena itu, opsi B adalah jawaban yang benar."
)
# Soal 73: meta in alasan
p4['daftar_soal'][72]['pembahasan']['alasan_kunci'] = (
    "Opsi C merupakan jawaban yang tepat mengenai target maturitas pelaksana senior. Pegawai yang telah berdinas lebih dari satu tahun "
    "diharapkan telah melampaui fase sekadar tahu (sadar) dan mulai memahami urgensi nilai serta mengamalkannya dalam porsi nyata tugas pekerjaan harian (*understand the values*). "
    "Tingkat kematangan ini menuntut pemahaman mendalam dan penerapan nyata sebagian sikap dasar. Dengan demikian, opsi C adalah target yang tepat."
)
# Soal 75: meta in alasan
p4['daftar_soal'][74]['pembahasan']['alasan_kunci'] = (
    "Opsi E merupakan jawaban yang tepat mengenai benteng pengawasan institusional budaya. Itjen dan UKI bertindak sebagai garda terdepan "
    "penegakan kepatuhan etika dan disiplin, sedangkan Kehumasan memperkuat amplifikasi narasi positif budaya ke seluruh kanal komunikasi. "
    "Struktur pengawasan dan publikasi menekankan penguatan peran Inspektorat Jenderal, UKI, dan Kehumasan. Oleh karena itu, opsi E adalah unit kerja yang tepat."
)
# Soal 77: meta in alasan
p4['daftar_soal'][76]['pembahasan']['alasan_kunci'] = (
    "Opsi B merupakan jawaban yang tepat mengenai target kematangan level manajemen operasional. Pejabat Pengawas dan Administrator "
    "memimpin langsung tim kerja lapangan, sehingga dituntut telah mengimplementasikan seluruh sikap dasar dan perilaku efektif secara utuh "
    "(*implementing the values*) agar menjadi teladan bagi bawahannya. Standar kompetensi manajerial menetapkan penerapan seluruh sikap dasar dan perilaku efektif. Oleh sebab itu, opsi B adalah target yang benar."
)
# Soal 78: meta in alasan
p4['daftar_soal'][77]['pembahasan']['alasan_kunci'] = (
    "Opsi C merupakan jawaban yang tepat mengenai peran keteladanan pimpinan tingkat pengawas dan administrator. Keteladanan nyata seorang atasan "
    "langsung dalam melayani, bersikap adil, dan berintegritas memiliki daya tular terkuat dalam membentuk budaya di seksi atau subbagian terkecil yang dipimpinnya. "
    "Fungsi kepemimpinan operasional menetapkan peran menjadi role model bagi unit terkecilnya. Maka dari itu, opsi C adalah jawaban yang benar."
)
# Soal 80: E
p4['daftar_soal'][79]['pilihan']['E'] = 'Integrasi kurikulum diklat kepemimpinan dan soft skill'
# Soal 81: meta in alasan
p4['daftar_soal'][80]['pembahasan']['alasan_kunci'] = (
    "Opsi A merupakan jawaban yang tepat mengenai puncak kematangan kepemimpinan nilai (*owning the values*). Pimpinan tinggi tidak hanya mengamalkan "
    "nilai secara pribadi, melainkan menginternalisasikannya hingga memiliki rasa kepemilikan penuh (*owning*), aktif mengajak seluruh pegawai, "
    "serta memperkaya sistem pengembangan budaya organisasi. Standar kepemimpinan puncak merinci karakter *owning the values*. Oleh sebab itu, opsi A adalah target yang tepat."
)
# Soal 83: meta in alasan
p4['daftar_soal'][82]['pembahasan']['alasan_kunci'] = (
    "Opsi C merupakan jawaban yang tepat mengenai keberpihakan kebijakan terhadap nilai budaya. Kebijakan publik dan regulasi internal yang ditandatangani "
    "oleh pimpinan tinggi harus memancarkan roh keadilan, integritas, dan pelayanan publik yang mencerminkan budaya luhur Kemenkeu. "
    "Kepemimpinan strategis menuntut pembuatan keputusan dan kebijakan sebagai perwujudan budaya kementerian. Oleh sebab itu, opsi C adalah jawaban yang benar."
)
# Soal 85: meta in alasan
p4['daftar_soal'][84]['pembahasan']['alasan_kunci'] = (
    "Opsi E merupakan jawaban yang tepat mengenai legalitas pengikatan mitra terhadap standar etika kementerian. Mencantumkan klausul budaya "
    "dalam kontrak atau perjanjian kerja sama memaksa vendor, perbankan, dan mitra internasional mematuhi asas transparansi, antisuap, dan integritas "
    "saat bertransaksi dengan Kemenkeu. Tata kelola kemitraan menetapkan pencantuman klausul nilai ke dalam naskah kerja sama pihak eksternal. Dengan demikian, opsi E adalah langkah yang benar."
)
# Soal 87: meta in alasan
p4['daftar_soal'][86]['pembahasan']['alasan_kunci'] = (
    "Opsi B merupakan jawaban yang tepat mengenai esensi definisi nilai dalam penutup uraian. Nilai berkedudukan sebagai kompas moral internal "
    "yang menyaring pilihan dan mengarahkan bagaimana seseorang bersikap, bertutur kata, dan mengambil keputusan dalam kehidupannya. "
    "Secara konseptual ditegaskan bahwa nilai memengaruhi perilaku orang dalam melakukan tindakan. Oleh karena itu, opsi B adalah jawaban yang benar."
)
# Soal 88: meta in alasan
p4['daftar_soal'][87]['pembahasan']['alasan_kunci'] = (
    "Opsi C merupakan jawaban yang tepat tentang fungsi pemersatu nilai organisasi. Nilai organisasi menyatukan energi dan pola kerja seluruh pegawai "
    "dari berbagai latar belakang agar bergerak selaras mencapai visi bersama instansi. Prinsip budaya mengarahkan aparatur berpikir, bertindak, "
    "dan berperilaku menuju tujuan organisasi. Dengan demikian, opsi C adalah rumusan yang benar."
)
# Soal 89: meta in alasan
p4['daftar_soal'][88]['pembahasan']['alasan_kunci'] = (
    "Opsi D merupakan jawaban yang tepat sesuai kaidah penguatan kinerja kelembagaan. Soliditas hubungan antarpegawai dan kekompakan antartim menciptakan "
    "sinergi tanpa gesekan yang memacu produktivitas organisasi ke tingkat optimal. Penguatan kelembagaan menegaskan kohesi antarindividu dan kesatuan "
    "organisasi sebagai prasyarat peningkatan kinerja. Oleh sebab itu, opsi D adalah jawaban yang benar."
)
# Soal 90: meta in alasan
p4['daftar_soal'][89]['pembahasan']['alasan_kunci'] = (
    "Opsi E merupakan jawaban yang tepat tentang signifikansi historis peluncuran Core Values BerAKHLAK. Penyeragaman nilai dasar menghapus fragmentasi "
    "budaya antar-instansi pemerintah, menyatukan orientasi pelayanan, serta menyelaraskan etos kerja seluruh ASN di seluruh pelosok Indonesia. "
    "Transformasi budaya ASN bertujuan agar aparatur memiliki langkah yang sama membangun budaya kerja prima. Maka dari itu, opsi E adalah jawaban yang benar."
)
# Soal 91: meta in alasan
p4['daftar_soal'][90]['pembahasan']['alasan_kunci'] = (
    "Opsi A merupakan jawaban yang tepat tentang titik temu filosofis kedua rumpun nilai. Baik BerAKHLAK maupun Nilai Kemenkeu bertumpu pada kemampuan "
    "aparatur mengendalikan diri, menjaga integritas, dan menyelaraskan motif pribadi dengan keluhuran tujuan tugas kedinasan negara. "
    "Secara fundamental ditegaskan pentingnya mengelola pribadi yang selaras dengan tujuan tugas kedinasan. Oleh sebab itu, opsi A adalah rumusan yang benar."
)
# Soal 92: meta in alasan
p4['daftar_soal'][91]['pembahasan']['alasan_kunci'] = (
    "Opsi B merupakan jawaban yang tepat mengenai ringkasan makna Integritas di bab penutup. Integritas mencakup kejujuran paripurna dalam pikiran, "
    "perkataan, dan tindakan serta ketaatan mutlak terhadap kode etik kedinasan dan prinsip moral. Makna integritas mencakup berpikir, berkata, berperilaku, "
    "dan bertindak baik dan benar serta memegang teguh kode etik. Dengan demikian, opsi B adalah jawaban yang benar."
)
# Soal 93: meta in alasan
p4['daftar_soal'][92]['pembahasan']['alasan_kunci'] = (
    "Opsi C merupakan jawaban yang tepat mengenai ringkasan pilar Sinergi. Kerja sama produktif antarseksi internal dan hubungan harmonis dengan "
    "mitra pemangku kepentingan menjadi kunci utama terciptanya hasil kerja yang bernilai tambah tinggi bagi bangsa. Pilar sinergi berfokus pada pembangunan "
    "kerja sama internal produktif dan kemitraan harmonis. Oleh karena itu, opsi C adalah jawaban yang benar."
)
# Soal 94: meta in alasan
p4['daftar_soal'][93]['pembahasan']['alasan_kunci'] = (
    "Opsi D merupakan jawaban yang tepat mengenai hakikat nilai Pelayanan di bab penutup uraian. Pelayanan publik diselenggarakan dengan meletakkan kepuasan "
    "pemangku kepentingan sebagai tolok ukur utama, yang dieksekusi dengan ketulusan hati, keterbukaan, dan akurasi tinggi. "
    "Standar pelayanan aparatur mewajibkan pemberian pelayanan terbaik untuk memenuhi kepuasan para pemangku kepentingan. Oleh sebab itu, opsi D adalah rumusan yang benar."
)
# Soal 100: meta in alasan
p4['daftar_soal'][99]['pembahasan']['alasan_kunci'] = (
    "Opsi E merupakan jawaban yang tepat sebagai rangkuman filosofis penutup uraian. Keluhuran integritas, profesionalitas, dan sinergi antarpegawai bermuara "
    "pada dua output utama: menghadirkan pelayanan prima bagi masyarakat serta tekad melakukan perbaikan tiada henti di seluruh bidang tugas demi kemajuan bangsa. "
    "Komitmen aparatur merangkum pelayanan prima dan perbaikan berkelanjutan secara terpadu. Oleh sebab itu, opsi E adalah jawaban yang tepat."
)

with open(p4_file, 'w', encoding='utf-8') as f:
    json.dump(p4, f, indent=2, ensure_ascii=False)
print("Paket 04 M2 updated.")

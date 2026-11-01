import json
import re

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)
page_texts = {p['page']: p['text'] for p in modul['pages']}

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_01.json', 'r', encoding='utf-8') as f:
    paket1 = json.load(f)

soals = {q['nomor']: q for q in paket1['daftar_soal']}

# Dictionaries of fixed questions for Paket 1
fixes = {}

# ----------------- Halaman 7 (Soal 4, 5, 8) -----------------
fixes[4] = {
    "pertanyaan": "Undang-Undang Nomor 20 Tahun 2023 tentang Aparatur Sipil Negara secara sah menggantikan...",
    "pilihan": {
        "A": "Undang-Undang Nomor 8 Tahun 1974",
        "B": "Undang-Undang Nomor 43 Tahun 1999",
        "C": "Undang-Undang Nomor 32 Tahun 2004",
        "D": "Undang-Undang Nomor 5 Tahun 2014",
        "E": "Undang-Undang Nomor 23 Tahun 2014"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Undang-Undang Nomor 20 Tahun 2023 secara resmi **menggantikan Undang-Undang Nomor 5 Tahun 2014 tentang Aparatur Sipil Negara**.",
        "alasan_kunci": "Opsi D merupakan regulasi terdahulu yang secara resmi digantikan oleh undang-undang aparatur sipil negara terbaru. Penggantian regulasi ini dilakukan guna mengakomodasi transformasi manajemen ASN, digitalisasi birokrasi, dan fleksibilitas mobilitas talenta nasional. Ketentuan penutup perundang-undangan secara tegas menetapkan pencabutan dan penggantian Undang-Undang Nomor 5 Tahun 2014. Oleh sebab itu, opsi D merupakan jawaban yang benar.",
        "bedah_pengecoh": {
            "A": "Merupakan undang-undang pokok-pokok kepegawaian masa orde baru yang telah lama dicabut.",
            "B": "Merupakan undang-undang perubahan atas pokok-pokok kepegawaian tahun 1999 terdahulu.",
            "C": "Merupakan undang-undang tentang pemerintahan daerah tahun 2004 yang telah digantikan.",
            "E": "Merupakan undang-undang tentang pemerintahan daerah tahun 2014 yang mengatur otonomi daerah."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[5] = {
    "pertanyaan": "Berdasarkan Pasal 77 Undang-Undang Nomor 20 Tahun 2023, ketentuan undang-undang ini mulai berlaku pada...",
    "pilihan": {
        "A": "Enam bulan sejak disetujui resmi oleh DPR",
        "B": "Awal tahun anggaran baru tahun berikutnya",
        "C": "Satu tahun setelah pengesahan oleh presiden",
        "D": "Dua tahun setelah masa sosialisasi selesai",
        "E": "Tanggal diundangkan regulasi bersangkutan"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Sesuai Pasal 77 UU Nomor 20 Tahun 2023, undang-undang ini **mulai berlaku pada tanggal diundangkan** yaitu 31 Oktober 2023.",
        "alasan_kunci": "Opsi E merupakan klausul keberlakuan undang-undang yang tepat sesuai norma hukum positif. Asas keberlakuan pada tanggal diundangkan memberikan kepastian hukum seketika bagi seluruh tatanan baru manajemen aparatur sipil negara. Ketentuan penutup Pasal 77 secara eksplisit menetapkan bahwa undang-undang ini mulai berlaku pada tanggal diundangkan. Dengan demikian, opsi E merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Masa tenggang enam bulan bukan merupakan klausul waktu keberlakuan UU 20/2023.",
            "B": "Pemberlakuan undang-undang tidak ditangguhkan hingga awal tahun anggaran baru berikutnya.",
            "C": "Masa tunggu satu tahun bukan klausul yang ditetapkan pada pasal penutup undang-undang.",
            "D": "Masa sosialisasi dua tahun bukan klausul tanggal berlakunya regulasi manajemen ASN."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[8] = {
    "pertanyaan": "Nomor pasal dalam Undang-Undang Nomor 20 Tahun 2023 yang menetapkan tanggal mulai berlakunya regulasi ini adalah...",
    "pilihan": {
        "A": "Pasal 55",
        "B": "Pasal 62",
        "C": "Pasal 77",
        "D": "Pasal 84",
        "E": "Pasal 91"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Ketentuan penutup mengenai saat mulai berlakunya undang-undang tercantum dalam **Pasal 77 UU Nomor 20 Tahun 2023**.",
        "alasan_kunci": "Opsi C merupakan pasal penutup yang secara tegas mengatur waktu keberlakuan regulasi aparatur sipil negara. Pasal penutup menjadi pedoman yuridis bagi seluruh pengelola kepegawaian dalam menerapkan norma baru manajemen ASN. Ketentuan penutup undang-undang secara spesifik menetapkan klausul saat mulai berlaku pada Pasal 77. Dengan demikian, opsi C merupakan rujukan pasal yang tepat.",
        "bedah_pengecoh": {
            "A": "Bukan pasal penutup yang mengatur saat berlakunya regulasi kepegawaian nasional.",
            "B": "Merupakan pasal yang mengatur substansi pengembangan karier aparatur dan bukan ketentuan penutup.",
            "D": "Undang-Undang Nomor 20 Tahun 2023 tidak memiliki rumusan pasal hingga nomor 84.",
            "E": "Undang-Undang Nomor 20 Tahun 2023 tidak memiliki sistematika pasal hingga nomor 91."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# ----------------- Halaman 9 (Soal 11, 13, 14, 15, 16, 17, 18, 19, 20) -----------------
fixes[11] = {
    "pertanyaan": "Aparatur Sipil Negara (ASN) didefinisikan secara resmi dalam undang-undang sebagai profesi bagi...",
    "pilihan": {
        "A": "Tenaga penunjang operasional dan pegawai kontrak instansi",
        "B": "Pegawai Negeri Sipil dan Pegawai Pemerintah Perjanjian Kerja",
        "C": "Karyawan tetap lembaga perbankan dan badan usaha negara",
        "D": "Tenaga konsultan teknis yang bermitra dengan pemerintah",
        "E": "Staf fungsional khusus pada lembaga nonstruktural independen"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "**Aparatur Sipil Negara (disingkat ASN) adalah profesi bagi Pegawai Negeri Sipil dan pegawai pemerintah dengan perjanjian kerja yang bekerja pada instansi pemerintah**.",
        "alasan_kunci": "Opsi B merupakan rumusan resmi profesi aparatur sipil negara yang sah dan baku. Undang-undang menyatukan dua entitas kepegawaian resmi pemerintah di bawah payung profesi ASN guna menjamin integritas dan profesionalitas pelayanan publik. Ketentuan perundang-undangan merumuskan ASN sebagai profesi bagi Pegawai Negeri Sipil dan Pegawai Pemerintah dengan Perjanjian Kerja. Oleh karena itu, opsi B merupakan jawaban yang benar.",
        "bedah_pengecoh": {
            "A": "Tenaga penunjang operasional dan pegawai kontrak bukan merupakan profesi ASN resmi.",
            "C": "Karyawan perbankan dan badan usaha milik negara tunduk pada regulasi ketenagakerjaan umum.",
            "D": "Konsultan teknis bermitra melalui perjanjian perdata pengadaan barang dan jasa pemerintah.",
            "E": "Staf khusus lembaga nonstruktural memiliki ikatan penugasan tersendiri di luar ASN."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[13] = {
    "pertanyaan": "Ciri pembeda utama pengangkatan Pegawai Negeri Sipil (PNS) dibandingkan dengan PPPK adalah PNS diangkat secara...",
    "pilihan": {
        "A": "Periodik berdasarkan masa kontrak perjanjian kerja",
        "B": "Bertahap sesuai formasi transisi kelembagaan baru",
        "C": "Terjadwal menurut evaluasi penugasan berkala dinas",
        "D": "Tetap oleh pejabat pembina kepegawaian instansi",
        "E": "Fleksibel mengikuti kebutuhan penyelesaian proyek"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "PNS adalah WNI yang memenuhi syarat tertentu, **diangkat sebagai Pegawai ASN secara tetap oleh pejabat pembina kepegawaian untuk menduduki jabatan pemerintahan**.",
        "alasan_kunci": "Opsi D merupakan karakter yuridis status PNS yang berorientasi pada kesinambungan karier jangka panjang. Berbeda dengan PPPK yang berbasis kontrak kerja waktu tertentu, PNS memegang status kepegawaian tetap dalam birokrasi pemerintahan. Ketentuan perundang-undangan menegaskan bahwa PNS diangkat sebagai Pegawai ASN secara tetap oleh pejabat pembina kepegawaian. Oleh sebab itu, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Pengangkatan periodik berbasis masa kontrak kerja merupakan karakteristik pokok status PPPK.",
            "B": "Pengangkatan bertahap formasi transisi bukan karakteristik yuridis pembeda status PNS.",
            "C": "Evaluasi penugasan berkala merupakan bagian manajemen kinerja dan bukan dasar status PNS.",
            "E": "Penugasan fleksibel berbasis proyek merupakan mekanisme pekerja kontrak penugasan khusus."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[14] = {
    "pertanyaan": "Pegawai Pemerintah dengan Perjanjian Kerja (PPPK) diangkat dalam jabatan pemerintahan berdasarkan...",
    "pilihan": {
        "A": "Keputusan pengangkatan pegawai tetap seumur hidup",
        "B": "Penugasan karier struktural tanpa batas masa kerja",
        "C": "Rekomendasi masa percobaan formasi kepegawaian",
        "D": "Kebijakan penataan mutasi dinas lintas kementerian",
        "E": "Perjanjian kerja untuk jangka waktu tertentu kedinasan"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "PPPK diangkat **berdasarkan perjanjian kerja untuk jangka waktu tertentu dalam rangka melaksanakan tugas pemerintahan**.",
        "alasan_kunci": "Opsi E merupakan dasar ikatan hukum kepegawaian bagi aparatur berstatus PPPK yang tepat. Hubungan kerja PPPK dituangkan dalam perjanjian kerja resmi yang mengatur hak, kewajiban, masa tugas, serta target kinerja terukur. Ketentuan perundang-undangan menetapkan bahwa PPPK diangkat berdasarkan perjanjian kerja untuk jangka waktu tertentu dalam menjalankan tugas pemerintahan. Dengan demikian, opsi E merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Pengangkatan sebagai pegawai tetap seumur hidup dinas merupakan karakteristik status PNS.",
            "B": "Penugasan karier struktural tanpa batas masa kerja merupakan pola pembinaan karier PNS.",
            "C": "Masa percobaan formasi kepegawaian merupakan tahapan seleksi awal bagi calon pegawai negeri.",
            "D": "Mutasi dinas lintas kementerian merupakan mekanisme perpindahan posisi dan bukan dasar ikatan kerja."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[15] = {
    "pertanyaan": "Pihak yang memiliki wewenang sah untuk mengangkat Pegawai ASN (PNS dan PPPK) adalah...",
    "pilihan": {
        "A": "Pejabat Pembina Kepegawaian pada instansi bersangkutan",
        "B": "Pejabat yang Berwenang menetapkan kenaikan pangkat",
        "C": "Tim independen penilai kinerja aparatur sipil negara",
        "D": "Majelis pertimbangan kode etik dan perilaku birokrasi",
        "E": "Lembaga akreditasi penyelenggara pendidikan kedinasan"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Pegawai ASN terdiri dari PNS dan PPPK yang **diangkat oleh pejabat pembina kepegawaian**.",
        "alasan_kunci": "Opsi A merupakan otoritas kepegawaian yang memegang wewenang pengangkatan aparatur secara sah. Pejabat Pembina Kepegawaian (PPK) pada instansi pusat maupun daerah memiliki kewenangan atributif dalam menetapkan pengangkatan, pemindahan, dan pemberhentian pegawai ASN. Ketentuan hukum menetapkan bahwa pegawai ASN diangkat secara resmi oleh pejabat pembina kepegawaian. Oleh karena itu, opsi A merupakan jawaban yang benar.",
        "bedah_pengecoh": {
            "B": "Pejabat yang Berwenang (PyB) menjalankan fungsi administratif teknis kepegawaian di bawah mandat PPK.",
            "C": "Tim penilai kinerja bertugas memberikan pertimbangan evaluasi prestasi kerja dan bukan mengangkat pegawai.",
            "D": "Majelis etik bertugas menegakkan standar perilaku aparatur dan tidak memiliki wewenang pengangkatan.",
            "E": "Lembaga akreditasi bertugas menilai kelayakan program pelatihan dan bukan mengangkat pegawai aparatur."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[16] = {
    "pertanyaan": "Sesuai asas netralitas yang diatur dalam Pasal 9 UU Nomor 20 Tahun 2023, kewajiban pegawai ASN terhadap politik adalah...",
    "pilihan": {
        "A": "Menjadi anggota penasihat independen kebijakan partai",
        "B": "Bebas dari pengaruh dan intervensi semua partai politik",
        "C": "Menyalurkan aspirasi politik melalui fraksi perwakilan",
        "D": "Memfasilitasi kegiatan konsultasi publik bersama partai",
        "E": "Memberikan telaah teknis penyusunan program calon bupati"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Pegawai ASN harus **bebas dari pengaruh dan intervensi semua golongan dan partai politik**.",
        "alasan_kunci": "Opsi B merupakan rumusan prinsip netralitas aparatur sipil negara yang tepat. Netralitas menjamin birokrasi pemerintahan tetap fokus bekerja secara profesional, adil, dan tidak terkooptasi oleh kepentingan golongan partisan. Ketentuan Pasal 9 UU Nomor 20 Tahun 2023 menegaskan pegawai ASN wajib bebas dari pengaruh dan intervensi semua golongan dan partai politik. Dengan demikian, opsi B merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Menjadi penasihat kebijakan partai politik bertentangan dengan larangan keterlibatan partisan ASN.",
            "C": "Aparatur sipil negara dilarang memiliki keterikatan aspirasi dengan fraksi politik manapun.",
            "D": "Memfasilitasi kegiatan partai politik menyalahi asas imparsialitas pelayanan publik aparatur.",
            "E": "Penyusunan program calon kepala daerah merupakan kegiatan politik praktis yang dilarang bagi ASN."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[17] = {
    "pertanyaan": "Tujuan utama penyerahan tugas pemerintahan kepada Pegawai ASN adalah...",
    "pilihan": {
        "A": "Mewujudkan kepatuhan administratif pada tata kelola",
        "B": "Meningkatkan efisiensi pemanfaatan sarana dinas",
        "C": "Mencapai tujuan organisasi melalui kinerja akuntabel",
        "D": "Mengembangkan sinergi kerja sama antar-instansi negara",
        "E": "Mengoptimalkan standarisasi pelayanan publik modern"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Pegawai ASN diserahi tugas dalam suatu jabatan pemerintahan atau tugas negara lainnya untuk **mencapai tujuan organisasi**.",
        "alasan_kunci": "Opsi C merupakan esensi utama penyerahan mandat tugas pemerintahan kepada aparatur sipil negara. ASN diangkat dan diberikan wewenang kedinasan guna menggerakkan roda birokrasi demi mencapai sasaran kinerja dan tujuan instansi secara akuntabel. Tata aturan kepegawaian menegaskan bahwa penugasan jabatan aparatur diarahkan pada keberhasilan pencapaian target organisasi. Oleh karena itu, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Kepatuhan administratif merupakan sarana tata kelola instansi dan bukan tujuan puncak penugasan.",
            "B": "Efisiensi sarana dinas merupakan aspek manajerial logistik penunjang dan bukan tujuan pokok ASN.",
            "D": "Pengembangan sinergi kelembagaan merupakan metode pelaksanaan tugas dan bukan tujuan akhir organisasi.",
            "E": "Standarisasi pelayanan publik merupakan instrumen mutu operasional unit kerja pemerintahan."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[18] = {
    "pertanyaan": "Setiap Pegawai ASN yang diserahi tugas negara berhak memperoleh penghasilan yang bersumber dari...",
    "pilihan": {
        "A": "Alokasi hibah operasional kemitraan swasta",
        "B": "Penerimaan hasil kerja sama investasi daerah",
        "C": "Kebijakan diskresi anggaran internal pimpinan",
        "D": "Ketentuan resmi peraturan perundang-undangan",
        "E": "Dana taktis operasional unit pengelola kas"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Pegawai ASN yang diserahi tugas jabatan pemerintahan **diberikan penghasilan berdasarkan peraturan perundang-undangan** yang sah.",
        "alasan_kunci": "Opsi D merupakan dasar legalitas pemberian kompensasi keuangan bagi aparatur sipil negara yang sah. Hak keuangan pegawai bersumber dari kas negara yang dialokasikan dan dicairkan sesuai standar aturan hukum perbendaharaan. Ketentuan hukum menegaskan bahwa Pegawai ASN diberikan penghasilan resmi berdasarkan peraturan perundang-undangan. Dengan demikian, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Hibah operasional swasta bukan merupakan dasar hukum pemberian penghasilan bagi aparatur negara.",
            "B": "Hasil investasi daerah masuk ke kas daerah dan pembayarannya tetap tunduk pada perundang-undangan.",
            "C": "Diskresi anggaran pimpinan tanpa payung regulasi melanggar asas legalitas tata kelola keuangan negara.",
            "E": "Dana taktis operasional unit kas diperuntukkan bagi belanja kedinasan darurat dan bukan penghasilan."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[19] = {
    "pertanyaan": "Di lingkungan instansi pemerintah Republik Indonesia, Pegawai ASN dikelompokkan secara sah ke dalam...",
    "pilihan": {
        "A": "Pegawai Tetap Wilayah dan Pegawai Kementerian",
        "B": "Tenaga Fungsional Daerah dan Tenaga Khusus Staf",
        "C": "Staf Operasional Satker dan Tenaga Administrasi",
        "D": "Tenaga Manajerial Utama dan Pegawai Teknis Kas",
        "E": "Pegawai Negeri Sipil dan Pegawai Pemerintah PPPK"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Sesuai undang-undang, Pegawai ASN terdiri atas dua jenis status resmi: **Pegawai Negeri Sipil (PNS)** dan **Pegawai Pemerintah dengan Perjanjian Kerja (PPPK)**.",
        "alasan_kunci": "Opsi E merupakan klasifikasi yuridis kepegawaian yang sah dan diakui secara nasional dalam hukum kepegawaian. Transformasi manajemen aparatur sipil menyatukan status kepegawaian resmi pemerintah ke dalam dua kelompok aparatur negara. Ketentuan perundang-undangan menetapkan secara tegas bahwa Pegawai ASN terdiri atas PNS dan PPPK. Oleh karena itu, opsi E merupakan klasifikasi yang tepat.",
        "bedah_pengecoh": {
            "A": "Pengelompokan wilayah dan kementerian bukan merupakan nomenklatur resmi status pegawai ASN.",
            "B": "Tenaga khusus staf tidak membentuk kelompok tersendiri dalam payung yuridis pegawai aparatur sipil.",
            "C": "Staf operasional dan administrasi merupakan uraian rumpun penugasan dan bukan jenis status kepegawaian.",
            "D": "Manajerial utama dan pegawai teknis merupakan klasifikasi jabatan struktural dan bukan kelompok status ASN."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[20] = {
    "pertanyaan": "Syarat mutlak status kewarganegaraan untuk dapat diangkat menjadi Pegawai Negeri Sipil adalah...",
    "pilihan": {
        "A": "Warga Negara Indonesia yang memenuhi kriteria",
        "B": "Warga negara asing pemegang izin tinggal dinas",
        "C": "Mantan warga negara asing ahli bidang teknologi",
        "D": "Penduduk berstatus dwi-kewarganegaraan sah",
        "E": "Warga negara asing penerima beasiswa bilateral"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "PNS disyaratkan secara mutlak merupakan **warga negara Indonesia yang memenuhi syarat tertentu**.",
        "alasan_kunci": "Opsi A merupakan syarat kedaulatan utama dalam pengisian formasi birokrasi pemerintahan aparatur sipil. Pengabdian pada tugas kedinasan menuntut loyalitas dan kesetiaan tunggal kepada Negara Kesatuan Republik Indonesia. Ketentuan hukum menegaskan bahwa Pegawai Negeri Sipil adalah warga negara Indonesia yang memenuhi syarat tertentu. Dengan demikian, opsi A merupakan persyaratan yang benar.",
        "bedah_pengecoh": {
            "B": "Warga negara asing tidak diperkenankan menjadi pegawai tetap dalam birokrasi pemerintahan.",
            "C": "Keahlian teknologi tenaga luar negeri dapat diakomodasi melalui kerja sama tenaga ahli non-ASN.",
            "D": "Hukum ketatanegaraan Republik Indonesia melarang kepemilikan kewarganegaraan ganda bagi aparatur.",
            "E": "Penerima beasiswa luar negeri berstatus pemelajar dan tidak otomatis berhak menjadi pegawai negeri."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# ----------------- Halaman 10 (Soal 21, 22, 24, 25, 26, 27, 28) -----------------
fixes[21] = {
    "pertanyaan": "Berdasarkan Undang-Undang Nomor 20 Tahun 2023, jabatan dalam ASN secara garis besar terdiri atas...",
    "pilihan": {
        "A": "Jabatan Struktural Formal dan Jabatan Karier Teknis",
        "B": "Jabatan Eksekutif Daerah dan Jabatan Penasihat Dinas",
        "C": "Jabatan Manajerial dan Kelompok Jabatan Nonmanajerial",
        "D": "Jabatan Operasional Satker dan Jabatan Staf Penunjang",
        "E": "Jabatan Kepegawaian Madya dan Jabatan Pelaksana Teknis"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Sesuai undang-undang, **Jabatan dalam ASN terdiri atas 2 (dua) jabatan yaitu: Jabatan Manajerial dan Jabatan Nonmanajerial**.",
        "alasan_kunci": "Opsi C merupakan klasifikasi dua kelompok jabatan dalam regulasi ASN terbaru yang tepat. Pengelompokan ini membedakan posisi yang mengemban kepemimpinan unit organisasi dengan posisi yang berfokus pada keahlian profesional substantif. Ketentuan perundang-undangan menetapkan bahwa jabatan dalam ASN terdiri atas Jabatan Manajerial dan Jabatan Nonmanajerial. Oleh sebab itu, opsi C merupakan klasifikasi yang tepat.",
        "bedah_pengecoh": {
            "A": "Pengelompokan jabatan struktural formal dan karier teknis bukan pembagian utama undang-undang ASN.",
            "B": "Jabatan eksekutif daerah dan penasihat dinas bukan pembagian kelompok jabatan dalam undang-undang.",
            "D": "Jabatan operasional satker dan penunjang merupakan peran administratif kerja dan bukan klasifikasi resmi.",
            "E": "Kepegawaian madya dan pelaksana teknis merupakan perpaduan jenjang karier dan bukan pembagian pokok."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[22] = {
    "pertanyaan": "Sekelompok jabatan dalam birokrasi yang memiliki fungsi memimpin unit organisasi dan membawahkan pegawai langsung disebut...",
    "pilihan": {
        "A": "Kelompok Jabatan Fungsional",
        "B": "Kelompok Jabatan Pelaksana",
        "C": "Kelompok Jabatan Terampil",
        "D": "Kelompok Jabatan Manajerial",
        "E": "Kelompok Jabatan Penasihat"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "**Jabatan Manajerial adalah sekelompok jabatan yang memiliki fungsi memimpin unit organisasi** dan memiliki pegawai langsung di bawahnya.",
        "alasan_kunci": "Opsi D mendefinisikan kelompok jabatan manajerial secara tepat sesuai perundang-undangan. Jabatan ini memegang kendali kepemimpinan, pengawasan, serta pengoordinasian sumber daya manusia demi pencapaian target kinerja unit. Ketentuan hukum merumuskan bahwa kelompok jabatan yang berfungsi memimpin unit organisasi dan memiliki pegawai langsung di bawahnya adalah Jabatan Manajerial. Dengan demikian, opsi D merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Jabatan fungsional berfokus pada keahlian pelayanan teknis dan bukan fungsi memimpin unit organisasi.",
            "B": "Jabatan pelaksana bertanggung jawab atas tugas operasional rutin pelayanan tanpa membawahkan pegawai.",
            "C": "Jabatan terampil merupakan jenjang kualifikasi pada kelompok jabatan fungsional keterampilan.",
            "E": "Jabatan penasihat bertugas memberikan telaah pertimbangan keahlian tanpa hierarki kepemimpinan unit."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[24] = {
    "pertanyaan": "Jabatan Pimpinan Tinggi (JPT) sebagai manajerial tingkat tinggi diklasifikasikan ke dalam tiga jenjang yaitu...",
    "pilihan": {
        "A": "JPT Utama, JPT Madya, dan JPT Pratama",
        "B": "JPT Pembina, JPT Pengarah, dan JPT Pelaksana",
        "C": "JPT Penasihat, JPT Pengawas, dan JPT Penyelia",
        "D": "JPT Eksekutif, JPT Pemimpin, dan JPT Pengelola",
        "E": "JPT Wilayah, JPT Regional, dan JPT Teritorial"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "**Jabatan Pimpinan Tinggi terdiri atas JPT Utama, JPT Madya, dan JPT Pratama** yang merupakan jabatan manajerial tingkat tinggi.",
        "alasan_kunci": "Opsi A merupakan klasifikasi resmi tiga jenjang Jabatan Pimpinan Tinggi dalam sistem kepegawaian. Ketiga tingkatan ini mencakup posisi kepemimpinan puncak pada instansi pemerintah pusat maupun daerah. Ketentuan perundang-undangan menegaskan bahwa Jabatan Pimpinan Tinggi terdiri atas JPT Utama, JPT Madya, dan JPT Pratama. Dengan demikian, opsi A merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "B": "Pembina, pengarah, dan pelaksana merupakan fungsi koordinasi penugasan dan bukan jenjang resmi JPT.",
            "C": "Penasihat, pengawas, dan penyelia merupakan istilah pada jenjang jabatan administrasi dan fungsional.",
            "D": "Eksekutif, pemimpin, dan pengelola bukan nomenklatur yuridis tingkatan jabatan dalam undang-undang ASN.",
            "E": "Wilayah, regional, dan teritorial merupakan pembagian administratif wilayah dan bukan jenjang JPT."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[25] = {
    "pertanyaan": "Jabatan manajerial yang bertanggung jawab memimpin pelaksanaan seluruh kegiatan pelayanan publik serta administrasi pemerintahan adalah...",
    "pilihan": {
        "A": "Jabatan Fungsional Keahlian",
        "B": "Jabatan Administrator Dinas",
        "C": "Jabatan Pimpinan Tinggi Utama",
        "D": "Jabatan Pelaksana Teknis Staf",
        "E": "Jabatan Pengawas Wilayah Unit"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Merupakan Jabatan Manajerial yang **bertanggung jawab memimpin pelaksanaan seluruh kegiatan pelayanan publik serta administrasi pemerintahan dan pembangunan** adalah **Jabatan administrator**.",
        "alasan_kunci": "Opsi B merupakan jabatan manajerial yang memegang tanggung jawab manajerial tingkat menengah. Jabatan administrator memimpin pengelolaan program kerja operasional, penjaminan standar mutu layanan publik, serta administrasi pemerintahan. Ketentuan perundang-undangan menetapkan bahwa pemimpin pelaksanaan kegiatan pelayanan publik dan administrasi adalah Jabatan Administrator. Oleh karena itu, opsi B merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Jabatan fungsional keahlian berfokus pada penguasaan profesi teknis dan bukan memimpin administrasi umum.",
            "C": "Jabatan pimpinan tinggi utama bertanggung jawab pada arah kebijakan makro strategis nasional.",
            "D": "Jabatan pelaksana melaksanakan tugas teknis operasional harian di bawah instruksi pimpinan.",
            "E": "Jabatan pengawas bertanggung jawab mengendalikan pelaksanaan operasional yang dilakukan pelaksana."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[26] = {
    "pertanyaan": "Fungsi utama dari pejabat yang menduduki Jabatan Pengawas di lingkungan unit kerja instansi pemerintah adalah...",
    "pilihan": {
        "A": "Menetapkan arah kebijakan makro jangka panjang",
        "B": "Mengelola alokasi belanja operasional satuan kerja",
        "C": "Mengendalikan pelaksanaan kegiatan bawahan pelaksana",
        "D": "Mengoordinasikan penyusunan rencana strategis instansi",
        "E": "Mengembangkan sistem pelayanan digital dinas satuan"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Jabatan pengawas merupakan Jabatan Manajerial yang **bertanggung jawab mengendalikan pelaksanaan kegiatan yang dilakukan oleh pejabat pelaksana**.",
        "alasan_kunci": "Opsi C merupakan fungsi pengawasan manajerial lini depan dalam struktur birokrasi pemerintahan. Pejabat pengawas bertindak sebagai supervisor operasional yang memastikan tugas harian pejabat pelaksana berjalan sesuai prosedur dan target mutu. Ketentuan hukum menetapkan bahwa pejabat pengawas bertanggung jawab mengendalikan pelaksanaan kegiatan bawahan pelaksana. Dengan demikian, opsi C merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Menetapkan kebijakan makro merupakan wewenang strategis pimpinan puncak pada Jabatan Pimpinan Tinggi.",
            "B": "Mengelola anggaran belanja operasional satker merupakan wewenang kuasa pengguna anggaran dan PPK.",
            "D": "Mengoordinasikan rencana strategis merupakan peran manajerial pada jenjang Jabatan Administrator.",
            "E": "Mengembangkan sistem pelayanan digital merupakan fungsi fungsional keahlian pranata komputer."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[27] = {
    "pertanyaan": "Berdasarkan UU ASN terbaru, kelompok Jabatan Nonmanajerial terdiri atas...",
    "pilihan": {
        "A": "Jabatan Administrator dan Pengawas",
        "B": "Jabatan Penasihat Ahli dan Konsultan",
        "C": "Jabatan Pimpinan Tinggi dan Madya",
        "D": "Jabatan Fungsional dan Pelaksana",
        "E": "Jabatan Pegawai Tetap dan Kontrak"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "**Jabatan Nonmanajerial terdiri atas: a. Jabatan Fungsional; dan b. Jabatan Pelaksana**.",
        "alasan_kunci": "Opsi D merupakan klasifikasi resmi kelompok jabatan nonmanajerial dalam sistem kepegawaian aparatur sipil. Jabatan nonmanajerial berfokus pada penguasaan kompetensi profesional dan pelaksanaan layanan teknis tanpa struktur manajerial hierarki pimpinan. Ketentuan perundang-undangan menetapkan bahwa Jabatan Nonmanajerial terdiri atas Jabatan Fungsional dan Jabatan Pelaksana. Oleh sebab itu, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Jabatan administrator dan jabatan pengawas termasuk ke dalam kelompok Jabatan Manajerial.",
            "B": "Penasihat ahli dan konsultan bukan merupakan klasifikasi jabatan karier baku dalam undang-undang ASN.",
            "C": "Jabatan pimpinan tinggi dan madya merupakan tingkatan pada kelompok Jabatan Manajerial.",
            "E": "Pegawai tetap dan kontrak merupakan status kepegawaian dan bukan klasifikasi jabatan dalam ASN."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[28] = {
    "pertanyaan": "Dua rumpun jabatan yang tercakup dalam klasifikasi Jabatan Fungsional pada birokrasi pemerintahan adalah...",
    "pilihan": {
        "A": "Jabatan Manajerial dan Nonmanajerial",
        "B": "Jabatan Struktural dan Fungsional Umum",
        "C": "Jabatan Administrasi dan Teknis Satker",
        "D": "Jabatan Kehormatan dan Fungsional Luar",
        "E": "Jabatan Keahlian dan Jabatan Keterampilan"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Jabatan fungsional terdiri atas: **Jabatan Fungsional Keahlian** dan **Jabatan Fungsional Keterampilan**.",
        "alasan_kunci": "Opsi E merupakan dua rumpun spesialisasi jabatan fungsional yang baku dalam tata kelola kepegawaian negara. Pembagian ini didasarkan pada kualifikasi pendidikan formal akademis serta tuntutan keahlian teoritis dan praktis profesi. Ketentuan hukum kepegawaian menetapkan bahwa Jabatan Fungsional terdiri atas Jabatan Fungsional Keahlian dan Jabatan Fungsional Keterampilan. Dengan demikian, opsi E merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Manajerial dan nonmanajerial merupakan pembagian besar seluruh jabatan dalam ASN dan bukan sub-rumpun JF.",
            "B": "Jabatan fungsional umum merupakan istilah lama yang kini telah bertransformasi menjadi Jabatan Pelaksana.",
            "C": "Jabatan administrasi dan teknis satker bukan pembagian yuridis resmi rumpun jabatan fungsional.",
            "D": "Jabatan kehormatan dan fungsional luar bukan kategori jabatan dalam tata aturan kepegawaian ASN."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# ----------------- Halaman 11 (Soal 35, 36, 38) -----------------
fixes[35] = {
    "pertanyaan": "Seorang pejabat administrator ditugaskan menjalani tugas belajar selama 12 bulan. Konsekuensi terhadap jabatannya adalah...",
    "pilihan": {
        "A": "Tetap memangku jabatan definitif selama masa studi",
        "B": "Dialihkan menjadi pejabat fungsional keahlian madya",
        "C": "Diberhentikan dari jabatan administrasi yang diduduki",
        "D": "Diturunkan satu tingkat ke dalam jabatan pengawas dinas",
        "E": "Dibebaskan sementara dari tugas tanpa lepas jabatan"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "PNS **diberhentikan dari jabatan administrasi** apabila: **ditugaskan menjalani tugas belajar lebih dari 6 (enam) bulan**.",
        "alasan_kunci": "Opsi C merupakan akibat hukum penugasan tugas belajar jangka panjang bagi pemangku jabatan administrasi. Batas waktu toleransi memegang jabatan definitif saat menjalani tugas belajar adalah maksimal 6 bulan guna menjaga efektivitas organisasi. Karena tugas belajar berlangsung selama 12 bulan, pejabat tersebut secara hukum wajib diberhentikan dari jabatan administrasi yang diembannya. Oleh sebab itu, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Memangku jabatan definitif tidak diperbolehkan apabila penugasan tugas belajar melampaui 6 bulan.",
            "B": "Pengalihan ke jabatan fungsional memerlukan uji kompetensi serta formasi dan tidak terjadi otomatis.",
            "D": "Penurunan tingkat jabatan merupakan jenis sanksi disiplin berat dan bukan dampak tugas belajar.",
            "E": "Pembebasan tugas tanpa pelepasan jabatan hanya berlaku untuk tugas belajar singkat di bawah 6 bulan."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[36] = {
    "pertanyaan": "Salah satu persyaratan administratif mutlak untuk dapat diangkat dalam Jabatan Pengawas adalah memiliki pengalaman pada jabatan pelaksana paling singkat...",
    "pilihan": {
        "A": "1 tahun",
        "B": "2 tahun",
        "C": "3 tahun",
        "D": "4 tahun",
        "E": "5 tahun"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Syarat pengangkatan jabatan pengawas antara lain: **memiliki pengalaman pada Jabatan pelaksana paling singkat 4 (empat) tahun** atau JF yang setingkat.",
        "alasan_kunci": "Opsi D merupakan masa pengalaman kerja minimal yang dipersyaratkan regulasi kepegawaian bagi calon pengawas. Durasi pengalaman 4 tahun pada jabatan pelaksana dipandang mencukupi untuk menguasai prosedur teknis operasional sebelum memimpin pengawasan unit. Ketentuan peraturan pemerintah menetapkan syarat pengalaman pada jabatan pelaksana paling singkat 4 tahun. Dengan demikian, opsi D merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Masa kerja satu tahun belum memenuhi batas pengalaman minimal yang disyaratkan untuk jabatan pengawas.",
            "B": "Masa kerja dua tahun belum mencukupi standar pengalaman operasional bagi promosi pengawas.",
            "C": "Masa kerja tiga tahun merupakan syarat pengalaman pada jabatan administrator dan bukan pengawas.",
            "E": "Masa kerja lima tahun melampaui batas minimal formal yang ditentukan dalam regulasi manajemen PNS."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[38] = {
    "pertanyaan": "Akuntabilitas jabatan yang wajib dipenuhi oleh seorang pejabat administrator di lingkungan birokrasi pemerintahan adalah...",
    "pilihan": {
        "A": "Perencanaan kegiatan efisien dan peningkatan kinerja berkesinambungan",
        "B": "Pengendalian langsung kegiatan operasional pelaksana sesuai prosedur",
        "C": "Pelaksanaan tugas teknis pelayanan harian sesuai standar operasional",
        "D": "Penetapan arah kebijakan strategis makro kepegawaian instansi pusat",
        "E": "Pengawasan independen penegakan meritokrasi pada kementerian lembaga"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Akuntabilitas **Jabatan administrator** adalah **seluruh kegiatan yang sudah direncanakan dengan baik dan efisien sesuai standar operasional prosedur dan terselenggaranya peningkatan kinerja secara berkesinambungan**.",
        "alasan_kunci": "Opsi A merupakan rumusan akuntabilitas jabatan bagi jenjang administrator dalam birokrasi. Pejabat administrator memikul tanggung jawab menjamin efisiensi pelaksanaan rencana program kerja dan memacu peningkatan kinerja organisasi berkelanjutan. Ketentuan perundang-undangan menetapkan akuntabilitas administrator mencakup terlaksananya kegiatan efisien dan peningkatan kinerja berkesinambungan. Oleh karena itu, opsi A merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "B": "Pengendalian langsung kegiatan operasional pelaksana merupakan akuntabilitas Jabatan Pengawas.",
            "C": "Pelaksanaan tugas teknis pelayanan harian merupakan wujud akuntabilitas Jabatan Pelaksana.",
            "D": "Penetapan arah kebijakan strategis makro kepegawaian merupakan ranah Jabatan Pimpinan Tinggi.",
            "E": "Pengawasan penegakan meritokrasi merupakan kewenangan kementerian yang membidangi aparatur negara."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# ----------------- Halaman 12 (Soal 41, 47, 49) -----------------
fixes[41] = {
    "pertanyaan": "Jenjang Jabatan Fungsional Keahlian dari tingkatan tertinggi hingga tingkatan terendah secara berurutan adalah...",
    "pilihan": {
        "A": "Penyelia, Mahir, Terampil, serta Pemula",
        "B": "Utama, Madya, Muda, serta Administrator",
        "C": "Pembina Utama, Penata, Pengatur, dan Juru",
        "D": "Pengawas, Administrator, Pratama, dan Madya",
        "E": "Ahli Utama, Ahli Madya, Ahli Muda, dan Pertama"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "**Jabatan fungsional keahlian** terdiri atas **Ahli utama**, **Ahli madya**, **Ahli muda**, dan **Ahli pertama**.",
        "alasan_kunci": "Opsi E merupakan susunan hierarkis jenjang Jabatan Fungsional Keahlian dari strata tertinggi ke strata terendah. Keempat jenjang ini mencerminkan gradasi kualifikasi akademis, tingkat kesulitan mandat tugas, dan kemandirian profesi aparatur. Ketentuan hukum kepegawaian menetapkan jenjang keahlian terdiri atas Ahli Utama, Ahli Madya, Ahli Muda, dan Ahli Pertama. Dengan demikian, opsi E merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Penyelia, Mahir, Terampil, dan Pemula merupakan hierarki pada Jabatan Fungsional Keterampilan.",
            "B": "Mencampurkan nomenklatur pimpinan dan administrator ke dalam formasi jabatan fungsional.",
            "C": "Merupakan susunan pangkat dan golongan ruang kepangkatan PNS tradisional masa lampau.",
            "D": "Merupakan perpaduan jenjang jabatan administrasi dan kelompok jabatan manajerial tinggi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[47] = {
    "pertanyaan": "Kelompok Jabatan Pelaksana pada instansi pemerintah memiliki tanggung jawab utama dalam memberikan pelayanan serta...",
    "pilihan": {
        "A": "Melaksanakan pekerjaan operasional rutin dan sederhana",
        "B": "Mengendalikan program kerja yang dipimpin administrator",
        "C": "Menetapkan arah kebijakan teknis instansi secara mandiri",
        "D": "Merumuskan perencanaan strategis penataan kelembagaan",
        "E": "Mengoordinasikan pengawasan sistem merit aparatur sipil"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "**Jabatan Pelaksana** bertanggung jawab **memberikan pelayanan dan melaksanakan pekerjaan yang bersifat rutin dan sederhana**.",
        "alasan_kunci": "Opsi A merupakan ruang lingkup tanggung jawab utama bagi pemangku Jabatan Pelaksana. Jabatan nonmanajerial ini dirancang untuk memastikan operasional harian birokrasi berjalan lancar melalui penyelesaian tugas administratif rutin dan pelayanan sederhana. Ketentuan hukum kepegawaian menetapkan Jabatan Pelaksana bertanggung jawab memberikan pelayanan dan melaksanakan pekerjaan rutin dan sederhana. Oleh sebab itu, opsi A merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "B": "Pengendalian pelaksanaan program kerja operasional merupakan tanggung jawab Jabatan Pengawas.",
            "C": "Penetapan kebijakan teknis mandiri berbasis keahlian merupakan domain Jabatan Fungsional.",
            "D": "Perumusan perencanaan strategis kelembagaan merupakan mandat Jabatan Pimpinan Tinggi dan Administrator.",
            "E": "Koordinasi pengawasan penerapan sistem merit merupakan wewenang Kementerian PAN-RB."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[49] = {
    "pertanyaan": "Penguasaan keahlian atau keterampilan tertentu pada Jabatan Fungsional wajib dibuktikan oleh aparatur melalui...",
    "pilihan": {
        "A": "Penilaian kepatuhan disiplin kehadiran dari pimpinan",
        "B": "Hasil evaluasi akuntabilitas kinerja instansi tahunan",
        "C": "Sertifikasi keahlian dan/atau penilaian kompetensi",
        "D": "Surat rekomendasi penugasan dinas dari tim penilai",
        "E": "Bukti kelulusan pelatihan kepemimpinan pengawas unit"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Kriteria penetapan Jabatan Fungsional mensyaratkan **keahlian atau keterampilan tertentu yang dibuktikan dengan sertifikasi dan/atau penilaian tertentu**.",
        "alasan_kunci": "Opsi C merupakan instrumen pembuktian kompetensi formal bagi pejabat fungsional dalam birokrasi. Penempatan aparatur ke dalam rumpun jabatan berbasis keahlian menuntut legitimasi baku atas kemahiran teknis yang dikuasai. Ketentuan regulasi menetapkan bahwa penguasaan keahlian atau keterampilan wajib dibuktikan melalui sertifikasi dan/atau penilaian tertentu. Oleh karena itu, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Disiplin kehadiran merupakan kewajiban umum seluruh pegawai dan bukan bukti kepakaran fungsional.",
            "B": "Evaluasi AKIP mengukur kinerja kelembagaan instansi secara makro dan bukan kompetensi personal pegawai.",
            "D": "Surat rekomendasi merupakan kelengkapan berkas administratif dan bukan uji sertifikasi kompetensi.",
            "E": "Pelatihan kepemimpinan pengawas diperuntukkan bagi jalur manajerial dan bukan sertifikasi fungsional."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# ----------------- Halaman 13 (Soal 60) -----------------
fixes[60] = {
    "pertanyaan": "Kebijakan penyetaraan jabatan administrasi ke dalam jabatan fungsional dilaksanakan pemerintah dengan tujuan utama untuk...",
    "pilihan": {
        "A": "Mengembangkan kapasitas kepemimpinan struktural madya",
        "B": "Memperkuat standarisasi pembagian urusan pemerintahan",
        "C": "Mengoptimalkan pemenuhan target pembentukan formasi",
        "D": "Mewujudkan tata kelola birokrasi yang lincah dan efektif",
        "E": "Menyeragamkan pola penganggaran belanja satuan kerja"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Penyetaraan jabatan dilakukan dalam hal **diperlukan penataan birokrasi** penyesuaian jabatan ke dalam Jabatan Fungsional.",
        "alasan_kunci": "Opsi D merupakan esensi mendasar dari agenda penyetaraan jabatan dalam reformasi birokrasi nasional. Transformasi jabatan administrasi ke jabatan fungsional memangkas hierarki berlapis agar pengambilan keputusan berlangsung cepat, adaptif, dan profesional. Peraturan perundang-undangan menetapkan penyetaraan jabatan ditujukan bagi kebutuhan penataan birokrasi yang lebih lincah dan berdaya guna. Dengan demikian, opsi D merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Penyetaraan jabatan mengalihkan peran struktural menjadi peran fungsional berbasis kepakaran teknis.",
            "B": "Pembagian urusan pemerintahan diatur dalam undang-undang pemda dan bukan tujuan penyetaraan jabatan.",
            "C": "Pemenuhan formasi merupakan instrumen perencanaan pegawai dan bukan sasaran pokok penyederhanaan birokrasi.",
            "E": "Pola penganggaran belanja diatur dalam regulasi perbendaharaan dan bukan fokus transformasi jabatan."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# ----------------- Halaman 15 (Soal 61, 63, 64, 65, 67, 70) -----------------
fixes[61] = {
    "pertanyaan": "Berdasarkan ketentuan UU ASN, Pegawai ASN berhak memperoleh penghargaan dan pengakuan dalam bentuk...",
    "pilihan": {
        "A": "Pengelolaan modal usaha dan kepemilikan aset sarana",
        "B": "Penghargaan dan pengakuan materiel dan/atau nonmateriel",
        "C": "Dispensasi kewajiban pemeriksaan akuntabilitas dinas",
        "D": "Hak penentuan formasi promosi tanpa seleksi kompetensi",
        "E": "Fasilitas anggaran operasional privat di luar pagu"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Pegawai ASN berhak memperoleh **penghargaan dan pengakuan berupa materiel dan/atau nonmateriel**.",
        "alasan_kunci": "Opsi B merupakan rumusan payung hak bagi seluruh pegawai aparatur sipil negara dalam undang-undang terbaru. Ketentuan ini menyatukan beragam hak kesejahteraan ke dalam klasifikasi penghargaan berwujud materiel (seperti penghasilan) dan nonmateriel (seperti lingkungan kerja dan motivasi). Ketentuan hukum menetapkan Pegawai ASN berhak memperoleh penghargaan dan pengakuan materiel dan/atau nonmateriel. Oleh sebab itu, opsi B merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Aset sarana dinas merupakan Barang Milik Negara yang dilarang dialihkan menjadi kepemilikan privat.",
            "C": "Pegawai ASN wajib tunduk pada asas akuntabilitas hukum dan audit keuangan tanpa dispensasi kekebalan.",
            "D": "Pengisian formasi jabatan wajib berlandaskan sistem merit serta uji kualifikasi dan kompetensi terbuka.",
            "E": "Seluruh fasilitas keuangan aparatur bersumber dari APBN/APBD dan dilarang melebihi pagu resmi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[63] = {
    "pertanyaan": "Seorang pegawai ASN menghadapi permasalahan hukum terkait pelaksanaan tugas resmi kedinasannya. Hak yang wajib difasilitasi pemerintah adalah...",
    "pilihan": {
        "A": "Bantuan hukum dalam perkara pelaksanaan tugas kedinasan",
        "B": "Fasilitasi mediasi perdamaian di luar instansi peradilan",
        "C": "Penugasan tim advokat privat dengan pembiayaan mandiri",
        "D": "Pemberian dana ganti rugi perkara secara sepihak langsung",
        "E": "Perlindungan administratif mutasi kilat antar-kementerian"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Salah satu komponen penghargaan dan pengakuan Pegawai ASN butir ke-7 adalah hak memperoleh **Bantuan hukum**.",
        "alasan_kunci": "Opsi A merupakan bentuk perlindungan hukum yang wajib disediakan negara bagi para abdi negara. Perlindungan ini menjamin rasa aman bagi aparatur dalam menjalankan kewenangan dan kebijakan kedinasan yang sah dari risiko kriminalisasi. Ketentuan undang-undang secara tegas mencantumkan hak memperoleh bantuan hukum bagi pegawai ASN dalam menjalankan tugas dinas. Dengan demikian, opsi A merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "B": "Proses peradilan perkara kedinasan tetap berjalan sesuai hukum acara yang berlaku tanpa mediasi liar.",
            "C": "Bantuan hukum kedinasan disediakan oleh pemerintah melalui biro hukum instansi, bukan pembiayaan mandiri.",
            "D": "Pembayaran ganti rugi keuangan negara wajib melalui mekanisme putusan pengadilan yang berkekuatan hukum.",
            "E": "Mutasi kepegawaian bukan instrumen perlindungan yuridis atas sengketa hukum yang sedang berlangsung."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[64] = {
    "pertanyaan": "Kewajiban pertama dan mendasar bagi setiap Pegawai ASN sebagaimana dirumuskan dalam ketentuan perundang-undangan adalah...",
    "pilihan": {
        "A": "Menyelenggarakan pembinaan kemasyarakatan di wilayah",
        "B": "Menyusun laporan kinerja instansi pada akhir triwulan",
        "C": "Setia dan taat pada Pancasila, UUD 1945, NKRI, dan pemerintah",
        "D": "Mengikuti uji sertifikasi profesi internasional mandiri",
        "E": "Mengembangkan sistem pelayanan digital terpadu modern"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Kewajiban ASN butir pertama adalah **setia dan taat kepada Pancasila, UUD Tahun 1945, NKRI, dan pemerintah yang sah**.",
        "alasan_kunci": "Opsi C merupakan fondasi utama komitmen kebangsaan yang wajib dipegang teguh oleh setiap aparatur sipil negara. Kesetiaan pada ideologi negara, konstitusi, kedaulatan wilayah, serta legitimasi pemerintah menjadi syarat mutlak penyelenggaraan pelayanan publik. Ketentuan hukum kepegawaian menempatkan kewajiban setia dan taat kepada Pancasila, UUD 1945, NKRI, dan pemerintah sah pada urutan pertama. Oleh sebab itu, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Pembinaan kemasyarakatan merupakan peran sosial aparatur dan bukan rumusan butir kewajiban pokok pertama.",
            "B": "Penyusunan laporan kinerja berkala merupakan kewajiban teknis administratif akuntabilitas satuan kerja.",
            "D": "Uji sertifikasi keahlian merupakan bagian dari pengembangan kompetensi profesional individu pegawai.",
            "E": "Pengembangan teknologi pelayanan merupakan sasaran inovasi tata kelola unit kerja kepegawaian."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[65] = {
    "pertanyaan": "Dalam penyelenggaraan tugas pemerintahan, asas legalitas aparatur sipil negara diwujudkan melalui kewajiban untuk...",
    "pilihan": {
        "A": "Melaksanakan nilai dasar serta kode etik dan perilaku",
        "B": "Menjaga netralitas aparatur dari pengaruh kepentingan",
        "C": "Bersedia ditempatkan di seluruh wilayah kedaulatan negara",
        "D": "Setia dan taat kepada Pancasila dan Undang-Undang Dasar",
        "E": "Menaati seluruh ketentuan peraturan perundang-undangan"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Kewajiban ASN butir kedua adalah **menaati ketentuan peraturan perundang-undangan**.",
        "alasan_kunci": "Opsi E merupakan perwujudan asas legalitas dan tertib hukum dalam seluruh penyelenggaraan administrasi birokrasi pemerintahan. Setiap tindakan, keputusan, dan pelayanan yang diambil pegawai ASN wajib bersandar pada koridor norma hukum yang berlaku sah. Ketentuan perundang-undangan menetapkan kewajiban butir kedua ASN adalah menaati ketentuan peraturan perundang-undangan. Oleh karena itu, opsi E merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Melaksanakan nilai dasar dan kode etik merupakan kewajiban ASN pada butir ketiga ketentuan undang-undang.",
            "B": "Menjaga netralitas aparatur merupakan kewajiban ASN pada butir keempat ketentuan undang-undang.",
            "C": "Kesediaan penempatan di seluruh wilayah negara merupakan kewajiban ASN pada butir kelima undang-undang.",
            "D": "Setia dan taat kepada Pancasila dan konstitusi merupakan kewajiban ASN butir pertama undang-undang."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[67] = {
    "pertanyaan": "Butir kewajiban aparatur yang ditegaskan guna menjamin kemandirian dan keadilan pelayanan dari intervensi politik adalah...",
    "pilihan": {
        "A": "Setia dan taat kepada ideologi negara dan pemerintah",
        "B": "Menaati seluruh ketentuan peraturan perundang-undangan",
        "C": "Melaksanakan nilai dasar serta kode etik profesi aparatur",
        "D": "Menjaga netralitas aparatur dari pengaruh kepentingan",
        "E": "Bersedia ditempatkan di seluruh wilayah kedaulatan dinas"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Kewajiban ASN butir keempat adalah **menjaga netralitas**.",
        "alasan_kunci": "Opsi D merupakan butir kewajiban pokok aparatur yang melindungi birokrasi dari polarisasi dan kepentingan politik praktis. Asas netralitas memastikan bahwa alokasi anggaran, pembuatan kebijakan, dan pelayanan masyarakat dieksekusi secara adil, objektif, dan profesional. Ketentuan undang-undang menetapkan kewajiban aparatur sipil negara untuk senantiasa menjaga netralitas. Dengan demikian, opsi D merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Kewajiban setia dan taat berfokus pada loyalitas konsensus kebangsaan dan kedaulatan NKRI.",
            "B": "Kewajiban menaati peraturan perundang-undangan berfokus pada penegakan asas legalitas tindakan dinas.",
            "C": "Kewajiban nilai dasar dan kode etik berfokus pada standar moralitas dan etika profesi pegawai.",
            "E": "Kewajiban bersedia ditempatkan berfokus pada distribusi pemerataan pelayanan publik di Nusantara."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[70] = {
    "pertanyaan": "Secara keseluruhan, jumlah butir kewajiban pokok Aparatur Sipil Negara yang dirumuskan dalam ketentuan UU ASN terdiri atas...",
    "pilihan": {
        "A": "Tiga butir kewajiban pokok aparatur",
        "B": "Empat butir kewajiban pokok aparatur",
        "C": "Enam butir kewajiban pokok aparatur",
        "D": "Tujuh butir kewajiban pokok aparatur",
        "E": "Lima butir kewajiban pokok aparatur"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "**Kewajiban ASN terdiri atas 5 butir**: 1. Setia pada Pancasila, UUD 1945, NKRI, dan pemerintah sah; 2. Menaati perundang-undangan; 3. Melaksanakan nilai dasar dan kode etik/perilaku; 4. Menjaga netralitas; 5. Bersedia ditempatkan di seluruh NKRI dan luar negeri.",
        "alasan_kunci": "Opsi E merupakan jumlah butir kewajiban pokok pegawai aparatur sipil negara yang ditetapkan secara limitatif dalam undang-undang kepegawaian. Kelima butir kewajiban ini mengikat seluruh ASN dalam menjalankan tugas kenegaraan dan pelayanan publik. Ketentuan regulasi merumuskan secara berurutan lima butir kewajiban pokok bagi seluruh pegawai ASN. Oleh sebab itu, opsi E merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Tiga butir merupakan jumlah kategori kompetensi jabatan aparatur (teknis, manajerial, sosio-kultural).",
            "B": "Empat butir merupakan jumlah konsensus dasar berbangsa dan bernegara Republik Indonesia.",
            "C": "Enam butir bukan merupakan pembagian baku angka kewajiban maupun hak pegawai dalam regulasi ASN.",
            "D": "Tujuh butir merupakan jumlah komponen hak penghargaan dan pengakuan bagi Pegawai ASN."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# ----------------- Halaman 17 (Soal 71, 73, 74, 77, 79) -----------------
fixes[71] = {
    "pertanyaan": "Pemegang kekuasaan pemerintahan tertinggi dalam kebijakan, pembinaan profesi, dan Manajemen ASN adalah...",
    "pilihan": {
        "A": "Menteri Pendayagunaan Aparatur Negara Birokrasi",
        "B": "Kepala Badan Kepegawaian Negara Republik Indonesia",
        "C": "Presiden Republik Indonesia selaku kepala negara",
        "D": "Ketua Dewan Perwakilan Rakyat Republik Indonesia",
        "E": "Ketua Komisi Aparatur Sipil Negara Indonesia"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "**Presiden merupakan pemegang kekuasaan pemerintahan tertinggi** dalam **kebijakan, pembinaan profesi, dan Manajemen Aparatur Sipil Negara (ASN)**.",
        "alasan_kunci": "Opsi C merupakan pemegang kekuasaan eksekutif tertinggi negara sesuai konstitusi Republik Indonesia. Seluruh kewenangan pembinaan karier, penetapan kebijakan makro kepegawaian, serta pengawasan manajemen ASN secara nasional berakar pada mandat kekuasaan Presiden. Ketentuan undang-undang menegaskan Presiden memegang kekuasaan tertinggi dalam kebijakan, pembinaan profesi, dan manajemen ASN. Dengan demikian, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Menteri PAN-RB menyelenggarakan kewenangan kepegawaian berdasarkan mandat pendelegasian Presiden.",
            "B": "Kepala BKN melaksanakan penyelenggaraan teknis operasional dan pengawasan manajemen ASN secara nasional.",
            "D": "Ketua DPR memegang mandat kekuasaan legislasi dan pengawasan politik di tingkat perwakilan rakyat.",
            "E": "Komisi Aparatur Sipil Negara menjalankan fungsi pengawasan nilai dasar dan sistem merit aparatur."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[73] = {
    "pertanyaan": "Pendelegasian kewenangan dari Presiden kepada Kementerian PAN-RB berkaitan erat dengan tiga mandat utama yaitu...",
    "pilihan": {
        "A": "Perencanaan anggaran, audit kepatuhan kas, dan pengadaan",
        "B": "Perumusan kebijakan, koordinasi sinkronisasi, dan kendali",
        "C": "Pengawasan hukum pidana, penindakan dinas, dan sidang",
        "D": "Penyelenggaraan sekolah kedinasan, vokasi, dan sertifikasi",
        "E": "Pengelolaan data base digital, mutasi, dan pensiun pegawai"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Pendelegasian tersebut berkaitan dengan kewenangan **perumusan dan penetapan kebijakan strategis**, **koordinasi dan sinkronisasi kebijakan**, dan **pengendalian kebijakan Manajemen ASN secara nasional**.",
        "alasan_kunci": "Opsi B merupakan ruang lingkup pilar pendelegasian wewenang kelembagaan dari Presiden kepada Kementerian PAN-RB. Tiga pilar ini mencakup fungsi perumusan norma strategis, penyelarasan lintas kementerian/lembaga/pemda, serta pengendalian evaluasi implementasi kebijakan ASN nasional. Ketentuan perundang-undangan merumuskan tiga mandat pendelegasian mencakup perumusan kebijakan, koordinasi sinkronisasi, dan pengendalian manajemen ASN. Oleh karena itu, opsi B merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Perencanaan anggaran dan audit perbendaharaan merupakan domain kewenangan Kementerian Keuangan dan BPK.",
            "Bukan": "Pilihan B adalah kunci jawaban yang benar.",
            "C": "Pengawasan pidana dan persidangan merupakan kompetensi aparat penegak hukum dan lembaga yudisial.",
            "D": "Penyelenggaraan diklat kedinasan teknis dilaksanakan oleh Lembaga Administrasi Negara dan instansi pembina.",
            "E": "Pengelolaan basis data kepegawaian dan mutasi operasional merupakan tugas teknis operasional BKN."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}
if 'Bukan' in fixes[73]['pembahasan']['bedah_pengecoh']:
    del fixes[73]['pembahasan']['bedah_pengecoh']['Bukan']

fixes[74] = {
    "pertanyaan": "Dalam penyelenggaraan Manajemen ASN, Kementerian PAN-RB mengoordinasikan rencana kerja lembaga serta melakukan...",
    "pilihan": {
        "A": "Pengawasan transaksi rekening operasional bendahara kas",
        "B": "Penetapan alokasi belanja modal kementerian secara mutlak",
        "C": "Penyelenggaraan pengadaan aset gedung kantor dinas daerah",
        "D": "Sinkronisasi pengendalian tugas pembelajaran pegawai ASN",
        "E": "Pemeriksaan laporan harta kekayaan aparatur negara satker"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Kementerian PAN-RB bertugas melakukan **sinkronisasi pengendalian tugas dan fungsi pengembangan kapasitas dan pembelajaran ASN**.",
        "alasan_kunci": "Opsi D merupakan wewenang substantif Kementerian PAN-RB dalam memastikan program pembelajaran aparatur berjalan terintegrasi secara nasional. Harmonisasi ini penting agar pengembangan kompetensi pegawai sejalan dengan arah prioritas pembangunan dan reformasi birokrasi. Ketentuan perundang-undangan menetapkan fungsi kementerian mencakup sinkronisasi pengendalian tugas dan fungsi pengembangan kapasitas ASN. Oleh sebab itu, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Pengawasan rekening kas bendahara merupakan kewenangan Direktorat Jenderal Perbendaharaan Kemenkeu.",
            "B": "Penetapan pagu belanja modal kementerian merupakan kewenangan penganggaran Kemenkeu dan Bappenas.",
            "C": "Pengadaan aset fisik gedung kantor merupakan wewenang Pengguna Barang pada instansi masing-masing.",
            "E": "Pemeriksaan kepatuhan laporan harta kekayaan penyelenggara negara merupakan mandat lembaga anti-korupsi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[77] = {
    "pertanyaan": "Selain menetapkan kebijakan strategis makro, Kementerian PAN-RB bertugas melakukan pengendalian atas...",
    "pilihan": {
        "A": "Alokasi besaran pagu indikatif belanja pegawai dinas",
        "B": "Penetapan standar tarif penerimaan negara bukan pajak",
        "C": "Mekanisme penyelesaian sengketa tata usaha pengadilan",
        "D": "Penyusunan kurikulum teknis pendidikan vokasi instansi",
        "E": "Pelaksanaan kebijakan teknis operasional Manajemen ASN"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Kementerian PAN-RB juga melakukan **pengendalian pelaksanaan kebijakan teknis Manajemen ASN**.",
        "alasan_kunci": "Opsi E merupakan fungsi pengawasan eksekutif Kementerian PAN-RB dalam memastikan implementasi norma kepegawaian berjalan efektif di lapangan. Pengendalian teknis operasional menjamin tidak terjadinya deviasi dalam pelaksanaan manajemen aparatur di instansi pusat maupun daerah. Ketentuan hukum menegaskan Kementerian PAN-RB bertugas mengendalikan pelaksanaan kebijakan teknis Manajemen ASN. Dengan demikian, opsi E merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Penetapan alokasi pagu belanja kepegawaian merupakan wewenang Menteri Keuangan dalam penyusunan APBN.",
            "B": "Standar tarif PNBP diatur melalui peraturan pemerintah atas prakarsa kementerian teknis dan Kemenkeu.",
            "C": "Penyelesaian sengketa administrasi kepegawaian merupakan ranah yurisdiksi PTUN dan Badan Pertimbangan ASN.",
            "D": "Kurikulum teknis diklat dirancang oleh instansi pembina jabatan fungsional bersama LAN."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[79] = {
    "pertanyaan": "Tiga aspek penyelenggaraan aparatur yang menjadi kewenangan tertinggi Presiden selaku kepala pemerintahan meliputi...",
    "pilihan": {
        "A": "Pengawasan internal, penuntutan pidana, dan penegakan",
        "B": "Perencanaan tata ruang, pembangunan fisik, dan perizinan",
        "C": "Penetapan tarif fiskal, peredaran moneter, dan devisa",
        "D": "Kebijakan, pembinaan profesi, dan Manajemen ASN terpadu",
        "E": "Pengadaan logistik, pertahanan maritim, dan pangan"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Presiden merupakan pemegang kekuasaan pemerintahan tertinggi dalam **kebijakan, pembinaan profesi, dan Manajemen Aparatur Sipil Negara (ASN)**.",
        "alasan_kunci": "Opsi D merupakan tritunggal kekuasaan kepegawaian Presiden Republik Indonesia dalam ranah eksekutif pemerintahan. Aspek ini mencakup kewenangan menetapkan arah kebijakan makro, membina integritas korps profesi, serta memimpin tata kelola manajemen ASN. Ketentuan undang-undang menegaskan mandat tertinggi Presiden mencakup kebijakan, pembinaan profesi, dan Manajemen ASN. Oleh karena itu, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Penuntutan pidana dan peradilan merupakan fungsi lembaga kejaksaan dan kekuasaan kehakiman.",
            "B": "Tata ruang dan izin pembangunan fisik merupakan tugas kementerian agraria dan pekerjaan umum.",
            "C": "Kebijakan moneter dan devisa merupakan mandat konstitusional bank sentral Bank Indonesia.",
            "E": "Pertahanan maritim dan logistik pangan merupakan tugas kementerian pertahanan dan kementerian pertanian."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# ----------------- Halaman 19 (Soal 82, 87, 90) -----------------
fixes[82] = {
    "pertanyaan": "Berdasarkan ketentuan UU ASN, ruang lingkup Manajemen Aparatur Sipil Negara secara komprehensif mencakup...",
    "pilihan": {
        "A": "Manajemen Pegawai Tetap dan Manajemen Pegawai Magang",
        "B": "Manajemen Aparatur Sipil dan Manajemen Personel Militer",
        "C": "Manajemen Tenaga Alih Daya dan Staf Pendukung Lembaga",
        "D": "Manajemen Pegawai Negeri Sipil dan Manajemen PPPK Sah",
        "E": "Manajemen Tenaga Kontrak dan Tenaga Ahli Independen"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "**Manajemen ASN** meliputi **Manajemen Pegawai Negeri Sipil (PNS)** dan **Manajemen Pegawai Pemerintah dengan Perjanjian Kerja (PPPK)**.",
        "alasan_kunci": "Opsi D merupakan cakupan komprehensif tata kelola kepegawaian aparatur sipil negara yang ditetapkan undang-undang. Pilar profesi ASN secara legalitas hanya menaungi dua entitas pegawai pemerintah, yaitu PNS dan PPPK. Ketentuan perundang-undangan menetapkan secara tegas bahwa Manajemen ASN meliputi Manajemen PNS dan Manajemen PPPK. Oleh sebab itu, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Pegawai magang bukan bagian dari profesi aparatur sipil negara sehingga tidak masuk lingkup manajemen ASN.",
            "B": "Personel militer tunduk pada undang-undang pertahanan tentara nasional di luar manajemen ASN umum.",
            "C": "Tenaga alih daya berstatus pekerja pihak ketiga vendor dan tidak termasuk dalam sub-manajemen ASN.",
            "E": "Tenaga ahli independen terikat perjanjian perdata dan berada di luar kerangka Manajemen ASN."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[87] = {
    "pertanyaan": "Penyelenggaraan Manajemen Pegawai Negeri Sipil pada unit instansi daerah secara sah dilaksanakan oleh...",
    "pilihan": {
        "A": "Pemerintah daerah yang bersangkutan langsung",
        "B": "Kantor perwakilan kementerian vertikal daerah",
        "C": "Dewan pengawas independen tata kelola daerah",
        "D": "Komando kewilayahan aparat teritorial dinas",
        "E": "Kamar dagang industri perwakilan wilayah kota"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "**Manajemen PNS pada Instansi Daerah dilaksanakan oleh pemerintah daerah**.",
        "alasan_kunci": "Opsi A merupakan pelaksana sah manajemen kepegawaian bagi PNS di tingkat daerah provinsi maupun kabupaten/kota. Kewenangan ini didelegasikan kepada pemerintah daerah dengan kewajiban berpedoman pada standar dan kriteria yang ditetapkan pemerintah pusat. Ketentuan hukum kepegawaian menegaskan Manajemen PNS pada Instansi Daerah dilaksanakan oleh pemerintah daerah bersangkutan. Oleh karena itu, opsi A merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "B": "Kantor kementerian vertikal mengelola pegawai instansi pusat di wilayah dan bukan PNS pemerintah daerah.",
            "C": "Dewan pengawas bukan lembaga pemegang wewenang eksekutif kepegawaian pada pemerintahan daerah.",
            "D": "Komando teritorial merupakan institusi pertahanan negara dan tidak mengelola kepegawaian sipil daerah.",
            "E": "Kamar dagang dan industri merupakan wadah dunia usaha komersial privat di luar birokrasi pemerintahan."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[90] = {
    "pertanyaan": "Selain prioritas pembangunan jangka menengah, penyusunan kebijakan perencanaan kebutuhan ASN nasional wajib mempertimbangkan...",
    "pilihan": {
        "A": "Fluktuasi indeks harga saham di bursa modal nasional",
        "B": "Kemampuan keuangan negara dalam membiayai belanja",
        "C": "Tren pergerakan kurs valuta asing terhadap rupiah",
        "D": "Tingkat suku bunga pinjaman perbankan lembaga niaga",
        "E": "Target pertumbuhan ekonomi global kawasan perdagangan"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Perencanaan kebutuhan pegawai ASN ditetapkan berdasar prioritas nasional serta **dengan mempertimbangkan kemampuan keuangan negara**.",
        "alasan_kunci": "Opsi B merupakan aspek fundamental dalam menjaga kesinambungan fiskal belanja aparatur negara. Pengangkatan pegawai ASN membawa implikasi belanja anggaran jangka panjang (gaji, tunjangan, dan jaminan pensiun), sehingga wajib diselaraskan dengan kapasitas keuangan negara. Ketentuan perundang-undangan mewajibkan perencanaan kebutuhan pegawai ASN disusun dengan mempertimbangkan kemampuan keuangan negara. Dengan demikian, opsi B merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Indeks harga saham merupakan indikator pasar modal komersial dan bukan syarat perencanaan aparatur negara.",
            "C": "Cadangan devisa dan kurs valas merupakan domain moneter bank sentral dalam stabilitas nilai tukar.",
            "D": "Suku bunga pinjaman komersial perbankan tidak menjadi parameter hukum penentuan formasi ASN.",
            "E": "Pertumbuhan ekonomi global merupakan asumsi makro internasional dan bukan variabel limitatif perencanaan ASN."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# ----------------- Halaman 20 (Soal 97, 98, 99) -----------------
fixes[97] = {
    "pertanyaan": "Rangkaian tahapan pengadaan Pegawai Negeri Sipil dari awal hingga akhir secara berurutan terdiri atas...",
    "pilihan": {
        "A": "Pengumuman, pelamaran, seleksi, percobaan, dan pengangkatan definitif",
        "B": "Perencanaan, pengumuman, pelamaran, seleksi, kelulusan, percobaan, dan PNS",
        "C": "Formasi, registrasi, ujian kompetensi, latsar, penilaian, dan pelantikan",
        "D": "Perencanaan, verifikasi dokumen, wawancara, orientasi, dan sumpah jabatan",
        "E": "Pendaftaran kuota, seleksi berkas, ujian CAT, pembekalan, dan penempatan"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Tahapan pengadaan PNS secara berurutan adalah: **1. Perencanaan, 2. Pengumuman lowongan, 3. Pelamaran, 4. Seleksi, 5. Pengumuman hasil seleksi, 6. Masa percobaan, dan 7. Pengangkatan menjadi PNS**.",
        "alasan_kunci": "Opsi B merupakan urutan tujuh tahapan baku pengadaan calon aparatur sipil negara yang ditetapkan regulasi. Rangkaian proses ini wajib dilalui secara tertib dan berkesinambungan, dimulai dari penetapan formasi perencanaan hingga pengangkatan resmi PNS setelah lulus masa percobaan. Ketentuan peraturan pemerintah merumuskan secara berurutan 7 tahapan pengadaan PNS yang tepat. Oleh sebab itu, opsi B merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Melewatkan tahapan perencanaan kebutuhan di awal dan pengumuman hasil kelulusan seleksi.",
            "C": "Menggunakan istilah nonbaku serta mencampuradukkan tahapan seleksi dengan pelatihan prajabatan.",
            "D": "Memuat mekanisme wawancara dan orientasi yang tidak mencerminkan tujuh tahapan resmi perundang-undangan.",
            "E": "Bukan merupakan nomenklatur tahapan resmi pengadaan pegawai aparatur sipil negara."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[98] = {
    "pertanyaan": "Tiga tahapan ujian seleksi pengadaan PNS yang wajib diselenggarakan secara bertahap terdiri atas...",
    "pilihan": {
        "A": "Uji kesamaptaan fisik, portofolio kerja, dan psikologi",
        "B": "Seleksi berkas umum, uji integritas, dan uji kesehatan",
        "C": "Uji bahasa asing, wawancara pimpinan, dan studi kasus",
        "D": "Seleksi administrasi, seleksi SKD, dan seleksi SKB",
        "E": "Seleksi kualifikasi, uji wawasan dasar, dan wawancara"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Penyelenggaraan seleksi pengadaan PNS terdiri dari 3 tahap: **seleksi administrasi**, **seleksi kompetensi dasar (SKD)**, dan **seleksi kompetensi bidang (SKB)**.",
        "alasan_kunci": "Opsi D merupakan tiga tingkatan seleksi kompetensi objektif yang diwajibkan dalam pengadaan calon PNS. Penyaringan berjenjang ini diawali verifikasi berkas administrasi, dilanjutkan Seleksi Kompetensi Dasar berbasis CAT, dan diakhiri Seleksi Kompetensi Bidang sesuai rumpun formasi. Ketentuan regulasi menetapkan seleksi pengadaan PNS terdiri atas seleksi administrasi, SKD, dan SKB. Dengan demikian, opsi D merupakan rujukan yang tepat.",
        "bedah_pengecoh": {
            "A": "Kesamaptaan dan psikotes merupakan variasi materi pelengkap pada SKB instansi tertentu.",
            "B": "Uji kesehatan merupakan persyaratan teknis pemberkasan dan bukan tahapan seleksi tripartit baku.",
            "C": "Uji bahasa asing dan wawancara merupakan instrumen turunan pada tahapan kompetensi bidang.",
            "E": "Bukan merupakan penamaan baku tiga tahapan seleksi kompetensi pengadaan calon pegawai negeri."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

fixes[99] = {
    "pertanyaan": "Tujuan pokok diselenggarakannya tahap seleksi administrasi dalam pengadaan calon PNS adalah...",
    "pilihan": {
        "A": "Mencocokkan syarat administrasi dengan dokumen pelamar",
        "B": "Mengukur kecerdasan intelegensia dan logika pemikiran",
        "C": "Menilai penguasaan substansi teknis keahlian formasi",
        "D": "Menguji ketahanan fisik dan kesiapan mental bertugas",
        "E": "Menentukan besaran tunjangan kinerja dan kelas jabatan"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "**Seleksi administrasi** adalah untuk **mencocokkan antara persyaratan administrasi dengan dokumen pelamaran yang disampaikan oleh pelamar**.",
        "alasan_kunci": "Opsi A merupakan esensi dan tujuan utama pelaksanaan seleksi administrasi calon pegawai aparatur sipil negara. Tahapan ini melakukan verifikasi kelayakan dokumen hukum formal (seperti kualifikasi ijazah, usia, dan surat pernyataan) pelamar terhadap standar jabatan. Ketentuan hukum perundang-undangan merumuskan seleksi administrasi bertujuan mencocokkan persyaratan administrasi dengan dokumen pelamaran. Oleh karena itu, opsi A merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "B": "Mengukur kecerdasan dan kemampuan logika merupakan fungsi Seleksi Kompetensi Dasar materi TIU.",
            "C": "Menilai penguasaan keahlian teknis formasi merupakan fungsi Seleksi Kompetensi Bidang (SKB).",
            "D": "Menguji ketahanan fisik dan mental merupakan fungsi pengujian kesehatan dan kesamaptaan dinas.",
            "E": "Besaran tunjangan kinerja diatur berdasarkan ketentuan kelas jabatan dan bukan ditentukan oleh seleksi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Apply all fixes to paket 1
applied = 0
for n, fix in fixes.items():
    q = soals[n]
    for k, v in fix.items():
        if k == 'pembahasan':
            for pk, pv in v.items():
                q['pembahasan'][pk] = pv
        else:
            q[k] = v
    applied += 1

print(f"Applied {applied} fixes to Paket 1.")

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_01.json', 'w', encoding='utf-8') as f:
    json.dump(paket1, f, ensure_ascii=False, indent=2)

print("Saved updated Paket 1 JSON.")


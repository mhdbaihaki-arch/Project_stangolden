import json
import re

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_03.json', 'r', encoding='utf-8') as f:
    paket3 = json.load(f)

soals = {q['nomor']: q for q in paket3['daftar_soal']}

fixes = {}

# Soal 4
fixes[4] = {
    "pertanyaan": "Jaminan pensiun dan jaminan hari tua diberikan kepada PNS dengan fungsi utama sebagai...",
    "pilihan": {
        "A": "Dana cadangan darurat penanggulangan inflasi daerah",
        "B": "Uang kompensasi atas pelepasan hak kepegawaian dinas",
        "C": "Bantuan modal usaha bagi kegiatan kewirausahaan baru",
        "D": "Perlindungan kesinambungan penghasilan dan penghargaan jasa",
        "E": "Alokasi tabungan investasi portofolio pasar modal negara"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Jaminan pensiun dan jaminan hari tua PNS diberikan sebagai **perlindungan kesinambungan penghasilan hari tua, sebagai hak dan sebagai penghargaan atas pengabdian PNS**.",
        "alasan_kunci": "Opsi D merupakan filosofi perlindungan sosial negara bagi purnakaryawan aparatur sipil. Setelah puluhan tahun mengabdi pada negara, kestabilan finansial aparatur di masa pensiun dijamin sebagai wujud hak hukum dan apresiasi pengabdian bangsa. Ketentuan perundang-undangan menetapkan fungsi jaminan pensiun sebagai perlindungan kesinambungan penghasilan dan penghargaan atas jasa pegawai. Oleh karena itu, opsi D merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Dana cadangan inflasi daerah dikelola melalui instrumen fiskal APBD dan bukan hak pensiun.",
            "B": "Jaminan pensiun merupakan hak pengabdian dan bukan kompensasi pelepasan status pegawai.",
            "C": "Modal wirausaha difasilitasi melalui perbankan komersial dan bukan tujuan utama program pensiun.",
            "E": "Pengelolaan dana pensiun berfokus pada jaminan proteksi hari tua dan bukan spekulasi modal."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 6
fixes[6] = {
    "pertanyaan": "Sebuah instansi menggabungkan dua unit riset sehingga terjadi perampingan organisasi. Pegawai terdampak yang memenuhi syarat berhak atas...",
    "pilihan": {
        "A": "Hak pensiun dini dengan hormat sesuai kebijakan pemerintah",
        "B": "Pemindahan tugas berkala pada jabatan pelaksana administrasi",
        "C": "Penetapan status penugasan penyesuaian kompetensi teknis",
        "D": "Pemberian dispensasi pembebasan tugas dinas sementara waktu",
        "E": "Pengalihan status kepegawaian menjadi tenaga ahli kontrak"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "PNS diberhentikan dengan hormat atau **diberikan pensiun dini dengan hormat apabila memenuhi syarat usia dan masa kerja akibat perampingan organisasi**.",
        "alasan_kunci": "Opsi A merupakan bentuk jaminan hak aparatur yang terdampak penataan struktur kelembagaan negara. Pegawai yang posisinya terhapus akibat reorganisasi dan memenuhi batas minimal usia serta masa kerja berhak memperoleh pensiun dini secara terhormat. Ketentuan perundang-undangan menjamin pemberian hak pensiun dini dengan hormat akibat perampingan organisasi. Oleh karena itu, opsi A merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "B": "Pemindahan ke jabatan pelaksana hanya alternatif penempatan dan bukan hak purna jabatan.",
            "C": "Penyesuaian kompetensi merupakan langkah pembinaan dan bukan solusi terminasi organisasi.",
            "D": "Pembebasan tugas sementara tidak memberikan kepastian status hukum kepegawaian jangka panjang.",
            "E": "PNS tidak dapat dialihkan menjadi tenaga kontrak swasta akibat perampingan organisasi pemerintah."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 7
fixes[7] = {
    "pertanyaan": "Pengelolaan jaminan pensiun dan jaminan hari tua bagi aparatur sipil negara terintegrasi ke dalam kerangka...",
    "pilihan": {
        "A": "Asuransi komersial privat yang terdaftar pada otoritas jasa",
        "B": "Program jaminan sosial nasional yang diatur undang-undang",
        "C": "Lembaga pembiayaan perumahan dan tabungan hari tua pegawai",
        "D": "Badan pengelola dana pensiun sukarela kemitraan perbankan",
        "E": "Unit pengelola dana amanah kesejahteraan pegawai instansi"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Jaminan pensiun dan jaminan hari tua **diberikan dalam program jaminan sosial nasional** sesuai ketentuan peraturan perundang-undangan.",
        "alasan_kunci": "Opsi B merupakan kebijakan integrasi sistem proteksi sosial aparatur secara nasional. Program pensiun ASN dipayungi kerangka Sistem Jaminan Sosial Nasional (SJSN) guna memastikan tata kelola dana amanah yang akuntabel, transparan, dan berkelanjutan. Ketentuan perundang-undangan menegaskan bahwa jaminan pensiun diberikan dalam program jaminan sosial nasional. Dengan demikian, opsi B merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Asuransi komersial privat berorientasi laba dan berada di luar jaminan sosial resmi negara.",
            "C": "Pembiayaan perumahan pegawai merupakan fasilitas penunjang dan bukan payung jaminan pensiun.",
            "D": "Dana pensiun sukarela perbankan merupakan program tambahan nonwajib di luar jaminan sosial nasional.",
            "E": "Unit dana amanah internal tidak memiliki mandat hukum mengelola jaminan pensiun nasional."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 9
fixes[9] = {
    "pertanyaan": "PNS yang dinyatakan tidak cakap jasmani dan/atau rohani oleh tim penguji kesehatan resmi diberhentikan secara...",
    "pilihan": {
        "A": "Pemberhentian sementara menunggu pemulihan kesehatan dinas",
        "B": "Pemberhentian dengan penyesuaian penurunan kelas jabatan",
        "C": "Pembebasan tugas operasional dengan tunjangan pemeliharaan",
        "D": "Hormat dengan hak pensiun sesuai ketentuan perundangan",
        "E": "Pemberhentian administratif berjangka tanpa penetapan SK"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "PNS diberhentikan dengan hormat apabila **tidak cakap jasmani dan/atau rohani sehingga tidak dapat menjalankan tugas dan kewajiban**, dan diberikan hak pensiun.",
        "alasan_kunci": "Opsi D merupakan perlakuan hukum yang adil dan manusiawi bagi aparatur yang mengalami kendala kesehatan permanen. Berdasarkan vonis resmi tim penguji kesehatan pemerintah, pegawai diberhentikan secara terhormat serta tetap diberikan jaminan hak pensiun. Ketentuan hukum kepegawaian menetapkan pemberhentian dilakukan dengan hormat disertai hak pensiun sesuai regulasi. Dengan demikian, opsi D merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Pemberhentian sementara hanya berlaku bila tim dokter menyatakan sakit bersifat sementara.",
            "B": "Penurunan kelas jabatan merupakan sanksi disiplin dan bukan perlakuan bagi disabilitas medis.",
            "C": "Pembebasan tugas bukan penetapan permanen bagi kondisi ketidakcakapan permanen.",
            "E": "Seluruh pemberhentian ASN wajib dituangkan secara formal melalui Surat Keputusan resmi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 11
fixes[11] = {
    "pertanyaan": "Dalam sejarah transformasi kelembagaan, Kementerian Keuangan telah mengimplementasikan Sistem Merit secara konsisten sejak...",
    "pilihan": {
        "A": "Pengesahan undang-undang perbendaharaan negara tahun 1999",
        "B": "Pelaksanaan program reformasi birokrasi kementerian tahun 2007",
        "C": "Penerapan kebijakan desentralisasi fiskal daerah tahun 2014",
        "D": "Penerbitan paket undang-undang keuangan negara tahun 2003",
        "E": "Pemberlakuan undang-undang penanaman modal terpadu tahun 2008"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Kementerian Keuangan telah menerapkan Sistem Merit dalam manajemen ASN **sejak tahun 2007 sejalan dengan Reformasi Birokrasi Kementerian Keuangan**.",
        "alasan_kunci": "Opsi B merupakan tonggak sejarah penting pembaharuan manajemen sumber daya manusia di Kementerian Keuangan. Sejak tahun 2007, Kemenkeu memelopori tata kelola ASN berbasis meritokrasi, pemeringkatan jabatan terukur, transparansi seleksi, dan remunerasi berbasis kinerja. Linimasa reformasi birokrasi mencatat tahun 2007 sebagai awal implementasi terpadu sistem merit. Oleh karena itu, opsi B merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Tahun 1999 merupakan era awal reformasi hukum perbankan dan bukan reformasi birokrasi Kemenkeu.",
            "C": "Tahun 2014 merupakan momentum lahirnya UU Nomor 5 Tahun 2014 tentang ASN.",
            "D": "Tahun 2003 menandai penerbitan UU Keuangan Negara dan bukan penerapan sistem merit kepegawaian.",
            "E": "Tahun 2008 merupakan penataan kelembagaan umum penanaman modal dan bukan tata kelola ASN Kemenkeu."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 15
fixes[15] = {
    "pertanyaan": "Manajemen Talenta di Kementerian Keuangan diselenggarakan berdasarkan asas keterbukaan dan keadilan guna...",
    "pilihan": {
        "A": "Menjamin objektivitas pembinaan karier aparatur kementerian",
        "B": "Membatasi hak mutasi penugasan pegawai pada instansi daerah",
        "C": "Menetapkan penempatan jabatan struktural berdasarkan domisili",
        "D": "Mengurangi alokasi belanja pendidikan dan pelatihan dinas",
        "E": "Menyeragamkan pola kenaikan pangkat tanpa evaluasi kompetensi"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Manajemen Talenta Kemenkeu diselenggarakan secara objektif, terencana, terbuka, tepat waktu, akuntabel, bebas intervensi politik, dan **bersih dari praktik korupsi, kolusi, dan nepotisme**.",
        "alasan_kunci": "Opsi A merupakan tujuan mendasar penerapan manajemen talenta dalam menjamin objektivitas pembinaan karier pegawai. Penilaian yang transparan dan terukur memastikan setiap aparatur memiliki peluang yang setara untuk berkembang sesuai kapasitas dan kompetensinya. Prinsip sistem merit menegaskan pentingnya objektivitas dalam pengembangan karier pegawai. Oleh sebab itu, opsi A merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "B": "Manajemen talenta justru mendorong mobilitas talenta yang dinamis dan bukan membatasi mutasi.",
            "C": "Penempatan jabatan didasarkan pada kompetensi dan kinerja pegawai, bukan faktor kedekatan domisili.",
            "D": "Pengembangan kompetensi merupakan bagian integral dari pembinaan talenta dan bukan untuk dikurangi.",
            "E": "Pola karier menuntut diferensiasi berbasis capaian kinerja nyata dan bukan penyeragaman otomatis."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 18
fixes[18] = {
    "pertanyaan": "Pelaksanaan asesmen kompetensi dalam Manajemen Talenta Kementerian Keuangan bertujuan utama untuk...",
    "pilihan": {
        "A": "Menentukan besaran tarif potongan iuran kesehatan pegawai",
        "B": "Menyeleksi calon penerima fasilitas perumahan dinas satker",
        "C": "Menetapkan kriteria penugasan keanggotaan tim panitia lelang",
        "D": "Memetakan profil kecakapan dan potensi kepemimpinan pegawai",
        "E": "Menilai tingkat kepatuhan jam kerja operasional harian kantor"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Asesmen kompetensi dilaksanakan untuk **mengetahui profil kompetensi dan potensi Pegawai** sebagai dasar penempatan dalam *talent pool*.",
        "alasan_kunci": "Opsi D merupakan fungsi strategis uji asesmen kompetensi manajerial dan sosial kultural dalam manajemen talenta. Pemetaan profil keahlian dan potensi kepemimpinan menjadi dasar penempatan pegawai dalam kotak talenta (*nine box matrix*) secara tepat sasaran. Regulasi menetapkan asesmen bertujuan memetakan kompetensi dan potensi pegawai aparatur. Dengan demikian, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Iuran kesehatan diatur berdasarkan persentase gaji pokok dan bukan melalui uji asesmen.",
            "B": "Fasilitas rumah dinas dialokasikan berdasar ketersediaan aset dan bukan hasil asesmen talenta.",
            "C": "Sertifikasi pengadaan barang dan jasa memiliki instrumen tersendiri di luar asesmen manajerial.",
            "E": "Kepatuhan jam kerja diukur melalui presensi digital dan bukan instrumen asesmen potensi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 20
fixes[20] = {
    "pertanyaan": "Instrumen pemetaan talenta yang digunakan untuk mengelompokkan pegawai berdasarkan kinerja dan potensi adalah...",
    "pilihan": {
        "A": "Matriks sembilan kotak manajemen talenta aparatur",
        "B": "Daftar urut kepangkatan pegawai struktural kementerian",
        "C": "Bagan hierarki komando operasional satuan kerja dinas",
        "D": "Formulir sasaran kinerja pegawai tahunan terpadu",
        "E": "Tabel analisis beban kerja dan evaluasi formasi unit"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Pemetaan talenta dilakukan menggunakan **matriks 9 (sembilan) kotak (*nine-box matrix*)** yang memadukan sumbu kinerja dan sumbu potensi.",
        "alasan_kunci": "Opsi A merupakan instrumen diagnostik standar dalam manajemen talenta aparatur sipil negara. Matriks sembilan kotak memadukan evaluasi capaian kinerja pada satu sumbu dan potensi pengembangan pada sumbu lainnya guna menentukan strategi pembinaan pegawai. Ketentuan manajemen talenta menetapkan pemetaan dilakukan melalui matriks sembilan kotak. Oleh karena itu, opsi A merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "B": "Daftar urut kepangkatan hanya memuat urutan administratif kepangkatan dan bukan pemetaan potensi.",
            "C": "Bagan hierarki komando menggambarkan jalur struktural wewenang dan bukan instrumen talenta.",
            "D": "Sasaran kinerja pegawai hanya mengukur dimensi capaian kerja tanpa mengukur sumbu potensi.",
            "E": "Analisis beban kerja digunakan untuk menghitung kebutuhan jumlah pegawai dan bukan kotak talenta."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 22
fixes[22] = {
    "pertanyaan": "Pegawai yang berada pada kotak talenta tertinggi (kotak 9) dikategorikan sebagai pegawai dengan kualifikasi...",
    "pilihan": {
        "A": "Potensi tinggi namun kinerja operasional masih berkembang",
        "B": "Kinerja memuaskan dengan potensi manajerial tingkat dasar",
        "C": "Potensi memadai dan kinerja sesuai standar target dinas",
        "D": "Kinerja luar biasa dan potensi kepemimpinan sangat tinggi",
        "E": "Kinerja unggul namun belum mengikuti asesmen kompetensi"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Kotak 9 (kelompok talenta unggul) merupakan kategori bagi pegawai dengan **kinerja di atas ekspektasi dan potensi tinggi**, yang siap dipromosikan.",
        "alasan_kunci": "Opsi D merupakan karakteristik profil pegawai pada puncak matriks manajemen talenta (*top talent*). Pegawai pada kotak ini telah membuktikan capaian kinerja luar biasa melampaui target serta memiliki kapasitas kepemimpinan yang matang untuk suksesi jabatan. Ketentuan manajemen talenta menetapkan kotak sembilan berisi pegawai berkinerja unggul dan berpotensi tinggi. Dengan demikian, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Potensi tinggi dengan kinerja berkembang menempati kuadran kotak yang berbeda.",
            "B": "Kinerja memuaskan dengan potensi dasar menempati area tengah matriks talenta.",
            "C": "Potensi memadai dengan kinerja standar menempati kotak kategori pegawai solid.",
            "E": "Seluruh pegawai dalam matriks talenta wajib telah menyelesaikan tahapan asesmen kompetensi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 23
fixes[23] = {
    "pertanyaan": "Rencana suksesi dalam manajemen talenta dipersiapkan oleh instansi pemerintah secara terencana untuk...",
    "pilihan": {
        "A": "Menghapus keberadaan jenjang jabatan struktural menengah",
        "B": "Menentukan alokasi pagu belanja operasional perjalanan dinas",
        "C": "Mengurangi beban tugas administratif pada satuan kerja daerah",
        "D": "Membatasi mobilitas penugasan karier lintas kementerian",
        "E": "Mengisi lowongan jabatan target melalui kelompok talenta"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Rencana suksesi disusun untuk **mempersiapkan suksesor yang siap menduduki jabatan target (*critical position*)** berdasarkan pemetaan kelompok talenta.",
        "alasan_kunci": "Opsi E merupakan fungsi strategis rencana suksesi dalam menjamin keberlanjutan kepemimpinan organisasi (*leadership continuity*). Pemetaan kader siap promosi dari kelompok talenta unggul memastikan posisi strategis lowong dapat segera terisi oleh figur yang kompeten dan berintegritas. Ketentuan perundang-undangan menetapkan rencana suksesi ditujukan untuk mengisi jabatan target melalui kelompok talenta. Oleh sebab itu, opsi E merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Rencana suksesi tidak bertujuan menghapus hierarki jabatan struktural dalam birokrasi.",
            "B": "Alokasi anggaran belanja perjalanan dinas diatur dalam ketentuan standar biaya masukan.",
            "C": "Beban tugas administratif diselesaikan melalui efisiensi proses bisnis dan digitalisasi kerja.",
            "D": "Rencana suksesi justru membuka mobilitas talenta aparatur secara dinamis dan transparan."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 25
fixes[25] = {
    "pertanyaan": "Penerapan sistem merit melalui manajemen talenta ditujukan untuk memastikan jabatan target diduduki oleh...",
    "pilihan": {
        "A": "Pegawai berdasar masa kerja penugasan terlama di instansi",
        "B": "Pegawai yang tepat pada waktu yang tepat secara profesional",
        "C": "Aparatur yang memiliki jaringan kemitraan bisnis luas",
        "D": "Pegawai senior yang telah menyelesaikan seluruh diklat teknis",
        "E": "Staf yang ditunjuk langsung melalui rekomendasi asosiasi"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Manajemen talenta bertujuan menempatkan **pegawai yang tepat pada jabatan yang tepat (*right person on the right place*) pada waktu yang tepat** sejalan tata kelola yang baik.",
        "alasan_kunci": "Opsi B merupakan prinsip penempatan pegawai presisi dalam tata kelola sumber daya manusia modern. Penempatan tepat sasaran waktu mencegah terjadinya kekosongan kepemimpinan serta menjamin kesiapan kompetensi pejabat baru dalam memimpin unit kerja. Ketentuan manajemen talenta menegaskan tujuan penempatan pegawai yang tepat pada waktu yang tepat. Dengan demikian, opsi B merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Masa kerja semata tidak menjamin kesesuaian kompetensi aparatur terhadap jabatan target.",
            "C": "Kemitraan bisnis privat bukan kriteria penempatan jabatan dalam birokrasi pemerintahan.",
            "D": "Kelulusan diklat merupakan komponen penunjang kualifikasi dan bukan satu-satunya penentu penempatan.",
            "E": "Penunjukan langsung tanpa proses asesmen bertentangan dengan prinsip meritokrasi terbuka."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 27
fixes[27] = {
    "pertanyaan": "Salah satu komponen baru yang masuk ke dalam delapan siklus Manajemen ASN menurut UU Nomor 20 Tahun 2023 adalah...",
    "pilihan": {
        "A": "Penyediaan fasilitas perumahan dinas permanen bagi aparatur",
        "B": "Pemberian dispensasi jam kerja operasional kantor pelayanan",
        "C": "Pembentukan unit advokasi bantuan politik kepegawaian dinas",
        "D": "Penguatan budaya kerja dan citra institusi aparatur negara",
        "E": "Penataan sistem pengadaan perlengkapan kantor secara mandiri"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Siklus Manajemen ASN menurut UU 20/2023 mencakup komponen baru yaitu: **Penguatan budaya kerja dan citra institusi**.",
        "alasan_kunci": "Opsi D merupakan pembaruan fundamental dalam regulasi aparatur sipil negara modern. Budaya kerja berorientasi pelayanan dan integritas serta reputasi positif institusi diposisikan sebagai pilar strategis penentu keberhasilan transformasi birokrasi. Ketentuan perundang-undangan mencantumkan penguatan budaya kerja dan citra institusi sebagai bagian siklus resmi manajemen ASN. Dengan demikian, opsi D merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Penyediaan rumah dinas merupakan aspek pengelolaan aset dan bukan siklus manajemen ASN.",
            "B": "Dispensasi jam kerja bukan komponen siklus pembinaan sumber daya manusia aparatur.",
            "C": "Pembentukan unit bantuan politik melanggar asas netralitas aparatur sipil negara.",
            "E": "Pengadaan perlengkapan kantor termasuk siklus pengelolaan barang milik negara."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 28
fixes[28] = {
    "pertanyaan": "Dua siklus Manajemen ASN yang berfokus langsung pada peningkatan kapasitas dan penataan jalur penugasan aparatur adalah...",
    "pilihan": {
        "A": "Pengadaan logistik dinas dan penataan tata ruang gedung",
        "B": "Pengelolaan data kepegawaian dan penyusunan formasi unit",
        "C": "Penetapan standar kinerja dan evaluasi kepatuhan presensi",
        "D": "Pemberian penghargaan disiplin dan pengelolaan arsip satker",
        "E": "Pengembangan talenta karier serta pengembangan kompetensi"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Dua siklus yang berfokus pada kapabilitas aparatur adalah **pengembangan talenta dan karier** serta **pengembangan kompetensi**.",
        "alasan_kunci": "Opsi E merupakan pilar investasi modal manusia dalam birokrasi aparatur sipil negara. Siklus pengembangan kompetensi membekali aparatur dengan pengetahuan dan keterampilan mutakhir, sedangkan pengembangan talenta menata penempatan karier pegawai secara optimal. Ketentuan perundang-undangan menetapkan pengembangan talenta/karier dan kompetensi sebagai siklus pembinaan aparatur. Oleh sebab itu, opsi E merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Logistik dan tata ruang gedung merupakan aspek penunjang sarana prasarana perkantoran.",
            "B": "Basis data kepegawaian dan formasi merupakan komponen perencanaan kebutuhan pegawai.",
            "C": "Standar kinerja dan presensi merupakan komponen siklus pengelolaan kinerja aparatur.",
            "D": "Penghargaan dan arsip merupakan bagian komponen kesejahteraan dan tata usaha kepegawaian."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 29
fixes[29] = {
    "pertanyaan": "Bagi pegawai yang masuk ke dalam kelompok talenta unggul (talent pool), kementerian memberikan kesempatan akselerasi melalui...",
    "pilihan": {
        "A": "Pengembangan karier dan peningkatan kompetensi terencana",
        "B": "Pemberian hak menentukan penempatan tugas secara mandiri",
        "C": "Dispensasi pembebasan kewajiban evaluasi capaian kinerja",
        "D": "Fasilitasi promosi langsung tanpa verifikasi syarat jabatan",
        "E": "Pemberian tunjangan khusus di luar batas pagu anggaran sah"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Pegawai dalam *talent pool* diprioritaskan untuk **pengembangan karier dan peningkatan kompetensi secara terencana**.",
        "alasan_kunci": "Opsi A merupakan bentuk pembinaan institusi yang terstruktur bagi para kader aparatur berkinerja unggul. Pegawai dalam *talent pool* dipersiapkan melalui penugasan strategis lintas unit, pelatihan kepemimpinan lanjutan, serta akselerasi karier yang transparan dan akuntabel. Ketentuan manajemen talenta menetapkan prioritas bagi talenta unggul adalah pengembangan karier dan kompetensi terencana. Dengan demikian, opsi A merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "B": "Penempatan tugas pegawai tetap didasarkan pada kebutuhan organisasi dan formasi yang lowong.",
            "C": "Setiap pegawai ASN tanpa terkecuali wajib dievaluasi kinerjanya secara berkala.",
            "D": "Promosi jabatan tetap wajib memenuhi persyaratan kualifikasi, rekam jejak, dan integritas.",
            "E": "Pemberian hak keuangan aparatur wajib berpedoman ketat pada pagu peraturan perundang-undangan."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 35
fixes[35] = {
    "pertanyaan": "Ruang lingkup Jabatan Karier dalam pola mutasi di lingkungan Kementerian Keuangan mencakup...",
    "pilihan": {
        "A": "Seluruh jabatan pimpinan tinggi madya dan staf penasihat",
        "B": "Tenaga staf khusus menteri dan pimpinan lembaga nonstruktural",
        "C": "Jabatan struktural eselon II, III, IV, V, dan fungsional",
        "D": "Seluruh pegawai berstatus tenaga alih daya dan staf kontrak",
        "E": "Pegawai penugasan khusus pada badan usaha milik negara"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Jabatan Karier di Kemenkeu meliputi **jabatan struktural eselon II, III, IV, dan V, serta jabatan fungsional**.",
        "alasan_kunci": "Opsi C merupakan batasan yuridis cakupan jabatan karier aparatur sipil negara di lingkungan Kementerian Keuangan. Jabatan karier menaungi pegawai berstatus ASN tetap yang meniti tangga kepangkatan struktural dan keahlian fungsional dalam struktur kementerian. Ketentuan regulasi menetapkan ruang lingkup jabatan karier mencakup eselon II sampai V dan jabatan fungsional. Oleh karena itu, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Staf penasihat ahli merupakan jabatan nonkarier tertentu di luar tangga jabatan struktural.",
            "B": "Staf khusus menteri merupakan posisi politis penugasan khusus yang bukan jabatan karier ASN.",
            "D": "Tenaga alih daya dan staf kontrak terikat perjanjian kerja pihak ketiga dan bukan jabatan karier.",
            "E": "Penugasan BUMN merupakan penugasan luar instansi dan bukan klasifikasi internal jabatan karier."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 36
fixes[36] = {
    "pertanyaan": "Dalam tata aturan kepegawaian Kementerian Keuangan, batasan pengertian dari Pola Mutasi adalah...",
    "pilihan": {
        "A": "Penyesuaian formasi penugasan demi pemerataan beban kerja",
        "B": "Mekanisme perpindahan tugas atas dasar permohonan mandiri",
        "C": "Proses seleksi penerimaan calon aparatur sipil negara baru",
        "D": "Sistem pemindahan pegawai yang terencana dan berjenjang",
        "E": "Pola penataan karier berdasar evaluasi tahunan kementerian"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Pola Mutasi adalah **pola pemindahan Pegawai dari suatu jabatan ke jabatan lain yang setara, baik di dalam satu unit kerja maupun antarunit kerja, yang terencana dan berjenjang**.",
        "alasan_kunci": "Opsi D merupakan definisi baku pola mutasi kepegawaian dalam regulasi internal kementerian. Kebijakan mutasi dirancang secara sistematis untuk memperluas wawasan tugas aparatur, mencegah kejenuhan kerja, serta memperkuat koordinasi kelembagaan secara berjenjang. Ketentuan tata aturan kepegawaian mendefinisikan pola mutasi sebagai sistem pemindahan pegawai yang terencana dan berjenjang. Oleh sebab itu, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Pemerataan beban kerja merupakan dampak operasional dan bukan rumusan definisi pola mutasi.",
            "B": "Mutasi pegawai merupakan kewenangan dinas instansi dan bukan mekanisme permohonan bebas.",
            "C": "Seleksi penerimaan baru merupakan tahapan pengadaan CPNS dan bukan perpindahan mutasi.",
            "E": "Penataan berdasar evaluasi tahunan merupakan manajemen kinerja dan bukan pengertian mutasi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 39
fixes[39] = {
    "pertanyaan": "Tujuan mendasar dari penyelenggaraan kegiatan pengadaan ASN adalah...",
    "pilihan": {
        "A": "Mengisi formasi kebutuhan pegawai pada instansi pemerintah",
        "B": "Menyerap anggaran belanja pegawai operasional pada akhir tahun",
        "C": "Memfasilitasi penempatan kerja bagi lulusan sekolah kedinasan",
        "D": "Menambah jumlah staf administrasi penunjang satuan kerja",
        "E": "Menggantikan seluruh posisi pegawai yang memasuki masa pensiun"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Pengadaan ASN bertujuan untuk **mengisi kebutuhan formasi Pegawai ASN pada Instansi Pemerintah** sesuai analisis jabatan dan beban kerja.",
        "alasan_kunci": "Opsi A merupakan esensi utama pengadaan aparatur sipil negara dalam birokrasi pemerintahan. Rekrutmen diselenggarakan murni untuk memenuhi lowongan formasi jabatan yang dibutuhkan unit organisasi demi keberlangsungan pelayanan publik dan tugas pemerintahan. Ketentuan perundang-undangan menetapkan pengadaan ASN bertujuan mengisi kebutuhan pegawai pada instansi pemerintah. Oleh karena itu, opsi A merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "B": "Pengadaan aparatur berorientasi pada pemenuhan SDM dan bukan sarana penyerapan anggaran kas.",
            "C": "Lulusan kedinasan hanya salah satu sumber pelamar dan bukan tujuan umum pengadaan ASN.",
            "D": "Penambahan pegawai harus berbasis analisis jabatan dan beban kerja, bukan sekadar memperbanyak staf.",
            "E": "Pengadaan tidak otomatis mengganti seluruh pensiunan melainkan mengikuti formasi yang disetujui."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 44
fixes[44] = {
    "pertanyaan": "Ketentuan batas masa penempatan atau penugasan PNS Kementerian Keuangan pada unit organisasi non-eselon adalah...",
    "pilihan": {
        "A": "Paling lama satu tahun tanpa kemungkinan perpanjangan",
        "B": "Permanen seumur hidup tanpa proses evaluasi berkala",
        "C": "Paling lama 3 tahun dan dapat diperpanjang tiap 2 tahun",
        "D": "Paling lama lima tahun penuh mengikuti periode menteri",
        "E": "Dua tahun kalender dan wajib dilakukan rotasi satker"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Masa penugasan PNS Kemenkeu pada unit non-eselon adalah **paling lama 3 (tiga) tahun dan dapat diperpanjang setiap 2 (dua) tahun** berdasarkan evaluasi kinerja.",
        "alasan_kunci": "Opsi C merupakan jangka waktu penugasan aparatur pada struktur organisasi non-eselon yang ditetapkan regulasi internal kementerian. Batasan durasi 3 tahun dengan opsi perpanjangan per 2 tahun memberikan kepastian operasional unit kerja khusus sekaligus menjaga rotasi dinamis aparatur. Ketentuan regulasi menetapkan masa penugasan non-eselon paling lama 3 tahun dan dapat diperpanjang tiap 2 tahun. Dengan demikian, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Satu tahun merupakan evaluasi kinerja berkala dan bukan batas maksimal penugasan unit non-eselon.",
            "B": "Penugasan non-eselon tidak bersifat permanen guna mencegah stagnasi karier aparatur.",
            "D": "Periode menteri tidak menentukan masa tugas operasional penugasan pegawai pada unit non-eselon.",
            "E": "Rotasi wajib dua tahun tidak sejalan dengan jangka waktu maksimal 3 tahun yang diatur regulasi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 48
fixes[48] = {
    "pertanyaan": "Dua orang PNS Kemenkeu yang berstatus suami-istri mengajukan penempatan kerja di wilayah kota yang sama. Dasar pertimbangan yang sah adalah...",
    "pilihan": {
        "A": "Preferensi lokasi domisili pribadi keluarga pegawai",
        "B": "Pasangan suami istri di Kemenkeu dan aspek kemanusiaan",
        "C": "Rekomendasi tertulis dari perhimpunan pegawai kementerian",
        "D": "Hak penentuan tempat dinas berdasar masa kerja penugasan",
        "E": "Tingkat senioritas kepangkatan tertinggi dalam keluarga"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Pertimbangan mutasi dapat memperhatikan **pasangan suami/istri yang bekerja di lingkungan Kementerian Keuangan dan aspek kemanusiaan**, dengan tetap memperhatikan kebutuhan organisasi.",
        "alasan_kunci": "Opsi B merupakan kebijakan humanis dalam manajemen pola mutasi Kementerian Keuangan. Pimpinan memberikan ruang pertimbangan bagi keutuhan keluarga aparatur sesama pegawai Kemenkeu dengan tetap memprioritaskan kepentingan efektivitas organisasi. Ketentuan KMK Pola Mutasi menetapkan pertimbangan pasangan suami/istri di Kemenkeu dan aspek kemanusiaan sebagai dasar yang sah. Oleh sebab itu, opsi B merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Preferensi domisili pribadi bukan dasar pertimbangan hukum resmi dalam penataan formasi.",
            "C": "Perhimpunan pegawai tidak memiliki kewenangan formal memberikan rekomendasi mutasi aparatur.",
            "D": "Masa kerja penugasan tidak memberikan hak menentukan sendiri lokasi penempatan kerja.",
            "E": "Senioritas kepangkatan bukan parameter dalam penyatuan lokasi kerja pasangan aparatur."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 49
fixes[49] = {
    "pertanyaan": "Dalam pelaksanaan mutasi penugasan PNS Kemenkeu ke satuan kerja baru, pembiayaan perpindahan dinas diatur dengan ketentuan...",
    "pilihan": {
        "A": "Seluruh biaya transportasi ditanggung mandiri oleh pegawai",
        "B": "Biaya perpindahan ditanggung bersama mitra kerja di daerah",
        "C": "Penyediaan anggaran biaya perpindahan bagi PNS bersangkutan",
        "D": "Pembiayaan diakumulasikan dalam tunjangan kinerja bulanan",
        "E": "Pegawai menanggung separuh biaya dan separuh dari instansi"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "PNS yang dimutasi **diberikan biaya perpindahan** yang dibebankan pada anggaran instansi sesuai peraturan perundang-undangan.",
        "alasan_kunci": "Opsi C merupakan hak perlindungan finansial bagi aparatur yang berpindah tugas demi kepentingan dinas negara. Karena mutasi dilaksanakan atas perintah kedinasan, negara berkewajiban menanggung biaya perjalanan, akomodasi, serta pengangkutan barang pindahan pegawai beserta keluarganya. Ketentuan perundang-undangan menetapkan penyediaan anggaran biaya perpindahan bagi PNS yang dimutasi. Oleh karena itu, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Pembiayaan mandiri membebani aparatur dan melanggar hak perlindungan perjalanan dinas pindah.",
            "B": "Mitra kerja pihak swasta dilarang membiayai perjalanan dinas pegawai demi mencegah gratifikasi.",
            "D": "Biaya perpindahan dialokasikan terpisah dalam pos belanja perjalanan dinas pindah, bukan tunjangan.",
            "E": "Skema bagi hasil biaya perjalanan pindah dinas tidak dikenal dalam tata kelola keuangan APBN."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 50
fixes[50] = {
    "pertanyaan": "Kebijakan mutasi dan promosi di Kementerian Keuangan secara terencana memprioritaskan penempatan strategis bagi...",
    "pilihan": {
        "A": "Pegawai dengan masa kerja pengabdian kantor paling lama",
        "B": "Aparatur yang aktif dalam organisasi kepanitiaan informal",
        "C": "Staf yang telah menyelesaikan pendidikan formal pascasarjana",
        "D": "Talenta terbaik yang masuk ke dalam kelompok talent pool",
        "E": "Pegawai dengan tingkat kepatuhan administratif tertinggi"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Mutasi dan promosi diprioritaskan bagi **pegawai yang masuk dalam kelompok rencana suksesi (*talent pool*)**.",
        "alasan_kunci": "Opsi D merupakan perwujudan meritokrasi dalam penempatan jabatan strategis kementerian. Pegawai yang telah teruji kompetensi dan kinerjanya serta menempati kuadran talenta terbaik diprioritaskan mengisi posisi kepemimpinan untuk menjamin keberhasilan kinerja instansi. Ketentuan pola karier memprioritaskan penempatan bagi talenta terbaik dalam *talent pool*. Dengan demikian, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Masa kerja lama tanpa diimbangi kinerja unggul bukan prioritas utama dalam sistem merit.",
            "B": "Keaktifan organisasi informal bukan parameter formal penilaian manajemen talenta kementerian.",
            "C": "Gelar pascasarjana merupakan kualifikasi pendidikan dan bukan jaminan prioritas promosi.",
            "E": "Kepatuhan administratif merupakan kewajiban dasar seluruh pegawai dan bukan tolok ukur promosi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 58
fixes[58] = {
    "pertanyaan": "Seorang PNS Kemenkeu telah menyelesaikan penugasan pada unit non-eselon. Penempatan kembali ke struktur reguler yang setingkat merupakan wujud...",
    "pilihan": {
        "A": "Pemberian sanksi pembebasan tugas dinas kementerian",
        "B": "Penurunan jabatan akibat reorganisasi kelembagaan unit",
        "C": "Promosi akselerasi jabatan struktural tingkat tinggi",
        "D": "Penataan administratif mutasi berkala antardaerah dinas",
        "E": "Jaminan perlindungan status karier kepegawaian aparatur"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Pegawai yang selesai bertugas di unit non-eselon **ditempatkan kembali pada jabatan yang setingkat dengan jabatan sebelum penugasan** sebagai jaminan karier.",
        "alasan_kunci": "Opsi E merupakan kepastian hukum dan perlindungan karier bagi pegawai yang bersedia ditugaskan pada unit khusus. Setelah mandat penugasan selesai, instansi menjamin pengembalian pegawai ke struktur jabatan reguler yang setara dengan posisi sebelumnya tanpa kehilangan hak kepegawaiannya. Ketentuan regulasi menjamin perlindungan status karier melalui penempatan kembali pada jabatan setingkat. Oleh sebab itu, opsi E merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Penempatan kembali bukan sanksi melainkan hak perlindungan karier pegawai purnatugas unit.",
            "B": "Penempatan pada jabatan setingkat bukan bentuk penurunan jabatan struktural pegawai.",
            "C": "Penempatan kembali pada jenjang setara bukan merupakan mekanisme promosi akselerasi.",
            "D": "Penataan ini berfokus pada reintegrasi jabatan aparatur pasca penugasan khusus unit non-eselon."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 59
fixes[59] = {
    "pertanyaan": "Seorang pejabat pengawas terpilih menduduki Jabatan Fungsional Ahli Madya (setara Eselon III). Perpindahan ini menerapkan pola...",
    "pilihan": {
        "A": "Pola karier horizontal pada rumpun jabatan kerja yang sama",
        "B": "Pola karier diagonal antarkelompok jabatan ke jenjang tinggi",
        "C": "Pola karier vertikal murni dalam jalur struktural pengawasan",
        "D": "Pola karier penyesuaian otomatis tanpa perubahan tanggung jawab",
        "E": "Pola karier penugasan khusus sementara di luar instansi induk"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Perpindahan dari Jabatan Administrasi ke Jabatan Fungsional dengan jenjang lebih tinggi merupakan **pola karier diagonal**.",
        "alasan_kunci": "Opsi B merupakan pola mobilitas karier diagonal dalam birokrasi pemerintahan modern. Pola diagonal terjadi ketika aparatur berpindah melintasi batas kelompok jabatan (dari jabatan administrasi pengawas ke jabatan fungsional) sekaligus mengalami peningkatan kesetaraan jenjang karier yang lebih tinggi. Ketentuan hukum kepegawaian mengategorikan perpindahan antarkelompok jabatan ke jenjang lebih tinggi sebagai pola karier diagonal. Oleh karena itu, opsi B merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Pola horizontal merupakan perpindahan jabatan setara dalam satu kelompok jabatan yang sama.",
            "C": "Pola vertikal murni berlangsung dalam satu kelompok jabatan struktural yang sama ke jenjang atas.",
            "D": "Perpindahan ini membawa konsekuensi perubahan peran substantif dan bukan penyesuaian otomatis.",
            "E": "Perpindahan ini berlangsung definitif di dalam birokrasi dan bukan penugasan khusus sementara."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 61
fixes[61] = {
    "pertanyaan": "Berdasarkan regulasi manajemen kepegawaian negara, empat jenis kenaikan pangkat bagi PNS meliputi...",
    "pilihan": {
        "A": "Kenaikan pangkat berkala, prestasi, jabatan, dan khusus",
        "B": "Kenaikan pangkat reguler, pilihan, pengabdian, dan anumerta",
        "C": "Kenaikan pangkat otomatis, penyesuaian, dinas, dan jasa",
        "D": "Kenaikan pangkat fungsional, struktural, madya, dan utama",
        "E": "Kenaikan pangkat administratif, kehormatan, masa, dan tugas"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Kenaikan pangkat PNS terdiri atas: **a. kenaikan pangkat reguler; b. kenaikan pangkat pilihan; c. kenaikan pangkat anumerta; dan d. kenaikan pangkat pengabdian**.",
        "alasan_kunci": "Opsi B merupakan klasifikasi resmi empat rumpun kenaikan pangkat aparatur sipil negara dalam sistem kepegawaian nasional. Keempat jenis ini mengakomodasi kenaikan pangkat periodik rutin, apresiasi jabatan/prestasi, penghargaan purnatugas pengabdian, serta penghormatan bagi yang gugur dalam tugas. Ketentuan perundang-undangan menetapkan jenis kenaikan pangkat PNS terdiri atas reguler, pilihan, pengabdian, dan anumerta. Dengan demikian, opsi B merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Kenaikan pangkat berkala dan prestasi bukan nomenklatur yuridis baku dalam regulasi kepegawaian.",
            "C": "Otomatis dan penyesuaian merupakan mekanisme teknis pengusulan dan bukan jenis resmi pangkat.",
            "D": "Fungsional dan struktural merupakan jalur dalam kenaikan pangkat pilihan, bukan jenis pokok.",
            "E": "Administratif dan kehormatan bukan pembagian empat kategori kenaikan pangkat dalam undang-undang."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 62
fixes[62] = {
    "pertanyaan": "Syarat masa dinas minimum dalam pangkat terakhir yang wajib dipenuhi seorang PNS untuk memperoleh Kenaikan Pangkat Reguler adalah...",
    "pilihan": {
        "A": "Sekurang-kurangnya telah berdinas satu tahun",
        "B": "Sekurang-kurangnya telah bertugas dua tahun",
        "C": "Sekurang-kurangnya telah berdinas tiga tahun",
        "D": "Sekurang-kurangnya telah empat tahun berpangkat",
        "E": "Sekurang-kurangnya telah bertugas lima tahun"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Kenaikan pangkat reguler diberikan kepada PNS yang: **sekurang-kurangnya telah 4 (empat) tahun dalam pangkat terakhir**.",
        "alasan_kunci": "Opsi D merupakan masa tenggang kepangkatan minimum bagi pemberian kenaikan pangkat reguler pegawai negeri sipil. Periode 4 tahun dipandang sebagai siklus pengabdian wajar bagi aparatur untuk mengumpulkan pengalaman dan membuktikan kesetiaan tugas sebelum dinaikkan ke jenjang kepangkatan berikutnya. Ketentuan regulasi menetapkan syarat sekurang-kurangnya telah 4 tahun dalam pangkat terakhir. Oleh sebab itu, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Satu tahun belum memenuhi ambang batas waktu minimal untuk usulan kenaikan pangkat reguler.",
            "B": "Dua tahun merupakan syarat untuk kenaikan pangkat pilihan jabatan tertentu dan bukan reguler.",
            "C": "Tiga tahun belum mencukupi batas waktu normatif 4 tahun yang ditentukan peraturan kepegawaian.",
            "E": "Lima tahun melampaui batas waktu normatif 4 tahun yang disyaratkan dalam kenaikan pangkat reguler."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 64
fixes[64] = {
    "pertanyaan": "Kenaikan Pangkat Pilihan dapat dianugerahkan kepada PNS yang menunjukkan prestasi kerja luar biasa baiknya, dengan bukti yuridis berupa...",
    "pilihan": {
        "A": "Rekomendasi tertulis dari perhimpunan profesi aparatur",
        "B": "Penetapan hasil evaluasi berkala pimpinan unit kerja",
        "C": "Surat keputusan yang disahkan Pejabat Pembina Kepegawaian",
        "D": "Sertifikat keikutsertaan program pendidikan dinas luar",
        "E": "Piagam penghargaan prestasi kerja tingkat satuan kerja"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Prestasi kerja luar biasa baiknya dibuktikan dengan **surat keputusan yang ditandatangani oleh Pejabat Pembina Kepegawaian (PPK)**.",
        "alasan_kunci": "Opsi C merupakan instrumen legalitas formal pengakuan prestasi istimewa aparatur negara. Pengesahan keputusan oleh PPK membuktikan bahwa karya luar biasa pegawai telah diverifikasi secara objektif dan diakui secara resmi oleh pimpinan tertinggi instansi. Ketentuan perundang-undangan menegaskan bukti prestasi kerja luar biasa dibuktikan dengan surat keputusan dari Pejabat Pembina Kepegawaian. Oleh karena itu, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Rekomendasi perhimpunan profesi bukan dasar penetapan yuridis kenaikan pangkat pilihan aparatur.",
            "B": "Evaluasi pimpinan unit kerja merupakan bahan usulan awal dan belum memiliki kekuatan SK PPK.",
            "D": "Sertifikat diklat merupakan bukti pengembangan kompetensi dan bukan penetapan prestasi luar biasa.",
            "E": "Piagam penghargaan satker merupakan apresiasi internal yang belum berkekuatan penetapan PPK."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 65
fixes[65] = {
    "pertanyaan": "Seorang PNS peneliti berhasil menciptakan inovasi teknologi yang terbukti menghemat belanja negara miliaran rupiah. Pegawai bersangkutan berhak atas...",
    "pilihan": {
        "A": "Pemberian dana bagi hasil efisiensi anggaran belanja",
        "B": "Kenaikan pangkat anumerta atas pengabdian kepada negara",
        "C": "Pemberhentian dinas dengan hak pensiun dini maksimal",
        "D": "Pembebasan kewajiban pelaporan kinerja instansi tahunan",
        "E": "Kenaikan pangkat pilihan atas prestasi kerja luar biasa"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "PNS yang menemukan penemuan baru yang bermanfaat bagi negara berhak atas **kenaikan pangkat pilihan karena menunjukkan prestasi kerja luar biasa baiknya**.",
        "alasan_kunci": "Opsi E merupakan bentuk penghargaan kepangkatan istimewa bagi aparatur yang menghasilkan karya inovasi strategis bagi negara. Temuan yang memberikan manfaat nyata berupa efisiensi belanja negara memenuhi kriteria prestasi kerja luar biasa sehingga pegawai layak dianugerahi kenaikan pangkat pilihan mendahului periode reguler. Ketentuan hukum menetapkan hak atas kenaikan pangkat pilihan prestasi kerja luar biasa. Dengan demikian, opsi E merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Hukum perbendaharaan negara melarang bagi hasil penghematan anggaran kas kepada pegawai pribadi.",
            "B": "Kenaikan pangkat anumerta hanya dianugerahkan bagi aparatur yang gugur atau tewas dalam tugas.",
            "C": "Pensiun dini diberikan atas permohonan atau reorganisasi dan bukan bentuk apresiasi inovasi.",
            "D": "Pelaporan kinerja merupakan kewajiban abadi setiap aparatur tanpa pengecualian prestasi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 69
fixes[69] = {
    "pertanyaan": "Salah satu syarat batas waktu bagi PNS untuk diusulkan memperoleh Kenaikan Pangkat Pengabdian menjelang pensiun adalah...",
    "pilihan": {
        "A": "Paling lama satu bulan sebelum pemberhentian tugas dinas",
        "B": "Paling lama dua bulan sebelum berlakunya surat keputusan",
        "C": "Paling lama satu bulan sebelum tanggal mencapai batas pensiun",
        "D": "Paling lambat enam bulan sebelum berakhirnya masa jabatan",
        "E": "Paling lambat tiga bulan sebelum berakhirnya tugas kedinasan"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Kenaikan pangkat pengabdian diusulkan **paling lama 1 (satu) bulan sebelum PNS yang bersangkutan mencapai batas usia pensiun**.",
        "alasan_kunci": "Opsi C merupakan batas waktu administrasi pengusulan tanda jasa kenaikan pangkat pengabdian kepegawaian. Tenggat waktu satu bulan sebelum mencapai BUP menjamin proses penetapan keputusan kenaikan pangkat dan penyesuaian hak pensiun berjalan tuntas sebelum masa dinas aktif berakhir. Ketentuan perundang-undangan menetapkan batas waktu pengusulan paling lama 1 bulan sebelum mencapai batas usia pensiun. Oleh sebab itu, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Bukan rumusan baku yang diatur dalam tata tertib pengusulan pangkat pengabdian BKN.",
            "B": "Tenggat dua bulan bukan batas waktu limitatif yang ditentukan dalam regulasi kepegawaian.",
            "D": "Enam bulan merupakan persiapan berkas usul pensiun dan bukan batas limit pengusulan pangkat.",
            "E": "Tiga bulan bukan batas akhir penerimaan berkas usulan kenaikan pangkat pengabdian PNS."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 71
fixes[71] = {
    "pertanyaan": "Seorang pejabat pengawas yang berkinerja amat baik dipromosikan menduduki Jabatan Administrator. Mekanisme promosi ini menerapkan...",
    "pilihan": {
        "A": "Pola karier horizontal dalam kelompok jabatan administrasi",
        "B": "Pola karier diagonal melintasi kelompok jabatan fungsional",
        "C": "Pola karier vertikal murni dalam struktur kepemimpinan",
        "D": "Pola penugasan penyesuaian formasi jabatan kepegawaian",
        "E": "Pola rotasi berkala berbasis pertimbangan kebutuhan dinas"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Promosi dari Jabatan Pengawas ke Jabatan Administrator merupakan **pola karier vertikal** dalam kelompok Jabatan Manajerial.",
        "alasan_kunci": "Opsi C merupakan pola pengembangan karier bertingkat lurus ke atas dalam kelompok jabatan manajerial. Pergerakan dari jabatan pengawas ke jenjang administrator mencerminkan kenaikan tanggung jawab kepemimpinan dan hierarki wewenang dalam rumpun jabatan manajerial yang sama. Ketentuan perundang-undangan mengategorikan promosi berjenjang ini sebagai pola karier vertikal. Oleh karena itu, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Pola horizontal merupakan mutasi antarnama jabatan yang setara pada jenjang yang sama.",
            "B": "Pola diagonal melibatkan pergerakan antar-rumpun kelompok jabatan yang berbeda kualifikasi.",
            "D": "Penyesuaian formasi merupakan proses administrasi penempatan dan bukan mekanisme promosi vertikal.",
            "E": "Rotasi berkala berorientasi pada penyegaran tugas dan bukan promosi kenaikan jenjang jabatan."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 76
fixes[76] = {
    "pertanyaan": "Pola mutasi di lingkungan Kementerian Keuangan dilaksanakan dengan memegang teguh prinsip dasar yaitu...",
    "pilihan": {
        "A": "Kepentingan pribadi pegawai dengan dispensasi jam dinas",
        "B": "Keseragaman penempatan berdasar kedekatan geografis asal",
        "C": "Transparan, objektif, kompetitif, dan berkeadilan kerja",
        "D": "Kewenangan mutlak pimpinan satker tanpa instrumen asesmen",
        "E": "Rotasi massal tahunan tanpa melihat kebutuhan organisasi"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Prinsip mutasi di Kemenkeu meliputi: **transparan, objektif, kompetitif, berkeadilan, dan berorientasi pada pencapaian kinerja organisasi**.",
        "alasan_kunci": "Opsi C merupakan nilai-nilai dasar pelaksanaan mutasi aparatur sipil negara di lingkungan kementerian. Prinsip transparansi, objektivitas, kompetensi, dan keadilan memastikan bahwa setiap perpindahan tugas aparatur bermuara pada peningkatan kinerja kelembagaan dan pembinaan talenta yang berimbang. Ketentuan KMK Pola Mutasi menetapkan prinsip mutasi adalah transparan, objektif, kompetitif, dan berkeadilan. Dengan demikian, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Kepentingan dinas dan organisasi mengungguli kepentingan preferensi pribadi aparatur.",
            "B": "Kedekatan geografis bukan dasar utama pemerataan penugasan aparatur sipil nasional.",
            "D": "Kewenangan pimpinan wajib berpedoman pada koridor meritokrasi dan instrumen asesmen.",
            "E": "Rotasi massal tanpa analisis kebutuhan bertentangan dengan prinsip penataan organisasi efisien."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 79
fixes[79] = {
    "pertanyaan": "Asas pemerataan dan keadilan dalam pola mutasi kepegawaian Kementerian Keuangan diwujudkan melalui penataan...",
    "pilihan": {
        "A": "Penempatan kerja berdasar pertimbangan kesukuan daerah",
        "B": "Alokasi fasilitas dinas mewah bagi seluruh staf kantor",
        "C": "Pemberian dispensasi tugas bagi pegawai di unit tertentu",
        "D": "Keseimbangan penugasan antara kantor pusat dan kantor daerah",
        "E": "Penghapusan evaluasi capaian target kinerja tahunan dinas"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Pola mutasi memperhatikan **keseimbangan penugasan antara unit kantor pusat dan kantor vertikal daerah** untuk pemerataan pengalaman dan keadilan karier.",
        "alasan_kunci": "Opsi D merupakan perwujudan asas keadilan distributif dalam pembinaan karier aparatur sipil kementerian. Rotasi berimbang antara kantor pusat (perumus kebijakan) dan kantor vertikal daerah (eksekutor pelayanan/pengawasan) membentuk profil kompetensi pegawai yang tangguh, komprehensif, dan berpengalaman luas. Ketentuan pola mutasi menetapkan keseimbangan penugasan antara kantor pusat dan daerah sebagai pilar keadilan. Oleh sebab itu, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Pertimbangan kedaerahan kesukuan dilarang keras dalam manajemen kepegawaian berbasis merit.",
            "B": "Fasilitas dinas dialokasikan berdasar standar kebutuhan kedinasan dan bukan azas pemerataan mutasi.",
            "C": "Dispensasi tugas merugikan pelayanan publik dan melanggar prinsip disiplin aparatur sipil.",
            "E": "Evaluasi kinerja merupakan roh akuntabilitas organisasi yang wajib diselenggarakan secara kontinu."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 80
fixes[80] = {
    "pertanyaan": "Ruang lingkup pembinaan karier aparatur yang diselenggarakan berdasarkan Sistem Merit meliputi...",
    "pilihan": {
        "A": "Pengembangan kompetensi, pola karier, mutasi, dan promosi",
        "B": "Pengelolaan aset logistik perkantoran dan belanja modal",
        "C": "Pengawasan penegakan hukum perdata dan pidana korporasi",
        "D": "Penyelenggaraan bantuan keuangan sosial dan hibah daerah",
        "E": "Penetapan tarif pajak perdagangan internasional terpadu"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Pembinaan karier berdasarkan Sistem Merit meliputi: **pengembangan kompetensi, pengembangan karier, pola karier, mutasi, dan promosi**.",
        "alasan_kunci": "Opsi A merupakan pilar substantif manajemen karier aparatur sipil negara berbasis meritokrasi. Seluruh rangkaian perjalanan karier aparatur dipandu secara terstruktur melalui pemetaan kompetensi, pola pergerakan karier yang jelas, serta mekanisme mutasi dan promosi yang objektif. Ketentuan perundang-undangan menegaskan pembinaan karier sistem merit mencakup pengembangan kompetensi, pola karier, mutasi, dan promosi. Oleh karena itu, opsi A merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "B": "Logistik dan belanja modal merupakan ruang lingkup pengelolaan barang milik negara.",
            "C": "Penegakan hukum perdata dan pidana merupakan fungsi yudisial lembaga peradilan negara.",
            "D": "Bantuan keuangan sosial merupakan program penanggulangan kemiskinan dan bukan manajemen ASN.",
            "E": "Tarif pajak internasional merupakan domain kebijakan fiskal makro Kementerian Keuangan."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 81
fixes[81] = {
    "pertanyaan": "Salah satu tujuan utama manajemen karier di Kementerian Keuangan dalam rangka mencetak pemimpin masa depan adalah...",
    "pilihan": {
        "A": "Mendorong peningkatan kompetensi manajerial pegawai swasta",
        "B": "Memusatkan seluruh wewenang pengelolaan dinas pada pimpinan",
        "C": "Membatasi regenerasi kepemimpinan pada unit kerja tertentu",
        "D": "Mempersiapkan kader suksesi kepemimpinan yang berintegritas",
        "E": "Menyeragamkan pola penugasan tanpa melihat keahlian teknis"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Tujuan manajemen karier antara lain **mempersiapkan calon pemimpin masa depan (*future leaders*) yang memiliki integritas dan kompetensi tinggi**.",
        "alasan_kunci": "Opsi D merupakan orientasi jangka panjang dari pelembagaan manajemen karier aparatur kementerian. Dengan membina para kader bertalenta secara terencana, organisasi memiliki pasokan pemimpin masa depan yang tidak hanya cakap secara manajerial tetapi juga teguh berintegritas. Ketentuan manajemen karier menetapkan tujuannya mempersiapkan kader suksesi kepemimpinan yang berintegritas. Dengan demikian, opsi D merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Manajemen karier Kemenkeu diperuntukkan bagi aparatur sipil negara kementerian, bukan pegawai swasta.",
            "B": "Sentralisasi berlebihan menghambat pendelegasian wewenang dan pembinaan inisiatif kepemimpinan.",
            "C": "Regenerasi kepemimpinan wajib dibuka secara merata di seluruh unit kerja kementerian.",
            "E": "Pola penugasan menuntut kesesuaian keahlian teknis spesifik dan bukan penyeragaman kaku."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 86
fixes[86] = {
    "pertanyaan": "Penyelenggaraan Manajemen Karier di Kementerian Keuangan secara spesifik diarahkan untuk...",
    "pilihan": {
        "A": "Menyerahkan proses penilaian kompetensi kepada pihak mitra",
        "B": "Membatasi pengumuman lowongan formasi pada pejabat pusat",
        "C": "Menetapkan masa kerja sebagai satu-satunya penentu karier",
        "D": "Mewujudkan kepastian arah pengembangan karier setiap pegawai",
        "E": "Menghapuskan mekanisme seleksi terbuka pada jenjang madya"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Manajemen karier bertujuan **memberikan kepastian arah karier bagi Pegawai** berdasarkan kualifikasi, kompetensi, dan kinerja.",
        "alasan_kunci": "Opsi D merupakan manfaat utama penerapan pola karier yang terstruktur dan transparan bagi aparatur. Adanya peta jalan karier yang jelas memberikan motivasi bagi setiap pegawai untuk berkinerja terbaik karena mengetahui jalur kenaikan jenjang yang dapat diraih secara adil. Ketentuan regulasi menetapkan tujuan manajemen karier adalah mewujudkan kepastian arah karier pegawai. Oleh sebab itu, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Asesmen kompetensi diselenggarakan oleh unit resmi kementerian dan asesor bersertifikat.",
            "B": "Transparansi formasi lowongan wajib diumumkan secara terbuka bagi seluruh pegawai yang memenuhi syarat.",
            "C": "Sistem merit menolak masa kerja sebagai dasar tunggal penetapan karier pegawai.",
            "E": "Seleksi terbuka merupakan amanat undang-undang yang wajib dipatuhi pada jenjang manajerial."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 89
fixes[89] = {
    "pertanyaan": "Perumusan Manajemen Karier di Kementerian Keuangan memiliki tujuan penting dalam mendorong peningkatan...",
    "pilihan": {
        "A": "Beban tugas kerja harian pegawai tanpa penyesuaian sarana",
        "B": "Persaingan informal antar-satuan kerja dalam alokasi aset",
        "C": "Pengeluaran belanja perjalanan dinas operasional kementerian",
        "D": "Jumlah staf administrasi pada kantor perwakilan wilayah",
        "E": "Kinerja dan profesionalitas aparatur dalam pelayanan publik"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Manajemen karier bertujuan **mendorong peningkatan kinerja dan profesionalitas Pegawai** secara berkesinambungan.",
        "alasan_kunci": "Opsi E merupakan sasaran puncak dari tata kelola sumber daya manusia aparatur berbasis meritokrasi. Manajemen karier yang sehat mendorong profesionalitas pegawai dalam bekerja, yang pada akhirnya bermuara pada peningkatan kualitas pelayanan publik dan pengelolaan keuangan negara. Ketentuan regulasi menetapkan manajemen karier bertujuan mendorong peningkatan kinerja dan profesionalitas pegawai. Oleh karena itu, opsi E merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Manajemen karier bertujuan mengoptimalkan efisiensi kerja dan bukan menambah beban tanpa sarana.",
            "B": "Pola karier menumbuhkan budaya kolaborasi sinergis dan bukan persaingan perebutan aset.",
            "C": "Efisiensi anggaran perjalanan dinas merupakan prinsip perbendaharaan dan bukan sasaran karier.",
            "D": "Penataan jumlah pegawai berpedoman pada analisis beban kerja dan bukan target manajemen karier."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 91
fixes[91] = {
    "pertanyaan": "Berdasarkan regulasi Manajemen Karier Kemenkeu, pengembangan talenta dan karier pegawai wajib mempertimbangkan empat aspek pokok yaitu...",
    "pilihan": {
        "A": "Masa dinas, golongan kepangkatan, usia, dan riwayat mutasi",
        "B": "Pendidikan formal, sertifikat pelatihan, kehadiran, dan eselon",
        "C": "Rekomendasi atasan, kepatuhan disiplin, integritas, dan gelar",
        "D": "Pengalaman penugasan, keaktifan organisasi, jabatan, dan asal",
        "E": "Kualifikasi, kompetensi, kinerja, dan kebutuhan instansi"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Pengembangan talenta dan karier dilakukan dengan mempertimbangkan **kualifikasi, kompetensi, kinerja, dan kebutuhan organisasi**.",
        "alasan_kunci": "Opsi E merupakan empat pilar pertimbangan mutlak dalam sistem meritokrasi aparatur sipil negara modern. Pemaduan antara latar belakang pendidikan (kualifikasi), kecakapan kerja (kompetensi), rekam jejak prestasi (kinerja), serta formasi organisasi (kebutuhan instansi) menjamin penempatan yang akuntabel. Ketentuan hukum menegaskan pertimbangan talenta mencakup kualifikasi, kompetensi, kinerja, dan kebutuhan organisasi. Dengan demikian, opsi E merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Masa dinas dan usia semata merupakan parameter lama yang tidak lagi menjadi penentu pokok.",
            "B": "Sertifikat pelatihan dan eselon merupakan bagian dari instrumen pendukung kualifikasi.",
            "C": "Rekomendasi atasan bersifat subjektif bila tidak didasarkan pada data capaian kinerja terukur.",
            "D": "Keaktifan organisasi di luar dinas bukan parameter pokok dalam pertimbangan talenta ASN."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 92
fixes[92] = {
    "pertanyaan": "Tiga mekanisme operasional yang digunakan dalam pengembangan karier bagi PNS Kementerian Keuangan adalah...",
    "pilihan": {
        "A": "Penugasan berkala, evaluasi tahunan, dan pembekalan teknis",
        "B": "Mutasi, promosi, dan/atau penugasan khusus instansi luar",
        "C": "Rotasi dinas, perpanjangan masa kontrak, dan uji kelayakan",
        "D": "Pemberian tugas belajar, cuti besar, dan masa pembinaan",
        "E": "Asesmen kompetensi, pembinaan disiplin, dan uji keahlian"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Pengembangan karier PNS dilakukan melalui **mutasi, promosi, dan/atau penugasan khusus di luar Instansi Pemerintah**.",
        "alasan_kunci": "Opsi B merupakan tiga saluran mobilitas karier resmi bagi aparatur sipil negara di lingkungan kementerian. Pengembangan karier ditempuh melalui pergerakan horizontal (mutasi), pergerakan vertikal (promosi), maupun perluasan wawasan pengalaman melalui perbantuan penugasan di luar kementerian. Ketentuan perundang-undangan menetapkan mekanisme pengembangan karier melalui mutasi, promosi, dan penugasan khusus luar instansi. Oleh karena itu, opsi B merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Pembekalan teknis merupakan instrumen diklat dan bukan mekanisme pergerakan karier pegawai.",
            "C": "Perpanjangan kontrak merupakan rezim kepegawaian PPPK dan bukan pola karier PNS.",
            "D": "Cuti besar merupakan hak istirahat pegawai dan bukan mekanisme mobilitas karier dinas.",
            "E": "Asesmen dan pembinaan disiplin merupakan sarana evaluasi dan bukan saluran mobilitas karier."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 93
fixes[93] = {
    "pertanyaan": "Seorang pejabat administrator Kemenkeu ditugaskan berdinas pada kementerian koordinator. Status jabatannya diatur dengan ketentuan...",
    "pilihan": {
        "A": "Tetap memegang jabatan definitif dan menerima gaji ganda",
        "B": "Diberhentikan tetap dari status korps pegawai negeri sipil",
        "C": "Wajib mengajukan surat pensiun dini sukarela dari instansi",
        "D": "Diberhentikan dari jabatan lamanya dan diangkat di instansi baru",
        "E": "Dinonaktifkan sementara dari keanggotaan aparatur kementerian"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "PNS yang ditugaskan di luar instansi induk **diberhentikan dari jabatannya pada instansi induk dan diangkat dalam jabatan pada instansi penerima**.",
        "alasan_kunci": "Opsi D merupakan status administrasi jabatan bagi aparatur yang melaksanakan penugasan di luar instansi induk. Pelepasan jabatan pada instansi asal dilakukan agar posisi tersebut dapat diisi pejabat baru, sementara pegawai bersangkutan dilantik menduduki jabatan definitif pada kementerian penerima tanpa memutus status kepegawaian PNS-nya. Ketentuan regulasi menetapkan pegawai diberhentikan dari jabatan lama dan diangkat pada instansi baru. Oleh sebab itu, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Rangkap jabatan dan penerimaan gaji ganda dilarang keras dalam hukum keuangan negara.",
            "B": "Penugasan di kementerian lain tidak memutus ikatan status kewarganegaraan PNS pegawai.",
            "C": "Penugasan antar-instansi merupakan pengabdian aktif dan bukan alasan pengajuan pensiun dini.",
            "E": "Pegawai tetap berstatus ASN aktif pada instansi penerima dan bukan pegawai nonaktif."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 94
fixes[94] = {
    "pertanyaan": "Jalur pengembangan karier aparatur dalam Jabatan Fungsional (JF) di Kemenkeu yang BUKAN merupakan mekanisme resmi adalah...",
    "pilihan": {
        "A": "Pengangkatan pertama formasi jabatan fungsional",
        "B": "Perpindahan tugas kepegawaian dari jabatan lain",
        "C": "Penyesuaian kualifikasi keahlian atau inpassing",
        "D": "Penetapan otomatis tanpa verifikasi angka kredit",
        "E": "Promosi kenaikan jenjang jabatan fungsional ahli"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Pengangkatan JF dilakukan melalui: **pengangkatan pertama, perpindahan dari jabatan lain, penyesuaian (inpassing), dan promosi**. Tidak ada penetapan otomatis tanpa syarat.",
        "alasan_kunci": "Opsi D merupakan mekanisme nonprosedural yang bertentangan dengan prinsip meritokrasi jabatan fungsional. Pengangkatan dan kenaikan jenjang fungsional wajib melalui pengujian kompetensi serta pemenuhan angka kredit yang terverifikasi secara ketat. Ketentuan perundang-undangan menetapkan empat jalur resmi JF yaitu pengangkatan pertama, perpindahan, inpassing, dan promosi. Dengan demikian, opsi D merupakan jawaban pengecualian yang tepat.",
        "bedah_pengecoh": {
            "A": "Pengangkatan pertama merupakan jalur masuk resmi bagi CPNS yang melamar pada formasi JF.",
            "B": "Perpindahan dari jabatan lain merupakan jalur resmi peralihan struktural/pelaksana ke JF.",
            "C": "Inpassing merupakan jalur resmi penyesuaian bagi kebutuhan penataan kelembagaan birokrasi.",
            "E": "Promosi merupakan jalur kenaikan jenjang jabatan fungsional bagi yang berprestasi unggul."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 95
fixes[95] = {
    "pertanyaan": "Syarat mutlak yang wajib dipenuhi oleh pejabat fungsional untuk dapat naik jenjang jabatan satu tingkat lebih tinggi adalah...",
    "pilihan": {
        "A": "Mendapatkan rekomendasi kepemimpinan dari kepala daerah",
        "B": "Telah mencapai usia minimal lima puluh lima tahun kalender",
        "C": "Telah memenuhi perolehan angka kredit yang dipersyaratkan",
        "D": "Memiliki masa kerja pengabdian minimal sepuluh tahun dinas",
        "E": "Menyelesaikan penugasan operasional pada kantor terluar"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Kenaikan jenjang JF mensyaratkan **pemenuhan angka kredit kumulatif minimal**, lulus uji kompetensi, dan tersedianya lowongan formasi.",
        "alasan_kunci": "Opsi C merupakan tolok ukur utama produktivitas dan kepakaran teknis pejabat fungsional dalam birokrasi. Kenaikan jenjang jabatan fungsional menuntut pembuktian kinerja melalui akumulasi angka kredit butir kegiatan profesi serta kelulusan uji kompetensi. Ketentuan hukum kepegawaian menetapkan syarat kenaikan jenjang JF adalah pemenuhan angka kredit yang dipersyaratkan. Oleh karena itu, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Rekomendasi kepala daerah bukan syarat substantif bagi pejabat fungsional kementerian.",
            "B": "Kenaikan jenjang JF ditentukan oleh capaian kinerja profesi dan bukan faktor ambang usia.",
            "D": "Masa kerja sepuluh tahun bukan syarat mutlak apabila angka kredit telah terpenuhi lebih cepat.",
            "E": "Penugasan kantor terluar bukan syarat limitatif kenaikan jenjang jabatan fungsional."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 96
fixes[96] = {
    "pertanyaan": "Ketentuan mengenai mutasi dan promosi bagi PNS Kementerian Keuangan yang bertugas pada Unit Non-Eselon mengatur bahwa...",
    "pilihan": {
        "A": "Pegawai non-eselon dilarang memperoleh promosi jabatan",
        "B": "Seluruh hak karier kepegawaian dinonaktifkan sementara",
        "C": "Diprioritaskan menduduki jabatan fungsional pelaksana",
        "D": "Hanya dapat dimutasi pada sesama unit penugasan khusus",
        "E": "Tetap memiliki hak promosi dan mutasi sesuai kompetensi"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "PNS pada unit non-eselon **tetap memiliki hak yang sama dalam pengembangan karier, mutasi, dan promosi** berdasarkan kualifikasi, kompetensi, dan kinerja.",
        "alasan_kunci": "Opsi E merupakan jaminan kesetaraan hak karier bagi seluruh pegawai aparatur sipil negara di lingkungan kementerian. Penugasan pada unit non-eselon (seperti badan pengelola dana atau komite khusus) tidak mengurangi hak aparatur untuk berkompetisi meraih promosi dan mutasi jabatan reguler. Ketentuan regulasi menetapkan bahwa pegawai pada unit non-eselon tetap memiliki hak promosi dan mutasi sesuai kompetensi. Oleh sebab itu, opsi E merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Penugasan khusus pada unit non-eselon tidak menghapuskan hak promosi karier pegawai.",
            "B": "Status kepegawaian dan pembinaan talenta tetap melekat aktif selama masa penugasan unit.",
            "C": "Penempatan diarahkan sesuai kualifikasi dan kompetensi nyata, bukan pembatasan pelaksana.",
            "D": "Pegawai dapat kembali bermutasi ke struktur unit eselon reguler kementerian induk."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 97
fixes[97] = {
    "pertanyaan": "Seorang pejabat fungsional yang sebelumnya ditugaskan menjadi pejabat negara telah selesai bertugas. Pemulihan jabatan fungsionalnya ditempuh melalui...",
    "pilihan": {
        "A": "Pengangkatan pertama melalui jalur penerimaan formasi baru",
        "B": "Mekanisme pengangkatan kembali ke dalam Jabatan Fungsional",
        "C": "Penetapan status penugasan penyesuaian berkala kepegawaian",
        "D": "Pemberian dispensasi kelulusan tanpa uji portofolio kinerja",
        "E": "Pengalihan otomatis menjadi pejabat administrasi pengawas"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Pejabat Fungsional yang berhenti karena diangkat menjadi Pejabat Negara **dapat diangkat kembali dalam Jabatan Fungsional yang didudukinya** setelah selesai menjalankan tugas.",
        "alasan_kunci": "Opsi B merupakan jalur resmi pemulihan profesi aparatur sipil negara pasca pengabdian sebagai pejabat negara. Karena pegawai dibebaskan sementara dari tugas profesi selama menjabat di lembaga negara, yang bersangkutan berhak diangkat kembali pada jenjang jabatan fungsional terakhir yang didudukinya. Ketentuan regulasi menetapkan pemulihan ditempuh melalui mekanisme pengangkatan kembali dalam Jabatan Fungsional. Dengan demikian, opsi B merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Pengangkatan pertama hanya berlaku bagi calon aparatur yang baru pertama kali memasuki JF.",
            "C": "Penyesuaian berkala diperuntukkan bagi inpassing massal dan bukan pemulihan purnatugas.",
            "D": "Pengangkatan kembali tetap memperhatikan formasi yang lowong dan syarat kepegawaian sah.",
            "E": "Pejabat fungsional berhak kembali ke rumpun profesinya dan tidak dialihkan ke pengawas."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 99
fixes[99] = {
    "pertanyaan": "Jalur pengangkatan ke dalam Jabatan Fungsional dalam rangka penataan organisasi atau pembentukan jabatan fungsional baru disebut jalur...",
    "pilihan": {
        "A": "Penyesuaian atau inpassing jabatan fungsional",
        "B": "Perpindahan tugas kepegawaian antar-instansi",
        "C": "Pengangkatan pertama pelamar formasi kementerian",
        "D": "Promosi kenaikan jenjang jabatan profesi unggul",
        "E": "Penyetaraan penugasan berkala aparatur sipil"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Pengangkatan melalui **penyesuaian (inpassing)** dilaksanakan untuk kepentingan penataan organisasi atau penetapan JF baru.",
        "alasan_kunci": "Opsi A merupakan mekanisme transisi kelembagaan resmi untuk mengakomodasi penataan birokrasi pemerintahan. Jalur inpassing membuka kesempatan bagi PNS yang telah berpengalaman di bidang tugas terkait untuk dialihkan menduduki jabatan fungsional baru sesuai kualifikasinya. Ketentuan perundang-undangan menetapkan pengangkatan dalam rangka penataan organisasi dilakukan melalui penyesuaian atau inpassing. Oleh karena itu, opsi A merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "B": "Perpindahan tugas antar-instansi merupakan jalur mobilitas reguler perorangan di luar inpassing.",
            "C": "Pengangkatan pertama diperuntukkan bagi formasi awal CPNS yang melamar langsung ke JF.",
            "D": "Promosi merupakan kenaikan jenjang fungsional berdasarkan prestasi kinerja angka kredit.",
            "E": "Penyetaraan penugasan berkala bukan penamaan resmi mekanisme pengangkatan jabatan fungsional."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Apply fixes to Paket 3
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

print(f"Applied {applied} fixes to Paket 3.")

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_03.json', 'w', encoding='utf-8') as f:
    json.dump(paket3, f, ensure_ascii=False, indent=2)

print("Saved updated Paket 3 JSON.")

import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_02.json", "r", encoding="utf-8") as f:
    pkg = json.load(f)

with open("modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

modul_pages = {p["page"]: p["text"] for p in modul["pages"]}

updates = {}

# Q4 (Hlm 15, Key A)
updates[4] = {
    "pertanyaan": "Pemahaman yang baik mengenai situasi dan karakteristik pegawai sangat membantu pimpinan dalam...",
    "pilihan": {
        "A": "Membuat strategi di organisasi",
        "B": "Menata alokasi anggaran program prioritas",
        "C": "Menyusun standar prosedur operasional dinas",
        "D": "Menyelaraskan sasaran kinerja unit kerja",
        "E": "Mengembangkan sistem pelaporan terpadu"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Pemahaman yang baik mengenai situasi dan karakteristik pegawai sangat membantu pimpinan dalam **membuat strategi di organisasi**.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena pemahaman terhadap karakteristik pegawai dan cara mereka bersikap menjadi landasan utama bagi pimpinan dalam menentukan arah kebijakan. Pengetahuan atas dinamika kerja membantu perumusan langkah strategis organisasi secara efektif. Dengan demikian, pembuatan strategi menjadi manfaat langsung dari pemahaman situasi pegawai.",
        "bedah_pengecoh": {
            "A": "Merupakan manfaat nyata pemahaman karakteristik pegawai dalam membuat strategi di organisasi.",
            "B": "Kurang tepat karena penataan alokasi anggaran berfokus pada aspek perbendaharaan dan perencanaan fiskal.",
            "C": "Kurang tepat karena penyusunan standar operasional menitikberatkan pada standardisasi alur birokrasi teknis.",
            "D": "Kurang tepat karena penyelarasan sasaran kinerja merupakan instrumen manajemen kinerja individu dan unit.",
            "E": "Kurang tepat karena pengembangan sistem pelaporan terpadu berkaitan dengan tata kelola akuntabilitas data."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q5 (Hlm 15, Key C)
updates[5] = {
    "pertanyaan": "Pimpinan unit melibatkan berbagai elemen staf dalam perumusan strategi kantor agar mereka dapat...",
    "pilihan": {
        "A": "Menyusun rencana aksi operasional terpadu",
        "B": "Meningkatkan efisiensi alur kerja birokrasi",
        "C": "Melakukan kolaborasi dan berinovasi dengan baik",
        "D": "Memperkuat komitmen akuntabilitas kedinasan",
        "E": "Mengoptimalkan pemanfaatan teknologi digital"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Dalam membuat strategi, pelibatan banyak pihak bertujuan agar para pegawai dapat **melakukan kolaborasi dan berinovasi dengan baik** sehingga tujuan organisasi terealisasi.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena pelibatan berbagai pihak dalam penyusunan strategi ditujukan secara spesifik untuk membangun wadah kolaborasi dan inovasi bersama. Ketika pegawai dilibatkan, mereka merasa memiliki andil dalam keputusan organisasi sehingga pencapaian tujuan bersama menjadi lebih terarah. Oleh sebab itu, ruang kolaborasi dan inovasi merupakan tujuan utama pelibatan staf.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena penyusunan rencana aksi operasional merupakan tahap teknis penjabaran rencana kerja.",
            "B": "Kurang tepat karena efisiensi alur kerja birokrasi berfokus pada penyederhanaan tata kelola administrasi.",
            "C": "Merupakan tujuan utama pelibatan banyak pihak dalam perumusan strategi organisasi.",
            "D": "Kurang tepat karena komitmen akuntabilitas kedinasan berkaitan dengan pertanggungjawaban hasil kerja.",
            "E": "Kurang tepat karena pemanfaatan teknologi digital berorientasi pada modernisasi sarana prasarana dinas."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q6 (Hlm 15, Key B)
updates[6] = {
    "pertanyaan": "Tujuan organisasi lebih mudah dicapai ketika pegawai memiliki rasa kepemilikan tinggi karena...",
    "pilihan": {
        "A": "Memperoleh target kinerja yang terukur dan realistis",
        "B": "Merasa dilibatkan dalam pengambilan keputusan organisasi",
        "C": "Mendapatkan kesempatan pengembangan kompetensi berkala",
        "D": "Memiliki lingkungan kerja fisik yang kondusif dan aman",
        "E": "Menerima umpan balik konstruktif atas capaian penugasan"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Tujuan organisasi dapat terealisasi karena tingginya **rasa memiliki (sense of belonging)** dari pegawai yang **merasa dilibatkan dalam pengambilan keputusan**.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena rasa memiliki yang mendalam tumbuh secara alamiah saat pegawai diberikan ruang berkontribusi dalam proses pengambilan keputusan strategis. Keterlibatan tersebut menumbuhkan tanggung jawab moral untuk bersama-sama merealisasikan sasaran yang telah disepakati. Dengan demikian, pelibatan dalam keputusan menjadi sumber utama timbulnya rasa memiliki.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena target kinerja yang realistis berfokus pada kejelasan kontrak sasaran kerja aparatur.",
            "B": "Merupakan faktor penumbuh rasa memiliki (sense of belonging) yang tinggi menurut materi organisasi.",
            "C": "Kurang tepat karena pengembangan kompetensi berkala merupakan pemenuhan hak peningkatan kapasitas SDM.",
            "D": "Kurang tepat karena lingkungan kerja fisik kondusif berkaitan dengan kenyamanan sarana operasional.",
            "E": "Kurang tepat karena umpan balik konstruktif merupakan bagian dari mekanisme evaluasi pembinaan pegawai."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q7 (Hlm 15, Key E)
updates[7] = {
    "pertanyaan": "Pengamatan mendalam terhadap cara pegawai dalam organisasi bersikap membantu pimpinan untuk...",
    "pilihan": {
        "A": "Menata struktur pembagian kerja unit secara proporsional",
        "B": "Menetapkan alokasi anggaran berbasis kinerja tahunan",
        "C": "Menyusun pedoman teknis standar operasional prosedur",
        "D": "Mengoptimalkan pemanfaatan sarana prasarana kedinasan",
        "E": "Menentukan strategi paling tepat diterapkan bagi organisasi"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Dengan mengetahui bagaimana cara pegawai dalam organisasi bersikap, pimpinan dapat **menentukan strategi apa yang paling tepat diterapkan** untuk mencapai tujuan organisasi.",
        "alasan_kunci": "Opsi E merupakan jawaban yang tepat karena pemahaman atas pola perilaku pegawai memungkinkan perumusan metode dan pendekatan implementasi yang paling selaras dengan kondisi riil organisasi. Ketepatan strategi organisasi sangat bergantung pada kecocokan antara rencana pimpinan dan kesiapan budaya kerja para pegawainya. Oleh karena itu, penentuan strategi yang tepat merupakan fungsi utama analisis sikap pegawai.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena penataan struktur pembagian kerja berkaitan dengan desain organisasi dan peta jabatan.",
            "B": "Kurang tepat karena alokasi anggaran berbasis kinerja merupakan instrumen perencanaan keuangan negara.",
            "C": "Kurang tepat karena penyusunan pedoman teknis standar operasional bertujuan menjaga konsistensi prosedur.",
            "D": "Kurang tepat karena pengoptimalan sarana prasarana berkaitan dengan efisiensi pengelolaan aset dinas.",
            "E": "Merupakan tujuan penetapan langkah manajerial saat mengetahui cara pegawai dalam organisasi bersikap."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q9 (Hlm 15, Key C)
updates[9] = {
    "pertanyaan": "Salah satu poin penting peran budaya organisasi bagi keberlanjutan sumber daya manusia adalah...",
    "pilihan": {
        "A": "Menyempurnakan sistem pelaporan akuntabilitas kinerja",
        "B": "Mempercepat proses digitalisasi tata kelola kearsipan",
        "C": "Menarik talent berbobot atau bibit unggul ke organisasi",
        "D": "Memperluas cakupan layanan bimbingan teknis kepegawaian",
        "E": "Menyelaraskan target penerimaan negara dengan potensi riil"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Poin penting peran budaya organisasi adalah kehadirannya untuk **menarik talent berbobot atau bibit unggul** agar bergabung dan mengabdi di organisasi.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena lingkungan budaya kerja yang sehat dan positif menjadi daya tarik kuat bagi individu berprestasi tinggi untuk berkarier di instansi tersebut. Ketersediaan talenta unggul secara berkelanjutan merupakan faktor kunci keberhasilan jangka panjang organisasi. Dengan demikian, penarikan bibit unggul merupakan peran penting budaya organisasi bagi SDM.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena penyempurnaan sistem akuntabilitas berfokus pada pelaporan hasil kerja institusi.",
            "B": "Kurang tepat karena percepatan digitalisasi kearsipan merupakan aspek otomatisasi tata kelola naskah.",
            "C": "Merupakan peran penting budaya organisasi yang sehat dalam menjaring bibit unggul bagi instansi.",
            "D": "Kurang tepat karena bimbingan teknis kepegawaian adalah program pembinaan dan transfer pengetahuan.",
            "E": "Kurang tepat karena penyelarasan target penerimaan negara berkaitan dengan proyeksi fiskal perbendaharaan."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q10 (Hlm 15, Key A)
updates[10] = {
    "pertanyaan": "Dengan memiliki individu berkualitas sebagai SDM, instansi pemerintah akan lebih mudah dalam...",
    "pilihan": {
        "A": "Mencapai kesuksesan berkelanjutan yang dicita-citakan",
        "B": "Mewujudkan target penyerapan anggaran secara proporsional",
        "C": "Mengembangkan kemitraan strategis dengan pemangku kebijakan",
        "D": "Menyederhanakan prosedur birokrasi perizinan terpadu",
        "E": "Mempertahankan predikat kepatuhan standar pelayanan publik"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Dengan memiliki individu berkualitas sebagai SDM, organisasi akan lebih mudah **mencapai kesuksesan berkelanjutan seperti yang dicita-citakan** berkat dukungan para talenta.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena kualitas aparatur yang mumpuni menjamin konsistensi kinerja dan daya adaptasi institusi dalam menghadapi tantangan masa depan. Dukungan talenta terbaik menjadi fondasi utama kesinambungan capaian visi organisasi dari waktu ke waktu. Oleh sebab itu, pencapaian kesuksesan berkelanjutan adalah hasil dari pengelolaan SDM berkualitas.",
        "bedah_pengecoh": {
            "A": "Merupakan capaian utama yang diraih organisasi berkat kehadiran individu berkualitas sebagai SDM.",
            "B": "Kurang tepat karena target penyerapan anggaran merupakan indikator kinerja keuangan operasional tahunan.",
            "C": "Kurang tepat karena kemitraan strategis berfokus pada aspek koordinasi dan komunikasi kelembagaan.",
            "D": "Kurang tepat karena penyederhanaan prosedur perizinan berfokus pada deregulasi pelayanan birokrasi.",
            "E": "Kurang tepat karena predikat standar pelayanan publik berkaitan dengan kepatuhan tata kelola loket."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q17 (Hlm 16, Key A)
updates[17] = {
    "pertanyaan": "Bagi seluruh aparatur sipil negara, nilai BerAKHLAK diposisikan sebagai panduan perilaku serta menjadi...",
    "pilihan": {
        "A": "Fondasi budaya kerja ASN yang profesional",
        "B": "Pedoman penyusunan standar kompetensi teknis",
        "C": "Kerangka acuan pengukuran efektivitas satker",
        "D": "Tolok ukur perumusan kebijakan reformasi dinas",
        "E": "Rujukan penyusunan rencana strategis instansi"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Nilai BerAKHLAK merupakan panduan perilaku bagi ASN yang harus dijalankan dengan tanggung jawab serta menjadi **fondasi budaya kerja ASN yang profesional**.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena Core Values BerAKHLAK dirancang sebagai pijakan dasar dalam membangun mentalitas dan etos kerja seluruh aparatur secara nasional. Nilai-nilai ini menjadi landasan moral dan operasional agar budaya kerja aparatur semakin adaptif dan profesional. Dengan demikian, BerAKHLAK menjadi fondasi budaya kerja ASN yang kokoh.",
        "bedah_pengecoh": {
            "A": "Merupakan kedudukan mutlak nilai BerAKHLAK sebagai fondasi budaya kerja profesional aparatur.",
            "B": "Kurang tepat karena penyusunan standar kompetensi teknis merupakan pedoman pengelompokan jabatan.",
            "C": "Kurang tepat karena pengukuran efektivitas satker menggunakan instrumen evaluasi kelembagaan.",
            "D": "Kurang tepat karena perumusan kebijakan reformasi birokrasi mencakup area perubahan tata laksana.",
            "E": "Kurang tepat karena rencana strategis instansi berfokus pada perencanaan jangka menengah kementerian."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q24 (Hlm 17, Key D)
updates[24] = {
    "pertanyaan": "Dalam panduan perilaku Kompeten, upaya aparatur untuk terus meningkatkan kompetensi diri bertujuan agar...",
    "pilihan": {
        "A": "Memenuhi ekspektasi layanan pemangku kepentingan",
        "B": "Mewujudkan tata kelola sarana dinas secara efisien",
        "C": "Membangun suasana rukun dalam keberagaman pegawai",
        "D": "Menjawab tantangan lingkungan yang selalu berubah",
        "E": "Menghasilkan nilai tambah melalui kemitraan kerja"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Panduan perilaku nilai **Kompeten** mewajibkan setiap aparatur untuk **meningkatkan kompetensi diri untuk menjawab tantangan yang selalu berubah**.",
        "alasan_kunci": "Opsi D merupakan jawaban yang tepat karena dinamika regulasi dan perkembangan teknologi menuntut aparatur untuk selalu memperbarui pengetahuannya. Peningkatan keahlian secara berkelanjutan menjadi kunci agar pelayanan publik tetap relevan dan berkualitas tinggi. Oleh sebab itu, menjawab tantangan yang selalu berubah adalah tujuan peningkatan kapasitas aparatur.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena memenuhi ekspektasi layanan masyarakat merupakan tujuan nilai Berorientasi Pelayanan.",
            "B": "Kurang tepat karena penggunaan sarana dinas secara efisien merupakan panduan perilaku nilai Akuntabel.",
            "C": "Kurang tepat karena membangun kerukunan keberagaman merupakan perwujudan dari nilai Harmonis.",
            "D": "Merupakan tujuan eksplisit dari panduan perilaku meningkatkan kompetensi diri pada nilai Kompeten.",
            "E": "Kurang tepat karena menghasilkan nilai tambah melalui kerja sama merupakan panduan nilai Kolaboratif."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q26 (Hlm 17, Key E)
updates[26] = {
    "pertanyaan": "Sikap suka menolong dan saling mendukung antarrekan sekerja pada nilai Harmonis bertujuan untuk...",
    "pilihan": {
        "A": "Mempercepat penyelesaian target capaian kinerja unit",
        "B": "Mengoptimalkan pemanfaatan sumber daya bersama satker",
        "C": "Meningkatkan akurasi penyusunan laporan pertanggungjawaban",
        "D": "Memperkuat komitmen kesetiaan terhadap tugas kedinasan",
        "E": "Membangun lingkungan kerja kedinasan yang kondusif"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Panduan perilaku nilai **Harmonis** mencakup sikap menghargai setiap orang, suka menolong, serta **membangun lingkungan kerja yang kondusif**.",
        "alasan_kunci": "Opsi E merupakan jawaban yang tepat karena suasana tolong-menolong memperkuat soliditas tim dan menghilangkan ketegangan dalam pelaksanaan tugas sehari-hari. Hubungan interpersonal yang positif secara langsung menciptakan atmosfer kerja yang menyenangkan dan produktif. Dengan demikian, terwujudnya iklim kerja kondusif menjadi tujuan dari perilaku suka menolong.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena kecepatan penyelesaian target kinerja berorientasi pada ketuntasan nilai Kompeten.",
            "B": "Kurang tepat karena pemanfaatan sumber daya bersama merupakan panduan perilaku pada nilai Kolaboratif.",
            "C": "Kurang tepat karena akurasi laporan pertanggungjawaban merupakan wujud kecermatan nilai Akuntabel.",
            "D": "Kurang tepat karena komitmen kesetiaan kedinasan merupakan cerminan pengamalan dari nilai Loyal.",
            "E": "Merupakan tujuan utama dari panduan perilaku suka menolong pada nilai Harmonis."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q29 (Hlm 17, Key D)
updates[29] = {
    "pertanyaan": "Ketika instansi menerapkan sistem administrasi digital terintegrasi, aparatur segera menyesuaikan diri demi...",
    "pilihan": {
        "A": "Menjaga kerahasiaan data jabatan dan rahasia negara",
        "B": "Membangun hubungan kemitraan strategis lintas instansi",
        "C": "Memberikan kepuasan pelayanan bagi pemangku kepentingan",
        "D": "Cepat beradaptasi dalam menghadapi perubahan lingkungan",
        "E": "Mewujudkan efektivitas penggunaan kekayaan milik negara"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Pada nilai **Adaptif**, aparatur dituntut memiliki kelincahan untuk **cepat menyesuaikan diri menghadapi perubahan** alur kerja maupun teknologi.",
        "alasan_kunci": "Opsi D merupakan jawaban yang tepat karena kesiapan mengadopsi sistem digital menunjukkan sikap tanggap terhadap pembaruan metode kerja birokrasi. Aparatur yang adaptif tidak menunggu lama untuk mempelajari hal baru demi kelancaran operasional kantor. Oleh sebab itu, adaptasi terhadap perubahan merupakan perwujudan utama perilaku nilai Adaptif.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena menjaga rahasia jabatan dan negara merupakan panduan perilaku dari nilai Loyal.",
            "B": "Kurang tepat karena membangun kemitraan lintas instansi merupakan panduan perilaku nilai Kolaboratif.",
            "C": "Kurang tepat karena kepuasan pemangku kepentingan merupakan orientasi nilai Berorientasi Pelayanan.",
            "D": "Merupakan wujud nyata panduan perilaku cepat menyesuaikan diri pada nilai Adaptif.",
            "E": "Kurang tepat karena efektivitas penggunaan kekayaan negara merupakan panduan nilai Akuntabel."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q30 (Hlm 17, Key B)
updates[30] = {
    "pertanyaan": "Sikap terbuka dalam bekerja sama pada nilai Kolaboratif diarahkan secara jelas guna...",
    "pilihan": {
        "A": "Menjaga keselarasan etika dan kode etik aparatur",
        "B": "Menghasilkan nilai tambah bagi pencapaian bersama",
        "C": "Memelihara reputasi positif korps kepegawaian dinas",
        "D": "Mempercepat perbaikan layanan publik berkelanjutan",
        "E": "Mewujudkan ketepatan waktu penyelesaian laporan dinas"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Nilai **Kolaboratif** menuntut aparatur bersikap **terbuka dalam bekerja sama untuk menghasilkan nilai tambah** bagi organisasi.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena kerja sama yang sinergis antarseksi mampu menggabungkan berbagai keahlian untuk melahirkan solusi terbaik. Kemitraan kerja tidak sekadar formalitas, melainkan sarana menciptakan efektivitas dan dampak positif yang lebih besar. Dengan demikian, penciptaan nilai tambah menjadi tujuan pokok dari keterbukaan kerja sama.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena kepatuhan kode etik merupakan fokus utama pelaksanaan tugas pada nilai Integritas.",
            "B": "Merupakan rumusan tujuan eksplisit keterbukaan kerja sama pada panduan perilaku nilai Kolaboratif.",
            "C": "Kurang tepat karena menjaga nama baik instansi dan negara merupakan panduan perilaku nilai Loyal.",
            "D": "Kurang tepat karena perbaikan berkesinambungan merupakan panduan nilai Berorientasi Pelayanan.",
            "E": "Kurang tepat karena ketepatan waktu penyelesaian laporan mencerminkan kecermatan nilai Akuntabel."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q31 (Hlm 18, Key C)
updates[31] = {
    "pertanyaan": "Pencetusan employer branding #BanggaMelayaniBangsa menegaskan kedudukan ASN sebagai...",
    "pilihan": {
        "A": "Insan birokrasi pengawal kebijakan fiskal nasional",
        "B": "Penggerak utama transformasi digital instansi negara",
        "C": "Profesi yang bangga dalam melayani kepentingan bangsa",
        "D": "Aparatur perencana standar operasional layanan publik",
        "E": "Agen pembaruan tata kelola akuntabilitas kedinasan"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Employer branding **#BanggaMelayaniBangsa** menunjukkan bahwa ASN adalah **profesi yang bangga dalam melayani bangsa**.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena semboyan ini menanamkan kebanggaan luhur dan jiwa pengabdian bagi seluruh aparatur negara. ASN diposisikan bukan sebagai pejabat birokratis yang dilayani, melainkan pelayan masyarakat yang mengabdi demi kemakmuran negeri. Oleh karena itu, profesi yang bangga melayani bangsa adalah makna sejati dari branding tersebut.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena pengawal kebijakan fiskal merupakan tugas spesifik pada rumpun perbendaharaan negara.",
            "B": "Kurang tepat karena penggerak transformasi digital merupakan peran fungsional analis teknologi informasi.",
            "C": "Merupakan makna filosofis utama employer branding #BanggaMelayaniBangsa yang dicetuskan Presiden.",
            "D": "Kurang tepat karena perencana standar layanan merupakan fungsi tata laksana kelembagaan satker.",
            "E": "Kurang tepat karena agen pembaruan akuntabilitas berfokus pada pengawasan kinerja internal instansi."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q32 (Hlm 18, Key A)
updates[32] = {
    "pertanyaan": "Salah satu alasan mendasar mengapa profesi ASN patut dibanggakan oleh setiap aparatur adalah karena...",
    "pilihan": {
        "A": "Diberi pengakuan dan penghargaan yang adil",
        "B": "Diberi wewenang menyusun regulasi kelembagaan",
        "C": "Diberi keleluasaan menetapkan metode audit kerja",
        "D": "Diberi penugasan dinas pada lembaga mitra strategis",
        "E": "Diberi tanggung jawab memimpin program lintas seksi"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Profesi ASN patut dibanggakan karena ASN **diberi pengakuan dan penghargaan yang adil**, kesempatan meningkatkan kompetensi, serta kesempatan terbuka untuk berkarier.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena sistem merit aparatur sipil negara menjamin apresiasi yang setara atas setiap dedikasi dan kinerja pegawai. Keadilan perlakuan ini memberikan kepastian rasa aman dan penghargaan yang layak atas pengabdian kepada bangsa. Dengan demikian, pengakuan dan penghargaan yang adil menjadi pilar kebanggaan berprofesi.",
        "bedah_pengecoh": {
            "A": "Merupakan salah satu alasan eksplisit mengapa profesi ASN patut dibanggakan menurut modul kepegawaian.",
            "B": "Kurang tepat karena wewenang regulasi kelembagaan merupakan mandat struktural jabatan tertentu.",
            "C": "Kurang tepat karena penetapan metode audit kerja merupakan ranah tugas auditor fungsional pemeriksa.",
            "D": "Kurang tepat karena penugasan pada lembaga mitra merupakan penugasan kedinasan khusus pimpinan.",
            "E": "Kurang tepat karena memimpin program lintas seksi adalah penugasan manajerial koordinatif."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q33 (Hlm 18, Key E)
updates[33] = {
    "pertanyaan": "Berikut ini adalah landasan mengapa profesi ASN patut dibanggakan dalam tata kelola aparatur, KECUALI...",
    "pilihan": {
        "A": "Diberi pengakuan dan penghargaan yang adil",
        "B": "Diberi kesempatan meningkatkan kompetensi seluasnya",
        "C": "Diberi kesempatan terbuka untuk berkarier",
        "D": "Diberi ruang untuk mengabdi kepada bangsa",
        "E": "Diberi otonomi penuh menentukan standar belanja dinas"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Alasan profesi ASN patut dibanggakan meliputi: **diberi pengakuan dan penghargaan yang adil**, **diberi kesempatan meningkatkan kompetensi seluas-luasnya**, serta **diberi kesempatan terbuka untuk berkarier**.",
        "alasan_kunci": "Opsi E merupakan jawaban yang tepat untuk soal pengecualian karena standar belanja dinas diatur ketat melalui standar biaya masukan perbendaharaan negara dan tidak ditentukan secara mandiri oleh aparatur. Adapun opsi A, B, C, dan D merupakan alasan nyata kebanggaan profesi sebagaimana tercantum dalam materi modul. Oleh sebab itu, opsi E adalah pilihan yang bukan merupakan alasan kebanggaan profesi ASN.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena pengakuan dan penghargaan yang adil adalah alasan resmi kebanggaan ASN.",
            "B": "Kurang tepat karena kesempatan meningkatkan kapasitas seluas-luasnya adalah hak kebanggaan profesi ASN.",
            "C": "Kurang tepat karena kesempatan terbuka dalam jenjang karier dijamin dalam manajemen ASN.",
            "D": "Kurang tepat karena ruang pengabdian kepada bangsa merupakan hakikat utama pengabdian aparatur.",
            "E": "Tepat sebagai pengecualian karena penetapan standar belanja dinas bukan merupakan hak atau kebanggaan ASN."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q34 (Hlm 18, Key B)
updates[34] = {
    "pertanyaan": "Sebagai wujud kebanggaan berprofesi, seorang aparatur menyeimbangkan ekspektasi organisasi melalui upaya...",
    "pilihan": {
        "A": "Menjaga kemitraan harmonis dengan seluruh pemangku tugas",
        "B": "Terus meningkatkan kinerja kedinasan secara berkelanjutan",
        "C": "Mengoptimalkan pemanfaatan sarana penunjang tugas satker",
        "D": "Memperkuat ketaatan pada prosedur baku operasional dinas",
        "E": "Memastikan akuntabilitas pelaporan pertanggungjawaban kas"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Dengan kebanggaan berprofesi, ASN diharapkan mampu menyeimbangkan ekspektasi organisasi terhadap dirinya dengan **meningkatkan kinerja secara terus menerus**.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena peningkatan capaian kinerja yang berkesinambungan mencerminkan dedikasi nyata pegawai dalam membalas kepercayaan institusi. Sikap proaktif ini memastikan bahwa output kerja pegawai selalu sepadan dengan harapan tinggi organisasi publik. Dengan demikian, peningkatan kinerja terus menerus merupakan wujud keseimbangan ekspektasi.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena menjaga kemitraan harmonis merupakan pengamalan prinsip kolaborasi antarseksi.",
            "B": "Merupakan perwujudan utama upaya menyeimbangkan harapan dan ekspektasi organisasi menurut modul.",
            "C": "Kurang tepat karena pemanfaatan sarana penunjang tugas berkaitan dengan efisiensi pengelolaan aset dinas.",
            "D": "Kurang tepat karena ketaatan prosedur operasional mencerminkan kepatuhan terhadap regulasi kedinasan.",
            "E": "Kurang tepat karena akuntabilitas pelaporan kas merupakan cerminan ketelitian pengelolaan keuangan."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q35 (Hlm 18, Key D)
updates[35] = {
    "pertanyaan": "Nilai Dasar ASN dan Nilai Kementerian Keuangan saling mendukung karena berlandaskan konsep agar pegawai...",
    "pilihan": {
        "A": "Mewujudkan akuntabilitas pengelolaan anggaran negara",
        "B": "Meningkatkan produktivitas kerja berbasis teknologi",
        "C": "Membangun jejaring kerja sama lintas satuan kerja dinas",
        "D": "Mengelola pribadi yang selaras dengan tujuan tugasnya",
        "E": "Mengembangkan standar layanan publik yang solutif-aman"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Antara Nilai Dasar ASN dan Nilai Kementerian Keuangan saling mendukung karena memiliki konsep dasar yang sama, yaitu agar pegawai dapat **mengelola pribadi yang selaras dengan tujuan dari pelaksanaan tugasnya**.",
        "alasan_kunci": "Opsi D merupakan jawaban yang tepat karena kedua tatanan nilai tersebut berakar pada pembentukan karakter moral dan profesionalitas individu dalam birokrasi. Penyelarasan sikap mental aparatur dengan tujuan organisasi menjamin pelaksanaan kewajiban dinas secara berintegritas. Oleh karena itu, pengelolaan pribadi selaras tujuan tugas adalah konsep dasar perpaduan kedua nilai.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena akuntabilitas pengelolaan anggaran berfokus pada ranah teknis perbendaharaan.",
            "B": "Kurang tepat karena produktivitas kerja berbasis teknologi merupakan sasaran modernisasi sistem kantor.",
            "C": "Kurang tepat karena jejaring kerja sama lintas unit merupakan instrumen kelembagaan pada nilai Sinergi.",
            "D": "Merupakan konsep dasar yang melandasi keselarasan antara Nilai Dasar ASN dan Nilai Kementerian Keuangan.",
            "E": "Kurang tepat karena pengembangan standar layanan publik berorientasi pada kepuasan pengguna jasa loket."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q36 (Hlm 18, Key C)
updates[36] = {
    "pertanyaan": "Dalam peluncuran Core Values BerAKHLAK, Kementerian PANRB mengharapkan nilai-nilai yang telah ada di instansi...",
    "pilihan": {
        "A": "Dijadikan acuan tambahan khusus untuk unit pelayanan teknis",
        "B": "Disesuaikan formulasi kalimatnya mengikuti urutan BerAKHLAK",
        "C": "Tidak dihadapkan sebagai ketentuan yang saling bertentangan",
        "D": "Diterapkan bertahap berdasarkan kesiapan masing-masing balai",
        "E": "Diposisikan sebagai instrumen pelengkap regulasi kepegawaian"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Kementerian PANRB mengharapkan agar peluncuran Nilai Dasar BerAKHLAK **tidak dihadapkan dengan nilai-nilai yang telah ada di instansinya sebagai sesuatu yang bertentangan**.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena nilai-nilai instansi yang telah terbangun justru berfungsi memperkaya dan memperkuat nilai BerAKHLAK pada ranah operasional. Penyatuan semangat kedua nilai menciptakan harmoni budaya kerja tanpa harus merombak fondasi yang sudah berjalan baik. Dengan demikian, ketiadaan pertentangan nilai menjadi prinsip pokok yang diharapkan.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena nilai dasar berlaku mengikat seluruh struktur organisasi, bukan hanya unit teknis.",
            "B": "Kurang tepat karena instansi tidak diwajibkan mengubah rumusan bahasa nilai yang sudah berlaku.",
            "C": "Merupakan harapan resmi Kementerian PANRB dalam harmonisasi BerAKHLAK dengan nilai kementerian/lembaga.",
            "D": "Kurang tepat karena penerapan nilai dasar nasional diinstruksikan berlaku serentak di seluruh instansi.",
            "E": "Kurang tepat karena nilai organisasi berkedudukan sebagai acuan budaya dan perilaku kerja utama."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q52 (Hlm 20, Key D)
updates[52] = {
    "pertanyaan": "Sebagai organisasi modern dan berkembang, Kementerian Keuangan menggerakkan organisasinya dengan mengadopsi...",
    "pilihan": {
        "A": "Pola evaluasi kinerja berbasis manajemen risiko terpadu",
        "B": "Sistem perencanaan program berbasis penganggaran kinerja",
        "C": "Standar pelayanan prima berorientasi kepuasan masyarakat",
        "D": "Praktik-praktik manajemen yang sehat dalam organisasi dinas",
        "E": "Kerangka pembinaan karier aparatur berbasis uji kompetensi"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Sebagai organisasi modern, Kementerian Keuangan telah mengadopsi dan mengadaptasi **praktik-praktik manajemen yang sehat (best practices)** dalam menjalankan roda organisasinya.",
        "alasan_kunci": "Opsi D merupakan jawaban yang tepat karena penerapan best practices memastikan bahwa seluruh tata kelola birokrasi berstandar internasional dan akuntabel. Adopsi praktik manajemen yang sehat mendorong terciptanya budaya kerja yang efektif, transparan, dan terpercaya. Oleh sebab itu, praktik manajemen yang sehat menjadi landasan operasional kementerian.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena manajemen risiko terpadu merupakan salah satu instrumen pengendalian internal kantor.",
            "B": "Kurang tepat karena perencanaan berbasis anggaran kinerja merupakan metode penyusunan alokasi fiskal.",
            "C": "Kurang tepat karena standar pelayanan prima merupakan wujud operasional pada unit pelayanan publik.",
            "D": "Merupakan rumusan resmi adopsi manajemen yang sehat (best practices) sebagaimana dijelaskan pada modul.",
            "E": "Kurang tepat karena pembinaan karier berbasis uji kompetensi merupakan bagian dari manajemen talenta ASN."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q68 (Hlm 21, Key E)
updates[68] = {
    "pertanyaan": "Secara batiniah, integritas diartikan sebagai dorongan hati nurani untuk melaksanakan tanggung jawab dengan...",
    "pilihan": {
        "A": "Komitmen menjaga keharmonisan hubungan kerja sama internal",
        "B": "Ketuntasan bekerja secara cermat atas kompetensi profesional",
        "C": "Kesungguhan melayani demi pemenuhan kepuasan pemangku tugas",
        "D": "Semangat melakukan perbaikan berkelanjutan di segala bidang",
        "E": "Tekad yang mulia dalam melaksanakan setiap tugas kedinasan"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Integritas dapat diartikan sebagai dorongan hati nurani untuk melaksanakan tugas dan tanggung jawab dengan **tekad yang mulia**.",
        "alasan_kunci": "Opsi E merupakan jawaban yang tepat karena integritas berakar pada kemurnian niat dan komitmen luhur yang bersumber dari suara hati sanubari aparatur. Keikhlasan bertindak benar tanpa pamrih lahir dari tekad mulia dalam mengemban amanah pelayanan publik. Dengan demikian, pelaksanaan tugas berlandaskan tekad mulia menjadi hakikat batiniah integritas.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena menjaga keharmonisan kerja sama internal mencerminkan fokus utama dari nilai Sinergi.",
            "B": "Kurang tepat karena ketuntasan bekerja atas dasar kompetensi mencerminkan makna dari nilai Profesionalisme.",
            "C": "Kurang tepat karena melayani demi kepuasan pemangku kepentingan merupakan makna pokok dari nilai Pelayanan.",
            "D": "Kurang tepat karena semangat perbaikan berkelanjutan di segala bidang mencerminkan nilai Kesempurnaan.",
            "E": "Merupakan pengertian batiniah dari nilai Integritas sebagaimana tercantum dalam naskah modul resmi."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q71 (Hlm 22, Key D)
updates[71] = {
    "pertanyaan": "Seseorang dikatakan mempunyai integritas apabila tindakannya selaras dengan...",
    "pilihan": {
        "A": "Target kuantitatif capaian sasaran kinerja tahunan",
        "B": "Arahan kebijakan teknis operasional satuan kerja",
        "C": "Standar baku prosedur pelayanan prima masyarakat",
        "D": "Nilai, keyakinan, dan prinsip yang dipegangnya",
        "E": "Kesepakatan hasil musyawarah kelompok kerja dinas"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Seseorang dikatakan mempunyai integritas apabila tindakannya **sesuai dengan nilai, keyakinan, dan prinsip yang dipegangnya**.",
        "alasan_kunci": "Opsi D merupakan jawaban yang tepat karena integritas pribadi dicirikan oleh konsistensi teguh antara prinsip kebenaran yang dipercayai dengan perilaku lahiriah. Pegawai yang berintegritas tidak mudah goyah oleh desakan situasi karena memiliki pegangan nilai moral yang kokoh. Oleh karena itu, keselarasan dengan nilai dan prinsip yang dipegang adalah indikator seseorang berintegritas.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena target kuantitatif sasaran kinerja merupakan tolok ukur capaian output aparatur.",
            "B": "Kurang tepat karena arahan kebijakan teknis merupakan petunjuk pelaksanaan tugas pimpinan unit kerja.",
            "C": "Kurang tepat karena standar operasional pelayanan prima merupakan panduan baku tata kelola loket.",
            "D": "Merupakan definisi konseptual seseorang yang berintegritas menurut rujukan resmi pada naskah modul.",
            "E": "Kurang tepat karena kesepakatan hasil musyawarah kelompok kerja berorientasi pada mufakat tim kerja."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q72 (Hlm 22, Key B)
updates[72] = {
    "pertanyaan": "Salah satu ciri utama yang menandai bahwa seorang pegawai memiliki integritas tinggi adalah...",
    "pilihan": {
        "A": "Kemahiran memaparkan laporan kerja secara persuasif",
        "B": "Bersatunya kata dan perbuatan dalam bertindak dinas",
        "C": "Kecepatan memproses dokumen penugasan administrasi",
        "D": "Keluasan hubungan kerja sama dengan mitra lembaga",
        "E": "Ketelitian memeriksa kalkulasi data anggaran kantor"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Salah satu ciri seseorang yang berintegritas adalah **bersatunya kata dan perbuatan** dalam setiap perkataan dan tindakannya.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena kejujuran sejati tercermin ketika ucapan yang disampaikan selaras sejalan dengan tindakan nyata di lapangan. Pegawai yang berintegritas menjunjung tinggi kesatuan moral ini sehingga tidak pernah menampilkan perilaku kepura-puraan. Dengan demikian, bersatunya kata dan perbuatan menjadi ciri mutlak integritas tinggi.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena kemahiran memaparkan laporan kerja mencerminkan keterampilan komunikasi presentasi.",
            "B": "Merupakan ciri khas utama seseorang yang berintegritas sebagaimana ditegaskan dalam materi modul.",
            "C": "Kurang tepat karena kecepatan memproses dokumen administrasi mencerminkan ketangkasan nilai Pelayanan.",
            "D": "Kurang tepat karena keluasan hubungan kerja sama dengan mitra mencerminkan jejaring nilai Sinergi.",
            "E": "Kurang tepat karena ketelitian memeriksa kalkulasi anggaran mencerminkan kecermatan Profesionalisme."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q73 (Hlm 22, Key E)
updates[73] = {
    "pertanyaan": "Ditinjau dari karakter integritas, seorang aparatur sejati menjauhkan diri dari tipe figur yang...",
    "pilihan": {
        "A": "Bekerja dengan ketelitian tinggi dalam tata kelola",
        "B": "Berinteraksi ramah serta solutif melayani masyarakat",
        "C": "Berpegang teguh pada ideologi dan regulasi kedinasan",
        "D": "Menjaga kerahasiaan berkas jabatan secara bertanggung jawab",
        "E": "Memiliki banyak wajah dan penampilan sesuai motif pribadi"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Seorang yang mempunyai integritas **bukan tipe manusia dengan banyak wajah dan penampilan yang disesuaikan dengan motif dan kepentingan pribadinya**.",
        "alasan_kunci": "Opsi E merupakan jawaban yang tepat karena perilaku bermuka dua menunjukkan sikap hipokrit dan oportunistis yang bertolak belakang dengan kejujuran hati. Pegawai berintegritas selalu tulus dan konsisten dalam memegang prinsip tanpa memanipulasi citra diri demi keuntungan tertentu. Oleh sebab itu, tipe manusia dengan banyak wajah merupakan sifat yang dijauhi aparatur berintegritas.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena bekerja dengan ketelitian tinggi merupakan karakter positif nilai Profesionalisme.",
            "B": "Kurang tepat karena berinteraksi ramah dan solutif merupakan karakter positif Berorientasi Pelayanan.",
            "C": "Kurang tepat karena berpegang teguh pada ideologi dan regulasi merupakan cerminan luhur nilai Loyal.",
            "D": "Kurang tepat karena menjaga kerahasiaan berkas jabatan merupakan kewajiban etik aparatur negara.",
            "E": "Merupakan tipe manusia yang bukan mencerminkan integritas sebagaimana dirumuskan dalam naskah modul."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q76 (Hlm 22, Key D)
updates[76] = {
    "pertanyaan": "Dalam kerangka nilai integritas, hakikat berbicara jujur pada pelaksanaan tugas kedinasan dimaknai sebagai...",
    "pilihan": {
        "A": "Menyampaikan laporan kerja secara ringkas kepada pimpinan",
        "B": "Memberikan saran konstruktif demi kemajuan seksi pelayanan",
        "C": "Mengomunikasikan kebijakan instansi secara ramah dan santun",
        "D": "Berbicara sesuai kenyataan tanpa disembunyikan atau diubah",
        "E": "Menjelaskan alur prosedur operasional sesuai ketentuan baku"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "**Berbicara jujur** adalah berbicara sesuai kenyataan, **tidak ada yang disembunyikan atau dimanipulasi** dalam penyampaian informasi.",
        "alasan_kunci": "Opsi D merupakan jawaban yang tepat karena kejujuran menuntut keterbukaan fakta apa adanya tanpa rekayasa data untuk kepentingan apa pun. Keakuratan informasi yang disampaikan kepada atasan maupun masyarakat menjadi landasan pengambilan keputusan yang tepat. Dengan demikian, berbicara sesuai kenyataan tanpa manipulasi adalah esensi berbicara jujur.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena penyampaian laporan secara ringkas berkaitan dengan efektivitas tata naskah dinas.",
            "B": "Kurang tepat karena pemberian saran konstruktif mencerminkan kontribusi positif dalam kerja sama tim.",
            "C": "Kurang tepat karena komunikasi kebijakan secara ramah mencerminkan etika nilai Berorientasi Pelayanan.",
            "D": "Merupakan definisi hakiki dari perilaku berbicara jujur pada nilai Integritas menurut modul resmi.",
            "E": "Kurang tepat karena penjelasan alur prosedur sesuai ketentuan baku berkaitan dengan transparansi loket."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q77 (Hlm 22, Key B)
updates[77] = {
    "pertanyaan": "Kejujuran harus senantiasa diiringi ketulusan, yang dalam penyampaian laporan kedinasan berarti...",
    "pilihan": {
        "A": "Memastikan data realisasi telah diverifikasi secara berjenjang",
        "B": "Tidak ada maksud lain di balik informasi selain isi informasi itu",
        "C": "Menjaga keselarasan data antara pembukuan bulanan dan tahunan",
        "D": "Menyelaraskan informasi teknis dengan pedoman penyusunan laporan",
        "E": "Menyajikan rincian fakta secara tepat waktu sebelum batas akhir"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Jujur diikuti oleh sikap ketulusan, yaitu **tidak ada maksud lain di balik informasi yang diberikan kecuali sesuai dengan isi informasi tersebut**.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena ketulusan menegaskan bahwa pelaporan data dilakukan semata-mata demi kebenaran kedinasan dan bukan sarana pamrih pribadi. Informasi yang bersih dari motif tersembunyi menjamin objektivitas tata kelola birokrasi kementerian. Oleh karena itu, ketiadaan maksud lain selain isi informasi adalah bukti ketulusan.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena verifikasi berjenjang merupakan tahapan validasi administratif perbendaharaan.",
            "B": "Merupakan makna ketulusan dalam penyampaian informasi kedinasan pada nilai Integritas menurut modul.",
            "C": "Kurang tepat karena keselarasan pembukuan bulanan dan tahunan berfokus pada rekonsiliasi akuntansi.",
            "D": "Kurang tepat karena penyelarasan informasi dengan pedoman laporan berkaitan dengan ketaatan teknis.",
            "E": "Kurang tepat karena penyajian rincian fakta secara tepat waktu merupakan wujud kedisiplinan kerja."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q78 (Hlm 22, Key E)
updates[78] = {
    "pertanyaan": "Memberikan informasi kepada atasan dengan pamrih agar diberikan promosi jabatan dinilai bertentangan dengan...",
    "pilihan": {
        "A": "Standar keahlian ideal dalam menjaga citra profesi",
        "B": "Kecepatan merespons kebutuhan masyarakat pengguna jasa",
        "C": "Hubungan kemitraan produktif pada nilai kerja sama",
        "D": "Perbaikan berkelanjutan dalam sistem tata kelola dinas",
        "E": "Sikap tulus pada perilaku utama pertama nilai integritas"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Memberikan informasi penting dengan harapan imbalan promosi **bukanlah sikap berintegritas** karena tidak mencerminkan **sikap tulus** pada perilaku utama pertama integritas.",
        "alasan_kunci": "Opsi E merupakan jawaban yang tepat karena ketulusan menuntut kejujuran informasi yang murni tanpa pamrih atau kepentingan pamrih terselubung. Informasi kedinasan wajib disampaikan demi kemaslahatan organisasi, bukan sebagai alat tawar keuntungan pribadi pegawai. Dengan demikian, pamrih promosi mencederai sikap tulus pada nilai integritas.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena standar keahlian ideal berfokus pada rujukan kompetensi pada nilai Profesionalisme.",
            "B": "Kurang tepat karena kecepatan merespons kebutuhan masyarakat berfokus pada nilai Berorientasi Pelayanan.",
            "C": "Kurang tepat karena hubungan kemitraan produktif merupakan fokus utama pada nilai Sinergi.",
            "D": "Kurang tepat karena perbaikan berkelanjutan merupakan fokus implementasi dari nilai Kesempurnaan.",
            "E": "Merupakan prinsip integritas yang dilanggar ketika pelaporan informasi disertai pamrih imbalan promosi."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q80 (Hlm 22, Key C)
updates[80] = {
    "pertanyaan": "Pegawai yang menjaga kehormatan diri dengan mematuhi norma hukum, agama, dan sosial pada hakikatnya telah...",
    "pilihan": {
        "A": "Meningkatkan kepuasan pemangku kepentingan atas layanan kantor",
        "B": "Mewujudkan efisiensi penggunaan sarana dan perlengkapan dinas",
        "C": "Menjaga kehormatan organisasi tempatnya mengabdi sebagai aparatur",
        "D": "Memperkuat sinergi kerja sama produktif dengan seksi kerja lain",
        "E": "Memastikan ketuntasan penyelesaian berkas administrasi kedinasan"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Dengan **menjaga kehormatan dirinya** dari hal-hal tercela, seorang pegawai pada hakikatnya **telah menjaga kehormatan organisasi**.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena reputasi kementerian tercermin langsung dari martabat moral yang dijaga oleh setiap individu pegawainya. Kepatuhan aparatur terhadap norma hukum, agama, dan sosial membentengi citra luhur instansi di mata publik. Oleh sebab itu, menjaga kehormatan diri identik dengan menjaga kehormatan institusi.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena kepuasan pemangku kepentingan merupakan tolok ukur keberhasilan nilai Pelayanan.",
            "B": "Kurang tepat karena efisiensi sarana perlengkapan dinas merupakan wujud pengelolaan nilai Akuntabel.",
            "C": "Merupakan dampak hakiki dari penjagaan kehormatan diri aparatur terhadap institusi tempatnya mengabdi.",
            "D": "Kurang tepat karena penguatan sinergi kerja sama produktif merupakan perwujudan dari nilai Sinergi.",
            "E": "Kurang tepat karena ketuntasan berkas administrasi kedinasan mencerminkan penerapan nilai Profesionalisme."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q81 (Hlm 23, Key B)
updates[81] = {
    "pertanyaan": "Penerapan perilaku menjaga martabat pegawai dalam kehidupan kedinasan sehari-hari ditunjukkan melalui...",
    "pilihan": {
        "A": "Mengikuti pelatihan teknis kepemimpinan secara terjadwal",
        "B": "Melaksanakan dan mematuhi sumpah jabatan dengan penuh kesadaran",
        "C": "Mengembangkan sistem inovasi digital dalam tata kelola berkas",
        "D": "Menata kearsipan dokumen kepegawaian secara rapi dan sistematis",
        "E": "Membantu perumusan rencana operasional tahunan satuan kerja dinas"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Aplikasi sehari-hari perilaku menjaga martabat ditunjukkan dalam bentuk **melaksanakan dan mematuhi sumpah jabatan** yang diucapkan saat dilantik dengan penuh kesadaran.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena sumpah jabatan memuat janji suci aparatur kepada Tuhan dan negara untuk selalu menjaga kehormatan diri dari hal tercela. Pelaksanaan sumpah secara sadar menjadi benteng moral terkuat dalam menunaikan setiap mandat kedinasan. Dengan demikian, ketaatan pada sumpah jabatan adalah wujud menjaga martabat pegawai.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena pelatihan kepemimpinan berorientasi pada pengembangan kompetensi manajerial SDM.",
            "B": "Merupakan bentuk nyata aplikasi perilaku menjaga martabat pegawai menurut modul Kementerian Keuangan.",
            "C": "Kurang tepat karena pengembangan inovasi digital tata kelola berkas mencerminkan nilai Kesempurnaan.",
            "D": "Kurang tepat karena penataan kearsipan dokumen secara sistematis mencerminkan kecermatan administrasi.",
            "E": "Kurang tepat karena perumusan rencana operasional tahunan satker berfokus pada perencanaan kinerja kantor."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q82 (Hlm 23, Key D)
updates[82] = {
    "pertanyaan": "Salah satu poin penting dalam sumpah jabatan pegawai Kementerian Keuangan terkait larangan gratifikasi adalah...",
    "pilihan": {
        "A": "Melaksanakan tugas kedinasan dengan penuh ketelitian dan tanggung jawab",
        "B": "Menjaga rahasia negara dan rahasia jabatan sebaik-baiknya kepada publik",
        "C": "Mengutamakan kepentingan negara di atas kepentingan golongan atau kelompok",
        "D": "Tidak menerima pemberian atau menjanjikan apa pun terkait jabatannya",
        "E": "Menaati segala ketentuan regulasi kedinasan aparatur sipil negara yang sah"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Salah satu isi sumpah jabatan pegawai adalah **tidak menerima pemberian atau tidak menjanjikan apapun dari pihak lain yang terkait dengan jabatannya (gratifikasi)**.",
        "alasan_kunci": "Opsi D merupakan jawaban yang tepat karena penolakan atas segala bentuk janji atau imbalan jabatan merupakan komitmen anti-gratifikasi yang diikrarkan secara resmi dalam sumpah. Sikap ini memastikan pelayanan birokrasi terbebas dari benturan kepentingan dan intervensi pihak luar. Oleh sebab itu, klausul penolakan pemberian jabatan adalah amanah sumpah pegawai.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena klausul ketelitian dan tanggung jawab berkaitan dengan sumpah ketuntasan kerja.",
            "B": "Kurang tepat karena penjagaan rahasia jabatan merupakan butir sumpah terkait perlindungan data dinas.",
            "C": "Kurang tepat karena pengutamaan kepentingan negara merupakan klausul komitmen kesetiaan kebangsaan.",
            "D": "Merupakan butir eksplisit sumpah jabatan terkait pencegahan gratifikasi sebagaimana tertulis di modul.",
            "E": "Kurang tepat karena kepatuhan pada regulasi yang sah merupakan klausul ketaatan hukum umum aparatur."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q83 (Hlm 23, Key A)
updates[83] = {
    "pertanyaan": "Dalam etika kedinasan, aparatur yang taat pada norma agama saat menjalankan tugas akan terdorong untuk...",
    "pilihan": {
        "A": "Membantu mengatasi kesulitan orang lain dalam lingkungan kerja",
        "B": "Meningkatkan kemahiran teknis profesional secara berkesinambungan",
        "C": "Menuntaskan berkas administrasi sebelum batas waktu yang ditetapkan",
        "D": "Mengembangkan gagasan inovatif bagi penyempurnaan layanan publik",
        "E": "Menjaga kerahasiaan data kedinasan sesuai standar operasional dinas"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Menaati aturan agama termasuk bentuk menjaga kehormatan diri, di mana **orang-orang yang taat pada agama akan membantu mengatasi kesulitan orang lain**.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena keimanan dan ketaatan moral menggerakkan kepedulian sosial aparatur untuk saling meringankan beban kerja sesama rekan kerja. Nilai-nilai kebajikan agama mewujud dalam tindakan nyata tolong-menolong dalam mengatasi kendala operasional dinas. Dengan demikian, membantu kesulitan orang lain adalah buah dari ketaatan beragama.",
        "bedah_pengecoh": {
            "A": "Merupakan dampak positif ketaatan pada norma agama dalam kehidupan bekerja sebagaimana diuraikan di modul.",
            "B": "Kurang tepat karena peningkatan kemahiran teknis merupakan dorongan pengembangan nilai Profesionalisme.",
            "C": "Kurang tepat karena penuntasan berkas administrasi tepat waktu berorientasi pada kedisiplinan kerja.",
            "D": "Kurang tepat karena pengembangan gagasan inovatif layanan publik merupakan penerapan nilai Kesempurnaan.",
            "E": "Kurang tepat karena penjagaan kerahasiaan data kedinasan merupakan penerapan kepatuhan kode etik Loyal."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q84 (Hlm 23, Key E)
updates[84] = {
    "pertanyaan": "Salah satu bentuk nyata pengamalan perilaku menjaga martabat pegawai di lingkungan kerja adalah...",
    "pilihan": {
        "A": "Menyusun standar prosedur layanan secara terkoordinasi",
        "B": "Mengoptimalkan pemanfaatan sistem jaringan informasi dinas",
        "C": "Membangun hubungan kemitraan strategis dengan instansi mitra",
        "D": "Melakukan evaluasi berkala terhadap beban kerja seksi kantor",
        "E": "Menaati kode etik dan perilaku yang sudah ditetapkan kementerian"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Aplikasi lain dari perilaku utama kedua nilai integritas adalah **menaati kode etik dan perilaku yang sudah ditetapkan** oleh Kementerian Keuangan sebagai panduan bekerja.",
        "alasan_kunci": "Opsi E merupakan jawaban yang tepat karena kode etik merumuskan batasan normatif mengenai kepatutan sikap, tutur kata, dan tindakan aparatur dalam bertugas. Kepatuhan terhadap pedoman ini menjaga keluhuran korps Kementerian Keuangan dari pelanggaran norma kerja. Oleh karena itu, ketaatan pada kode etik merupakan wujud menjaga martabat kedinasan.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena penyusunan standar prosedur layanan berfokus pada standardisasi operasional tugas.",
            "B": "Kurang tepat karena pemanfaatan jaringan informasi dinas berkaitan dengan penguasaan teknologi kerja.",
            "C": "Kurang tepat karena hubungan kemitraan strategis dengan mitra luar mencerminkan nilai Sinergi.",
            "D": "Kurang tepat karena evaluasi beban kerja seksi kantor berkaitan dengan penataan efisiensi organisasi.",
            "E": "Merupakan bentuk nyata aplikasi perilaku menjaga martabat pegawai pada nilai Integritas menurut modul."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q85 (Hlm 23, Key C)
updates[85] = {
    "pertanyaan": "Secara mendasar, profesionalisme didefinisikan sebagai sifat-sifat yang sewajarnya dimiliki seorang profesional, yaitu...",
    "pilihan": {
        "A": "Tingkat kepangkatan struktural tertinggi dalam formasi jabatan",
        "B": "Kecepatan beradaptasi dengan dinamika perubahan regulasi dinas",
        "C": "Kemampuan dan kemahiran dalam pelaksanaan kegiatan tertentu",
        "D": "Keluasan wewenang dalam pengelolaan alokasi keuangan satker",
        "E": "Kelengkapan sertifikasi keahlian dari institusi profesi mitra"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "**Profesionalisme** adalah sifat-sifat yang sewajarnya dimiliki atau dilakukan oleh seorang profesional, berupa **kemampuan dan kemahiran dalam pelaksanaan kegiatan tertentu**.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena kepakaran teknis dan keterampilan mumpuni menjadi esensi pembeda dari sosok profesional dalam menjalankan profesinya. Penguasaan bidang kerja yang mendalam memastikan setiap penugasan dapat diselesaikan secara presisi dan tepat sasaran. Dengan demikian, kemampuan dan kemahiran teknis merupakan hakikat dasar profesionalisme.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena kepangkatan struktural merupakan tingkatan hierarki dalam tata birokrasi kepegawaian.",
            "B": "Kurang tepat karena adaptasi terhadap regulasi baru mencerminkan kelincahan dari nilai Adaptif.",
            "C": "Merupakan definisi dasar dari profesionalisme sebagaimana diuraikan secara lugas dalam modul.",
            "D": "Kurang tepat karena keluasan wewenang alokasi keuangan merupakan mandat jabatan pada unit perbendaharaan.",
            "E": "Kurang tepat karena kelengkapan dokumen sertifikasi merupakan bukti formal pengakuan kompetensi."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q86 (Hlm 23, Key B)
updates[86] = {
    "pertanyaan": "Menurut Kamus Besar Bahasa Indonesia (KBBI), makna profesionalisme berkaitan dengan profesi yang memerlukan...",
    "pilihan": {
        "A": "Pengalaman penugasan lapangan yang panjang dalam birokrasi",
        "B": "Kepandaian khusus untuk menjalankan profesi bersangkutan",
        "C": "Ketersediaan instrumen evaluasi kinerja pegawai yang terpadu",
        "D": "Kemampuan koordinasi program lintas satuan kerja kementerian",
        "E": "Keterampilan memimpin tim perumus kebijakan anggaran dinas"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Menurut KBBI, makna profesionalisme berhubungan dengan profesi dan **memerlukan kepandaian khusus untuk menjalankannya**.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena profesi menuntut standar ilmu pengetahuan dan kecakapan spesifik yang tidak dapat dilakukan secara sembarangan oleh orang awam. Penguasaan kepandaian khusus inilah yang menjamin integritas mutu dan keandalan layanan kedinasan. Oleh sebab itu, kebutuhan kepandaian khusus menjadi unsur definisi KBBI.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena lamanya pengalaman lapangan mencerminkan jam terbang pengabdian seorang pegawai.",
            "B": "Merupakan bunyi definisi makna profesionalisme menurut KBBI sebagaimana dirujuk dalam modul.",
            "C": "Kurang tepat karena instrumen evaluasi terpadu merupakan sarana pengukuran kinerja manajemen ASN.",
            "D": "Kurang tepat karena koordinasi program lintas satuan kerja mencerminkan kepiawaian dalam nilai Sinergi.",
            "E": "Kurang tepat karena kepemimpinan perumusan anggaran mencerminkan keahlian manajerial perbendaharaan."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q87 (Hlm 23, Key D)
updates[87] = {
    "pertanyaan": "Dalam dimensi kualitas profesionalisme, konsep keahlian ideal dirumuskan sebagai...",
    "pilihan": {
        "A": "Standar kualifikasi teknis tingkat madya pada jabatan fungsional",
        "B": "Target capaian output tertinggi dalam kontrak sasaran kinerja pegawai",
        "C": "Penguasaan kompetensi manajerial berbasis kurikulum pendidikan dinas",
        "D": "Suatu perangkat perilaku yang dipandang paling sempurna sebagai rujukan",
        "E": "Wewenang otoritatif pejabat dalam menetapkan keputusan strategis unit"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Yang dimaksud dengan **keahlian ideal** ialah **suatu perangkat perilaku yang dipandang paling sempurna dan dijadikan sebagai rujukan** dalam bekerja.",
        "alasan_kunci": "Opsi D merupakan jawaban yang tepat karena keahlian ideal berfungsi sebagai tolok ukur keteladanan tertinggi yang senantiasa diupayakan pencapaiannya oleh aparatur profesional. Figur profesional akan terus mematutkan kompetensinya mendekati standar perilaku unggul tersebut dalam tugas hariannya. Dengan demikian, perangkat perilaku sempurna sebagai rujukan adalah makna keahlian ideal.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena standar kualifikasi fungsional merupakan persyaratan administratif jenjang jabatan.",
            "B": "Kurang tepat karena target capaian output tertinggi merupakan target kinerja kuantitatif pegawai.",
            "C": "Kurang tepat karena kompetensi manajerial berbasis kurikulum merupakan hasil diklat kepemimpinan.",
            "D": "Merupakan rumusan definisi konseptual dari keahlian ideal sebagaimana diuraikan dalam materi modul.",
            "E": "Kurang tepat karena wewenang penetapan keputusan strategis merupakan hak diskresi pimpinan satker."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q88 (Hlm 23, Key A)
updates[88] = {
    "pertanyaan": "Aparatur yang menjaga citra luhur korps melalui kerapian busana, kesantunan bertutur, dan etika bergaul sedang...",
    "pilihan": {
        "A": "Meningkatkan dan memelihara citra positif profesi aparatur",
        "B": "Mengoptimalkan efisiensi alur administrasi pelayanan berkas",
        "C": "Membangun jejaring komunikasi koordinatif lintas seksi kerja",
        "D": "Mempercepat penyelesaian target sasaran kinerja pegawai dinas",
        "E": "Mengembangkan standar kompetensi teknis pelayanan prima kantor"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Profesionalisme yang tinggi ditunjukkan oleh besarnya keinginan untuk selalu **meningkatkan dan memelihara image profesi melalui perwujudan perilaku profesional**.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena sikap hidup sehari-hari, kesantunan berbahasa, serta penampilan yang tertib mencerminkan kehormatan institusi di ruang publik. Keteladanan lahiriah ini memperkuat kepercayaan masyarakat terhadap kewibawaan birokrasi Kementerian Keuangan. Oleh sebab itu, pemeliharaan citra profesi terwujud melalui perilaku santun pegawai.",
        "bedah_pengecoh": {
            "A": "Merupakan salah satu ciri kualitas profesionalisme yang diwujudkan melalui penampilan dan tata tutur kata.",
            "B": "Kurang tepat karena efisiensi alur administrasi pelayanan berkas berfokus pada kecepatan teknis layanan.",
            "C": "Kurang tepat karena komunikasi koordinatif lintas seksi berorientasi pada penguatan nilai Sinergi.",
            "D": "Kurang tepat karena percepatan target sasaran kinerja berfokus pada ketuntasan kontrak kerja individu.",
            "E": "Kurang tepat karena pengembangan standar kompetensi teknis berfokus pada perumusan silabus diklat SDM."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q89 (Hlm 23, Key E)
updates[89] = {
    "pertanyaan": "Seorang pegawai yang secara proaktif mengikuti kursus keahlian mandiri demi meningkatkan kapasitas diri sedang...",
    "pilihan": {
        "A": "Memperluas jejaring kemitraan kerja dengan pemangku kebijakan",
        "B": "Mempercepat penyelesaian penugasan rutin operasional kedinasan",
        "C": "Memastikan ketaatan prosedur pada setiap tahapan layanan publik",
        "D": "Mengoptimalkan koordinasi internal antar-subbagian di lingkungan satker",
        "E": "Mengejar kesempatan pengembangan profesional demi perbaikan kualitas diri"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Seorang profesional ingin senantiasa **mengejar kesempatan pengembangan profesional yang dapat meningkatkan dan memperbaiki kualitas pengetahuan dan keterampilannya**.",
        "alasan_kunci": "Opsi E merupakan jawaban yang tepat karena semangat belajar mandiri membuktikan komitmen pegawai untuk senantiasa memperluas kompetensi demi memberikan kontribusi terbaik. Pengetahuan yang terus dimutakhirkan menghindarkan organisasi dari kejenuhan metode kerja konvensional. Dengan demikian, pengejaran kesempatan pengembangan profesional adalah ciri aparatur unggul.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena memperluas kemitraan kerja dengan pemangku kebijakan berorientasi pada nilai Sinergi.",
            "B": "Kurang tepat karena penyelesaian penugasan rutin operasional mencerminkan efisiensi kerja harian.",
            "C": "Kurang tepat karena kepatuhan prosedur pada tahapan layanan publik merupakan cerminan nilai Akuntabel.",
            "D": "Kurang tepat karena koordinasi internal antar-subbagian mencerminkan keterpaduan hubungan kerja satker.",
            "E": "Merupakan ciri kualitas profesionalisme dalam mengejar kesempatan pengembangan diri menurut modul."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q90 (Hlm 23, Key C)
updates[90] = {
    "pertanyaan": "Berikut ini merupakan ciri-ciri kualitas profesionalisme seseorang dalam standar keprofesian aparatur, KECUALI...",
    "pilihan": {
        "A": "Menampilkan perilaku yang mendekati keahlian ideal",
        "B": "Meningkatkan dan memelihara citra luhur profesi",
        "C": "Memprioritaskan kepentingan unit di atas tujuan bersama",
        "D": "Mengejar kesempatan pengembangan kapasitas diri",
        "E": "Mendorong diri mewujudkan kinerja yang profesional"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Ciri kualitas profesionalisme seseorang meliputi: **mendorong diri mewujudkan kerja profesional**, **menampilkan perilaku mendekati keahlian ideal**, **meningkatkan dan memelihara image profesi**, serta **mengejar kesempatan pengembangan profesional**.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat untuk soal pengecualian karena memprioritaskan unit kerja sendiri merupakan bentuk eksklusivitas kelompok yang bertentangan dengan sinergi kementerian. Adapun opsi A, B, D, dan E merupakan ciri asli kualitas profesionalisme yang diuraikan secara eksplisit di modul. Oleh karena itu, opsi C adalah pernyataan yang bukan merupakan ciri profesionalisme.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena menampilkan perilaku mendekati keahlian ideal adalah ciri resmi profesionalisme.",
            "B": "Kurang tepat karena memelihara citra profesi merupakan ciri penting etika seorang profesional.",
            "C": "Tepat sebagai pengecualian karena sikap sektoral unit bertentangan dengan komitmen keprofesian bersama.",
            "D": "Kurang tepat karena mengejar pengembangan profesional merupakan ciri peningkatan mutu diri aparatur.",
            "E": "Kurang tepat karena dorongan mewujudkan kerja profesional merupakan fondasi utama jiwa keprofesian."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q91 (Hlm 24, Key E)
updates[91] = {
    "pertanyaan": "Kualitas profesionalisme seorang pegawai terhadap penugasan kedinasan ditandai secara mendalam oleh...",
    "pilihan": {
        "A": "Keterampilan teknis dalam mengoperasikan aplikasi teknologi",
        "B": "Kecepatan menyelesaikan dokumen transaksi anggaran kedinasan",
        "C": "Keaktifan menyampaikan masukan solutif dalam forum rapat dinas",
        "D": "Kemampuan memimpin tim kerja penyusun program strategis kantor",
        "E": "Kualitas rasa bangga dan percaya diri akan profesi yang diemban"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Profesionalisme ditandai dengan **kualitas rasa bangga akan profesi yang dipegangnya** sehingga memiliki rasa percaya diri yang tinggi.",
        "alasan_kunci": "Opsi E merupakan jawaban yang tepat karena rasa bangga atas profesi menumbuhkan harga diri positif dan dedikasi total dalam melayani negara. Pegawai yang percaya diri dan mencintai bidang tugasnya akan selalu terdorong memberikan hasil kerja dengan standar kualitas tertinggi. Dengan demikian, rasa bangga dan percaya diri profesi menjadi penanda utama profesionalisme.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena keterampilan teknis aplikasi merupakan salah satu instrumen operasional kerja digital.",
            "B": "Kurang tepat karena kecepatan dokumen anggaran mencerminkan ketangkasan administratif perbendaharaan.",
            "C": "Kurang tepat karena keaktifan menyampaikan masukan dalam rapat mencerminkan kontribusi pada nilai Sinergi.",
            "D": "Kurang tepat karena memimpin tim program strategis mencerminkan kapasitas kepemimpinan manajerial.",
            "E": "Merupakan penanda substantif kualitas profesionalisme seseorang sebagaimana tertulis pada teks modul."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q94 (Hlm 24, Key D)
updates[94] = {
    "pertanyaan": "Dalam aplikasi sehari-hari, perwujudan dari perilaku utama mempunyai keahlian dan pengetahuan yang luas adalah...",
    "pilihan": {
        "A": "Bersikap jujur dan tulus dalam menyajikan data laporan",
        "B": "Membangun hubungan kemitraan yang produktif dan harmonis",
        "C": "Melayani secara transparan, tepat waktu, serta akuntabel",
        "D": "Bekerja secara efisien dan menghasilkan output berkualitas",
        "E": "Melakukan perbaikan tata kelola secara berkesinambungan"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Dalam aplikasi sehari-hari, perilaku utama pertama nilai profesionalisme adalah **bekerja dengan efisien dan berkualitas** melalui dukungan keahlian dan pengetahuan luas.",
        "alasan_kunci": "Opsi D merupakan jawaban yang tepat karena efisiensi kerja dan keunggulan mutu output hanya dapat dicapai apabila pegawai memahami ilmunya secara mendalam. Tanpa pengetahuan luas, pekerjaan akan memakan waktu lama dan rentan terjadi kesalahan fatal dalam eksekusi. Oleh sebab itu, bekerja efisien dan berkualitas adalah wujud nyata perilaku utama tersebut.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena bersikap jujur dan tulus dalam menyajikan data merupakan perilaku nilai Integritas.",
            "B": "Kurang tepat karena membangun hubungan kemitraan produktif merupakan perilaku utama pada nilai Sinergi.",
            "C": "Kurang tepat karena melayani secara transparan dan tepat waktu merupakan perilaku utama nilai Pelayanan.",
            "D": "Merupakan perwujudan nyata perilaku mempunyai keahlian dan pengetahuan luas pada nilai Profesionalisme.",
            "E": "Kurang tepat karena melakukan perbaikan berkesinambungan merupakan perilaku utama nilai Kesempurnaan."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q96 (Hlm 24, Key E)
updates[96] = {
    "pertanyaan": "Dalam prinsip bekerja dengan hati, seorang aparatur dituntut mampu mempertemukan dua simpul berlawanan yaitu...",
    "pilihan": {
        "A": "Rencana kerja tahunan dan dinamika penugasan insidental pimpinan",
        "B": "Target penerimaan negara dan kemudahan fasilitas perizinan usaha",
        "C": "Ketentuan disiplin aparatur dan pemenuhan hak pengembangan karier",
        "D": "Standar kompetensi teknis dan kebutuhan penempatan pegawai di daerah",
        "E": "Ketentuan standar operasional prosedur dengan kepekaan hati nurani"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Dalam bekerja terdapat dua hal penting yang harus diperhatikan yaitu **SOP dan hati nurani atau perasaan**. Seorang profesional mampu mempertemukan dua simpul berlawanan tersebut.",
        "alasan_kunci": "Opsi E merupakan jawaban yang tepat karena kepatuhan kaku pada prosedur tanpa empati dapat mengurangi kualitas pelayanan kemanusiaan, sebaliknya mengabaikan prosedur demi rasa iba melanggar hukum. Profesionalisme sejati terletak pada kebijaksanaan mempertemukan ketertiban SOP dengan ketulusan nurani secara berimbang. Dengan demikian, perpaduan SOP dan hati nurani adalah dua simpul yang dipertemukan.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena rencana kerja dan tugas insidental merupakan tantangan manajemen waktu pegawai.",
            "B": "Kurang tepat karena target fiskal dan perizinan usaha merupakan aspek kebijakan makro ekonomi negara.",
            "C": "Kurang tepat karena disiplin aparatur dan hak karier merupakan hak dan kewajiban dalam regulasi ASN.",
            "D": "Kurang tepat karena standar kompetensi dan penempatan kerja berkaitan dengan pola mutasi SDM.",
            "E": "Merupakan dua simpul penting yang harus mampu dipertemukan dalam prinsip bekerja dengan hati di modul."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q97 (Hlm 24, Key C)
updates[97] = {
    "pertanyaan": "Secara konseptual, ungkapan lain yang semakna dan menjadi hakikat dari bekerja dengan hati adalah...",
    "pilihan": {
        "A": "Menuntaskan pekerjaan tepat waktu sesuai target kinerja dinas",
        "B": "Menjaga integritas moral dan kode etik aparatur sipil negara",
        "C": "Mencintai pekerjaan yang menjadi amanah tanggung jawab kedinasan",
        "D": "Mematuhi prosedur standar operasional secara cermat dan teliti",
        "E": "Menguasai perkembangan teknologi informasi penunjang tugas kantor"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Kata lain dari bekerja dengan hati adalah **mencintai pekerjaan kita** sehingga tidak ada tugas yang dikerjakan dengan keterpaksaan.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena kecintaan tulus terhadap profesi melahirkan antusiasme, kebahagiaan batin, dan dedikasi maksimal dalam setiap penugasan. Ketika seseorang mencintai pekerjaannya, ia tidak merasa terbebani melainkan memandang tugas sebagai bentuk aktualisasi pengabdian. Oleh karena itu, mencintai pekerjaan adalah hakikat dari bekerja dengan hati.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena menuntaskan tugas tepat waktu merupakan wujud kedisiplinan pada target kinerja.",
            "B": "Kurang tepat karena menjaga kode etik kedinasan merupakan penerapan dari pilar nilai Integritas.",
            "C": "Merupakan ungkapan langsung yang semakna dengan konsep bekerja dengan hati menurut penjelasan modul.",
            "D": "Kurang tepat karena kepatuhan cermat pada SOP merupakan cerminan ketelitian nilai Akuntabel.",
            "E": "Kurang tepat karena penguasaan teknologi informasi merupakan wujud keahlian pada nilai Profesionalisme."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q98 (Hlm 24, Key A)
updates[98] = {
    "pertanyaan": "Seorang aparatur meyakini bahwa bekerja di lingkungan Kementerian Keuangan bukan sekadar mencari nafkah, melainkan...",
    "pilihan": {
        "A": "Panggilan jiwa dalam mengabdi",
        "B": "Amanah konstitusi kedinasan",
        "C": "Komitmen profesi aparatur",
        "D": "Tanggung jawab institusi kerja",
        "E": "Dedikasi pengabdian negara"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Bekerja bukan hanya karena dibayar tinggi, **tetapi karena panggilan jiwa** yang mendorong aparatur bekerja melampaui standar minimal.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena makna luhur bekerja sebagai panggilan jiwa menempatkan profesi di atas orientasi materi semata. Semangat pengabdian ini mendorong pegawai memberikan karya terbaik melebihi apa yang diterima sebagai hak kedinasan. Dengan demikian, panggilan jiwa merupakan filosofi tertinggi dalam bekerja dengan hati.",
        "bedah_pengecoh": {
            "A": "Merupakan landasan filosofis bekerja dengan hati sebagaimana diuraikan secara tersurat dalam modul.",
            "B": "Kurang tepat karena amanah konstitusi kedinasan berfokus pada mandat hukum penyelenggaraan negara.",
            "C": "Kurang tepat karena komitmen profesi aparatur berkaitan dengan kode etik dan standar profesi kerja.",
            "D": "Kurang tepat karena tanggung jawab institusi kerja mencerminkan kewajiban organisasi kepada publik.",
            "E": "Kurang tepat karena dedikasi pengabdian negara merupakan prinsip kesetiaan umum pada nilai Loyal."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q100 (Hlm 24, Key B)
updates[100] = {
    "pertanyaan": "Perwujudan nyata dari prinsip bekerja dengan hati dalam pelaksanaan tugas operasional ditunjukkan oleh sikap...",
    "pilihan": {
        "A": "Memperluas keahlian teknis melalui penguasaan aplikasi digital",
        "B": "Mampu mempertemukan simpul SOP kedinasan dengan nurani kemanusiaan",
        "C": "Menyampaikan informasi realisasi belanja sesuai fakta transaksi",
        "D": "Menjalin kerja sama produktif dan harmonis dengan rekan sejawat",
        "E": "Memberikan layanan prima secara cepat, transparan, dan akuntabel"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Salah satu ciri seorang profesional dalam **bekerja dengan hati** adalah **mampu mempertemukan dua simpul yang berlawanan** yaitu antara **SOP dan hati nurani atau perasaan**.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena bekerja dengan hati menolak sikap kaku yang mengabaikan nurani, sekaligus menolak pengabaian aturan operasional yang dapat merugikan negara. Keseimbangan antara ketertiban regulasi dan empati nurani membuktikan kematangan profesionalitas aparatur dalam menjalankan tugas. Oleh karena itu, mempertemukan simpul SOP dan nurani adalah perwujudan bekerja dengan hati.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena memperluas keahlian teknis aplikasi merupakan perilaku mempunyai keahlian luas.",
            "B": "Merupakan perwujudan hakiki prinsip bekerja dengan hati sebagaimana dijelaskan dalam naskah modul.",
            "C": "Kurang tepat karena menyampaikan informasi sesuai fakta transaksi merupakan cerminan nilai Integritas.",
            "D": "Kurang tepat karena menjalin kerja sama produktif dengan rekan kerja merupakan cerminan nilai Sinergi.",
            "E": "Kurang tepat karena memberikan layanan cepat, transparan, dan akuntabel merupakan cerminan nilai Pelayanan."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Apply updates to pkg
for q in pkg["daftar_soal"]:
    num = q["nomor"]
    if num in updates:
        for k, v in updates[num].items():
            q[k] = v

print("Applied updates for", len(updates), "questions.")

# Save updated JSON
with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_02.json", "w", encoding="utf-8") as f:
    json.dump(pkg, f, indent=2, ensure_ascii=False)

print("Saved updated JSON successfully.")

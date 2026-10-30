import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_03.json", "r", encoding="utf-8") as f:
    pkg = json.load(f)

with open("modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

modul_pages = {p["page"]: p["text"] for p in modul["pages"]}

updates = {}

# Q1 (Hlm 25, Key A)
updates[1] = {
    "pertanyaan": "Berdasarkan Kamus Besar Bahasa Indonesia (KBBI), sinergi diartikan secara lugas sebagai...",
    "pilihan": {
        "A": "Kegiatan atau operasi gabungan",
        "B": "Hubungan kemitraan kerja sama",
        "C": "Koordinasi terpadu antarlembaga",
        "D": "Penyelarasan tugas kedinasan",
        "E": "Penguatan jejaring kelembagaan"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Menurut KBBI, **sinergi** diartikan secara lugas sebagai **kegiatan atau operasi gabungan**.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena KBBI mendefinisikan sinergi sebagai kegiatan atau operasi gabungan dari berbagai unsur berbeda untuk menghasilkan kekuatan yang lebih besar. Penggabungan potensi ini melandasi terbangunnya koordinasi kerja sama yang efektif dalam organisasi. Dengan demikian, kegiatan atau operasi gabungan adalah makna leksikal resmi sinergi.",
        "bedah_pengecoh": {
            "A": "Merupakan definisi eksplisit sinergi menurut KBBI sebagaimana termaktub dalam teks modul.",
            "B": "Kurang tepat karena hubungan kemitraan kerja sama merupakan bentuk perwujudan praktis dari sinergi.",
            "C": "Kurang tepat karena koordinasi terpadu antarlembaga merupakan metode pelaksanaan sinergi di lapangan.",
            "D": "Kurang tepat karena penyelarasan tugas kedinasan berfokus pada sinkronisasi uraian tugas aparatur.",
            "E": "Kurang tepat karena penguatan jejaring kelembagaan merupakan sarana penunjang kemitraan organisasi."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q4 (Hlm 25, Key B)
updates[4] = {
    "pertanyaan": "Koordinasi dan kerja sama dalam nilai sinergi diwujudkan secara terarah demi memperoleh hasil yang...",
    "pilihan": {
        "A": "Seragam dan terstandarisasi penuh",
        "B": "Lebih besar, berkualitas, dan cepat",
        "C": "Tepat waktu, terukur, dan akuntabel",
        "D": "Efisien, terpadu, dan berdaya guna",
        "E": "Komprehensif dan berkelanjutan"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Sinergi diwujudkan dengan koordinasi dan kerja sama yang baik dengan pihak yang berkepentingan untuk **memperoleh hasil yang lebih besar, berkualitas, dan cepat**.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena integrasi kekuatan lintas unit mampu melipatgandakan output kerja dibandingkan penyelesaian secara parsial. Kecepatan dan keunggulan mutu hasil kerja menjadi keunggulan utama dari pola kerja kolaboratif. Oleh karena itu, hasil yang lebih besar, berkualitas, dan cepat adalah sasaran nilai sinergi.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena keseragaman kerja berfokus pada standardisasi operasional, bukan esensi sinergi.",
            "B": "Merupakan rumusan tujuan eksplisit koordinasi dan kerja sama sinergi menurut modul.",
            "C": "Kurang tepat karena tepat waktu dan akuntabel merupakan fokus utama pada nilai Akuntabel.",
            "D": "Kurang tepat karena efisien dan terpadu merupakan prinsip umum manajemen penyelenggaraan tugas.",
            "E": "Kurang tepat karena komprehensif dan berkelanjutan merupakan sasaran perbaikan nilai Kesempurnaan."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q5 (Hlm 25, Key D)
updates[5] = {
    "pertanyaan": "Nilai sinergi bukan hal baru bagi aparatur karena bangsa Indonesia memiliki kebiasaan luhur berupa...",
    "pilihan": {
        "A": "Musyawarah mufakat yang berkeadilan",
        "B": "Ketaatan pada tatanan adat istiadat",
        "C": "Kesantunan dalam bertutur dan bersikap",
        "D": "Gotong royong yang telah mengakar kuat",
        "E": "Semangat kemandirian dalam bertindak"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Nilai sinergi bagi pegawai Kementerian Keuangan bukan kebiasaan baru karena masyarakat Indonesia sangat erat dengan **sifat gotong royong yang telah mengakar**.",
        "alasan_kunci": "Opsi D merupakan jawaban yang tepat karena tradisi gotong royong mengajarkan kebiasaan tolong-menolong secara sukarela demi kepentingan bersama masyarakat. Nilai kultural warisan leluhur ini menjadi fondasi sosio-kultural yang kokoh bagi pengamalan sinergi di lingkungan birokrasi kementerian. Dengan demikian, gotong royong adalah akar budaya sinergi bangsa.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena musyawarah mufakat merupakan tradisi luhur dalam pengambilan keputusan mufakat.",
            "B": "Kurang tepat karena ketaatan adat istiadat berfokus pada kepatuhan terhadap norma kedaerahan.",
            "C": "Kurang tepat karena kesantunan bertutur kata merupakan etika pergaulan pada nilai pelayanan prima.",
            "D": "Merupakan akar budaya luhur bangsa Indonesia yang melandasi nilai sinergi sebagaimana dijelaskan di modul.",
            "E": "Kurang tepat karena kemandirian bertindak berorientasi pada ketuntasan kerja mandiri pegawai."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q9 (Hlm 25, Key B)
updates[9] = {
    "pertanyaan": "Modal awal yang sangat mendasar dalam membangun hubungan kerja sama yang baik secara konseptual adalah...",
    "pilihan": {
        "A": "Kesamaan latar belakang disiplin keilmuan",
        "B": "Persangkaan baik kepada rekan dan pimpinan",
        "C": "Keahlian teknis operasional yang mumpuni",
        "D": "Ketuntasan dalam menyelesaikan target kerja",
        "E": "Kepatuhan penuh pada instruksi kedinasan"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "**Persangkaan baik kepada teman, atasan maupun bawahan** adalah **modal awal dalam membangun hubungan yang baik**.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena prasangka positif menumbuhkan rasa aman psikologis dan keterbukaan antarsesama pegawai di tempat kerja. Tanpa adanya sangkaan baik, rasa saling curiga akan menghambat terciptanya koordinasi yang tulus dan produktif. Oleh sebab itu, persangkaan baik menjadi fondasi pemula hubungan yang baik.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena kesamaan disiplin keilmuan bukan prasyarat utama membangun keharmonisan relasi kerja.",
            "B": "Merupakan modal awal mendasar dalam membangun hubungan baik pada nilai Sinergi menurut modul.",
            "C": "Kurang tepat karena keahlian teknis operasional merupakan modal kapabilitas pada nilai Profesionalisme.",
            "D": "Kurang tepat karena ketuntasan menyelesaikan target kerja merupakan tolok ukur capaian output aparatur.",
            "E": "Kurang tepat karena kepatuhan instruksi dinas merupakan cerminan kedisiplinan dan loyalitas kerja."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q11 (Hlm 26, Key B)
updates[11] = {
    "pertanyaan": "Tanda nyata bahwa seorang pegawai senantiasa berpikir positif dalam tata krama kerja terlihat pada cara...",
    "pilihan": {
        "A": "Menata prioritas target capaian kinerja",
        "B": "Mengungkapkan sesuatu secara verbal dan nonverbal",
        "C": "Menyusun laporan dinas secara tertib dan cermat",
        "D": "Memilih metode penyelesaian masalah secara tepat",
        "E": "Mengelola waktu penyelesaian tugas operasional"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Tanda bahwa pegawai senantiasa berpikir positif terlihat di dalam **cara mereka mengungkapkan sesuatu, baik secara verbal maupun nonverbal**.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena pikiran positif memancar melalui pemilihan tutur kata yang santun serta gestur tubuh yang bersahabat dan menghargai orang lain. Cara berkomunikasi yang konstruktif mencerminkan kejernihan batin dan ketulusan niat pegawai dalam berinteraksi. Dengan demikian, ungkapan verbal dan nonverbal adalah indikator berpikir positif.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena penataan prioritas target kinerja berkaitan dengan manajemen efektivitas penugasan.",
            "B": "Merupakan wujud nyata cerminan berpikir positif pegawai sebagaimana dirumuskan dalam modul.",
            "C": "Kurang tepat karena penyusunan laporan dinas secara cermat mencerminkan ketelitian nilai Akuntabel.",
            "D": "Kurang tepat karena pemilihan metode pemecahan masalah mencerminkan kemampuan analitis Profesionalisme.",
            "E": "Kurang tepat karena pengelolaan waktu operasional mencerminkan kedisiplinan kerja harian."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q12 (Hlm 26, Key D)
updates[12] = {
    "pertanyaan": "Selain saling menyapa dan bersalaman, perilaku luhur yang penting dilakukan antarpegawai adalah...",
    "pilihan": {
        "A": "Memberikan apresiasi atas capaian kinerja tim",
        "B": "Membantu penyusunan berkas administrasi dinas",
        "C": "Berbagi referensi keahlian teknis pekerjaan",
        "D": "Mendoakan rekan kerja agar sukses dan bahagia",
        "E": "Mengajak berdiskusi mengenai strategi kantor"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Penting bagi pegawai untuk **mendoakan agar teman-temannya sukses dan bahagia**, baik diungkapkan secara langsung maupun sembunyi-sembunyi.",
        "alasan_kunci": "Opsi D merupakan jawaban yang tepat karena ketulusan mendoakan kebaikan rekan sekerja membersihkan hati dari rasa iri dengki serta mempererat ikatan persaudaraan korps. Doa yang tulus melahirkan empati dan kehangatan relasi yang memperkuat sinergi organisasi secara batiniah. Oleh karena itu, mendoakan kesuksesan rekan merupakan anjuran luhur nilai sinergi.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena pemberian apresiasi capaian kinerja tim merupakan penguatan motivasi manajerial.",
            "B": "Kurang tepat karena bantuan penyusunan berkas administrasi merupakan praktik tolong-menolong teknis.",
            "C": "Kurang tepat karena berbagi referensi keahlian teknis mencerminkan transfer pengetahuan Profesionalisme.",
            "D": "Merupakan perilaku luhur yang dianjurkan dalam menjaga keharmonisan hubungan kerja menurut modul.",
            "E": "Kurang tepat karena diskusi mengenai strategi kantor merupakan bagian dari forum koordinasi kerja."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q13 (Hlm 26, Key A)
updates[13] = {
    "pertanyaan": "Perumusan dan pelaksanaan solusi terbaik dalam nilai sinergi wajib didukung oleh ketersediaan...",
    "pilihan": {
        "A": "Data dan informasi terkini, lengkap, dan akurat",
        "B": "Petunjuk teknis operasional baku dari pimpinan",
        "C": "Alokasi sarana prasarana kerja yang memadai",
        "D": "Kesepakatan hasil musyawarah kelompok dinas",
        "E": "Dukungan koordinasi kemitraan lintas satuan kerja"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Solusi yang baik hanya dapat diperoleh jika didukung oleh **data dan informasi terkini, lengkap, dan akurat**.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena keandalan suatu keputusan atau rekomendasi kebijakan sangat bergantung pada validitas fakta dan data pendukung. Keputusan berbasis data yang mutakhir menjamin ketepatan solusi dalam menyelesaikan akar persoalan kedinasan. Dengan demikian, data terkini, lengkap, dan akurat adalah syarat mutlak solusi terbaik.",
        "bedah_pengecoh": {
            "A": "Merupakan prasyarat fundamental dalam merumuskan solusi terbaik sebagaimana diuraikan dalam modul.",
            "B": "Kurang tepat karena petunjuk teknis operasional merupakan panduan baku pelaksanaan tugas rutin.",
            "C": "Kurang tepat karena alokasi sarana prasarana merupakan dukungan fasilitas fisik penunjang kerja.",
            "D": "Kurang tepat karena kesepakatan hasil musyawarah merupakan wujud mufakat tim koordinasi kerja.",
            "E": "Kurang tepat karena dukungan koordinasi kemitraan merupakan instrumen kelembagaan kerja sama."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q14 (Hlm 26, Key C)
updates[14] = {
    "pertanyaan": "Saat menjumpai kendala dalam tugas, sikap proaktif pegawai yang tepat menurut nilai sinergi adalah...",
    "pilihan": {
        "A": "Menyerahkan telaah kendala kepada tim perumus",
        "B": "Menyesuaikan target output dengan kendala tugas",
        "C": "Berkontribusi menemukan ide penyelesaian masalah",
        "D": "Melaporkan perkembangan berkas secara berkala",
        "E": "Mengkaji regulasi pedoman operasional satker"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Seorang pegawai Kementerian Keuangan harus mampu **berkontribusi menemukan ide penyelesaian setiap kali menemui persoalan** dalam menjalankan tugas.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena aparatur yang sinergis dan bertanggung jawab tidak bersikap pasif atau sekadar menunggu perintah atasan saat menghadapi kendala. Inisiatif sumbangsih ide mempercepat penemuan jalan keluar terbaik demi kelancaran operasional satuan kerja. Oleh sebab itu, kontribusi ide pemecahan adalah perwujudan perilaku solusi terbaik.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena menyerahkan telaah kendala kepada tim lain mencerminkan sikap mengalihkan peran.",
            "B": "Kurang tepat karena menyesuaikan target output dapat menurunkan standar pencapaian kinerja kantor.",
            "C": "Merupakan wujud proaktif pegawai dalam menerapkan perilaku solusi terbaik sebagaimana tertulis di modul.",
            "D": "Kurang tepat karena pelaporan berkas berkala merupakan kepatuhan prosedur administratif formal.",
            "E": "Kurang tepat karena kajian regulasi pedoman merupakan langkah normatif dalam telaah aturan."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q15 (Hlm 26, Key E)
updates[15] = {
    "pertanyaan": "Pegawai didorong berani berpikir out of the box dalam menggali solusi dengan syarat gagasan tersebut...",
    "pilihan": {
        "A": "Mendapat persetujuan bulat dari seluruh staf",
        "B": "Memberikan efisiensi biaya operasional tinggi",
        "C": "Memanfaatkan sarana teknologi informasi terkini",
        "D": "Mengadopsi praktik terbaik institusi modern",
        "E": "Tidak bertentangan dengan norma dan aturan berlaku"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Pegawai didorong berpikir di luar kebiasaan (out of box), **selama idenya tidak bertentangan dengan norma dan aturan yang berlaku**.",
        "alasan_kunci": "Opsi E merupakan jawaban yang tepat karena inovasi dan kreativitas birokrasi harus tetap berlandaskan pada prinsip legalitas serta moralitas penyelenggaraan negara. Terobosan gagasan baru tidak boleh melanggar koridor regulasi maupun kode etik kedinasan yang sah. Dengan demikian, ketaatan pada norma dan aturan menjadi batasan mutlak berpikir kreatif.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena persetujuan bulat seluruh staf bukan syarat mutlak keabsahan gagasan kreatif.",
            "B": "Kurang tepat karena efisiensi biaya operasional merupakan salah satu manfaat positif inovasi kerja.",
            "C": "Kurang tepat karena pemanfaatan teknologi informasi merupakan sarana penunjang modernisasi ide.",
            "D": "Kurang tepat karena adopsi praktik terbaik merupakan metode pembanding dalam pengembangan gagasan.",
            "E": "Merupakan batasan normatif mutlak berpikir out of the box sebagaimana ditegaskan dalam materi modul."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q18 (Hlm 26, Key A)
updates[18] = {
    "pertanyaan": "Aktivitas positif yang disarankan guna memperkaya khazanah kreativitas pegawai kantor adalah...",
    "pilihan": {
        "A": "Memperbanyak diskusi dengan rekan dan membaca literatur",
        "B": "Mengikuti pelatihan teknis manajemen risiko kedinasan",
        "C": "Menata tata ruang kerja kantor agar lebih ergonomis",
        "D": "Melakukan studi banding tata kelola berkas ke satker lain",
        "E": "Menyusun telaah regulasi perbendaharaan secara berkala"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Kreativitas dapat diperkaya dengan **memperbanyak diskusi bersama teman-teman sekantor, membaca banyak literatur, berimajinasi**, dan sebagainya.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena pertukaran wawasan melalui dialog terbuka serta kekayaan literasi memicu keterhubungan gagasan-gagasan baru yang inovatif. Membaca dan berimajinasi melatih kelenturan kognitif aparatur dalam merumuskan terobosan solusi kerja. Oleh sebab itu, diskusi dan literasi merupakan sarana utama pengaya kreativitas.",
        "bedah_pengecoh": {
            "A": "Merupakan aktivitas praktis pengaya kreativitas aparatur sebagaimana dirinci secara jelas dalam modul.",
            "B": "Kurang tepat karena pelatihan manajemen risiko berfokus pada pengendalian mitigasi bahaya operasional.",
            "C": "Kurang tepat karena penataan ruang kerja ergonomis berkaitan dengan kenyamanan fisik lingkungan dinas.",
            "D": "Kurang tepat karena studi banding ke satker lain merupakan metode observasi komparatif kelembagaan.",
            "E": "Kurang tepat karena telaah regulasi perbendaharaan berfokus pada pengkajian kepatuhan yuridis norma kas."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q21 (Hlm 27, Key C)
updates[21] = {
    "pertanyaan": "Menurut Nilai-Nilai Kementerian Keuangan, nilai pelayanan didefinisikan sebagai pemberian layanan yang...",
    "pilihan": {
        "A": "Menjamin ketuntasan administrasi sesuai jadwal baku",
        "B": "Menerapkan prinsip keterbukaan informasi secara terukur",
        "C": "Memenuhi kepuasan pemangku kepentingan secara prima",
        "D": "Mengutamakan efisiensi pemanfaatan sumber daya kantor",
        "E": "Menyelaraskan prosedur dinas dengan standar mutu kerja"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Pelayanan diartikan sebagai **pemberian layanan yang memenuhi kepuasan pemangku kepentingan** yang dilakukan dengan sepenuh hati, transparan, cepat, akurat, dan aman.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena orientasi utama pelayanan Kementerian Keuangan adalah memastikan kebutuhan pemangku kepentingan terlayani dengan standar kepuasan terbaik. Layanan prima diberikan secara tulus dan akuntabel demi memberikan manfaat nyata bagi pengguna jasa. Dengan demikian, pemenuhan kepuasan pemangku kepentingan adalah definisi hakiki nilai pelayanan.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena ketuntasan administrasi sesuai jadwal mencerminkan pilar nilai Profesionalisme.",
            "B": "Kurang tepat karena keterbukaan informasi terukur merupakan bagian dari tata kelola akuntabilitas publik.",
            "C": "Merupakan rumusan definisi resmi nilai Pelayanan sebagaimana ditetapkan dalam naskah modul kementerian.",
            "D": "Kurang tepat karena efisiensi pemanfaatan sumber daya merupakan prinsip utama pada nilai Akuntabel.",
            "E": "Kurang tepat karena penyelarasan prosedur dengan standar mutu berfokus pada manajemen kendali mutu."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q24 (Hlm 27, Key B)
updates[24] = {
    "pertanyaan": "Salah satu contoh konkret tindakan untuk memahami kebutuhan pengguna layanan secara terarah adalah...",
    "pilihan": {
        "A": "Mengadakan rapat evaluasi kinerja internal kantor",
        "B": "Melakukan survei kebutuhan stakeholders secara berkala",
        "C": "Menyusun pedoman standar operasional prosedur loket",
        "D": "Mengoptimalkan pemanfaatan papan informasi dinas",
        "E": "Mengembangkan sistem pelaporan capaian output tugas"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "**Survei kebutuhan stakeholders** adalah salah satu contoh usaha **memahami kebutuhan pemangku kepentingan** (pengguna layanan).",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena survei berkala menyediakan data empiris langsung mengenai harapan dan kendala yang dialami oleh masyarakat pengguna layanan. Melalui umpan balik tersebut, unit kerja dapat menyempurnakan fasilitas dan mekanisme pelayanan secara tepat sasaran. Oleh sebab itu, survei kebutuhan adalah sarana konkret memahami pengguna layanan.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena rapat evaluasi kinerja internal berfokus pada penilaian output capaian organisasi.",
            "B": "Merupakan contoh konkret upaya memahami kebutuhan pengguna layanan sebagaimana tersurat dalam modul.",
            "C": "Kurang tepat karena penyusunan pedoman standar operasional loket bertujuan menjaga konsistensi alur.",
            "D": "Kurang tepat karena pemanfaatan papan informasi dinas berkaitan dengan penyebarluasan info kedinasan.",
            "E": "Kurang tepat karena pengembangan sistem pelaporan capaian tugas berkaitan dengan tata kelola akuntabilitas."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q25 (Hlm 27, Key D)
updates[25] = {
    "pertanyaan": "Perilaku asertif pegawai Kementerian Keuangan kepada para pemangku kepentingan diartikan sebagai...",
    "pilihan": {
        "A": "Sikap lugas dalam menyampaikan ketentuan regulasi",
        "B": "Ketegasan dalam menegakkan prosedur layanan publik",
        "C": "Kecepatan dalam merespons disposisi surat kedinasan",
        "D": "Perilaku ramah, sopan, menghargai, dan peduli sesama",
        "E": "Ketelitian dalam memverifikasi berkas permohonan dinas"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "**Perilaku asertif** adalah **perilaku ramah, sopan, menghargai dan peduli kepada orang lain** dalam memberikan pelayanan.",
        "alasan_kunci": "Opsi D merupakan jawaban yang tepat karena keasertifan dalam konteks pelayanan kementerian memadukan kesantunan sikap dengan kepedulian tulus terhadap kebutuhan masyarakat pemangku kepentingan. Sikap bersahabat ini menumbuhkan rasa nyaman dan kepercayaan publik terhadap profesionalitas aparatur. Dengan demikian, ramah, sopan, menghargai, dan peduli adalah makna perilaku asertif.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena kelugasan menyampaikan regulasi berfokus pada kejelasan komunikasi hukum.",
            "B": "Kurang tepat karena ketegasan menegakkan prosedur berkaitan dengan kedisiplinan operasional layanan.",
            "C": "Kurang tepat karena kecepatan merespons disposisi surat mencerminkan ketangkasan nilai Pelayanan.",
            "D": "Merupakan definisi perilaku asertif pada nilai Pelayanan sebagaimana tertulis dalam materi modul.",
            "E": "Kurang tepat karena ketelitian memverifikasi berkas permohonan mencerminkan kecermatan Profesionalisme."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q26 (Hlm 27, Key A)
updates[26] = {
    "pertanyaan": "Perilaku utama kedua dari nilai pelayanan yang wajib dimiliki oleh para pegawai adalah...",
    "pilihan": {
        "A": "Bersikap proaktif dan cepat tanggap dalam melayani",
        "B": "Melayani berorientasi kepuasan pemangku kepentingan",
        "C": "Mempunyai keahlian dan pengetahuan yang luas",
        "D": "Menemukan dan melaksanakan solusi terbaik bersama",
        "E": "Melakukan perbaikan terus-menerus di segala bidang"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Perilaku utama kedua dari nilai Pelayanan adalah **bersikap proaktif dan cepat tanggap** dalam melayani pemangku kepentingan.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena petugas pelayanan dituntut peka mengenali kebutuhan pengguna jasa dan tanggap memberikan solusi tanpa harus menunggu diminta. Ketangkasan merespons ini menjadi tolok ukur keunggulan birokrasi modern yang berorientasi pada kepuasan masyarakat. Oleh sebab itu, proaktif dan cepat tanggap adalah perilaku utama kedua nilai pelayanan.",
        "bedah_pengecoh": {
            "A": "Merupakan perilaku utama kedua dari nilai Pelayanan dalam naskah keputusan nilai-nilai kementerian.",
            "B": "Kurang tepat karena melayani berorientasi kepuasan merupakan perilaku utama pertama nilai Pelayanan.",
            "C": "Kurang tepat karena mempunyai keahlian luas merupakan perilaku utama pertama nilai Profesionalisme.",
            "D": "Kurang tepat karena menemukan solusi terbaik merupakan perilaku utama kedua dari nilai Sinergi.",
            "E": "Kurang tepat karena melakukan perbaikan terus-menerus merupakan perilaku utama nilai Kesempurnaan."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q27 (Hlm 27, Key C)
updates[27] = {
    "pertanyaan": "Contoh perilaku proaktif dan cepat tanggap saat melihat tamu nampak kebingungan di kantor dinas adalah...",
    "pilihan": {
        "A": "Meminta petugas resepsionis memanggil pemohon bersangkutan",
        "B": "Menunggu pemohon mendatangi loket pelayanan informasi",
        "C": "Menghampiri dan menawarkan bantuan tanpa menunggu diminta",
        "D": "Menyediakan brosur panduan alur perizinan di meja tunggu",
        "E": "Menjelaskan jadwal loket layanan melalui pengeras suara"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Jika ada seseorang nampak kebingungan, pegawai perlu **menghampiri dan menawarkan bantuan tanpa disuruh atau diminta**.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena inisiatif langsung menghampiri masyarakat yang membutuhkan panduan mencerminkan kepekaan empati aparatur dalam melayani. Pelayanan prima tidak bersifat pasif di balik meja, melainkan aktif menjemput kebutuhan pengguna jasa di ruang layanan. Dengan demikian, menawarkan bantuan langsung adalah wujud cepat tanggap.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena meminta resepsionis memanggil pemohon mencerminkan sikap mengalihkan tanggung jawab.",
            "B": "Kurang tepat karena menunggu pemohon mendatangi loket mencerminkan pola pelayanan pasif.",
            "C": "Merupakan contoh konkret penerapan perilaku proaktif dan cepat tanggap sebagaimana diuraikan di modul.",
            "D": "Kurang tepat karena penyediaan brosur alur perizinan merupakan fasilitas informasi pasif di ruang tunggu.",
            "E": "Kurang tepat karena pengumuman jadwal loket merupakan penyampaian informasi massal umum."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q31 (Hlm 28, Key B)
updates[31] = {
    "pertanyaan": "Ungkapan bijak yang ditekankan dalam etos kerja agar dicamkan oleh pegawai Kementerian Keuangan berbunyi...",
    "pilihan": {
        "A": "Selesaikan pekerjaan hari ini tanpa menunda waktu esok",
        "B": "Bekerjalah melebihi apa yang didapatkan demi hasil lebih",
        "C": "Utamakan ketepatan prosedur di atas kecepatan layanan",
        "D": "Jadikan integritas sebagai pedoman utama setiap tindakan",
        "E": "Bangun sinergi harmonis demi keberhasilan tugas bersama"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Ungkapan bijak yang perlu dicamkan pegawai berbunyi: **bekerjalah Anda melebihi apa yang Anda dapatkan, maka Anda akan mendapatkan melebihi apa yang Anda kerjakan**.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena pepatah tersebut menanamkan semangat pengabdian total di mana aparatur memberikan karya terbaik melampaui imbalan materiil yang diterimanya. Dedikasi tulus ini membuka jalan bagi pencapaian prestasi dan keberkahan karier yang jauh melampaui ekspektasi. Oleh karena itu, bekerja melampaui imbalan adalah nasihat bijak yang ditekankan.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena tidak menunda pekerjaan mencerminkan prinsip kedisiplinan manajemen waktu umum.",
            "B": "Merupakan ungkapan bijak yang ditekankan secara tersurat dalam materi nilai kementerian.",
            "C": "Kurang tepat karena ketepatan prosedur di atas kecepatan merupakan kaidah kepatuhan administratif.",
            "D": "Kurang tepat karena integritas sebagai pedoman tindakan mencerminkan amanat umum nilai moral.",
            "E": "Kurang tepat karena sinergi harmonis demi keberhasilan tugas merupakan prinsip kerja sama tim."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q33 (Hlm 28, Key A)
updates[33] = {
    "pertanyaan": "Terkait capaian prestasi tugas yang diraih saat ini, sikap mental yang wajib dimiliki pegawai adalah...",
    "pilihan": {
        "A": "Tidak cepat puas dengan prestasi yang sudah didapatkan",
        "B": "Menjaga konsistensi metode kerja lama secara cermat",
        "C": "Menjadikan prestasi sebagai standar pencapaian permanen",
        "D": "Memfokuskan energi pada pemeliharaan sistem yang ada",
        "E": "Mengupayakan pengakuan formal atas kinerja yang diraih"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Pegawai Kementerian Keuangan perlu memiliki perilaku **tidak cepat puas dengan prestasi yang sudah didapatkan saat ini** guna memacu perbaikan tiada henti.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena rasa cepat puas dapat memicu kejenuhan dan stagnasi kinerja di tengah dinamika tantangan birokrasi yang terus berkembang. Sikap rendah hati dan senantiasa haus perbaikan mendorong lahirnya inovasi-inovasi baru demi kemajuan institusi. Dengan demikian, tidak cepat berpuas diri adalah sikap mental nilai Kesempurnaan.",
        "bedah_pengecoh": {
            "A": "Merupakan sikap mental esensial dalam menerapkan nilai Kesempurnaan sebagaimana tertulis di modul.",
            "B": "Kurang tepat karena mempertahankan metode lama secara kaku dapat menghambat adaptasi perubahan zaman.",
            "C": "Kurang tepat karena menganggap prestasi sebagai standar permanen membatasi ruang pengembangan diri.",
            "D": "Kurang tepat karena pemeliharaan sistem yang ada tanpa pembaruan menimbulkan stagnasi birokrasi.",
            "E": "Kurang tepat karena mengejar pengakuan formal berorientasi pada pencitraan, bukan mutu kerja nyata."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q34 (Hlm 28, Key C)
updates[34] = {
    "pertanyaan": "Cara praktis yang diterapkan secara berkelanjutan untuk melakukan perbaikan terus-menerus adalah...",
    "pilihan": {
        "A": "Menata jadwal pelatihan pegawai secara berkala",
        "B": "Menyusun standar prosedur operasional baru",
        "C": "Selalu mengevaluasi setiap hasil pekerjaan",
        "D": "Memperkuat koordinasi tim lintas seksi kerja",
        "E": "Mengembangkan sarana teknologi informasi dinas"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Cara melakukan perbaikan secara terus menerus adalah **dengan selalu mengevaluasi setiap hasil pekerjaan**.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena evaluasi berkala memungkinkan pendeteksian dini terhadap celah kelemahan atau ketidakefisienan dalam proses bisnis kantor. Temuan evaluasi tersebut menjadi landasan konkret perumusan langkah-langkah penyempurnaan di masa berikutnya. Oleh sebab itu, evaluasi hasil kerja adalah metode praktis perbaikan terus-menerus.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena penataan jadwal pelatihan pegawai merupakan sarana pengembangan kapasitas SDM.",
            "B": "Kurang tepat karena penyusunan standar prosedur baru merupakan tindak lanjut dari standardisasi kerja.",
            "C": "Merupakan cara praktis melakukan perbaikan terus-menerus sebagaimana dinyatakan secara tegas di modul.",
            "D": "Kurang tepat karena penguatan koordinasi tim lintas seksi merupakan instrumen pada nilai Sinergi.",
            "E": "Kurang tepat karena pengembangan teknologi informasi merupakan modernisasi sarana prasarana dinas."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q35 (Hlm 28, Key E)
updates[35] = {
    "pertanyaan": "Melalui evaluasi rutin diperoleh temuan ketidaksempurnaan pekerjaan, yang secara positif berfungsi untuk...",
    "pilihan": {
        "A": "Menjadi dasar penyusunan kontrak kinerja baru",
        "B": "Menata alokasi sumber daya secara proporsional",
        "C": "Menyesuaikan target sasaran rencana operasional",
        "D": "Memperbarui panduan standar pelayanan masyarakat",
        "E": "Membuka ruang perbaikan demi keunggulan kerja"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Penemuan kekurangan atau ketidaksempurnaan hasil pekerjaan berfungsi secara positif untuk **membuka ruang perbaikan**.",
        "alasan_kunci": "Opsi E merupakan jawaban yang tepat karena pengakuan atas kekurangan kerja bukanlah alasan saling menyalahkan, melainkan titik tolak penyempurnaan mutu kelembagaan. Kesadaran terhadap celah perbaikan memacu seluruh pegawai untuk terus berinovasi menghasilkan karya yang lebih sempurna. Dengan demikian, terbukanya ruang perbaikan adalah fungsi utama evaluasi hasil kerja.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena penyusunan kontrak kinerja baru merupakan siklus tahunan manajemen kinerja ASN.",
            "B": "Kurang tepat karena penataan alokasi sumber daya merupakan aspek perimbangan kapasitas satuan kerja.",
            "C": "Kurang tepat karena penyesuaian target rencana operasional berfokus pada revisi target berkala.",
            "D": "Kurang tepat karena pembaruan panduan standar pelayanan masyarakat merupakan aspek tata kelola loket.",
            "E": "Merupakan fungsi konstruktif dari penemuan ketidaksempurnaan kerja sebagaimana diuraikan dalam modul."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q37 (Hlm 28, Key D)
updates[37] = {
    "pertanyaan": "Nilai tambah signifikan yang dihasilkan dari proses inovasi dalam organisasi terutama berupa...",
    "pilihan": {
        "A": "Efisiensi waktu dalam alur birokrasi perizinan",
        "B": "Keandalan sistem tata kelola administrasi naskah",
        "C": "Ketepatan perhitungan target penerimaan negara",
        "D": "Nilai ekonomi dan sosial bagi masyarakat publik",
        "E": "Penguatan sinergi kemitraan lintas kementerian"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Inovasi ditujukan untuk menciptakan produk atau sistem baru yang **memberikan nilai tambah secara signifikan (terutama nilai ekonomi dan sosial)**.",
        "alasan_kunci": "Opsi D merupakan jawaban yang tepat karena inovasi di sektor publik dinilai berdampak nyata apabila mampu mendorong pertumbuhan ekonomi nasional serta meningkatkan kesejahteraan masyarakat. Kemaslahatan sosial-ekonomi membuktikan bahwa pembaruan sistem birokrasi dirasakan langsung manfaatnya oleh rakyat. Oleh karena itu, nilai ekonomi dan sosial adalah nilai tambah utama inovasi.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena efisiensi waktu birokrasi perizinan merupakan salah satu dampak operasional inovasi.",
            "B": "Kurang tepat karena keandalan tata kelola naskah dinas merupakan dampak teknis kearsipan kantor.",
            "C": "Kurang tepat karena ketepatan perhitungan target penerimaan merupakan sasaran teknis perbendaharaan.",
            "D": "Merupakan bentuk nilai tambah signifikan yang ditegaskan dalam rumusan definisi inovasi pada modul.",
            "E": "Kurang tepat karena sinergi kemitraan lintas kementerian merupakan sasaran hubungan pada nilai Sinergi."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q41 (Hlm 29, Key A)
updates[41] = {
    "pertanyaan": "Untuk mampu mengembangkan inovasi dan kreativitas kerja, pegawai Kementerian Keuangan harus menjadi...",
    "pilihan": {
        "A": "Manusia pemelajar yang aktif memperbarui diri",
        "B": "Praktisi manajemen yang disiplin pada aturan",
        "C": "Pengkaji kebijakan yang cermat dan akuntabel",
        "D": "Perencana strategi yang berorientasi ke depan",
        "E": "Pelaksana operasional yang konsisten bertugas"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Untuk memiliki perilaku mengembangkan inovasi dan kreativitas, pegawai Kementerian Keuangan **perlu menjadi manusia pemelajar**.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena insan pemelajar memiliki kehausan intelektual untuk selalu mengeksplorasi ilmu baru dan berani mengadopsi kemajuan zaman ke dalam pekerjaannya. Sikap pembelajar berkelanjutan menjadi motor penggerak terciptanya gagasan segar dan terobosan metode kerja di instansi. Dengan demikian, menjadi manusia pemelajar adalah syarat mutlak inovasi.",
        "bedah_pengecoh": {
            "A": "Merupakan karakter esensial pegawai yang dituntut dalam mengembangkan inovasi menurut teks modul.",
            "B": "Kurang tepat karena disiplin aturan mencerminkan ketaatan terhadap prosedur pada nilai Integritas.",
            "C": "Kurang tepat karena kecermatan pengkajian kebijakan mencerminkan ketelitian pada nilai Profesionalisme.",
            "D": "Kurang tepat karena perencana strategi berorientasi ke depan berfokus pada fungsi perencanaan dinas.",
            "E": "Kurang tepat karena pelaksana operasional konsisten mencerminkan keandalan dalam eksekusi tugas."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q42 (Hlm 29, Key C)
updates[42] = {
    "pertanyaan": "Perilaku konkret yang ditunjukkan oleh orang-orang pemelajar di lingkungan kerja adalah...",
    "pilihan": {
        "A": "Mempelajari regulasi teknis kepegawaian terkini",
        "B": "Menghadiri agenda sosialisasi program dinas kantor",
        "C": "Mendorong diri menciptakan proses atau produk baru",
        "D": "Berbagi data informasi melalui jaringan intranet",
        "E": "Menyusun kajian komparatif terhadap metode kerja"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Orang-orang pemelajar akan **mendorong dan memfasilitasi dirinya untuk berbuat, serta menciptakan cara kerja (proses) atau produk-produk (hasil) baru**.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena pembelajar sejati tidak berhenti pada penguasaan teori semata, melainkan mengaktualisasikannya dalam bentuk karya nyata dan penyempurnaan alur kerja. Kemampuan mengubah pengetahuan menjadi solusi praktis merupakan bukti kematangan daya cipta aparatur. Oleh sebab itu, penciptaan proses atau hasil baru adalah ciri konkret manusia pemelajar.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena mempelajari regulasi terkini merupakan kewajiban umum pembaruan pengetahuan dinas.",
            "B": "Kurang tepat karena menghadiri sosialisasi program kantor merupakan kepatuhan agenda dinas kepegawaian.",
            "C": "Merupakan perwujudan konkret dari insan pemelajar sebagaimana diuraikan secara eksplisit di modul.",
            "D": "Kurang tepat karena berbagi informasi melalui intranet merupakan wujud kerja sama pada nilai Sinergi.",
            "E": "Kurang tepat karena penyusunan kajian komparatif merupakan metode penelitian analitis kebijakan."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q47 (Hlm 29, Key E)
updates[47] = {
    "pertanyaan": "Nilai-nilai organisasi (organizational values) berfungsi sebagai pedoman bertindak sekaligus untuk...",
    "pilihan": {
        "A": "Menata hierarki kepemimpinan dalam birokrasi",
        "B": "Mengatur alokasi pembagian pagu anggaran seksi",
        "C": "Menetapkan kriteria promosi jenjang kepangkatan",
        "D": "Menyusun instrumen evaluasi akuntabilitas satker",
        "E": "Meningkatkan kohesi dan rasa kesatuan organisasi"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Nilai-nilai organisasi adalah pedoman dalam mengambil tindakan dan keputusan, serta berfungsi untuk **meningkatkan kohesi dan rasa kesatuan dalam organisasi**.",
        "alasan_kunci": "Opsi E merupakan jawaban yang tepat karena nilai bersama merekatkan seluruh anggota dari berbagai latar belakang ke dalam satu identitas korps yang solid dan harmonis. Keterpaduan rasa kesatuan ini mencegah timbulnya ego sektoral dan memperkuat komitmen pencapaian visi institusi. Dengan demikian, peningkatan kohesi dan persatuan adalah fungsi utama nilai organisasi.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena penataan hierarki kepemimpinan merupakan ranah regulasi struktur organisasi.",
            "B": "Kurang tepat karena pengaturan pagu anggaran seksi merupakan instrumen teknis penganggaran negara.",
            "C": "Kurang tepat karena kriteria promosi jenjang kepangkatan diatur dalam sistem manajemen talenta ASN.",
            "D": "Kurang tepat karena evaluasi akuntabilitas satker menggunakan pedoman penilaian sistem kinerja publik.",
            "E": "Merupakan fungsi fundamental nilai-nilai organisasi bagi keutuhan institusi sebagaimana tertulis di modul."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q48 (Hlm 29, Key C)
updates[48] = {
    "pertanyaan": "Sebagai organisasi modern dan dinamis, arah pergerakan tata kelola Kementerian Keuangan selalu menuju...",
    "pilihan": {
        "A": "Standarisasi prosedur pelayanan berbasis digital",
        "B": "Penguatan struktur hierarki kelembagaan vertikal",
        "C": "Pengelolaan manajemen yang maju (best practices)",
        "D": "Kemitraan strategis dengan institusi internasional",
        "E": "Optimalisasi penerimaan fiskal berbasis potensi riil"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Kementerian Keuangan adalah organisasi modern yang dinamis yang pergerakannya selalu menuju **sebuah pengelolaan manajemen yang maju (best practices)**.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena Kementerian Keuangan senantiasa melakukan modernisasi tata kelola birokrasi mengadopsi standar-standar keunggulan manajemen kelas dunia. Pengelolaan yang maju menjamin efektivitas tata kelola fiskal negara yang transparan dan akuntabel. Oleh karena itu, pengelolaan manajemen maju (best practices) adalah arah pergerakan kementerian.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena standarisasi prosedur digital merupakan salah satu sarana penunjang tata laksana.",
            "B": "Kurang tepat karena penguatan struktur hierarki vertikal berkaitan dengan perancangan struktur kelembagaan.",
            "C": "Merupakan rumusan resmi arah pergerakan tata kelola kementerian sebagaimana dipaparkan pada materi modul.",
            "D": "Kurang tepat karena kemitraan internasional merupakan aspek diplomasi kerja sama bidang fiskal.",
            "E": "Kurang tepat karena optimalisasi penerimaan fiskal merupakan target output pada fungsi penerimaan negara."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q49 (Hlm 29, Key B)
updates[49] = {
    "pertanyaan": "Dalam misi penguatan kelembagaan, Kementerian Keuangan menargetkan pembangunan SDM yang...",
    "pilihan": {
        "A": "Terampil, cekatan, berwawasan luas, dan adaptif",
        "B": "Amanah, profesional, berintegritas, dan bertanggung jawab",
        "C": "Kritis, solutif, memiliki dedikasi tinggi, dan loyal",
        "D": "Mandiri, inovatif, berdaya saing global, dan tanggap",
        "E": "Kolaboratif, ramah, beretika luhur, dan disiplin"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Misi kementerian mencakup: membangun dan mengembangkan sumber daya manusia yang **amanah, profesional, berintegritas tinggi, dan bertanggung jawab**.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena keempat karakter luhur tersebut menjadi pilar utama pembentukan aparatur pengelola keuangan negara yang beretika mulia dan kompeten. Karakter amanah dan integritas menjaga kepercayaan rakyat terhadap tata kelola kas negara. Dengan demikian, amanah, profesional, berintegritas, dan bertanggung jawab adalah rumusan misi SDM.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena kombinasi kompetensi ini merupakan rumusan umum pengembangan kapasitas kerja.",
            "B": "Merupakan rumusan baku butir misi penguatan SDM Kementerian Keuangan sebagaimana tertulis di modul.",
            "C": "Kurang tepat karena kritis dan loyal merupakan nilai sikap umum dalam pembinaan etika pegawai.",
            "D": "Kurang tepat karena mandiri dan berdaya saing global merupakan visi pengembangan talenta aparatur.",
            "E": "Kurang tepat karena kolaboratif dan beretika luhur mencerminkan sikap pada nilai Harmonis dan Pelayanan."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q50 (Hlm 29, Key D)
updates[50] = {
    "pertanyaan": "Karakteristik sistem teknologi informasi keuangan yang dibangun dalam misi kelembagaan kementerian adalah...",
    "pilihan": {
        "A": "Mandiri, terdesentralisasi, dan berbasis web lokal",
        "B": "Fleksibel, adaptif, serta ditopang aplikasi terbuka",
        "C": "Cepat, responsif, dan berbasis kecerdasan buatan",
        "D": "Modern, terintegrasi, dan ditopang sarana strategis",
        "E": "Komprehensif, otomatis, dan berorientasi pengguna"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Misi penguatan kementerian mencakup: membangun dan mengembangkan **teknologi informasi keuangan yang modern dan terintegrasi serta sarana dan prasarana strategis lainnya**.",
        "alasan_kunci": "Opsi D merupakan jawaban yang tepat karena integrasi sistem teknologi informasi keuangan menjamin kelancaran arus data perbendaharaan negara secara aman, cepat, dan terpercaya. Keterpaduan infrastruktur strategis ini meminimalisasi duplikasi data dan meningkatkan akurasi analisis fiskal. Oleh sebab itu, modern, terintegrasi, dan ditopang sarana strategis adalah karakteristiknya.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena desentralisasi web lokal bertentangan dengan prinsip keterpaduan basis data nasional.",
            "B": "Kurang tepat karena aplikasi terbuka memiliki risiko kerentanan keamanan terhadap kerahasiaan data fiskal.",
            "C": "Kurang tepat karena kecerdasan buatan merupakan salah satu fitur pengembangan teknologi mutakhir dinas.",
            "D": "Merupakan rumusan baku karakteristik misi pengembangan teknologi informasi menurut materi modul.",
            "E": "Kurang tepat karena otomatis dan berorientasi pengguna merupakan karakteristik umum antarmuka sistem."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q61 (Hlm 31, Key A)
updates[61] = {
    "pertanyaan": "Dalam pembinaan budaya kerja, nilai-nilai organisasi tidak akan bermakna sama sekali apabila...",
    "pilihan": {
        "A": "Tidak diimplementasikan dalam kehidupan sehari-hari",
        "B": "Tidak didukung oleh alokasi sarana prasarana kerja",
        "C": "Tidak dituangkan dalam modul pembelajaran pegawai",
        "D": "Tidak disosialisasikan secara berkala oleh pimpinan",
        "E": "Tidak diujikan dalam penilaian kompetensi berkala"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Nilai-nilai yang sudah dirumuskan **tidak akan bermakna sama sekali jika tidak diimplementasikan atau tidak diterapkan dalam kehidupan sehari-hari**.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena nilai organisasi bukan sekadar pajangan slogan administratif, melainkan penuntun perilaku moral dan operasional nyata di lingkungan dinas. Keberhasilan budaya kerja diukur dari sejauh mana nilai tersebut diamalkan dalam setiap tindakan dan pengambilan keputusan aparatur. Dengan demikian, penerapan harian adalah penentu utama kebermaknaan nilai.",
        "bedah_pengecoh": {
            "A": "Merupakan penegasan substantif mengenai urgensi penerapan nilai bersama sebagaimana diuraikan di modul.",
            "B": "Kurang tepat karena sarana prasarana merupakan pendukung fisik, bukan penentu esensi hidupnya nilai.",
            "C": "Kurang tepat karena modul pembelajaran merupakan media sosialisasi transfer pengetahuan nilai.",
            "D": "Kurang tepat karena sosialisasi pimpinan merupakan metode penyebaran pemahaman konsep budaya kerja.",
            "E": "Kurang tepat karena uji kompetensi merupakan instrumen formal evaluasi pemahaman regulasi dinas."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q63 (Hlm 31, Key B)
updates[63] = {
    "pertanyaan": "Diktum ketiga KMK Nomor 312/KMK.01/2011 mewajibkan pelaksanaan tugas pokok berlandaskan nilai bagi...",
    "pilihan": {
        "A": "Pejabat struktural eselon satu dan eselon dua",
        "B": "Setiap pimpinan dan seluruh Pegawai Negeri Sipil",
        "C": "Pejabat fungsional pemeriksa dan auditor dinas",
        "D": "Aparatur yang bertugas pada seksi pelayanan loket",
        "E": "Seluruh agen perubahan di tingkat satuan kerja"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Dalam keputusan ketiga KMK 312/2011 disebutkan bahwa **setiap pimpinan dan seluruh Pegawai Negeri Sipil di lingkungan Kementerian Keuangan wajib melaksanakan tugas pokok dan fungsinya dengan mendasarkan pada nilai-nilai dan kaidah perilaku utama**.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena kewajiban menjunjung tinggi nilai kementerian mengikat seluruh jajaran pegawai tanpa terkecuali, mulai dari pejabat tertinggi hingga pelaksana. Keterikatan universal ini menjamin terbentuknya budaya integritas yang merata di seluruh lini organisasi kementerian. Oleh karena itu, pimpinan dan seluruh PNS adalah subjek wajib pelaksanaan nilai.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena kewajiban tidak terbatas pada pejabat eselon struktural saja, melainkan seluruh aparatur.",
            "B": "Merupakan bunyi eksplisit diktum keputusan ketiga KMK Nomor 312/KMK.01/2011 sebagaimana diatur di modul.",
            "C": "Kurang tepat karena pejabat fungsional auditor merupakan salah satu rumpun jabatan di kementerian.",
            "D": "Kurang tepat karena petugas pelayanan loket hanyalah sebagian dari seluruh aparatur sipil negara kementerian.",
            "E": "Kurang tepat karena agen perubahan merupakan penggerak budaya, sedangkan kewajiban nilai mengikat semua."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q70 (Hlm 31, Key C)
updates[70] = {
    "pertanyaan": "Penguatan internalisasi Nilai-Nilai Kementerian Keuangan secara terstruktur dilakukan melalui wahana...",
    "pilihan": {
        "A": "Standardisasi kurikulum diklat kepemimpinan aparatur",
        "B": "Evaluasi berkala terhadap peta proses bisnis kantor",
        "C": "Penguatan budaya organisasi yang hidup dan dinamis",
        "D": "Penyusunan kontrak kinerja tahunan berbasis output",
        "E": "Optimalisasi sistem reward and punishment kepegawaian"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Penguatan internalisasi Nilai-Nilai Kementerian Keuangan secara terstruktur dilakukan **melalui Budaya Organisasi**.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena budaya kerja organisasi menyediakan wahana pembiasaan hal-hal positif serta internalisasi kaidah perilaku utama dalam aktivitas harian aparatur. Melalui budaya kerja yang sehat, nilai-nilai kementerian bertransformasi menjadi norma kerja yang mengakar secara alamiah. Dengan demikian, budaya organisasi adalah wahana penguatan internalisasi.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena kurikulum diklat kepemimpinan merupakan sarana pendidikan formal manajerial SDM.",
            "B": "Kurang tepat karena evaluasi peta proses bisnis berfokus pada penyempurnaan alur tata kelola kerja.",
            "C": "Merupakan wahana utama penguatan internalisasi nilai-nilai kementerian sebagaimana tercantum di modul.",
            "D": "Kurang tepat karena penyusunan kontrak kinerja tahunan berkaitan dengan pengukuran target tugas individu.",
            "E": "Kurang tepat karena sistem reward and punishment merupakan instrumen motivasi kedisiplinan pegawai."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q74 (Hlm 32, Key C)
updates[74] = {
    "pertanyaan": "Program Budaya yang diatur dalam KMK Nomor 127/KMK.01/2013 wajib dilaksanakan oleh...",
    "pilihan": {
        "A": "Pejabat struktural pemegang wewenang otorisasi anggaran",
        "B": "Pegawai yang bertugas pada bagian kepegawaian dan umum",
        "C": "Seluruh Pegawai Negeri Sipil dan Calon Pegawai Negeri Sipil",
        "D": "Tenaga analis kebijakan dan fungsional perbendaharaan",
        "E": "Aparatur sipil negara di lingkungan kantor wilayah daerah"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "KMK Nomor 127/KMK.01/2013 berisi tentang Program Budaya yang perlu dilaksanakan oleh **seluruh Pegawai Negeri Sipil dan Calon Pegawai Negeri Sipil di lingkungan Kementerian Keuangan**.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena pembiasaan etika kerja dan disiplin ditanamkan sejak dini kepada CPNS serta diwajibkan kepada seluruh PNS kementerian tanpa diskriminasi jabatan. Inklusivitas sasaran program budaya memastikan tidak ada satupun insan aparatur yang luput dari pembinaan karakter. Oleh sebab itu, seluruh PNS dan CPNS adalah sasaran wajib pelaksanaan program budaya.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena pemegang otorisasi anggaran merupakan pejabat struktural perbendaharaan tertentu.",
            "B": "Kurang tepat karena bagian kepegawaian merupakan unit pengelola administrasi SDM satuan kerja.",
            "C": "Merupakan subjek hukum wajib yang diatur secara eksplisit dalam naskah KMK Nomor 127/KMK.01/2013.",
            "D": "Kurang tepat karena analis kebijakan dan fungsional merupakan kelompok jabatan fungsional keahlian.",
            "E": "Kurang tepat karena kewajiban berlaku menyeluruh di kantor pusat maupun kantor vertikal daerah."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q75 (Hlm 32, Key E)
updates[75] = {
    "pertanyaan": "Pertimbangan filosofis dibentuknya program budaya kerja dalam pembiasaan etika kerja pegawai adalah...",
    "pilihan": {
        "A": "Penegakan disiplin waktu menjamin keteraturan tugas",
        "B": "Penerapan tata tertib seragam memelihara citra korps",
        "C": "Komitmen bersama pimpinan mempercepat perubahan pola kerja",
        "D": "Pembentukan change agent memperkuat sinergi organisasi",
        "E": "Kebaikan yang berulang-ulang akan menjadi suatu kebiasaan"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Pemikiran yang dijadikan pertimbangan adalah bahwa **dengan melakukan kebaikan-kebaikan yang berulang, maka kegiatan yang dilakukan para pegawai akan menjadi kebiasaan**.",
        "alasan_kunci": "Opsi E merupakan jawaban yang tepat karena pembentukan karakter aparatur tidak dapat terwujud instan, melainkan melalui proses pengulangan perilaku kebajikan secara konsisten setiap hari. Ketika perbuatan baik telah menjadi kebiasaan alamiah, integritas kerja akan otomatis terpelihara tanpa perlu pengawasan ketat. Dengan demikian, kebaikan berulang yang menjadi kebiasaan adalah filosofi dasarnya.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena keteraturan tugas merupakan salah satu manfaat teknis penegakan disiplin waktu.",
            "B": "Kurang tepat karena memelihara citra korps merupakan dampak positif dari ketertiban pakaian seragam.",
            "C": "Kurang tepat karena komitmen pimpinan merupakan faktor pendorong akselerasi transformasi birokrasi.",
            "D": "Kurang tepat karena change agent merupakan instrumen penggerak budaya pada satuan kerja kantor.",
            "E": "Merupakan landasan pemikiran filosofis program budaya sebagaimana dirumuskan secara tegas dalam modul."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q78 (Hlm 32, Key A)
updates[78] = {
    "pertanyaan": "Ketentuan mengenai 'Two minutes before schedule' pada butir kedua program 5 Must bermakna...",
    "pilihan": {
        "A": "Hadir dua menit sebelum jadwal yang ditentukan",
        "B": "Membaca risalah rapat dua menit sebelum dimulai",
        "C": "Menyiapkan perlengkapan tugas dua menit lebih awal",
        "D": "Mengonfirmasi kehadiran dinas dua menit sebelum apel",
        "E": "Menyelesaikan disposisi surat dua menit sebelum tenggat"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Butir kedua program 5 Must adalah **Two minutes before schedule (hadir dua menit sebelum jadwal)**.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena pembiasaan hadir lebih awal melatih kedisiplinan tinggi, kesiapan mental, serta bentuk penghormatan terhadap waktu rekan kerja dan pemangku kepentingan. Ketepatan waktu menjamin setiap agenda rapat maupun pelayanan dinas dapat dimulai persis sesuai jadwal. Oleh karena itu, hadir dua menit sebelum jadwal adalah makna harfiahnya.",
        "bedah_pengecoh": {
            "A": "Merupakan arti dan maksud eksplisit butir kedua program 5 Must sebagaimana diatur dalam regulasi modul.",
            "B": "Kurang tepat karena membaca risalah rapat merupakan langkah persiapan materi sebelum agenda dimulai.",
            "C": "Kurang tepat karena menyiapkan perlengkapan tugas merupakan wujud kesiapan operasional kerja.",
            "D": "Kurang tepat karena konfirmasi kehadiran dinas merupakan bagian dari tertib absensi kepegawaian.",
            "E": "Kurang tepat karena penyelesaian disposisi surat berkaitan dengan ketepatan waktu tata persuratan."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q81 (Hlm 33, Key A)
updates[81] = {
    "pertanyaan": "Simbol fisik Nilai-Nilai Kementerian Keuangan dipasang pada area yang sering dilewati pegawai seperti...",
    "pilihan": {
        "A": "Pintu gerbang kantor, koridor, dinding, dan meja kerja",
        "B": "Ruang sidang pimpinan, perpustakaan, dan aula utama",
        "C": "Ruang arsip berkas, pos penjagaan, dan tempat parkir",
        "D": "Loket pelayanan umum, ruang tunggu, dan serambi depan",
        "E": "Ruang rapat koordinasi, laboratorium komputer, dan teras"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Simbol-simbol Nilai-Nilai Kementerian dipasang di tempat-tempat yang sering dilewati pegawai seperti di **pintu gerbang kantor, koridor kantor, dinding ruang kerja, meja kerja**, dan sebagainya.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena penempatan simbol nilai pada titik-titik lintasan harian pegawai berfungsi sebagai pengingat visual yang terus-menerus terhadap komitmen kode etik kementerian. Kekerapan melihat simbol nilai memperkuat proses internalisasi nilai secara bawah sadar dalam bekerja. Dengan demikian, pintu gerbang, koridor, dinding, dan meja kerja adalah lokasi pemasangannya.",
        "bedah_pengecoh": {
            "A": "Merupakan rincian lokasi fisik penempatan simbol nilai sebagaimana tertulis eksplisit dalam teks modul.",
            "B": "Kurang tepat karena perpustakaan dan aula utama merupakan area penunjang kegiatan tertentu saja.",
            "C": "Kurang tepat karena ruang arsip dan pos penjagaan bukan merupakan titik utama perlintasan seluruh pegawai.",
            "D": "Kurang tepat karena ruang tunggu loket pelayanan lebih ditujukan bagi masyarakat publik pengguna jasa.",
            "E": "Kurang tepat karena laboratorium komputer dan teras merupakan fasilitas penunjang teknis kantor."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q82 (Hlm 33, Key C)
updates[82] = {
    "pertanyaan": "Variasi bentuk simbol nilai yang digunakan di lingkungan kantor kementerian meliputi media...",
    "pilihan": {
        "A": "Papan pengumuman berkas, brosur cetak, dan majalah dinding",
        "B": "Spanduk kain, stiker inventaris dinas, dan kartu pegawai",
        "C": "Running text, baliho, standing tripod, dan sinyal web",
        "D": "Banner digital interaktif, kalender kerja, dan plakat kayu",
        "E": "Booklet panduan saku, selebaran dinas, dan papan petunjuk"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Simbol-simbol nilai dibuat dalam berbagai bentuk seperti **running text, baliho, tulisan-tulisan yang disangga dengan tripod, hingga signal-signal yang muncul di halaman utama web instansi**.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena pemanfaatan beragam media visual modern baik cetak maupun digital memastikan pesan nilai kementerian tersampaikan secara menarik dan dinamis. Kreativitas bentuk simbol menghindarkan pegawai dari kejenuhan visual serta memperkuat daya ingat pesan nilai. Oleh sebab itu, running text, baliho, tripod, dan notifikasi web adalah variasinya.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena majalah dinding dan papan pengumuman merupakan media informasi konvensional kantor.",
            "B": "Kurang tepat karena stiker inventaris dinas merupakan tanda registrasi barang milik negara satker.",
            "C": "Merupakan ragam bentuk media simbol nilai kementerian sebagaimana disebutkan secara rinci dalam modul.",
            "D": "Kurang tepat karena plakat kayu dan kalender kerja merupakan suvenir kedinasan operasional tahunan.",
            "E": "Kurang tepat karena booklet panduan saku merupakan dokumen pegangan fisik bahan ajar aturan."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q87 (Hlm 33, Key C)
updates[87] = {
    "pertanyaan": "Instruksi pertama Menteri Keuangan dalam IMK Nomor 346/IMK.01/2017 memerintahkan pegawai untuk...",
    "pilihan": {
        "A": "Menyusun rencana aksi penghematan belanja satuan kerja",
        "B": "Mengoptimalkan pemanfaatan aset sarana prasarana dinas",
        "C": "Mendorong perubahan mindset, pola kerja, dan spirit bertugas",
        "D": "Mengembangkan inovasi digital dalam alur administrasi kerja",
        "E": "Melakukan evaluasi berkala terhadap efektivitas anggaran unit"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Instruksi pertama IMK Nomor 346/IMK.01/2017 adalah mengimplementasikan dan menginternalisasikan Nilai-Nilai Kementerian Keuangan dan **mendorong perubahan mindset, pola kerja, dan spirit dalam pelaksanaan tugas**.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena efisiensi birokrasi sejati harus diawali dari pembaruan pola pikir (mindset) dan semangat kerja aparatur dari pola lama yang boros menuju tata kerja yang produktif dan hemat. Perubahan sikap mental menjadi fondasi utama keberhasilan penghematan anggaran negara. Dengan demikian, pembaruan mindset, pola kerja, dan spirit adalah instruksi pertama.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena penyusunan rencana aksi penghematan merupakan tahapan perencanaan teknis satker.",
            "B": "Kurang tepat karena optimalisasi aset sarana prasarana merupakan mandat teknis pengelolaan barang dinas.",
            "C": "Merupakan butir instruksi pertama Menkeu dalam IMK Nomor 346/IMK.01/2017 sebagaimana diatur di modul.",
            "D": "Kurang tepat karena pengembangan inovasi digital tata kelola kerja merupakan wujud nilai Kesempurnaan.",
            "E": "Kurang tepat karena evaluasi efektivitas anggaran unit merupakan fungsi pengawasan akuntabilitas kas."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q93 (Hlm 34, Key E)
updates[93] = {
    "pertanyaan": "Instruksi Menteri Keuangan memberikan penekanan khusus kepada Pejabat Pembuat Komitmen (PPK) agar...",
    "pilihan": {
        "A": "Mempercepat proses lelang pengadaan barang secara terpadu",
        "B": "Menyusun analisis standar biaya masukan satuan kerja",
        "C": "Melakukan rekonsiliasi data realisasi perbendaharaan kas",
        "D": "Mengoptimalkan penyerapan belanja operasional kantor",
        "E": "Mengelola anggaran pada satker secara efektif, efisien, dan beretika"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Instruksi ini menekankan kepada para Pejabat Pembuat Komitmen agar **mengelola anggaran pada satuan kerja masing-masing dengan efektif, efisien, dan beretika**.",
        "alasan_kunci": "Opsi E merupakan jawaban yang tepat karena PPK memegang peranan krusial sebagai pengambil keputusan komitmen belanja negara yang wajib mengedepankan integritas moral dan kehati-hatian fiskal. Tata kelola belanja yang efektif dan beretika memastikan setiap rupiah uang rakyat digunakan secara akuntabel bagi kepentingan publik. Oleh sebab itu, pengelolaan anggaran efektif, efisien, dan beretika adalah instruksinya.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena percepatan proses lelang pengadaan merupakan tugas pejabat pengadaan barang jasa.",
            "B": "Kurang tepat karena analisis standar biaya masukan disusun oleh unit perencana anggaran kementerian.",
            "C": "Kurang tepat karena rekonsiliasi realisasi kas merupakan tugas pejabat penatausahaan keuangan kantor.",
            "D": "Kurang tepat karena optimalisasi penyerapan belanja harus tetap memperhatikan prinsip efisiensi mutu.",
            "E": "Merupakan amanat instruksi khusus Menkeu kepada Pejabat Pembuat Komitmen sebagaimana tertulis di modul."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q94 (Hlm 34, Key B)
updates[94] = {
    "pertanyaan": "Himbauan pertama yang ditujukan kepada setiap pegawai dalam SE-12/MK.1/2018 adalah...",
    "pilihan": {
        "A": "Meningkatkan kompetensi teknis melalui pelatihan berkala",
        "B": "Memegang teguh Nilai-Nilai Kementerian Keuangan",
        "C": "Mengoptimalkan koordinasi kerja sama lintas direktorat",
        "D": "Menjaga standar mutu pelayanan publik secara konsisten",
        "E": "Menerapkan prinsip efisiensi anggaran dalam pelaksanaan tugas"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Himbauan pertama kepada setiap pegawai Kementerian Keuangan dalam SE-12/MK.1/2018 adalah **memegang teguh Nilai-Nilai Kementerian Keuangan**.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena memegang teguh nilai kementerian berfungsi sebagai sistem deteksi dini (Early Warning System) dalam membentengi pegawai dari perbuatan menyimpang. Nilai kementerian menjadi kompas moral dasar yang mengarahkan sikap dan integritas aparatur dalam berbagai situasi. Dengan demikian, memegang teguh nilai kementerian adalah butir himbauan pertama.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena peningkatan kompetensi berkala merupakan pilar peningkatan nilai Profesionalisme.",
            "B": "Merupakan butir pertama himbauan pegawai dalam SE-12/MK.1/2018 sebagaimana dipaparkan di modul.",
            "C": "Kurang tepat karena optimalisasi koordinasi lintas direktorat merupakan perwujudan dari nilai Sinergi.",
            "D": "Kurang tepat karena menjaga standar mutu pelayanan merupakan fokus pelaksanaan dari nilai Pelayanan.",
            "E": "Kurang tepat karena penerapan efisiensi anggaran merupakan fokus instruksi pada Gerakan Efisiensi."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q95 (Hlm 34, Key D)
updates[95] = {
    "pertanyaan": "Dalam SE-12/MK.1/2018, pegawai dihimbau senantiasa berpedoman pada etika dalam empat ranah, yaitu...",
    "pilihan": {
        "A": "Berbangsa, berkelembagaan, berkarier, dan berkepribadian",
        "B": "Berorganisasi, berkepemimpinan, beretika, dan bertoleransi",
        "C": "Bernegara, berintegritas, berakuntabilitas, dan bersinergi",
        "D": "Bernegara, berpemerintahan, berorganisasi, dan bermasyarakat",
        "E": "Berpemerintahan, berdemokrasi, berkorporasi, dan berbudaya"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Pegawai dihimbau berpedoman pada **etika dalam bernegara, dalam penyelenggaraan pemerintahan, dalam berorganisasi, dan dalam bermasyarakat**.",
        "alasan_kunci": "Opsi D merupakan jawaban yang tepat karena keempat ruang lingkup tersebut mencakup seluruh dimensi kehidupan aparatur sipil negara baik sebagai abdi negara, bagian dari birokrasi, insan korps, maupun warga masyarakat. Penjagaan etika di empat ranah ini memastikan integritas aparatur tetap terpelihara secara komprehensif. Oleh karena itu, bernegara, berpemerintahan, berorganisasi, dan bermasyarakat adalah empat ranahnya.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena berbangsa dan berkepribadian merupakan pembagian umum konsep etika sosial.",
            "B": "Kurang tepat karena berkepemimpinan dan bertoleransi merupakan dimensi keterampilan interpersonal.",
            "C": "Kurang tepat karena berintegritas dan bersinergi merupakan butir nama nilai kementerian, bukan ranah etika.",
            "D": "Merupakan rincian resmi empat ranah etika aparatur dalam SE-12/MK.1/2018 sebagaimana tertuang di modul.",
            "E": "Kurang tepat karena berdemokrasi dan berkorporasi merupakan terminologi politik dan tata kelola swasta."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q96 (Hlm 34, Key A)
updates[96] = {
    "pertanyaan": "Salah satu larangan penting dalam himbauan pegawai pada SE-12/MK.1/2018 adalah keharusan menghindari...",
    "pilihan": {
        "A": "Perilaku yang mengarah pada ujaran kebencian dan radikalisme",
        "B": "Sikap eksklusivisme yang mengabaikan kerja sama kemitraan",
        "C": "Keterlambatan dalam penyampaian laporan pertanggungjawaban kas",
        "D": "Tindakan mengambil keputusan dinas tanpa telaah komprehensif",
        "E": "Penggunaan sarana kantor di luar ketentuan standar operasional"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Pegawai dihimbau untuk **menghindari perilaku-perilaku yang mengarah pada ujaran kebencian dan radikalisme**.",
        "alasan_kunci": "Opsi A merupakan jawaban yang tepat karena ujaran kebencian dan paham radikal merusak persatuan bangsa, mencederai netralitas ASN, serta bertentangan secara mutlak dengan ideologi Pancasila dan UUD 1945. Penjagaan sikap aparatur dari paparan radikalisme menjaga marwah kementerian sebagai pilar NKRI. Dengan demikian, penghindaran ujaran kebencian dan radikalisme adalah larangannya.",
        "bedah_pengecoh": {
            "A": "Merupakan butir larangan penting bagi aparatur dalam SE-12/MK.1/2018 sebagaimana tercantum di modul.",
            "B": "Kurang tepat karena sikap eksklusivisme bertentangan dengan prinsip nilai Sinergi dan Kolaboratif.",
            "C": "Kurang tepat karena keterlambatan laporan kas berkaitan dengan ketaatan jadwal perbendaharaan dinas.",
            "D": "Kurang tepat karena keputusan tanpa telaah komprehensif berkaitan dengan kehati-hatian manajerial.",
            "E": "Kurang tepat karena penggunaan sarana kantor diatur dalam ketentuan tata tertib perlengkapan aset."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q97 (Hlm 34, Key C)
updates[97] = {
    "pertanyaan": "Kepada para atasan langsung pegawai, butir pertama himbauan SE-12/MK.1/2018 menginstruksikan untuk...",
    "pilihan": {
        "A": "Mengoordinasikan pembagian beban tugas kerja secara proporsional",
        "B": "Memberikan teladan nyata dalam penyelesaian target kinerja",
        "C": "Membina, memantau, dan mengawasi bawahan di lingkungan kerjanya",
        "D": "Menyusun instrumen evaluasi kompetensi fungsional staf kantor",
        "E": "Menyelenggarakan forum bimbingan teknis berkala bagi pelaksana"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Himbauan kepada atasan langsung adalah **membina, memantau, dan melakukan pengawasan kepada bawahannya di lingkungan kerjanya dalam rangka mencegah terjadinya pelanggaran**.",
        "alasan_kunci": "Opsi C merupakan jawaban yang tepat karena atasan langsung bertanggung jawab menjaga moralitas dan kedisiplinan staf melalui pengawasan melekat secara berkelanjutan. Pembinaan proaktif mampu mencegah potensi pelanggaran disiplin dan kode etik sebelum menjadi kasus fatal di satker. Oleh karena itu, membina, memantau, dan mengawasi bawahan adalah instruksi utamanya.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena pembagian beban tugas proporsional merupakan teknik manajerial pembagian kerja tim.",
            "B": "Kurang tepat karena keteladanan penyelesaian target kinerja merupakan kepemimpinan berbasis hasil kerja.",
            "C": "Merupakan butir pertama himbauan bagi atasan langsung dalam SE-12/MK.1/2018 sebagaimana tertulis di modul.",
            "D": "Kurang tepat karena penyusunan instrumen evaluasi kompetensi merupakan wujud tugas manajemen SDM kantor.",
            "E": "Kurang tepat karena forum bimbingan teknis berkala merupakan kegiatan transfer pengetahuan kedinasan."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q98 (Hlm 34, Key E)
updates[98] = {
    "pertanyaan": "Bila terdapat aduan, temuan, atau laporan pelanggaran bawahan, tindakan yang wajib diambil atasan langsung adalah...",
    "pilihan": {
        "A": "Meneruskan berkas aduan langsung ke instansi penegak hukum",
        "B": "Meminta rekomendasi tindakan administratif kepada badan kepegawaian",
        "C": "Mengoordinasikan telaah hukum dengan bagian advokasi kementerian",
        "D": "Menyampaikan laporan pendahuluan kepada pimpinan kantor wilayah",
        "E": "Melakukan pemeriksaan atas dugaan pelanggaran bawahan"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Atasan langsung dihimbau untuk **melakukan pemeriksaan atas dugaan pelanggaran yang dilakukan bawahannya jika terdapat aduan, temuan dan laporan pihak terkait**.",
        "alasan_kunci": "Opsi E merupakan jawaban yang tepat karena penanganan dugaan pelanggaran memerlukan langkah klarifikasi dan pemeriksaan objektif dari atasan langsung guna menguji kebenaran materiil laporan. Sikap tegas dan terukur menjamin keadilan bagi pegawai bersangkutan sekaligus menegakkan wibawa regulasi disiplin. Dengan demikian, melakukan pemeriksaan atas dugaan pelanggaran adalah kewajibannya.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena aduan disiplin internal wajib diperiksa berjenjang terlebih dahulu sebelum ke aparat luar.",
            "B": "Kurang tepat karena rekomendasi badan kepegawaian dimintakan setelah selesainya pemeriksaan internal satker.",
            "C": "Kurang tepat karena koordinasi advokasi hukum dilakukan bila menyangkut sengketa peradilan tata usaha.",
            "D": "Kurang tepat karena laporan pendahuluan disampaikan setelah adanya pemeriksaan faktual awal di lapangan.",
            "E": "Merupakan tindakan wajib atasan langsung saat ada aduan atau temuan menurut amanat SE-12/MK.1/2018."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q99 (Hlm 34, Key B)
updates[99] = {
    "pertanyaan": "Terkait temuan aparat pemeriksa, atasan langsung pegawai wajib secara sigap untuk...",
    "pilihan": {
        "A": "Melakukan verifikasi ulang terhadap seluruh bukti temuan audit",
        "B": "Menindaklanjuti hasil pemeriksaan Inspektur Jenderal atau UKI",
        "C": "Menyusun telaah sanggahan teknis bersama tim auditor internal",
        "D": "Mengoordinasikan perbaikan prosedur operasional baku satker",
        "E": "Meminta pendampingan hukum kepada biro hukum kementerian"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Atasan langsung wajib **menindaklanjuti hasil pemeriksaan yang dilakukan oleh Inspektur Jenderal atau Unit Kepatuhan Internal**.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat karena rekomendasi hasil pengawasan aparat internal (Itjen/UKI) bersifat mengikat untuk segera dieksekusi demi perbaikan tata kelola birokrasi dan penegakan akuntabilitas. Tindak lanjut yang cepat mencegah berulangnya penyimpangan serupa di masa depan. Oleh sebab itu, menindaklanjuti hasil pemeriksaan adalah kewajiban atasan langsung.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena verifikasi bukti temuan merupakan bagian dari proses audit yang telah diselesaikan auditor.",
            "B": "Merupakan amanat kewajiban atasan langsung terhadap temuan aparat pemeriksa sebagaimana tertulis di modul.",
            "C": "Kurang tepat karena sanggahan teknis diajukan saat exit meeting audit sebelum laporan resmi diterbitkan.",
            "D": "Kurang tepat karena perbaikan prosedur operasional merupakan salah satu bentuk aksi tindak lanjut kerja.",
            "E": "Kurang tepat karena pendampingan biro hukum dilakukan dalam konteks gugatan pihak ketiga peradilan."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Q100 (Hlm 34, Key D)
updates[100] = {
    "pertanyaan": "Dalam SE-12/MK.1/2018, setiap pimpinan unit memegang tanggung jawab penuh terhadap tiga pilar tata kelola, yaitu...",
    "pilihan": {
        "A": "Perencanaan strategis, akuntabilitas kinerja, dan reformasi birokrasi",
        "B": "Pengorganisasian tim, pengawasan melekat, dan evaluasi berkala",
        "C": "Integritas jabatan, efisiensi belanja, dan standardisasi pelayanan",
        "D": "Tata kelola, manajemen risiko, dan pengendalian intern unit",
        "E": "Pembinaan aparatur, penataan kelembagaan, dan keselamatan kerja"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Pimpinan unit dihimbau untuk **bertanggung jawab terhadap tata kelola, manajemen risiko, dan pengendalian intern** dalam menjalankan tugas pokok dan fungsinya.",
        "alasan_kunci": "Opsi D merupakan jawaban yang tepat karena integrasi antara tata kelola yang baik, mitigasi risiko yang matang, serta pengawasan intern yang andal menjadi benteng pertahanan organisasi modern Kementerian Keuangan. Ketiga pilar ini menjamin pencapaian target strategis secara aman dan akuntabel. Dengan demikian, tata kelola, manajemen risiko, dan pengendalian intern adalah tanggung jawab pimpinan unit.",
        "bedah_pengecoh": {
            "A": "Kurang tepat karena perencanaan strategis dan reformasi merupakan dimensi makro kelembagaan instansi.",
            "B": "Kurang tepat karena pengorganisasian dan evaluasi merupakan fungsi manajemen operasional kepemimpinan.",
            "C": "Kurang tepat karena integritas dan efisiensi merupakan prinsip moral dan operasional pelaksanaan tugas.",
            "D": "Merupakan rincian baku tiga pilar tanggung jawab pimpinan unit dalam SE-12/MK.1/2018 menurut modul.",
            "E": "Kurang tepat karena pembinaan aparatur dan penataan kelembagaan merupakan fungsi tata pamong kepegawaian."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
    }
}

# Apply updates
for q in pkg["daftar_soal"]:
    num = q["nomor"]
    if num in updates:
        for k, v in updates[num].items():
            q[k] = v

print("Applied updates for", len(updates), "questions in Paket 03.")

# Save updated JSON
with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_03.json", "w", encoding="utf-8") as f:
    json.dump(pkg, f, indent=2, ensure_ascii=False)

print("Saved updated Paket 03 JSON successfully.")

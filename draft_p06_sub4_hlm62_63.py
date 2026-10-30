import json

# Sub-Agent 4 (Paket 06): Halaman 62 dan 63 (Nomor 61 s/d 80)
# Halaman 62: Nomor 61 - 70
# Halaman 63: Nomor 71 - 80

questions = [
    # --- HALAMAN 62 (Q61 - Q70) ---
    {
        "nomor": 61,
        "halaman_sumber": 62,
        "kata_kunci_modul": ["Perubahan Ketiga yang ditetapkan pada Sidang Tahunan MPR Tahun 2001"],
        "topik_materi": "Materi Pokok Perubahan Ketiga UUD 1945",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Ruang lingkup materi pokok yang disahkan dalam Perubahan Ketiga UUD 1945 pada tanggal 9 November 2001 mencakup...",
        "pilihan": {
            "A": "Penetapan sistem uni monarki persekutuan dagang kepulauan",
            "B": "Asas landasan bernegara, kelembagaan negara, dan ketentuan Pemilihan Umum",
            "C": "Pembubaran peradilan umum dan pembentukan mahkamah militer tertutup",
            "D": "Pemberian hak veto eksekutif kepada menteri koordinator perekonomian",
            "E": "Pemisahan kas penerimaan pajak daerah dari neraca perbendaharaan negara"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Perubahan Ketiga yang ditetapkan pada Sidang Tahunan MPR Tahun 2001 meliputi ketentuan tentang asas-asas landasan bernegara, kelembagaan negara dan hubungan antar lembaga negara, dan ketentuan-ketentuan tentang Pemilihan Umum yang ditetapkan pada tanggal 9 November 2001**.",
            "alasan_kunci": "Opsi B tepat karena Perubahan Ketiga merupakan amandemen paling substantif yang meletakkan fondasi demokrasi modern: pemilihan presiden langsung, pembentukan MK, KY, DPD, serta bab pemilu.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Indonesia adalah negara kesatuan republik, bukan uni monarki.",
                "C": "Kurang tepat karena peradilan umum tetap dipertahankan di bawah naungan Mahkamah Agung.",
                "D": "Kurang tepat karena konstitusi tidak mengenal hak veto bagi pejabat menteri eksekutif.",
                "E": "Kurang tepat karena neraca keuangan negara tetap terpadu dalam kerangka kesatuan fiskal."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 62,
        "halaman_sumber": 62,
        "kata_kunci_modul": ["penghapusan Dewan Pertimbangan Agung (DPA)"],
        "topik_materi": "Materi Pokok Perubahan Keempat UUD 1945",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Salah satu agenda reformasi struktur kelembagaan negara yang resmi ditetapkan dalam Perubahan Keempat UUD 1945 adalah...",
        "pilihan": {
            "A": "Pemberian wewenang yudisial kepada dewan perwakilan rakyat daerah",
            "B": "Penghapusan Dewan Pertimbangan Agung (DPA) dari struktur lembaga tinggi",
            "C": "Pembentukan kementerian penerangan khusus sensor publikasi media cetak",
            "D": "Pengalihan fungsi kasasi peradilan kepada majelis permusyawaratan dewan",
            "E": "Penetapan status keanggotaan dewan perwakilan rakyat seumur hidup"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Materi perubahan pada Perubahan Keempat meliputi ketentuan tentang kelembagaan negara dan hubungan antar lembaga negara, **penghapusan Dewan Pertimbangan Agung (DPA)**, pendidikan, kebudayaan, perekonomian, dan kesejahteraan sosial.",
            "alasan_kunci": "Opsi B tepat karena Perubahan Keempat menyempurnakan perampingan struktur negara dengan menghapus DPA dan memberikan wewenang kepada Presiden untuk membentuk dewan pertimbangan internal (Wantimpres).",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena DPRD memegang fungsi legislatif daerah, bukan wewenang peradilan yudisial.",
                "C": "Kurang tepat karena era reformasi menjamin kebebasan pers dan menolak sensor otoriter.",
                "D": "Kurang tepat karena fungsi kasasi mutlak berada pada Mahkamah Agung, bukan MPR.",
                "E": "Kurang tepat karena masa jabatan anggota DPR dibatasi periodik 5 tahun melalui pemilu."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 63,
        "halaman_sumber": 62,
        "kata_kunci_modul": ["ditetapkan pada tanggal 10 Agustus 2002"],
        "topik_materi": "Tanggal Penetapan Perubahan Keempat UUD 1945",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Rangkaian empat tahapan amandemen konstitusi Republik Indonesia secara resmi dituntaskan dan disahkan pada...",
        "pilihan": {
            "A": "Tanggal 19 Oktober 1999 pada penutupan sidang umum pertama reformasi",
            "B": "Ditetapkan pada tanggal 10 Agustus 2002 pada Sidang Tahunan MPR",
            "C": "Tanggal 18 Agustus 2000 bertepatan dengan pengesahan bab hak asasi",
            "D": "Tanggal 9 November 2001 saat pembentukan mahkamah konstitusi disetujui",
            "E": "Tanggal 17 Agustus 1950 saat pembubaran negara serikat hasil perundingan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Perubahan Keempat UUD 1945 **ditetapkan pada tanggal 10 Agustus 2002** dalam Sidang Tahunan MPR RI, menandai selesainya pembaruan komprehensif konstitusi Indonesia.",
            "alasan_kunci": "Opsi B tepat karena tanggal 10 Agustus 2002 menjadi tonggak penyelesaian agenda reformasi konstitusi yang dimulai sejak 1999, menghasilkan naskah UUD 1945 yang modern dan demokratis.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena 19 Oktober 1999 adalah tanggal penetapan Perubahan Pertama.",
                "C": "Kurang tepat karena 18 Agustus 2000 adalah tanggal penetapan Perubahan Kedua.",
                "D": "Kurang tepat karena 9 November 2001 adalah tanggal penetapan Perubahan Ketiga.",
                "E": "Kurang tepat karena 17 Agustus 1950 adalah peringatan kembalinya NKRI pasca-RIS."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 64,
        "halaman_sumber": 62,
        "kata_kunci_modul": ["naskah asli UUD 1945 berisi 71 butir ketentuan, maka setelah empat kali mengalami perubahan, materi muatan UUD 1945 mencakup 199 butir ketentuan"],
        "topik_materi": "Perbandingan Kuantitatif Butir Ketentuan Konstitusi",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Perbandingan jumlah materi muatan antara naskah asli dengan naskah pasca-empat kali amandemen UUD 1945 tercermin pada data bahwa...",
        "pilihan": {
            "A": "Naskah asli berisi 100 butir ketentuan dan menyusut menjadi 50 butir aturan",
            "B": "Naskah asli berisi 71 butir ketentuan, dan setelah empat kali perubahan mencakup 199 butir",
            "C": "Naskah asli memuat 37 pasal dan dipadatkan menjadi 20 pasal regulasi",
            "D": "Jumlah ketentuan konstitusi bertambah dari 45 butir menjadi tepat 300 butir",
            "E": "Materi muatan awal sebanyak 80 butir diringkas menjadi 60 butir klausul"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Jika naskah asli UUD 1945 berisi 71 butir ketentuan, maka setelah empat kali mengalami perubahan, materi muatan UUD 1945 mencakup 199 butir ketentuan**, meningkat hampir tiga kali lipat.",
            "alasan_kunci": "Opsi B tepat karena penambahan ayat dan pasal baru (terutama mengenai rincian HAM, DPD, MK, KY, BPK, dan Pemilu) membuat UUD 1945 jauh lebih terperinci dan memberikan kepastian hukum yang kuat.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena materi muatan konstitusi bertambah banyak, bukan menyusut.",
                "C": "Kurang tepat karena jumlah pasal bertambah melalui penomoran huruf (seperti 28A–28J).",
                "D": "Kurang tepat karena angka kuantitatif resmi modul adalah 71 menjadi 199 butir ketentuan.",
                "E": "Kurang tepat karena data statistik modul menyebutkan perbandingan 71 menjadi 199 butir."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 65,
        "halaman_sumber": 62,
        "kata_kunci_modul": ["Aturan Peralihan Pasal I, II, dan III; Aturan Tambahan Pasal I dan II"],
        "topik_materi": "Penyempurnaan Aturan Peralihan dan Aturan Tambahan",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Penetapan Perubahan Keempat UUD 1945 pada bagian penutup menyempurnakan instrumen transisi hukum yang terdiri atas...",
        "pilihan": {
            "A": "Empat pasal aturan peralihan dan lima pasal klausul darurat perang",
            "B": "Aturan Peralihan Pasal I, II, dan III serta Aturan Tambahan Pasal I dan II",
            "C": "Satu pasal ketentuan penutup dan tiga pasal deklarasi pembentukan serikat",
            "D": "Dua pasal tata cara referendum dan satu pasal pembatasan amandemen dewan",
            "E": "Lima pasal ketentuan transisi peradilan dan dua pasal pembekuan kabinet"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Perubahan Keempat UUD 1945 menata kembali ketentuan transisi menjadi: **Aturan Peralihan Pasal I, II, dan III; serta Aturan Tambahan Pasal I dan II UUD 1945**.",
            "alasan_kunci": "Opsi B tepat karena Aturan Peralihan mengatur keberlakuan peraturan perundang-undangan lama, pembentukan Mahkamah Konstitusi selambat-lambatnya 17 Agustus 2003, dan wewenang transisi MA; sementara Aturan Tambahan menugaskan MPR meninjau ketetapan MPRS/MPR serta menegaskan struktur naskah UUD 1945.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Aturan Peralihan pasca-amandemen terdiri dari 3 pasal, bukan 4 pasal.",
                "C": "Kurang tepat karena format formal terdiri dari 3 pasal Peralihan dan 2 pasal Tambahan.",
                "D": "Kurang tepat karena aturan referendum telah dicabut dan ditiadakan.",
                "E": "Kurang tepat karena susunan baku konstitusi memuat 3 pasal Peralihan dan 2 pasal Tambahan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 66,
        "halaman_sumber": 62,
        "kata_kunci_modul": ["ketentuan tentang perekonomian dan kesejahteraan sosial"],
        "topik_materi": "Penyempurnaan Pasal Ekonomi dan Kesejahteraan Sosial",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Dalam Perubahan Keempat UUD 1945, penyempurnaan terhadap Bab XIV ditujukan untuk mempertegas...",
        "pilihan": {
            "A": "Pemberian izin monopoli perdagangan kepada konglomerasi perbankan swasta",
            "B": "Ketentuan tentang perekonomian nasional dan jaminan kesejahteraan sosial rakyat",
            "C": "Penarikan seluruh dana simpanan masyarakat untuk dialihkan ke kas pertahanan",
            "D": "Penghapusan jaminan pemeliharaan fakir miskin dan anak terlantar oleh negara",
            "E": "Penetapan upah buruh minimum secara sepihak tanpa koordinasi dewan pengupahan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Materi perubahan pada Perubahan Keempat mencakup **ketentuan tentang perekonomian dan kesejahteraan sosial** (Bab XIV Pasal 33 ayat 4 dan 5 serta Pasal 34 ayat 1, 2, 3, dan 4).",
            "alasan_kunci": "Opsi B tepat karena amandemen menambahkan prinsip efisiensi berkeadilan, keberlanjutan, berwawasan lingkungan, dan kemandirian dalam Pasal 33, serta mewajibkan negara mengembangkan sistem jaminan sosial dan memelihara fakir miskin dalam Pasal 34.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Pasal 33 melarang monopoli swasta yang menguasai hajat hidup orang banyak.",
                "C": "Kurang tepat karena negara menjamin perlindungan hak milik dan simpanan warga.",
                "D": "Kurang tepat karena negara justru diwajibkan memelihara fakir miskin (Pasal 34 ayat 1).",
                "E": "Kurang tepat karena regulasi ketenagakerjaan diatur secara adil dalam undang-undang ketenagakerjaan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 67,
        "halaman_sumber": 62,
        "kata_kunci_modul": ["ketentuan tentang pendidikan dan kebudayaan"],
        "topik_materi": "Reformasi Pendidikan Nasional Pada Amandemen Keempat",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pembaruan substansi dalam Bab XIII UUD 1945 melalui Perubahan Keempat memuat jaminan fundamental mengenai...",
        "pilihan": {
            "A": "Pembatasan akses pendidikan tinggi khusus bagi keluarga pejabat birokrasi",
            "B": "Ketentuan tentang pendidikan dan kebudayaan termasuk alokasi anggaran minimal 20%",
            "C": "Penghapusan mata pelajaran sejarah perjuangan kemerdekaan dari kurikulum",
            "D": "Pelarangan pelestarian kesenian tari adat tradisional di lingkungan sekolah",
            "E": "Penetapan bahasa asing sebagai bahasa pengantar wajib pada sekolah dasar"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Perubahan Keempat UUD 1945 menyempurnakan **ketentuan tentang pendidikan dan kebudayaan** (Pasal 31 dan Pasal 32), termasuk mewajibkan alokasi anggaran pendidikan sekurang-kurangnya 20% dari APBN dan APBD.",
            "alasan_kunci": "Opsi B tepat karena Pasal 31 ayat (4) menjamin komitmen pendanaan negara untuk mencerdaskan kehidupan bangsa, sementara Pasal 32 menjamin kemajuan kebudayaan nasional dan penghormatan bahasa daerah.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena setiap warga negara berhak mendapatkan pendidikan tanpa diskriminasi (Pasal 31 ayat 1).",
                "C": "Kurang tepat karena pendidikan nasional menumbuhkan nilai patriotisme dan kepribadian bangsa.",
                "D": "Kurang tepat karena negara memajukan kebudayaan nasional di tengah peradaban dunia.",
                "E": "Kurang tepat karena Bahasa Indonesia adalah bahasa resmi negara yang diutamakan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 68,
        "halaman_sumber": 62,
        "kata_kunci_modul": ["Perubahan-perubahan tersebut meliputi hampir keseluruhan materi UUD 1945"],
        "topik_materi": "Keluasan Lingkup Amandemen UUD 1945",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Keluasan jangkauan materi amandemen yang diselesaikan oleh MPR pada tahun 2002 dicirikan oleh realitas bahwa...",
        "pilihan": {
            "A": "Amandemen hanya mengubah susunan satu pasal administratif pemilihan dewan",
            "B": "Perubahan-perubahan tersebut meliputi hampir keseluruhan materi UUD 1945",
            "C": "MPR menolak seluruh usulan perubahan pasal yang disodorkan kalangan kampus",
            "D": "Perubahan hanya berfokus pada nama kementerian di bidang perekonomian negara",
            "E": "Naskah undang-undang dasar sama sekali tidak mengalami penambahan klausul"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Perubahan-perubahan tersebut meliputi hampir keseluruhan materi UUD 1945**, mengubah struktur ketatanegaraan dari sistem sentralistik mandataris MPR menuju sistem demokrasi pemisahan kekuasaan yang seimbang (*checks and balances*).",
            "alasan_kunci": "Opsi B tepat karena amandemen menyentuh hampir seluruh bab (kelembagaan, HAM, pemilu, pemda, ekonomi, peradilan, dan aturan peralihan), tanpa mengubah Pembukaan dan bentuk NKRI.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena amandemen mencakup 199 butir ketentuan, bukan hanya satu pasal.",
                "C": "Kurang tepat karena MPR menyerap aspirasi luas masyarakat dan perguruan tinggi.",
                "D": "Kurang tepat karena ruang lingkup amandemen bersifat menyeluruh di seluruh cabang kekuasaan.",
                "E": "Kurang tepat karena materi ketentuan bertambah dari 71 menjadi 199 butir."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 69,
        "halaman_sumber": 62,
        "kata_kunci_modul": ["ketentuan-ketentuan tentang Pemilihan Umum yang ditetapkan pada tanggal 9 November 2001"],
        "topik_materi": "Penetapan Regulasi Pemilu Dalam Amandemen Ketiga",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Payung hukum konstitusional penyelenggaraan pesta demokrasi secara periodik lima tahunan disahkan melalui...",
        "pilihan": {
            "A": "Instruksi darurat panglima militer mengenai pengamanan kotak suara wilayah",
            "B": "Ketentuan-ketentuan tentang Pemilihan Umum yang ditetapkan pada tanggal 9 November 2001",
            "C": "Maklumat kementerian penerangan mengenai jadwal siaran kampanye pemilu",
            "D": "Piagam kesepakatan tertutup antar pimpinan partai politik pemilik suara",
            "E": "Surat edaran mahkamah agung tentang penanganan sengketa perselisihan dewan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Perubahan Ketiga menetapkan **ketentuan-ketentuan tentang Pemilihan Umum yang ditetapkan pada tanggal 9 November 2001** yang dituangkan dalam Bab VIIB Pasal 22E UUD 1945.",
            "alasan_kunci": "Opsi B tepat karena Pasal 22E UUD 1945 memberikan jaminan bahwa pemilu wajib dilaksanakan secara LUBER dan JURDIL setiap lima tahun sekali serta diselenggarakan oleh komisi pemilihan umum yang bersifat nasional, tetap, dan mandiri.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pemilu diselenggarakan oleh lembaga sipil KPU, bukan instruksi militer.",
                "C": "Kurang tepat karena landasan pemilu adalah pasal konstitusi UUD, bukan maklumat penerangan.",
                "D": "Kurang tepat karena asas pemilu bertumpu pada hukum dasar tertulis yang terbuka bagi publik.",
                "E": "Kurang tepat karena sengketa perselisihan pemilu diadili oleh Mahkamah Konstitusi."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 70,
        "halaman_sumber": 62,
        "kata_kunci_modul": ["Pembukaan UUD 1945 tidak akan diubah"],
        "topik_materi": "Konsistensi Menjaga Kemurnian Pembukaan UUD",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Meskipun amandemen mengubah 199 butir ketentuan batang tubuh, komitmen MPR yang tetap terjaga secara konsisten adalah bahwa...",
        "pilihan": {
            "A": "Struktur kabinet presidensial dapat diganti menjadi kabinet parlementer",
            "B": "Pembukaan UUD 1945 tidak akan diubah dan tetap dipertahankan keutuhannya",
            "C": "Batas wilayah kedaulatan kepulauan diserahkan kepada persekutuan asing",
            "D": "Hak pilih pemilu warga negara dapat dibatasi berdasarkan latar belakang suku",
            "E": "Kewenangan peradilan kasasi dialihkan kepada komisi etik asosiasi advokat"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Sesuai dengan kesepakatan MPR yang menjadi lampiran Ketetapan MPR No. IX/MPR/1999, ditegaskan bahwa **Pembukaan UUD 1945 tidak akan diubah**.",
            "alasan_kunci": "Opsi B tepat karena Pembukaan UUD 1945 memuat dasar filsafat berdirinya NKRI dan Pancasila. Kesepakatan untuk tidak menyentuh Pembukaan menjamin bahwa identitas jati diri bangsa dan tujuan luhur proklamasi tetap abadi.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena kesepakatan dasar justru mempertahankan sistem presidensial.",
                "C": "Kurang tepat karena kedaulatan wilayah NKRI dipertahankan secara utuh.",
                "D": "Kurang tepat karena asas pemilu menjamin perlakuan setara bagi seluruh warga tanpa rasisme.",
                "E": "Kurang tepat karena kasasi tetap menjadi wewenang konstitusional Mahkamah Agung."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },

    # --- HALAMAN 63 (Q71 - Q80) ---
    {
        "nomor": 71,
        "halaman_sumber": 63,
        "kata_kunci_modul": ["Perubahan UUD 1945 mengukuhkan keberadaan Indonesia sebagai Negara Kesatuan"],
        "topik_materi": "Pengukuhan Eksistensi Bentuk Negara Kesatuan",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pengaruh mendasar dari rangkaian amandemen UUD 1945 terhadap bentuk kenegaraan Indonesia adalah...",
        "pilihan": {
            "A": "Membuka peluang formal pembentukan negara bagian serikat di daerah kepulauan",
            "B": "Perubahan UUD 1945 mengukuhkan keberadaan Indonesia sebagai Negara Kesatuan",
            "C": "Menghapuskan batas otonomi teritorial demi sentralisasi kekuasaan kementerian",
            "D": "Mendelegasikan fungsi pertahanan kepada milisi daerah otonom masing-masing",
            "E": "Mengalihkan kedaulatan teritorial kepulauan kepada persekutuan monarki"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Perubahan UUD 1945 mengukuhkan keberadaan Indonesia sebagai Negara Kesatuan dan menghilangkan keraguan terhadap pecahnya negara kesatuan Republik Indonesia**.",
            "alasan_kunci": "Opsi B tepat karena amandemen memberikan kepastian hukum bahwa reformasi dan otonomi daerah bukan langkah menuju disintegrasi atau federalisme, melainkan upaya memperkuat integrasi nasional dalam bingkai NKRI.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pasal-pasal amandemen secara tegas menolak bentuk negara bagian/serikat.",
                "C": "Kurang tepat karena amandemen justru menjamin pelaksanaan otonomi daerah seluas-luasnya (Pasal 18).",
                "D": "Kurang tepat karena pertahanan negara diselenggarakan tunggal melalui TNI.",
                "E": "Kurang tepat karena kedaulatan berada di tangan rakyat dalam bentuk republik."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 72,
        "halaman_sumber": 63,
        "kata_kunci_modul": ["tidak sedikitpun mengubah Negara Kesatuan Republik Indonesia menjadi bentuk negara yang lain"],
        "topik_materi": "Keteguhan Mempertahankan Bentuk NKRI",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Keteguhan para pembentuk konstitusi era reformasi dalam menjaga konsensus nasional dibuktikan oleh fakta bahwa pasal-pasal UUD 1945...",
        "pilihan": {
            "A": "Memberikan hak bagi daerah penghasil migas untuk membentuk mata uang regional",
            "B": "Tidak sedikitpun mengubah Negara Kesatuan Republik Indonesia menjadi bentuk negara lain",
            "C": "Mengizinkan pembentukan angkatan bersenjata mandiri di setiap provinsi terluar",
            "D": "Menghapuskan ikatan persatuan bangsa demi kebebasan mutlak kelompok minoritas",
            "E": "Menyerahkan urusan diplomasi internasional kepada pejabat eksekutif daerah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pasal-pasal dalam UUD 1945 telah memperkokoh prinsip Negara Kesatuan Republik Indonesia dan **tidak sedikitpun mengubah Negara Kesatuan Republik Indonesia menjadi bentuk negara yang lain**.",
            "alasan_kunci": "Opsi B tepat karena komitmen menjaga bentuk negara kesatuan telah dikunci secara abadi (*entrenched*) dalam Pasal 37 ayat (5) UUD 1945 yang menyatakan bentuk NKRI tidak dapat dilakukan perubahan.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena mata uang adalah urusan mutlak pemerintah pusat (Pasal 23B).",
                "C": "Kurang tepat karena TNI adalah kekuatan pertahanan tunggal negara (Pasal 30).",
                "D": "Kurang tepat karena prinsip persatuan bangsa dijunjung tinggi dalam Pancasila dan UUD 1945.",
                "E": "Kurang tepat karena urusan hubungan luar negeri merupakan wewenang Presiden dan pemerintah pusat."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 73,
        "halaman_sumber": 63,
        "kata_kunci_modul": ["mendorong pelaksanaan otonomi daerah untuk lebih memperkokoh Negara Kesatuan Republik Indonesia"],
        "topik_materi": "Otonomi Daerah Sebagai Pilar Pengokoh NKRI",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Hubungan substansial antara desentralisasi dengan kedaulatan negara dalam naskah perubahan UUD 1945 dirumuskan dengan tujuan untuk...",
        "pilihan": {
            "A": "Mempermudah pemisahan wilayah terpencil dari kesatuan hukum nasional",
            "B": "Mendorong pelaksanaan otonomi daerah untuk lebih memperkokoh Negara Kesatuan",
            "C": "Mengurangi alokasi anggaran pembangunan di luar wilayah ibu kota negara",
            "D": "Membatasi mobilitas perpindahan penduduk antardaerah di seluruh kepulauan",
            "E": "Menghapuskan tata kelola perizinan usaha mikro di tingkat pemerintahan desa"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Pasal-pasal dalam UUD 1945 mendorong pelaksanaan otonomi daerah untuk lebih memperkokoh Negara Kesatuan Republik Indonesia** dan meningkatkan proses pembangunan di daerah dalam rangka mewujudkan kesejahteraan masyarakat di daerah.",
            "alasan_kunci": "Opsi B tepat karena otonomi daerah yang adil dan demokratis menghilangkan ketidakpuasan antardaerah, mempercepat pemerataan pembangunan, dan menumbuhkan rasa kepemilikan bersama terhadap NKRI.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena otonomi daerah bertujuan memperkokoh persatuan, bukan pemisahan wilayah.",
                "C": "Kurang tepat karena otonomi daerah justru memperluas penyaluran transfer anggaran ke daerah.",
                "D": "Kurang tepat karena setiap warga negara bebas berpindah dan bertempat tinggal di wilayah NKRI.",
                "E": "Kurang tepat karena otonomi daerah mendukung kemudahan perizinan ekonomi lokal."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 74,
        "halaman_sumber": 63,
        "kata_kunci_modul": ["Negara Indonesia ialah negara kesatuan yang berbentuk Republik"],
        "topik_materi": "Prinsip Bentuk Negara Pasal 1 Ayat 1",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pasal 1 ayat (1) UUD 1945 naskah asli yang tetap dipertahankan utuh dalam amandemen memuat rumusan prinsip bahwa...",
        "pilihan": {
            "A": "Kekuasaan tertinggi negara berada pada majelis perwakilan utusan daerah",
            "B": "Negara Indonesia ialah negara kesatuan yang berbentuk Republik",
            "C": "Pemerintahan dipimpin oleh kabinet parlementer di bawah kendali parlemen",
            "D": "Indonesia merupakan persemakmuran yang terdiri atas kesultanan otonom",
            "E": "Birokrasi pemerintahan dijalankan berdasarkan hukum kebiasaan adat wilayah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pasal 1 ayat (1) UUD 1945 yang merupakan naskah asli mengandung prinsip bahwa **'Negara Indonesia ialah negara kesatuan yang berbentuk Republik'**.",
            "alasan_kunci": "Opsi B tepat karena pasal ini merupakan klausul fundamental yang menetapkan dua identitas utama negara: bentuk negara adalah Kesatuan (*unitary state*) dan bentuk pemerintahan adalah Republik (*res publica*).",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena kekuasaan tertinggi berada di tangan rakyat menurut konstitusi (Pasal 1 ayat 2).",
                "C": "Kurang tepat karena sistem pemerintahan Indonesia adalah presidensial, bukan parlementer.",
                "D": "Kurang tepat karena Indonesia adalah republik kesatuan, bukan persemakmuran kesultanan.",
                "E": "Kurang tepat karena birokrasi berlandaskan pada negara hukum modern (Pasal 1 ayat 3)."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 75,
        "halaman_sumber": 63,
        "kata_kunci_modul": ["tekad bangsa Indonesia yang menjadi sumpah anak bangsa pada 1928 yang dikenal dengan Sumpah Pemuda"],
        "topik_materi": "Akar Historis NKRI Pada Sumpah Pemuda 1928",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Perumusan Pasal 1 ayat (1) oleh para pendiri bangsa pada hakikatnya merupakan kelanjutan dari...",
        "pilihan": {
            "A": "Perjanjian persekutuan dagang antarpulau era kolonial abad ketujuh belas",
            "B": "Tekad bangsa yang menjadi sumpah anak bangsa pada 1928 yang dikenal Sumpah Pemuda",
            "C": "Nota perundingan gencatan senjata di hadapan utusan perdamaian internasional",
            "D": "Keputusan rapat koordinasi pimpinan serikat buruh transportasi kereta api",
            "E": "Maklumat komando pertahanan wilayah kepulauan bentukan militer asing"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pasal 1 ayat (1) UUD 1945 yang dirumuskan oleh PPKI merupakan **tekad bangsa Indonesia yang menjadi sumpah anak bangsa pada 1928 yang dikenal dengan Sumpah Pemuda**, yaitu satu nusa, satu bangsa, satu bahasa persatuan, satu tanah air yaitu Indonesia.",
            "alasan_kunci": "Opsi B tepat karena Sumpah Pemuda 28 Oktober 1928 meletakkan kesadaran geopolitik bangsa Indonesia, yang kemudian diformalkan ke dalam hukum tata negara positif oleh PPKI pada 18 Agustus 1945.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Sumpah Pemuda menolak imperialisme dagang VOC/Belanda.",
                "C": "Kurang tepat karena integrasi NKRI lahir dari tekad persatuan pemuda, bukan perundingan asing.",
                "D": "Kurang tepat karena Sumpah Pemuda mencakup seluruh pemuda nusantara dari berbagai organisasi kedaerahan.",
                "E": "Kurang tepat karena semangat persatuan murni bersumber dari pergerakan nasional Indonesia."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 76,
        "halaman_sumber": 63,
        "kata_kunci_modul": ["Penghargaan terhadap cita-cita luhur para pendiri bangsa (The Founding Fathers)"],
        "topik_materi": "Pedoman Dasar MPR Era Amandemen",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Sikap keteladanan yang dijadikan pedoman dasar oleh MPR periode 1999–2004 dalam mengawal arah amandemen konstitusi adalah...",
        "pilihan": {
            "A": "Keinginan mengadopsi secara mutlak sistem tata negara persemakmuran barat",
            "B": "Penghargaan terhadap cita-cita luhur pendiri bangsa yang menginginkan negara bangsa yang satu",
            "C": "Upaya memperluas kewenangan eksekutif tanpa adanya mekanisme pengawasan parlemen",
            "D": "Kecenderungan membatasi kebebasan berserikat dan berkumpul bagi masyarakat sipil",
            "E": "Tuntutan pembagian dana perimbangan secara sepihak di luar kerangka perundangan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Penghargaan terhadap cita-cita luhur para pendiri bangsa (The Founding Fathers) yang menginginkan Indonesia sebagai negara bangsa yang satu merupakan bagian dari pedoman dasar bagi MPR 1999-2004 dalam melakukan perubahan UUD 1945**.",
            "alasan_kunci": "Opsi B tepat karena MPR era reformasi tetap setia pada jiwa proklamasi dan visi kebangsaan para pendiri bangsa dalam mewujudkan negara merdeka, bersatu, berdaulat, adil, dan makmur.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena amandemen berakar dari kepribadian bangsa dan kebutuhan demokrasi nasional.",
                "C": "Kurang tepat karena amandemen justru membatasi kekuasaan eksekutif dan memperkuat checks and balances.",
                "D": "Kurang tepat karena jaminan kebebasan berserikat justru dipertegas dalam Pasal 28E ayat (3).",
                "E": "Kurang tepat karena dana perimbangan diatur secara transparan melalui undang-undang."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 77,
        "halaman_sumber": 63,
        "kata_kunci_modul": ["tetap mempertahankan Negara Kesatuan Republik Indonesia sebagai bentuk final negara"],
        "topik_materi": "Bentuk Final Negara Bagi Bangsa Indonesia",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Konsensus dasar nasional mengenai kedudukan Negara Kesatuan Republik Indonesia pasca-perubahan UUD 1945 menegaskan status bahwa NKRI adalah...",
        "pilihan": {
            "A": "Bentuk transisi sementara menuju pembentukan konfederasi monarki kepulauan",
            "B": "Bentuk final negara bagi bangsa Indonesia yang tidak dapat ditawar lagi",
            "C": "Format persekutuan wilayah yang dapat diubah melalui referendum daerah",
            "D": "Entitas kedaulatan yang bergantung pada persetujuan komisi internasional",
            "E": "Susunan pemerintahan yang dapat dibubarkan sewaktu-waktu oleh dekrit menteri"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Wujud NKRI semakin kokoh setelah amandemen, yang dimulai dari kesepakatan MPR untuk **tetap mempertahankan Negara Kesatuan Republik Indonesia sebagai bentuk final negara bagi bangsa Indonesia**.",
            "alasan_kunci": "Opsi B tepat karena status NKRI sebagai bentuk final (*final form of the state*) merupakan kesepakatan abadi yang mengikat seluruh generasi dan menjadi harga mati bagi kelangsungan hidup bangsa.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena NKRI adalah bentuk permanen dan final, bukan transisi.",
                "C": "Kurang tepat karena bentuk NKRI tidak dapat diubah oleh mekanisme apa pun (Pasal 37 ayat 5).",
                "D": "Kurang tepat karena kedaulatan NKRI mandiri dan merdeka dari campur tangan pihak asing.",
                "E": "Kurang tepat karena dekrit menteri tidak berwenang membubarkan bentuk negara kesatuan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 78,
        "halaman_sumber": 63,
        "kata_kunci_modul": ["paling tepat untuk mewadahi ide persatuan sebuah bangsa yang majemuk"],
        "topik_materi": "Rasionalitas Pemilihan Bentuk Negara Kesatuan",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pertimbangan sosiologis utama yang melandasi pemilihan bentuk negara kesatuan bagi bangsa Indonesia adalah keyakinan bahwa bentuk ini...",
        "pilihan": {
            "A": "Memudahkan pemusatan seluruh sumber daya tambang di tangan elit ibu kota",
            "B": "Paling tepat untuk mewadahi ide persatuan sebuah bangsa yang majemuk",
            "C": "Dapat meniadakan perbedaan latar belakang etnisitas secara paksa",
            "D": "Menghilangkan hak masyarakat daerah untuk menyampaikan aspirasi politik",
            "E": "Memberikan kekuasaan mutlak kepada pimpinan angkatan perang bersenjata"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Kesepakatan mempertahankan bentuk negara kesatuan didasari pertimbangan bahwa negara kesatuan adalah bentuk yang ditetapkan sejak awal berdirinya negara dan **dipandang paling tepat untuk mewadahi ide persatuan sebuah bangsa yang majemuk**.",
            "alasan_kunci": "Opsi B tepat karena kemajemukan suku, agama, ras, dan adat istiadat memerlukan wadah pemersatu yang kokoh (Bhinneka Tunggal Ika) guna mencegah disintegrasi dan rivalitas antardaerah seperti yang terjadi pada masa negara federal RIS.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pemusatan sumber daya tambang bertentangan dengan prinsip keadilan sosial.",
                "C": "Kurang tepat karena negara kesatuan menghargai dan melindungi kemajemukan etnis, bukan meniadakannya.",
                "D": "Kurang tepat karena aspirasi daerah disalurkan secara konstitusional lewat DPD, DPR, dan DPRD.",
                "E": "Kurang tepat karena negara kesatuan berlandaskan pada supremasi hukum dan demokrasi perwakilan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 79,
        "halaman_sumber": 63,
        "kata_kunci_modul": ["diperlukan adanya pengaturan dalam peraturan perundang-undangan yang komprehensif untuk pelaksanaan otonomi daerah"],
        "topik_materi": "Regulasi Pelaksanaan Otonomi Daerah Komprehensif",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Agar pelaksanaan otonomi daerah selaras dengan hakikat tujuan pembangunan nasional dan tidak menyimpang dari NKRI, UUD 1945 mengamanatkan bahwa...",
        "pilihan": {
            "A": "Pemerintah pusat wajib membekukan seluruh kewenangan pembentukan perda",
            "B": "Diperlukan pengaturan dalam peraturan perundang-undangan komprehensif bagi otonomi daerah",
            "C": "Seluruh pejabat daerah wajib diangkat dari kalangan aparatur pusat di kementerian",
            "D": "Pemerintah daerah dilarang menyusun anggaran pendapatan belanja secara mandiri",
            "E": "Tata kelola keuangan daerah diserahkan kepada perwakilan perbankan komersial"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam pelaksanaan desentralisasi: **diperlukan adanya pengaturan dalam peraturan perundang-undangan yang komprehensif untuk pelaksanaan otonomi daerah sehingga dapat dilaksanakan sesuai dengan hakikat tujuan pembangunan nasional**.",
            "alasan_kunci": "Opsi B tepat karena payung hukum komprehensif (seperti UU Pemerintahan Daerah dan UU Hubungan Keuangan Pusat-Daerah) menjamin otonomi berjalan tertib, transparan, akuntabel, dan sinergis dengan program strategis nasional.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pemda berhak menetapkan perda untuk melaksanakan otonomi (Pasal 18 ayat 6).",
                "C": "Kurang tepat karena pemda membina ASN daerah sesuai manajemen kepegawaian nasional.",
                "D": "Kurang tepat karena pemda bersama DPRD berwenang menetapkan APBD.",
                "E": "Kurang tepat karena keuangan daerah dikelola melalui Kas Umum Daerah yang diaudit BPK."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 80,
        "halaman_sumber": 63,
        "kata_kunci_modul": ["WAWASAN KEBANGSAAN DALAM NEGARA KESATUAN REPUBLIK INDONESIA"],
        "topik_materi": "Fokus Pembahasan Bab IV Wawasan Kebangsaan",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pokok pembahasan yang diuraikan pada Bab IV materi Wawasan Kebangsaan berfokus pada tema...",
        "pilihan": {
            "A": "Strategi ekspansi ekonomi dan penguasaan perdagangan maritim internasional",
            "B": "Wawasan Kebangsaan dalam Negara Kesatuan Republik Indonesia",
            "C": "Tata cara pembentukan dewan arbitrase perdata sengketa investasi asing",
            "D": "Sistem peradilan khusus tindak pidana korporasi lintas yurisdiksi kepulauan",
            "E": "Mekanisme pengajuan pinjaman luar negeri bagi badan usaha milik daerah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Judul pokok bahasan Bab IV adalah **BAB IV WAWASAN KEBANGSAAN DALAM NEGARA KESATUAN REPUBLIK INDONESIA**, yang mengulas penguatan cara pandang kebangsaan dalam menjaga keutuhan teritorial dan persatuan bangsa.",
            "alasan_kunci": "Opsi B tepat karena Bab IV menguraikan makna, hakikat, asas, dan penerapan wawasan kebangsaan dalam bingkai Negara Kesatuan Republik Indonesia demi tegaknya kedaulatan dan integrasi nasional.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena materi modul berfokus pada wawasan kebangsaan dan ketatanegaraan Indonesia.",
                "C": "Kurang tepat karena arbitrase investasi bukan pokok materi Bab IV Wawasan Kebangsaan.",
                "D": "Kurang tepat karena peradilan korporasi merupakan ranah teknis hukum perdata/pidana khusus.",
                "E": "Kurang tepat karena pinjaman BUMD merupakan urusan manajemen keuangan daerah."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    }
]

output_path = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch\draft_p06_sub4_hlm62_63.json"
with open(output_path, "w", encoding="utf-8") as f:
    json.dump(questions, f, indent=2, ensure_ascii=False)

print(f"Sub-Agent 4 (Paket 06) finished! Total questions: {len(questions)}")

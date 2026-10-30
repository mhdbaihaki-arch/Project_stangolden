import json

# Sub-Agent 3 (Paket 06): Halaman 60 dan 61 (Nomor 41 s/d 60)
# Halaman 60: Nomor 41 - 50
# Halaman 61: Nomor 51 - 60

questions = [
    # --- HALAMAN 60 (Q41 - Q50) ---
    {
        "nomor": 41,
        "halaman_sumber": 60,
        "kata_kunci_modul": ["MPR tidak lagi memegang dan melaksanakan kedaulatan rakyat"],
        "topik_materi": "Pergeseran Kedudukan MPR Pasca-Perubahan Ketiga",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Perubahan mendasar terkait doktrin kedaulatan rakyat dalam Perubahan Ketiga UUD 1945 tahun 2001 adalah...",
        "pilihan": {
            "A": "MPR diposisikan sebagai pemegang monopoli kekuasaan eksekutif dan peradilan",
            "B": "MPR tidak lagi memegang dan melaksanakan kedaulatan rakyat secara penuh",
            "C": "Kedaulatan negara diserahkan kepada dewan pimpinan partai politik mayoritas",
            "D": "Lembaga kepresidenan dibubarkan dan digantikan oleh kabinet presidium",
            "E": "Kekuasaan tertinggi negara dialihkan kepada pimpinan angkatan bersenjata"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam amandemen ketiga UUD 1945 ditegaskan bahwa **MPR tidak lagi memegang dan melaksanakan kedaulatan rakyat. Dengan demikian MPR bukan lagi sebagai lembaga tertinggi negara**, melainkan lembaga negara yang sejajar dengan lembaga negara lainnya.",
            "alasan_kunci": "Opsi B tepat karena kedaulatan rakyat pasca-amandemen Pasal 1 ayat (2) berada di tangan rakyat dan dilaksanakan menurut Undang-Undang Dasar, memutus konsep 'supremasi MPR' menjadi 'supremasi konstitusi'.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena MPR tidak memonopoli kekuasaan eksekutif atau yudikatif.",
                "C": "Kurang tepat karena kedaulatan berada di tangan rakyat, bukan di tangan partai politik.",
                "D": "Kurang tepat karena lembaga kepresidenan tetap kokoh sebagai pemegang kekuasaan pemerintahan.",
                "E": "Kurang tepat karena kekuasaan negara dipegang oleh lembaga sipil konstitusional."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 42,
        "halaman_sumber": 60,
        "kata_kunci_modul": ["MPR tidak lagi menetapkan GBHN"],
        "topik_materi": "Penghapusan Kewenangan Menetapkan GBHN",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Salah satu implikasi yuridis dari berakhirnya status MPR sebagai lembaga tertinggi negara dalam Perubahan Ketiga adalah...",
        "pilihan": {
            "A": "Presiden wajib meminta izin kepada dewan perwakilan sebelum merombak kabinet",
            "B": "MPR tidak lagi menetapkan GBHN sebagai pedoman mandataris kepala negara",
            "C": "Anggaran belanja kementerian ditetapkan secara mandiri oleh dewan moneter",
            "D": "Pemerintah daerah berhak menyusun garis haluan pembangunan luar negeri",
            "E": "Penyelenggaraan pemilihan umum diserahkan kepada panitia ad-hoc daerah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Perubahan mendasar dalam Perubahan Ketiga tahun 2001 antara lain menetapkan bahwa **MPR tidak lagi menetapkan GBHN** karena Presiden dipilih langsung oleh rakyat dan mempertanggungjawabkan visi misinya kepada rakyat.",
            "alasan_kunci": "Opsi B tepat karena arah pembangunan nasional kini dituangkan melalui undang-undang sistem perencanaan pembangunan nasional (SPPN/RPJPN) yang disusun bersama antara Presiden dan DPR, bukan lagi berupa GBHN produk MPR.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena perombakan kabinet merupakan hak prerogatif presiden.",
                "C": "Kurang tepat karena anggaran belanja ditetapkan dalam APBN oleh DPR bersama Presiden.",
                "D": "Kurang tepat karena urusan luar negeri merupakan urusan absolut pemerintah pusat.",
                "E": "Kurang tepat karena pemilu diselenggarakan oleh KPU yang bersifat nasional dan mandiri."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 43,
        "halaman_sumber": 60,
        "kata_kunci_modul": ["melantik Presiden dan Wakil Presiden hasil pemilihan umum secara langsung oleh rakyat"],
        "topik_materi": "Fungsi MPR Melantik Presiden Terpilih",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Peran konstitusional MPR terkait pasangan kepala eksekutif hasil pemilu langsung menurut Perubahan Ketiga UUD 1945 adalah...",
        "pilihan": {
            "A": "Menentukan pasangan calon pemenang melalui pemungutan suara berjenjang dewan",
            "B": "Bertugas melantik Presiden dan Wakil Presiden hasil pemilihan umum secara langsung oleh rakyat",
            "C": "Menguji kelayakan berkas administratif pencalonan di tingkat mahkamah peradilan",
            "D": "Menetapkan susunan menteri koordinator pembantu pasangan terpilih secara sepihak",
            "E": "Membatalkan mandat pasangan terpilih apabila tidak disetujui pimpinan fraksi"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam amandemen ketiga ditetapkan bahwa MPR tidak lagi memilih Presiden dan Wapres, tetapi bertugas **melantik Presiden dan Wakil Presiden hasil pemilihan umum secara langsung oleh rakyat**.",
            "alasan_kunci": "Opsi B tepat karena mandat keterpilihan pasangan Presiden/Wapres bersumber langsung dari kedaulatan rakyat lewat bilik suara, sedangkan MPR menjalankan fungsi konstitusional pengesahan formal pelantikan dan pengambilan sumpah jabatan.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena MPR tidak lagi menentukan pemenang pilpres lewat pemungutan suara tertutup dewan.",
                "C": "Kurang tepat karena pengujian berkas syarat pasangan calon merupakan wewenang KPU.",
                "D": "Kurang tepat karena susunan menteri merupakan hak prerogatif presiden terpilih.",
                "E": "Kurang tepat karena MPR tidak berwenang membatalkan mandat pemilu secara sewenang-wenang."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 44,
        "halaman_sumber": 60,
        "kata_kunci_modul": ["Presiden dan Wakil Presiden dipilih langsung oleh rakyat"],
        "topik_materi": "Asas Pemilihan Presiden Langsung Pasal 6A",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Perubahan mendasar pada Pasal 6A ayat (1) UUD 1945 yang disahkan dalam Perubahan Ketiga mengatur prinsip bahwa...",
        "pilihan": {
            "A": "Kepala negara diangkat oleh dewan pertimbangan agung berdasarkan musyawarah",
            "B": "Presiden dan Wakil Presiden dipilih langsung oleh rakyat dalam satu pasangan",
            "C": "Perdana menteri memegang mandat tertinggi eksekutif atas persetujuan senat",
            "D": "Pasangan pimpinan nasional dipilih bergantian oleh gabungan fraksi partai",
            "E": "Kepala pemerintahan ditetapkan oleh komisi pemilihan umum secara mandiri"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Poin perubahan mendasar nomor 4 dalam Perubahan Ketiga tahun 2001 adalah bahwa **Presiden dan Wakil Presiden dipilih langsung oleh rakyat** dalam satu pasangan melalui pemilihan umum.",
            "alasan_kunci": "Opsi B tepat karena pemilihan presiden secara langsung memperkuat legitimasi mandat kepemimpinan nasional dan memperkokoh stabilitas sistem presidensial dari ancaman manuver politik parlemen.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena DPA telah dihapuskan dan presiden dipilih oleh rakyat.",
                "C": "Kurang tepat karena Indonesia tidak mengenal jabatan perdana menteri.",
                "D": "Kurang tepat karena pasangan calon diusung oleh partai namun dipilih langsung oleh rakyat.",
                "E": "Kurang tepat karena KPU adalah penyelenggara pemilu, bukan pemilih yang menetapkan pemenang."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 45,
        "halaman_sumber": 60,
        "kata_kunci_modul": ["MPR hanya dapat memberhentikan Presiden dan atau Wakil Presiden atas usul DPR berdasarkan keputusan Mahkamah Konstitusi"],
        "topik_materi": "Prosedur Ketat Pemakzulan Pasca-Amandemen Ketiga",
        "level_kognitif": "C3 - Aplikasi",
        "pertanyaan": "Dalam hal terjadi usul pemberhentian Presiden dalam masa jabatannya, batasan konstitusional bagi kewenangan MPR adalah...",
        "pilihan": {
            "A": "MPR berwenang memberhentikan Presiden secara langsung atas kehendak pimpinan komisi",
            "B": "MPR memberhentikan Presiden atas usul DPR berdasarkan keputusan Mahkamah Konstitusi",
            "C": "MPR dapat mengeksekusi pemakzulan tanpa memerlukan pemeriksaan peradilan",
            "D": "MPR wajib membubarkan kabinet kementerian terlebih dahulu sebelum sidang",
            "E": "MPR menyerahkan putusan akhir pemakzulan kepada dewan pertimbangan daerah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam amandemen ketiga diatur bahwa **MPR hanya dapat memberhentikan Presiden dan atau Wakil Presiden atas usul DPR berdasarkan keputusan Mahkamah Konstitusi**.",
            "alasan_kunci": "Opsi B tepat karena konstitusi memadukan jalur *impeachment* hukum dan politik. Tuduhan pelanggaran hukum berat wajib dibuktikan dan diputus oleh Mahkamah Konstitusi terlebih dahulu sebelum MPR dapat memproses pemberhentian.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena MPR tidak dapat memberhentikan presiden secara sewenang-wenang tanpa putusan MK.",
                "C": "Kurang tepat karena pemeriksaan peradilan di MK merupakan syarat konstitusional mutlak.",
                "D": "Kurang tepat karena MPR tidak berwenang membubarkan kabinet kementerian.",
                "E": "Kurang tepat karena putusan akhir pemakzulan diambil di forum Sidang Paripurna MPR."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 46,
        "halaman_sumber": 60,
        "kata_kunci_modul": ["Dengan tegas dinyatakan bahwa Presiden tidak dapat membekukan dan membubarkan DPR"],
        "topik_materi": "Larangan Pembubaran DPR Pasal 7C",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Perubahan Ketiga UUD 1945 memasukkan ketentuan Pasal 7C yang menjamin independensi cabang legislatif, yaitu...",
        "pilihan": {
            "A": "DPR berwenang membekukan operasional seluruh kantor kementerian eksekutif",
            "B": "Dengan tegas dinyatakan bahwa Presiden tidak dapat membekukan dan membubarkan DPR",
            "C": "Ketua DPR memegang hak prerogatif pembentukan angkatan perang pertahanan",
            "D": "Anggota dewan dibebaskan dari kewajiban mematuhi putusan peradilan umum",
            "E": "Presiden dapat membubarkan DPR apabila rancangan anggaran ditolak dua kali"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Poin penting amandemen ketiga nomor 7 menyatakan: **Dengan tegas dinyatakan bahwa Presiden tidak dapat membekukan dan membubarkan DPR**.",
            "alasan_kunci": "Opsi B tepat karena pencantuman Pasal 7C memberikan kepastian hukum bahwa Presiden dilarang mengulangi peristiwa sejarah kelam tahun 1960 di mana Presiden membubarkan DPR hasil pemilu.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena DPR tidak berwenang membekukan kementerian eksekutif.",
                "C": "Kurang tepat karena komando tertinggi pertahanan berada di tangan Presiden (Pasal 10).",
                "D": "Kurang tepat karena anggota dewan tetap wajib tunduk pada hukum pidana yang berlaku.",
                "E": "Kurang tepat karena jika RAPBN ditolak, pemerintah menjalankan APBN tahun lalu, bukan membubarkan DPR."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 47,
        "halaman_sumber": 60,
        "kata_kunci_modul": ["Adanya lembaga baru yaitu: DPD, Mahkamah Konstitusi, Komisi Yudisial"],
        "topik_materi": "Kelahiran Lembaga Baru Dalam Amandemen Ketiga",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Lembaga-lembaga negara baru yang resmi dilahirkan melalui naskah Perubahan Ketiga UUD 1945 tahun 2001 adalah...",
        "pilihan": {
            "A": "Badan Intelijen Negara, Komisi Pemberantasan Korupsi, dan Badan Pusat Statistik",
            "B": "Dewan Perwakilan Daerah, Mahkamah Konstitusi, dan Komisi Yudisial",
            "C": "Dewan Pertimbangan Agung, Lembaga Ketahanan Nasional, dan Badan Sensor Film",
            "D": "Otoritas Jasa Keuangan, Lembaga Penjamin Simpanan, dan Komisi Kejaksaan",
            "E": "Badan Meteorologi Klimatologi, Ombudsman Republik Indonesia, dan KASN"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Salah satu pembaruan struktur ketatanegaraan pada Perubahan Ketiga adalah **adanya lembaga baru yaitu: DPD, Mahkamah Konstitusi, Komisi Yudisial**.",
            "alasan_kunci": "Opsi B tepat karena ketiga lembaga ini dibentuk untuk menyempurnakan demokrasi: DPD mewakili aspirasi teritorial daerah, MK mengawal kemurnian konstitusi, dan KY menegakkan integritas etika hakim kehakiman.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena BIN, KPK, dan BPS dibentuk melalui instrumen undang-undang, bukan tercantum dalam UUD.",
                "C": "Kurang tepat karena DPA merupakan lembaga lama yang justru dihapuskan pasca-reformasi.",
                "D": "Kurang tepat karena OJK dan LPS merupakan otoritas keuangan yang dibentuk berdasarkan undang-undang.",
                "E": "Kurang tepat karena Ombudsman dan instansi pendukung lainnya bukan organ konstitusi UUD 1945."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 48,
        "halaman_sumber": 60,
        "kata_kunci_modul": ["Adanya Bab baru tentang Pemilu; dan Penyempurnaan pasal 23"],
        "topik_materi": "Penyempurnaan Bab Pemilu dan Keuangan Negara",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pembaruan materi muatan konstitusi yang diperkenalkan dalam Perubahan Ketiga UUD 1945 mencakup...",
        "pilihan": {
            "A": "Pemberian hak veto eksekutif dan pembentukan komando militer wilayah",
            "B": "Adanya Bab baru tentang Pemilu serta penyempurnaan pengaturan Pasal 23",
            "C": "Penghapusan otonomi daerah dan penyeragaman status desa seluruh nusantara",
            "D": "Pemisahan kas daerah dari neraca pertanggungjawaban perbendaharaan negara",
            "E": "Penetapan sistem mata uang ganda untuk perdagangan ekspor kepulauan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam amandemen ketiga ditambahkan **Bab baru tentang Pemilu (Bab VIIB Pasal 22E) dan penyempurnaan pasal 23** tentang keuangan negara, BPK, dan perpajakan.",
            "alasan_kunci": "Opsi B tepat karena Bab VIIB meletakkan asas pemilu (luber jurdil), peserta pemilu, dan penyelenggara KPU, sementara penyempurnaan Pasal 23 mengatur asas keterbukaan dan akuntabilitas pengelolaan anggaran negara.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena konstitusi tidak memberikan hak veto kepada presiden.",
                "C": "Kurang tepat karena otonomi daerah justru diperkuat dalam Pasal 18 UUD 1945.",
                "D": "Kurang tepat karena kas daerah tetap terikat dalam kerangka kesatuan keuangan negara.",
                "E": "Kurang tepat karena mata uang Indonesia bersifat tunggal yaitu Rupiah (Pasal 23B)."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 49,
        "halaman_sumber": 60,
        "kata_kunci_modul": ["Susunan MPR tardiri dari anggota DPR dan DPD"],
        "topik_materi": "Susunan Bikameral MPR Pasca-Perubahan Keempat",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Perubahan mendasar dalam Perubahan Keempat UUD 1945 tahun 2002 terhadap struktur keanggotaan MPR adalah...",
        "pilihan": {
            "A": "Keanggotaan majelis terdiri atas delegasi utusan golongan dan ormas buruh",
            "B": "Susunan MPR terdiri dari anggota DPR dan anggota DPD hasil pemilihan umum",
            "C": "Seluruh perwira tinggi militer aktif otomatis menduduki kursi perwakilan",
            "D": "Para rektor perguruan tinggi negeri ditunjuk menjadi anggota kehormatan",
            "E": "Anggota majelis ditetapkan secara proporsional oleh pimpinan kementerian"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam Perubahan Keempat tahun 2002 ditetapkan bahwa **Susunan MPR terdiri dari anggota DPR dan DPD** yang kesemuanya dipilih melalui pemilihan umum.",
            "alasan_kunci": "Opsi B tepat karena perubahan ini menghapuskan unsur Utusan Golongan dan Utusan Daerah yang sebelumnya diangkat secara politis, sehingga seluruh anggota MPR 100% memperoleh mandat demokrasi dari rakyat.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena utusan golongan tanpa pemilu telah resmi ditiadakan dari MPR.",
                "C": "Kurang tepat karena TNI bersikap netral dan tidak mendapatkan alokasi kursi otomatis di parlemen.",
                "D": "Kurang tepat karena rektor universitas tidak memiliki kuota kursi formal di MPR.",
                "E": "Kurang tepat karena anggota MPR dipilih melalui pemilu legislatif, bukan penunjukan kementerian."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 50,
        "halaman_sumber": 60,
        "kata_kunci_modul": ["Tidak ada lagi lembaga tinggi negara yang namanya DPA, tapi Presiden diberi wewenang untuk membentuk Dewan Pertimbangan"],
        "topik_materi": "Penghapusan DPA dan Pembentukan Dewan Pertimbangan",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Penataan kelembagaan penasihat kepresidenan dalam Perubahan Keempat UUD 1945 diwujudkan melalui ketentuan bahwa...",
        "pilihan": {
            "A": "DPA diberikan wewenang yudisial membatalkan keputusan rapat kerja kabinet",
            "B": "Tidak ada lagi lembaga DPA, tetapi Presiden berwenang membentuk Dewan Pertimbangan",
            "C": "Dewan penasihat kepresidenan dipimpin secara bergantian oleh ketua parlemen",
            "D": "Presiden dilarang menerima saran dan pertimbangan dari pihak luar birokrasi",
            "E": "Lembaga penasihat negara dibubarkan permanen tanpa wadah pengganti apapun"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Materi Perubahan Keempat menetapkan: **Tidak ada lagi lembaga tinggi negara yang namanya DPA, tapi Presiden diberi wewenang untuk membentuk Dewan Pertimbangan yang memberi nasihat/pertimbangan kepada Presiden yang diatur dengan UU**.",
            "alasan_kunci": "Opsi B tepat karena Pasal 16 UUD 1945 diamandemen untuk menghapus DPA yang dinilai tidak efisien, dan menggantikannya dengan dewan pertimbangan yang berkedudukan di bawah eksekutif Presiden (Wantimpres).",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena DPA telah dihapus dari struktur kelembagaan negara.",
                "C": "Kurang tepat karena Dewan Pertimbangan berkedudukan membantu presiden, bukan dipimpin ketua DPR.",
                "D": "Kurang tepat karena Presiden justru diberi wewenang membentuk dewan pertimbangan.",
                "E": "Kurang tepat karena wadah pertimbangan tetap ada di bawah presiden (diatur dengan UU)."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },

    # --- HALAMAN 61 (Q51 - Q60) ---
    {
        "nomor": 51,
        "halaman_sumber": 61,
        "kata_kunci_modul": ["sepakat untuk tidak mengubah Pembukaan UUD 1945"],
        "topik_materi": "Kesepakatan Dasar Tidak Mengubah Pembukaan",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Kesepakatan dasar pertama fraksi-fraksi di MPR pada Sidang Tahunan 1999 terkait arah amandemen adalah...",
        "pilihan": {
            "A": "Sepakat untuk menyusun kembali naskah teks proklamasi kemerdekaan",
            "B": "Sepakat untuk tidak mengubah Pembukaan UUD 1945 dalam kondisi apa pun",
            "C": "Sepakat untuk mengubah dasar falsafah pancasila menjadi ideologi federal",
            "D": "Sepakat untuk menghapuskan cita-cita kemerdekaan pada alinea kedua",
            "E": "Sepakat untuk merombak tujuan perlindungan bangsa pada alinea keempat"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Kesepakatan dasar arah perubahan UUD 1945 nomor satu adalah **sepakat untuk tidak mengubah Pembukaan UUD 1945**.",
            "alasan_kunci": "Opsi B tepat karena Pembukaan UUD 1945 memuat dasar filosofis berdirinya negara (*staatsfundamentalnorm*), Pancasila, dan tujuan nasional. Mengubah Pembukaan sama artinya dengan membubarkan negara proklamasi 17 Agustus 1945.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena teks proklamasi adalah dokumen historis abadi yang tidak diubah.",
                "C": "Kurang tepat karena Pancasila sebagai dasar negara dijaga kemurniannya tanpa diubah.",
                "D": "Kurang tepat karena seluruh alinea Pembukaan UUD 1945 disepakati untuk tidak disentuh.",
                "E": "Kurang tepat karena tujuan nasional pada alinea keempat tetap menjadi panduan konstitusi."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 52,
        "halaman_sumber": 61,
        "kata_kunci_modul": ["sepakat untuk mempertahankan bentuk Negara Kesatuan Republik Indonesia"],
        "topik_materi": "Kesepakatan Mempertahankan Bentuk NKRI",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Komitmen kebangsaan seluruh fraksi MPR dalam proses amandemen UUD 1945 ditegaskan melalui konsensus untuk...",
        "pilihan": {
            "A": "Mengubah bentuk negara menjadi konfederasi serikat kepulauan otonom",
            "B": "Sepakat untuk mempertahankan bentuk Negara Kesatuan Republik Indonesia",
            "C": "Membagi kedaulatan nasional kepada persemakmuran negara-negara bagian",
            "D": "Memberikan hak penentuan nasib sendiri kepada setiap keresidenan wilayah",
            "E": "Menyerahkan pengelolaan wilayah terluar kepada komisi pengawasan asing"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Kesepakatan dasar nomor dua menegaskan: **sepakat untuk mempertahankan bentuk Negara Kesatuan Republik Indonesia**.",
            "alasan_kunci": "Opsi B tepat karena bentuk negara kesatuan merupakan kesepakatan final pendiri bangsa yang dinilai paling tepat untuk menjaga integrasi ribuan pulau dan kemajemukan suku bangsa Indonesia, yang kemudian dikunci dalam Pasal 37 ayat (5) UUD 1945.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena bentuk konfederasi atau federasi ditolak dalam kesepakatan dasar.",
                "C": "Kurang tepat karena kedaulatan negara kesatuan bersifat tunggal dan bulat.",
                "D": "Kurang tepat karena hak pemisahan diri bertentangan dengan keutuhan NKRI.",
                "E": "Kurang tepat karena wilayah terluar dijaga kedaulatannya secara utuh oleh negara."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 53,
        "halaman_sumber": 61,
        "kata_kunci_modul": ["sepakat untuk mempertahankan sistem presidensiil"],
        "topik_materi": "Kesepakatan Mempertahankan Sistem Presidensial",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Terkait sistem pemerintahan negara, arah perubahan UUD 1945 yang disepakati oleh fraksi-fraksi di MPR adalah...",
        "pilihan": {
            "A": "Beralih menuju sistem kabinet parlementer yang dipimpin perdana menteri",
            "B": "Sepakat untuk mempertahankan sistem presidensiil dalam tata kelola pemerintahan",
            "C": "Menerapkan sistem monarki konstitusional dengan kepala negara turun-temurun",
            "D": "Membentuk kabinet campuran yang bertanggung jawab bersama kepada komite adat",
            "E": "Mewajibkan presiden membubarkan parlemen saat terjadi ketidaksepahaman"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Kesepakatan dasar nomor tiga adalah **sepakat untuk mempertahankan sistem presidensiil** sekaligus memurnikan pelaksanaannya.",
            "alasan_kunci": "Opsi B tepat karena sistem presidensial menjamin masa jabatan eksekutif yang pasti (*fixed term*) selama 5 tahun, mencegah instabilitas jatuh-bangun kabinet seperti pada era parlementer, serta memperjelas mekanisme saling mengawasi dan mengimbangi (*checks and balances*).",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena sistem parlementer dihindari demi menjamin stabilitas pemerintahan.",
                "C": "Kurang tepat karena Indonesia adalah republik demokratis, bukan monarki.",
                "D": "Kurang tepat karena menteri adalah pembantu presiden yang bertanggung jawab kepada presiden.",
                "E": "Kurang tepat karena presiden dilarang membubarkan parlemen dalam sistem presidensial UUD 1945."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 54,
        "halaman_sumber": 61,
        "kata_kunci_modul": ["memindahkan hal-hal normatif yang ada dalam Penjelasan UUD 1945 ke dalam pasal-pasal"],
        "topik_materi": "Pemindahan Materi Normatif Penjelasan ke Batang Tubuh",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Penyempurnaan sistematika hukum dalam amandemen UUD 1945 diwujudkan melalui kesepakatan untuk...",
        "pilihan": {
            "A": "Menjadikan penjelasan umum sebagai dokumen hukum tertinggi di atas pasal",
            "B": "Sepakat untuk memindahkan hal-hal normatif dalam Penjelasan UUD ke dalam pasal-pasal",
            "C": "Menghapuskan seluruh asas negara hukum yang tercantum dalam teks konstitusi",
            "D": "Mengalihkan aturan hak asasi manusia ke dalam naskah penjelasan tambahan",
            "E": "Menyusun kitab hukum penjelasan tersendiri di luar naskah resmi undang-undang"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Kesepakatan dasar nomor empat adalah **sepakat untuk memindahkan hal-hal normatif yang ada dalam Penjelasan UUD 1945 ke dalam pasal-pasal UUD 1945**.",
            "alasan_kunci": "Opsi B tepat karena naskah asli Penjelasan UUD 1945 disusun oleh Prof. Soepomo tanpa pengesahan formal BPUPKI/PPKI. Dengan memindahkan norma penting (seperti prinsip negara hukum dan sistem konstitusional) ke batang tubuh pasal, kedudukan hukumnya menjadi berkekuatan mengikat penuh.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Penjelasan UUD 1945 justru dihapuskan dan materi normatifnya dimasukkan ke pasal.",
                "C": "Kurang tepat karena asas negara hukum justru dimasukkan ke Pasal 1 ayat (3) batang tubuh.",
                "D": "Kurang tepat karena aturan HAM dimasukkan ke batang tubuh Bab XA Pasal 28A–28J.",
                "E": "Kurang tepat karena naskah resmi UUD 1945 pasca-amandemen hanya terdiri dari Pembukaan dan Pasal-pasal."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 55,
        "halaman_sumber": 61,
        "kata_kunci_modul": ["sepakat untuk menempuh cara adendum dalam melakukan amandemen terhadap UUD 1945"],
        "topik_materi": "Teknik Perubahan Konstitusi Melalui Cara Adendum",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Metode penyusunan naskah perubahan yang disepakati oleh fraksi-fraksi di MPR dalam amandemen UUD 1945 adalah...",
        "pilihan": {
            "A": "Mengganti total seluruh naskah konstitusi dan membuang naskah asli proklamasi",
            "B": "Sepakat untuk menempuh cara adendum dalam melakukan amandemen terhadap UUD 1945",
            "C": "Menyusun piagam konstitusi darurat tanpa melampirkan teks undang-undang dasar asli",
            "D": "Menerbitkan lembaran hukum baru yang meniadakan kesinambungan historis bangsa",
            "E": "Menyerahkan kodifikasi perubahan pasal kepada dewan perancang internasional"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Kesepakatan dasar nomor lima menetapkan: **sepakat untuk menempuh cara adendum dalam melakukan amandemen terhadap UUD 1945**.",
            "alasan_kunci": "Opsi B tepat karena cara adendum mempertahankan naskah asli UUD 1945 tetap utuh, dan naskah perubahan (Perubahan I, II, III, dan IV) dilekatkan sebagai lampiran penyempurnaan, sehingga jejak sejarah autentik naskah proklamasi 1945 tetap terpelihara.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena metode adendum tidak mengganti total melainkan menempelkan naskah perubahan.",
                "C": "Kurang tepat karena naskah asli tetap dipertahankan dan disertakan berdampingan.",
                "D": "Kurang tepat karena adendum justru menjamin kontinuitas kesinambungan historis konstitusi.",
                "E": "Kurang tepat karena amandemen dilakukan secara mandiri oleh MPR bangsa Indonesia."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 56,
        "halaman_sumber": 61,
        "kata_kunci_modul": ["Ketetapan MPR No. IX/MPR/1999 tentang Penugasan Badan Pekerja"],
        "topik_materi": "Payung Hukum Lima Kesepakatan Dasar Amandemen",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Lima kesepakatan dasar arah perubahan UUD 1945 secara yuridis dituangkan dan dilampirkan dalam...",
        "pilihan": {
            "A": "Surat keputusan kementerian kehakiman mengenai pembentukan tim perumus undang-undang",
            "B": "Ketetapan MPR No. IX/MPR/1999 tentang Penugasan Badan Pekerja Majelis Permusyawaratan Rakyat",
            "C": "Instruksi presiden mengenai pengamanan aset perbendaharaan negara pasca-pemilu",
            "D": "Nota kesepahaman antarfraksi partai politik pemilik kursi mayoritas di parlemen",
            "E": "Ketetapan majelis darurat militer mengenai pemulihan ketertiban umum nasional"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Lima kesepakatan dasar tersebut secara resmi dilampirkan dalam **Ketetapan MPR No. IX/MPR/1999 tentang Penugasan Badan Pekerja Majelis Permusyawaratan Rakyat Republik Indonesia untuk Melanjutkan Perubahan UUD 1945**.",
            "alasan_kunci": "Opsi B tepat karena TAP MPR No. IX/1999 menjadi mandat konstitusional dan pedoman kerja mengikat bagi Panitia Ad Hoc I Badan Pekerja MPR (PAH I BP MPR) dalam merumuskan pasal-pasal amandemen secara bertahap.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena amandemen konstitusi adalah wewenang MPR, bukan kementerian kehakiman.",
                "C": "Kurang tepat karena kesepakatan dasar merupakan produk ketetapan legislatif majelis, bukan inpres.",
                "D": "Kurang tepat karena kesepakatan telah disahkan menjadi produk hukum Ketetapan MPR formal.",
                "E": "Kurang tepat karena amandemen berjalan dalam iklim demokrasi sipil reformasi."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 57,
        "halaman_sumber": 61,
        "kata_kunci_modul": ["Ketetapan MPR No. I/MPR/2002 tentang Pembentukan Komisi Konstitusi"],
        "topik_materi": "Pembentukan Komisi Konstitusi Tahun 2002",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Lembaga pengkaji independen yang dibentuk untuk melakukan pengkajian komprehensif terhadap perubahan UUD 1945 didasarkan pada...",
        "pilihan": {
            "A": "Surat edaran mahkamah agung mengenai peninjauan kembali perkara tata usaha",
            "B": "Ketetapan MPR No. I/MPR/2002 tentang Pembentukan Komisi Konstitusi",
            "C": "Keputusan dewan pertahanan nasional tentang kesiapsiagaan teritorial wilayah",
            "D": "Undang-undang pembentukan lembaga peradilan perselisihan hasil pemilihan umum",
            "E": "Peraturan pemerintah pengganti undang-undang tentang restrukturisasi birokrasi"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pada Sidang Tahunan 2002 disepakati pembentukan Komisi Konstitusi berdasarkan **Ketetapan MPR No. I/MPR/2002 tentang Pembentukan Komisi Konstitusi** yang bertugas melakukan pengkajian secara komprehensif tentang perubahan UUD 1945.",
            "alasan_kunci": "Opsi B tepat karena Komisi Konstitusi (beranggotakan para pakar hukum, akademisi, dan tokoh masyarakat) dibentuk untuk menyelaraskan dan menguji secara ilmiah keseluruhan hasil amandemen UUD 1945 dari Perubahan I hingga Perubahan IV.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena MA tidak berwenang membentuk Komisi Konstitusi pengkaji UUD.",
                "C": "Kurang tepat karena dewan pertahanan mengurusi pertahanan militer, bukan pengkajian konstitusi.",
                "D": "Kurang tepat karena pembentukan peradilan sengketa pemilu adalah kompetensi Mahkamah Konstitusi.",
                "E": "Kurang tepat karena Komisi Konstitusi dibentuk lewat TAP MPR, bukan lewat Perpu eksekutif."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 58,
        "halaman_sumber": 61,
        "kata_kunci_modul": ["arahnya adalah membatasi kekuasaan Presiden dan memperkuat kedudukan Dewan Perwakilan Rakyat (DPR)"],
        "topik_materi": "Fokus Substansi Perubahan Pertama UUD 1945",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Tujuan dan arah pokok Perubahan Pertama UUD 1945 yang disahkan pada tanggal 19 Oktober 1999 difokuskan untuk...",
        "pilihan": {
            "A": "Membubarkan susunan kementerian dan menggantikannya dengan dewan gubernur",
            "B": "Membatasi kekuasaan Presiden dan memperkuat kedudukan DPR sebagai lembaga legislatif",
            "C": "Menetapkan mata uang tunggal kawasan perdagangan bebas lintas benua",
            "D": "Mendelegasikan fungsi pertahanan kepulauan kepada pimpinan kepolisian daerah",
            "E": "Mewajibkan penyelenggaraan referendum sebelum penetapan anggaran belanja"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Perubahan Pertama UUD 1945 tahun 1999 yang **arahnya adalah membatasi kekuasaan Presiden dan memperkuat kedudukan Dewan Perwakilan Rakyat (DPR) sebagai lembaga legislatif**.",
            "alasan_kunci": "Opsi B tepat karena pengalaman otoritarianisme Orde Baru bersumber dari kekuasaan eksekutif yang terlalu dominan (*executive heavy*), sehingga agenda awal amandemen adalah membatasi masa jabatan presiden dan menggeser wewenang legislasi utama ke DPR.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena kementerian tetap dipertahankan di bawah Presiden (Pasal 17).",
                "C": "Kurang tepat karena pengaturan mata uang dibahas pada Perubahan Keempat, bukan Perubahan Pertama.",
                "D": "Kurang tepat karena pertahanan tetap berada di bawah TNI (Pasal 30).",
                "E": "Kurang tepat karena amandemen justru menghapus rezim referendum."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 59,
        "halaman_sumber": 61,
        "kata_kunci_modul": ["meliputi masalah wilayah negara dan pembagian pemerintahan daerah"],
        "topik_materi": "Materi Pokok Perubahan Kedua UUD 1945",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Materi muatan utama yang disahkan dalam Perubahan Kedua UUD 1945 pada tanggal 18 Agustus 2000 mencakup...",
        "pilihan": {
            "A": "Pemberian hak veto eksekutif dalam pembatalan produk perundang-undangan",
            "B": "Masalah wilayah negara dan pembagian pemerintahan daerah serta penegasan HAM",
            "C": "Penetapan susunan pimpinan mahkamah agung oleh perwakilan serikat buruh",
            "D": "Penghapusan bahasa daerah dari kurikulum pendidikan satuan kerja formal",
            "E": "Pemisahan kas daerah dari pembukuan pertanggungjawaban anggaran nasional"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Perubahan Kedua UUD 1945 tahun 2000 **meliputi masalah wilayah negara dan pembagian pemerintahan daerah, menyempurnakan fungsi DPR, serta ketentuan-ketentuan terperinci tentang HAM**.",
            "alasan_kunci": "Opsi B tepat karena Perubahan Kedua mereformasi Pasal 18, 18A, 18B (otonomi daerah), Pasal 25A (NKRI negara kepulauan nusantara), Pasal 20A (fungsi & hak DPR), dan menambahkan 10 pasal HAM (Pasal 28A s/d 28J).",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena presiden tidak diberi hak veto undang-undang.",
                "C": "Kurang tepat karena serikat buruh tidak memilih pimpinan Mahkamah Agung.",
                "D": "Kurang tepat karena bahasa daerah justru dihormati dan dipelihara oleh negara (Pasal 32).",
                "E": "Kurang tepat karena tata kelola keuangan tetap terintegrasi dalam kerangka NKRI."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 60,
        "halaman_sumber": 61,
        "kata_kunci_modul": ["Perubahan UUD 1945 kemudian dilakukan secara bertahap dan menjadi salah satu agenda Sidang Tahunan MPR dari tahun 1999 hingga perubahan keempat"],
        "topik_materi": "Karakteristik Tahapan Amandemen UUD 1945",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Karakteristik pelaksanaan proses amandemen konstitusi di Indonesia pada era reformasi ditandai oleh metode bahwa...",
        "pilihan": {
            "A": "Perubahan pasal disahkan secara serentak dalam satu kali persidangan kilat",
            "B": "Perubahan UUD 1945 dilakukan secara bertahap pada Sidang Tahunan MPR dari 1999 hingga 2002",
            "C": "Amandemen diserahkan kepada komite eksekutif kementerian tanpa melibatkan publik",
            "D": "Pasal-pasal konstitusi diganti secara bergantian berdasarkan dekrit presiden",
            "E": "Perubahan naskah dilakukan melalui musyawarah tertutup antar pimpinan fraksi dewan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Perubahan UUD 1945 kemudian dilakukan secara bertahap dan menjadi salah satu agenda Sidang Tahunan MPR dari tahun 1999 hingga perubahan keempat pada Sidang Tahunan MPR tahun 2002**.",
            "alasan_kunci": "Opsi B tepat karena proses amandemen empat tahap (1999, 2000, 2001, 2002) memungkinkan pembahasan yang mendalam, matang, partisipatif, serta menghindari kegoncangan politik hukum yang drastis (*constitutional continuity*).",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena amandemen tidak dilakukan serentak secara kilat melainkan 4 tahap berturut-turut.",
                "C": "Kurang tepat karena amandemen melibatkan dengar pendapat publik dan perdebatan terbuka di MPR.",
                "D": "Kurang tepat karena amandemen adalah wewenang MPR, bukan produk dekrit eksekutif presiden.",
                "E": "Kurang tepat karena perdebatan pasal berlangsung di komisi dan sidang paripurna MPR yang terbuka."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    }
]

output_path = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch\draft_p06_sub3_hlm60_61.json"
with open(output_path, "w", encoding="utf-8") as f:
    json.dump(questions, f, indent=2, ensure_ascii=False)

print(f"Sub-Agent 3 (Paket 06) finished! Total questions: {len(questions)}")

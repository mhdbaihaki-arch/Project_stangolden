import json

# Sub-Agent 2: Halaman 48 dan 49 (Nomor 21 s/d 40)
# Halaman 48: Nomor 21 - 30
# Halaman 49: Nomor 31 - 40

questions = [
    # --- HALAMAN 48 (Q21 - Q30) ---
    {
        "nomor": 21,
        "halaman_sumber": 48,
        "kata_kunci_modul": ["DPR tidak dapat dibekukan atau dibubarkan oleh Presiden"],
        "topik_materi": "Jaminan Konstitusional Kedudukan DPR",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Berdasarkan UUD 1945, jaminan kemandirian lembaga Dewan Perwakilan Rakyat ditegaskan melalui asas bahwa...",
        "pilihan": {
            "A": "Pimpinan DPR berhak membubarkan kabinet kementerian secara administratif",
            "B": "DPR tidak dapat dibekukan atau dibubarkan oleh Presiden dalam kondisi apa pun",
            "C": "Anggota dewan memegang kekuasaan peradilan banding perkara perdata daerah",
            "D": "Seluruh keputusan komisi legislatif bersifat mutlak tanpa pengujian konstitusi",
            "E": "DPR berwenang menetapkan peraturan perundang-undangan tanpa tandatangan Presiden"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Salah satu kedudukan pokok DPR dalam konstitusi adalah **DPR tidak dapat dibekukan atau dibubarkan oleh Presiden**, yang menegaskan kedudukan parlemen yang kokoh dan independen.",
            "alasan_kunci": "Opsi B tepat karena konstitusi melarang Presiden membekukan parlemen demi menjaga kemurnian sistem pemerintahan presidensial dan mencegah kesewenang-wenangan eksekutif seperti peristiwa pembubaran DPR pada era demokrasi terpimpin.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena DPR tidak berwenang membubarkan jajaran kabinet menteri.",
                "C": "Kurang tepat karena kekuasaan peradilan banding merupakan wewenang pengadilan tinggi di bawah Mahkamah Agung.",
                "D": "Kurang tepat karena produk legislasi dewan tetap dapat diuji konstitusionalitasnya di Mahkamah Konstitusi.",
                "E": "Kurang tepat karena pembentukan undang-undang mensyaratkan pembahasan bersama antara DPR dan Presiden."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 22,
        "halaman_sumber": 48,
        "kata_kunci_modul": ["Legislasi; Anggaran; dan Pengawasan"],
        "topik_materi": "Tiga Fungsi Utama Dewan Perwakilan Rakyat",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Tiga fungsi konstitusional yang diemban oleh Dewan Perwakilan Rakyat menurut Pasal 20A ayat (1) UUD 1945 adalah...",
        "pilihan": {
            "A": "Fungsi peradilan, penuntutan pidana, dan administrasi kepegawaian nasional",
            "B": "Fungsi legislasi, fungsi anggaran, dan fungsi pengawasan terhadap pemerintah",
            "C": "Fungsi eksekutif, koordinasi moneter, serta diplomasi pertahanan kawasan",
            "D": "Fungsi penyelidikan etik, audit perbankan, dan pengangkatan perwira militer",
            "E": "Fungsi konstitutif, penegakan disiplin birokrasi, dan bantuan hukum daerah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dewan Perwakilan Rakyat (DPR) memiliki tiga fungsi pokok yaitu fungsi **legislasi, anggaran, dan pengawasan**.",
            "alasan_kunci": "Opsi B tepat karena fungsi legislasi berfokus pada pembentukan undang-undang, fungsi anggaran berfokus pada penetapan APBN, dan fungsi pengawasan berfokus pada kontrol atas jalannya undang-undang serta kebijakan pemerintah.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena peradilan dan penuntutan merupakan wewenang MA dan Kejaksaan.",
                "C": "Kurang tepat karena fungsi eksekutif dan moneter dijalankan oleh Presiden dan Bank Indonesia.",
                "D": "Kurang tepat karena audit perbankan dijalankan OJK dan BI, bukan DPR.",
                "E": "Kurang tepat karena fungsi konstitutif merupakan kewenangan MPR."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 23,
        "halaman_sumber": 48,
        "kata_kunci_modul": ["Bersidang sedikitnya sekali dalam setahun"],
        "topik_materi": "Periodesasi Sidang Wajib Dewan Perwakilan Rakyat",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Kewajiban persidangan reguler bagi Dewan Perwakilan Rakyat diatur dalam konstitusi secara tegas yaitu...",
        "pilihan": {
            "A": "Bersidang satu kali setiap triwulan di kantor perwakilan tingkat daerah",
            "B": "Bersidang sedikitnya sekali dalam setahun untuk melaksanakan fungsi dewan",
            "C": "Mengadakan rapat kerja setiap permulaan semester anggaran belanja negara",
            "D": "Menyelenggarakan sidang pleno lima tahun sekali bersama pasangan kepresidenan",
            "E": "Berkumpul secara berkala enam bulan sekali atas undangan menteri keuangan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas dan wewenang DPR nomor satu adalah **bersidang sedikitnya sekali dalam setahun** di ibu kota negara.",
            "alasan_kunci": "Opsi B tepat karena Pasal 19 ayat (3) UUD 1945 menggariskan bahwa DPR bersidang sedikitnya sekali dalam setahun guna memastikan kontinuitas fungsi pengawasan, legislasi, dan anggaran.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena aturan konstitusi menetapkan frekuensi sidang sedikitnya sekali setahun di ibu kota.",
                "C": "Kurang tepat karena rapat kerja semesteran diatur dalam tata tertib operasional fraksi/komisi.",
                "D": "Kurang tepat karena sidang lima tahun sekali adalah ketentuan persidangan MPR, bukan DPR.",
                "E": "Kurang tepat karena sidang dewan dijalankan mandiri berdasarkan jadwal masa sidang parlemen."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 24,
        "halaman_sumber": 48,
        "kata_kunci_modul": ["Membentuk undang-undang yang dibahas dengan Presiden untuk mendapat persetujuan bersama"],
        "topik_materi": "Wewenang Legislasi DPR dan Persetujuan Bersama",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pelaksanaan wewenang legislasi oleh Dewan Perwakilan Rakyat diwujudkan melalui mekanisme konstitusional yaitu...",
        "pilihan": {
            "A": "Menetapkan peraturan pemerintah secara mandiri saat situasi keamanan kondusif",
            "B": "Membentuk undang-undang yang dibahas dengan Presiden untuk mendapat persetujuan bersama",
            "C": "Mengesahkan surat edaran kementerian menjadi undang-undang organik negara",
            "D": "Mengubah tata urutan perundang-undangan melalui keputusan pimpinan komisi",
            "E": "Membatalkan undang-undang yang sedang diuji materiil di mahkamah konstitusi"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "DPR memegang kekuasaan membentuk undang-undang, yaitu **membentuk undang-undang yang dibahas dengan Presiden untuk mendapat persetujuan bersama**.",
            "alasan_kunci": "Opsi B tepat karena Pasal 20 ayat (1) dan (2) UUD 1945 menegaskan kekuasaan pembentukan undang-undang berada pada DPR dan setiap rancangan undang-undang wajib dibahas bersama Presiden untuk mendapat persetujuan bersama.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penetapan Peraturan Pemerintah merupakan wewenang Presiden.",
                "C": "Kurang tepat karena surat edaran menteri bukan materi rancangan undang-undang.",
                "D": "Kurang tepat karena hierarki perundang-undangan diatur dalam undang-undang pembentukan peraturan.",
                "E": "Kurang tepat karena pembatalan undang-undang yang bertentangan dengan UUD adalah kewenangan MK."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 25,
        "halaman_sumber": 48,
        "kata_kunci_modul": ["Membahas dan memberikan persetujuan peraturan pemerintah penggati undang-undang"],
        "topik_materi": "Persetujuan Terhadap Perpu Oleh Dewan Perwakilan Rakyat",
        "level_kognitif": "C3 - Aplikasi",
        "pertanyaan": "Ketika Presiden menetapkan Peraturan Pemerintah Pengganti Undang-Undang (Perpu), wewenang yang wajib dijalankan DPR pada masa sidang berikutnya adalah...",
        "pilihan": {
            "A": "Menyerahkan naskah Perpu tersebut kepada kejaksaan agung untuk penuntutan",
            "B": "Membahas dan memberikan persetujuan atau penolakan atas peraturan pemerintah pengganti undang-undang",
            "C": "Menyusun permohonan pembatalan Perpu kepada majelis hakim pengadilan tinggi",
            "D": "Menginstruksikan aparatur daerah guna memboikot pelaksanaan isi naskah perpu",
            "E": "Menetapkan sanksi administratif sepihak terhadap menteri penerbit naskah perpu"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas DPR mencakup **membahas dan memberikan persetujuan peraturan pemerintah pengganti undang-undang (Perpu)** pada masa persidangan berikutnya.",
            "alasan_kunci": "Opsi B tepat karena Perpu merupakan produk darurat eksekutif yang memerlukan kontrol legislatif. Jika DPR memberikan persetujuan, Perpu ditetapkan menjadi Undang-Undang; jika tidak disetujui, Perpu wajib dicabut.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena kejaksaan tidak berwenang menguji keabsahan regulasi perpu.",
                "C": "Kurang tepat karena pengadilan tinggi menangani sengketa peradilan umum, bukan legislasi darurat.",
                "D": "Kurang tepat karena perpu yang berlaku sah mengikat seluruh aparatur sebelum dicabut resmi.",
                "E": "Kurang tepat karena penerbitan perpu merupakan hak konstitusional presiden dan bukan pelanggaran menteri."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 26,
        "halaman_sumber": 48,
        "kata_kunci_modul": ["Memperhatikan pertimbangan DPD atas RUU APBN"],
        "topik_materi": "Pertimbangan DPD Dalam Pembahasan RUU APBN",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Dalam pembahasan RUU APBN serta RUU yang berkaitan dengan pajak, pendidikan, dan agama, DPR secara konstitusional bertugas...",
        "pilihan": {
            "A": "Mendelegasikan wewenang persetujuan akhir sepenuhnya kepada komite eksekutif",
            "B": "Memperhatikan pertimbangan DPD atas RUU APBN dan RUU bidang tertentu tersebut",
            "C": "Menyusun rancangan belanja daerah tanpa berkoordinasi dengan pemerintah pusat",
            "D": "Menetapkan besaran tarif cukai komoditas impor secara rahasia dan sepihak",
            "E": "Mengalihkan alokasi belanja pendidikan menjadi pos bantuan modal perusahaan swasta"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "DPR bertugas **memperhatikan pertimbangan DPD atas RUU APBN** dan RUU yang berkaitan dengan pajak, pendidikan, dan agama sebelum disahkan.",
            "alasan_kunci": "Opsi B tepat karena DPD mewakili aspirasi kepentingan wilayah daerah kepulauan. Pertimbangan DPD memberikan masukan substantif mengenai kebutuhan riil keuangan dan pembangunan daerah dalam postur anggaran nasional.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena kewenangan persetujuan akhir APBN tetap berada di tangan DPR bersama Presiden.",
                "C": "Kurang tepat karena penyusunan belanja daerah (APBD) merupakan wewenang DPRD dan Pemda.",
                "D": "Kurang tepat karena kebijakan fiskal dan tarif cukai dibahas secara terbuka dalam kerangka UU APBN.",
                "E": "Kurang tepat karena anggaran pendidikan dijamin minimal 20% oleh konstitusi dan dilarang dialihkan serampangan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 27,
        "halaman_sumber": 48,
        "kata_kunci_modul": ["Menetapkan APBN bersama Presiden dengan memperhatikan pertimbangan DPD"],
        "topik_materi": "Penetapan Anggaran Pendapatan dan Belanja Negara",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Proses penetapan Anggaran Pendapatan dan Belanja Negara (APBN) menurut UUD 1945 dilaksanakan melalui kewenangan DPR untuk...",
        "pilihan": {
            "A": "Menerbitkan surat utang negara secara mandiri guna menutup defisit kas",
            "B": "Menetapkan APBN bersama Presiden dengan memperhatikan pertimbangan DPD",
            "C": "Mengesahkan laporan keuangan kementerian tanpa melalui telaah hasil BPK",
            "D": "Menghentikan seluruh pencairan gaji pegawai negeri sipil di satuan kerja",
            "E": "Menetapkan target penerimaan pajak daerah tanpa persetujuan kepala dinas"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas DPR dalam fungsi anggaran adalah **menetapkan APBN bersama Presiden dengan memperhatikan pertimbangan DPD**.",
            "alasan_kunci": "Opsi B tepat karena APBN merupakan wujud pengelolaan keuangan negara yang ditetapkan setiap tahun dengan undang-undang. DPR memegang hak anggaran untuk menyetujui atau menolak rancangan anggaran yang diajukan pemerintah.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penerbitan surat berharga negara merupakan kewenangan eksekutif kementerian keuangan.",
                "C": "Kurang tepat karena audit laporan pertanggungjawaban APBN wajib diperiksa oleh BPK.",
                "D": "Kurang tepat karena belanja pegawai dijamin dalam alokasi pos belanja rutin APBN yang sah.",
                "E": "Kurang tepat karena pajak daerah diatur dalam perda dan kewenangan pemda setempat."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 28,
        "halaman_sumber": 48,
        "kata_kunci_modul": ["Melaksanakan pengawasan terhadap pelaksanaan UU, APBN, serta kebijakan pemerintah"],
        "topik_materi": "Fungsi Pengawasan Dewan Perwakilan Rakyat",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Ruang lingkup pelaksanaan fungsi pengawasan oleh Dewan Perwakilan Rakyat mencakup kegiatan untuk...",
        "pilihan": {
            "A": "Mengintervensi jalannya pemeriksaan perkara pidana di tingkat penyidikan",
            "B": "Melaksanakan pengawasan terhadap pelaksanaan UU, APBN, serta kebijakan pemerintah",
            "C": "Memeriksa secara langsung pembukuan kas bendahara umum di daerah terpencil",
            "D": "Menjatuhkan sanksi kurungan pidana kepada pimpinan lembaga swadaya masyarakat",
            "E": "Mengawasi tata peribadatan keagamaan masyarakat dalam kehidupan sehari-hari"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas pengawasan DPR adalah **melaksanakan pengawasan terhadap pelaksanaan UU, APBN, serta kebijakan pemerintah**.",
            "alasan_kunci": "Opsi B tepat karena fungsi pengawasan dewan ditujukan untuk memastikan eksekutif menjalankan undang-undang dan mengelola anggaran sesuai target yang telah ditetapkan dalam APBN secara transparan dan akuntabel.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena parlemen dilarang mengintervensi proses hukum pro justitia kepolisian dan kejaksaan.",
                "C": "Kurang tepat karena pemeriksaan pembukuan kas keuangan negara secara fisik merupakan tugas auditor BPK.",
                "D": "Kurang tepat karena penjatuhan sanksi pidana merupakan kewenangan mutlak majelis hakim pengadilan.",
                "E": "Kurang tepat karena kemerdekaan beribadah dijamin konstitusi dan bukan objek pengawasan politik DPR."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 29,
        "halaman_sumber": 48,
        "kata_kunci_modul": ["Memilih anggota Badan Pemeriksa Keuangan dengan memperhatikan pertimbangan DPD"],
        "topik_materi": "Pemilihan Anggota Badan Pemeriksa Keuangan",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Sesuai Pasal 23F UUD 1945, wewenang DPR dalam suksesi keanggotaan Badan Pemeriksa Keuangan adalah...",
        "pilihan": {
            "A": "Mengangkat langsung kerabat pimpinan komisi menjadi dewan pemeriksa pusat",
            "B": "Memilih anggota Badan Pemeriksa Keuangan dengan memperhatikan pertimbangan DPD",
            "C": "Mendelegasikan pemilihan auditor utama keuangan kepada asosiasi akuntan",
            "D": "Meminta rekomendasi mutlak dari dewan pertimbangan kepresidenan negara",
            "E": "Menetapkan susunan pimpinan BPK tanpa melalui tahapan uji kepatutan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas DPR dalam rekrutmen pimpinan lembaga negara adalah **memilih anggota Badan Pemeriksa Keuangan dengan memperhatikan pertimbangan DPD** untuk kemudian diresmikan oleh Presiden.",
            "alasan_kunci": "Opsi B tepat karena keanggotaan BPK dipilih oleh DPR dengan masukan tertulis dari DPD guna memastikan integritas, kapasitas profesionalisme, dan independensi lembaga pemeriksa keuangan negara.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pemilihan anggota BPK wajib melalui proses seleksi kepatutan yang objektif dan meritokratis.",
                "C": "Kurang tepat karena mandat pemilihan anggota BPK dipegang oleh DPR, bukan asosiasi profesi.",
                "D": "Kurang tepat karena DPD yang memberikan pertimbangan, bukan dewan pertimbangan kepresidenan.",
                "E": "Kurang tepat karena calon anggota BPK wajib menjalani uji kepatutan dan kelayakan di komisi DPR."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 30,
        "halaman_sumber": 48,
        "kata_kunci_modul": ["Memberikan persetujuan kepada Presiden atas pengangkatan dan pemberhentian anggota Komisi Yudisial"],
        "topik_materi": "Persetujuan Keanggotaan Komisi Yudisial",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Wewenang DPR dalam mendukung pembentukan lembaga Komisi Yudisial menurut UUD 1945 adalah...",
        "pilihan": {
            "A": "Memimpin sidang pemeriksaan etik hakim yang diadukan oleh masyarakat umum",
            "B": "Memberikan persetujuan kepada Presiden atas pengangkatan dan pemberhentian anggota Komisi Yudisial",
            "C": "Menunjuk secara mandiri calon anggota komisi tanpa proses panitia seleksi",
            "D": "Mengesahkan tata cara persidangan peradilan kasasi di lingkungan mahkamah",
            "E": "Membatalkan berkas rekomendasi sanksi yang diajukan komisi ke mahkamah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas DPR nomor 11 adalah **memberikan persetujuan kepada Presiden atas pengangkatan dan pemberhentian anggota Komisi Yudisial**.",
            "alasan_kunci": "Opsi B tepat karena anggota Komisi Yudisial diangkat dan diberhentikan oleh Presiden dengan persetujuan DPR. Keterlibatan DPR menjamin representasi publik dalam memilih figur penjaga etika peradilan kehakiman.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena sidang pemeriksaan etik hakim dipimpin langsung oleh majelis kehormatan KY/MA.",
                "C": "Kurang tepat karena seleksi awal dilakukan oleh panitia seleksi pemerintah sebelum diajukan ke DPR.",
                "D": "Kurang tepat karena tata cara persidangan kasasi diatur dalam undang-undang MA dan hukum acara.",
                "E": "Kurang tepat karena rekomendasi sanksi KY disampaikan kepada MA, bukan kepada DPR."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },

    # --- HALAMAN 49 (Q31 - Q40) ---
    {
        "nomor": 31,
        "halaman_sumber": 49,
        "kata_kunci_modul": ["Memberikan persetujuan calon hakim agung yang diusulkan Komisi Yudisial"],
        "topik_materi": "Persetujuan Pengangkatan Hakim Agung Oleh DPR",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Keterlibatan DPR dalam proses seleksi kepemimpinan puncak kekuasaan kehakiman diwujudkan melalui wewenang untuk...",
        "pilihan": {
            "A": "Menyelenggarakan ujian kelulusan teknis yudisial bagi para calon hakim agung",
            "B": "Memberikan persetujuan calon hakim agung yang diusulkan Komisi Yudisial",
            "C": "Menetapkan komposisi susunan majelis hakim kasasi perkara niaga nasional",
            "D": "Menjatuhkan vonis peradilan pidana khusus terhadap mantan menteri negara",
            "E": "Mengangkat calon hakim tingkat banding di seluruh wilayah peradilan militer"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "DPR bertugas **memberikan persetujuan calon hakim agung yang diusulkan Komisi Yudisial untuk ditetapkan sebagai hakim agung oleh Presiden**.",
            "alasan_kunci": "Opsi B tepat karena proses rekrutmen Hakim Agung melibatkan tiga cabang kekuasaan: KY menyaring calon, DPR memberikan persetujuan, dan Presiden menetapkannya secara administratif.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena seleksi teknis dan uji kelayakan substantif dijalankan oleh Komisi Yudisial.",
                "C": "Kurang tepat karena pembagian komposisi majelis hakim kasasi merupakan kewenangan Ketua Mahkamah Agung.",
                "D": "Kurang tepat karena penjatuhan vonis pidana murni merupakan wewenang majelis hakim pengadilan.",
                "E": "Kurang tepat karena pengangkatan hakim tingkat banding merupakan kewenangan internal MA."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 32,
        "halaman_sumber": 49,
        "kata_kunci_modul": ["Memilih tiga orang calon anggota hakim konstitusi"],
        "topik_materi": "Pengusulan Hakim Konstitusi Oleh DPR",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Dalam rangka pembentukan keanggotaan Mahkamah Konstitusi, wewenang konstitusional yang dijalankan DPR adalah...",
        "pilihan": {
            "A": "Menunjuk seluruh sembilan orang hakim konstitusi dari kalangan pimpinan partai",
            "B": "Memilih tiga orang calon anggota hakim konstitusi dan mengajukannya kepada Presiden",
            "C": "Menguji kelayakan putusan peradilan konstitusi mengenai pengujian undang-undang",
            "D": "Membatalkan hasil putusan sengketa pemilihan umum yang ditetapkan oleh mahkamah",
            "E": "Memberhentikan secara sepihak hakim konstitusi yang berbeda pendapat hukum"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Mahkamah Konstitusi mempunyai 9 orang hakim konstitusi, di mana DPR berwenang **memilih tiga orang calon anggota hakim konstitusi dan mengajukannya kepada Presiden untuk ditetapkan**.",
            "alasan_kunci": "Opsi B tepat karena komposisi 9 hakim MK mencerminkan representasi trias politika yang seimbang: 3 orang diajukan oleh DPR, 3 orang diajukan oleh Presiden, dan 3 orang diajukan oleh Mahkamah Agung.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena DPR hanya mengajukan tiga orang calon, bukan sembilan orang.",
                "C": "Kurang tepat karena putusan MK bersifat final dan mengikat serta tidak dapat diuji ulang oleh DPR.",
                "D": "Kurang tepat karena putusan sengketa hasil pemilu di MK tidak dapat dibatalkan oleh parlemen.",
                "E": "Kurang tepat karena pemberhentian hakim konstitusi diatur ketat oleh undang-undang melalui majelis kehormatan MK."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 33,
        "halaman_sumber": 49,
        "kata_kunci_modul": ["Interpelasi; Angket; dan Menyatakan pendapat"],
        "topik_materi": "Hak-Hak Kelembagaan Dewan Perwakilan Rakyat",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Berdasarkan Pasal 20A ayat (2) UUD 1945, hak-hak yang dimiliki oleh lembaga DPR dalam rangka pelaksanaan fungsi pengawasan adalah...",
        "pilihan": {
            "A": "Hak veto, hak amnesti massal, dan hak penuntutan kejahatan politik luar negeri",
            "B": "Hak interpelasi, hak angket, dan hak menyatakan pendapat kepada pemerintah",
            "C": "Hak banding, hak kasasi yudisial, serta hak peninjauan kembali perkara pajak",
            "D": "Hak prerogatif, hak grasi kepala negara, serta hak pengangkatan menteri",
            "E": "Hak eksekusi aset daerah, hak audit mandiri, dan hak pembekuan korporasi"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam menjalankan fungsinya, lembaga DPR mempunyai hak: **interpelasi, angket, dan menyatakan pendapat**.",
            "alasan_kunci": "Opsi B tepat karena hak interpelasi adalah hak meminta keterangan kepada pemerintah, hak angket adalah hak menyelidiki kebijakan strategis pemerintah yang diduga melanggar undang-undang, dan hak menyatakan pendapat adalah hak menyampaikan sikap resmi lembaga dewan.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena hak veto dan amnesti bukan merupakan hak kelembagaan DPR melainkan ranah eksekutif.",
                "C": "Kurang tepat karena banding dan kasasi merupakan jalur hukum peradilan di badan yudikatif.",
                "D": "Kurang tepat karena hak prerogatif grasi dan pengangkatan kabinet merupakan hak presiden.",
                "E": "Kurang tepat karena eksekusi aset dan audit keuangan bukan instrumen hak kelembagaan DPR."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 34,
        "halaman_sumber": 49,
        "kata_kunci_modul": ["Mengajukan usul RUU; Mengajukan pertanyaan; Menyampaikan usul dan pendapat; dan Imunitas"],
        "topik_materi": "Hak-Hak Individu Anggota Dewan Perwakilan Rakyat",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Setiap anggota Dewan Perwakilan Rakyat secara perorangan dibekali oleh konstitusi dengan hak...",
        "pilihan": {
            "A": "Menetapkan vonis perkara korupsi, membubarkan parpol, dan hak menyita aset",
            "B": "Mengajukan usul RUU, mengajukan pertanyaan, menyampaikan pendapat, dan imunitas",
            "C": "Membekukan organisasi kemasyarakatan, hak menyadap telepon, dan hak protokoler",
            "D": "Mereshuffle kabinet menteri, hak grasi, serta hak pembentukan daerah otonom",
            "E": "Membatalkan perjanjian internasional, hak ekstradisi, dan hak suaka politik"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Anggota DPR mempunyai hak individu yang dijamin UUD 1945, yaitu: **mengajukan usul RUU, mengajukan pertanyaan, menyampaikan usul dan pendapat, dan imunitas**.",
            "alasan_kunci": "Opsi B tepat karena hak-hak ini menjamin setiap wakil rakyat dapat menyalurkan aspirasi daerah pemilihannya secara vokal dan kritis tanpa khawatir dikriminalisasi berkat adanya perlindungan hak imunitas.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena vonis pidana dan pembubaran parpol merupakan ranah kekuasaan peradilan.",
                "C": "Kurang tepat karena pembekuan ormas dan penyadapan merupakan ranah penegak hukum dan pengadilan.",
                "D": "Kurang tepat karena perombakan kabinet merupakan hak prerogatif presiden.",
                "E": "Kurang tepat karena ekstradisi dan suaka politik merupakan kewenangan hubungan luar negeri pemerintah."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 35,
        "halaman_sumber": 49,
        "kata_kunci_modul": ["lembaga perwakilan daerah yang berkedudukan sebagai lembaga negara"],
        "topik_materi": "Kedudukan Dewan Perwakilan Daerah (DPD)",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Dalam struktur ketatanegaraan Indonesia pasca-amandemen UUD 1945, Dewan Perwakilan Daerah (DPD) diposisikan sebagai...",
        "pilihan": {
            "A": "Badan otonom eksekutif yang membawahi seluruh kepala dinas di provinsi",
            "B": "Lembaga perwakilan daerah yang berkedudukan sebagai lembaga negara",
            "C": "Komisi pembantu kepresidenan dalam penetapan anggaran belanja militer",
            "D": "Lembaga peradilan adat tertinggi bagi penyelesaian sengketa tanah ulayat",
            "E": "Majelis perwakilan partai politik tingkat wilayah luar pulau Jawa"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Kedudukan DPD dalam UUD 1945 ditegaskan bahwa **DPD merupakan lembaga perwakilan daerah yang berkedudukan sebagai lembaga negara**.",
            "alasan_kunci": "Opsi B tepat karena DPD dibentuk untuk memperkuat representasi teritorial daerah dalam pengambilan kebijakan di tingkat nasional, berstatus sejajar sebagai lembaga tinggi negara bersama lembaga lainnya.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena DPD adalah lembaga perwakilan legislatif, bukan bagian dinas eksekutif daerah.",
                "C": "Kurang tepat karena DPD bukan komisi pembantu kepresidenan bidang militer.",
                "D": "Kurang tepat karena DPD tidak berwenang mengadili perkara peradilan adat ulayat.",
                "E": "Kurang tepat karena anggota DPD mewakili daerah provinsi secara independen non-partisan, bukan mewakili partai politik."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 36,
        "halaman_sumber": 49,
        "kata_kunci_modul": ["dipilih dari setiap provinsi melalui pemilihan umum"],
        "topik_materi": "Sistem Pemilihan Anggota Dewan Perwakilan Daerah",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Anggota Dewan Perwakilan Daerah (DPD) memperoleh mandat keterwakilan melalui tata cara...",
        "pilihan": {
            "A": "Penunjukan langsung oleh gubernur dan dewan perwakilan rakyat daerah",
            "B": "Dipilih dari setiap provinsi melalui pemilihan umum secara perseorangan",
            "C": "Seleksi berkas administrasi yang diselenggarakan oleh kementerian dalam negeri",
            "D": "Musyawarah utusan adat kesultanan dan tokoh masyarakat di daerah masing-masing",
            "E": "Penugasan dari pimpinan fraksi partai politik peserta pemilu legislatif"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Sesuai Pasal 22C UUD 1945, anggota DPD **dipilih dari setiap provinsi melalui pemilihan umum** dari jalur perseorangan non-partisan.",
            "alasan_kunci": "Opsi B tepat karena legitimasi anggota DPD bertumpu langsung pada suara pemilih di tiap provinsi, berbeda dengan anggota DPR yang diajukan oleh partai politik.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena gubernur dan DPRD tidak berwenang menunjuk anggota DPD.",
                "C": "Kurang tepat karena anggota DPD adalah pejabat politik hasil pemilu, bukan pegawai seleksi Kemendagri.",
                "D": "Kurang tepat karena penetapan melalui musyawarah adat utusan golongan tidak lagi berlaku dalam UUD 1945 pasca-amandemen.",
                "E": "Kurang tepat karena calon anggota DPD bukan merupakan utusan atau perwakilan fraksi partai."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 37,
        "halaman_sumber": 49,
        "kata_kunci_modul": ["Jumlah anggota DPD di setiap provinsi sama"],
        "topik_materi": "Prinsip Kesetaraan Keterwakilan Anggota DPD",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Prinsip kesetaraan perwakilan wilayah dalam pembentukan Dewan Perwakilan Daerah ditegaskan melalui aturan bahwa...",
        "pilihan": {
            "A": "Provinsi dengan populasi penduduk terpadat memperoleh alokasi kursi terbanyak",
            "B": "Jumlah anggota DPD di setiap provinsi sama dan terdistribusi adil di seluruh wilayah",
            "C": "Provinsi kepulauan diberikan hak kuota perwakilan dua kali lipat dibanding daratan",
            "D": "Jumlah kursi dewan ditentukan berdasarkan besarnya kontribusi pajak daerah ke APBN",
            "E": "Ibu kota negara memiliki hak monopoli menentukan separuh dari total kursi dewan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Ketentuan konstitusi menegaskan bahwa **jumlah anggota DPD di setiap provinsi sama**, masing-masing berjumlah 4 orang anggota per provinsi.",
            "alasan_kunci": "Opsi B tepat karena representasi DPD menganut prinsip kesetaraan teritorial (*equal representation of regions*). Setiap provinsi, baik yang berpenduduk padat maupun jarang, memiliki bobot suara keterwakilan yang persis sama di forum DPD.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena proporsionalitas jumlah penduduk diterapkan pada pemilihan DPR, bukan DPD.",
                "C": "Kurang tepat karena seluruh provinsi memperoleh kuota kursi yang setara tanpa diskriminasi geografis.",
                "D": "Kurang tepat karena kontribusi pajak daerah bukan dasar penentuan alokasi kursi perwakilan politik.",
                "E": "Kurang tepat karena ibu kota negara tidak memiliki hak istimewa memonopoli kursi keterwakilan dewan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 38,
        "halaman_sumber": 49,
        "kata_kunci_modul": ["tidak boleh lebih dari 1/3 dari jumlah anggota DPR"],
        "topik_materi": "Ambang Batas Maksimal Jumlah Anggota DPD",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Berdasarkan Pasal 22C ayat (2) UUD 1945, batasan kuota seluruh anggota DPD secara nasional diatur agar...",
        "pilihan": {
            "A": "Tepat menyamai separuh dari total pejabat struktural eselon satu kementerian",
            "B": "Jumlah seluruh anggota DPD tidak boleh lebih dari 1/3 dari jumlah anggota DPR",
            "C": "Mencapai sekurang-kurangnya dua pertiga dari jumlah anggota parlemen nasional",
            "D": "Dibatasi paling banyak seratus orang perwakilan dari pulau-pulau besar",
            "E": "Ditetapkan secara bebas oleh dewan perwakilan rakyat daerah di setiap provinsi"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "UUD 1945 mengatur bahwa jumlah anggota DPD dari setiap provinsi sama dan **jumlah seluruh anggota DPD tidak boleh lebih dari 1/3 dari jumlah anggota DPR**.",
            "alasan_kunci": "Opsi B tepat karena pembatasan rasio sepertiga ini dimaksudkan agar dalam forum persidangan gabungan MPR, komposisi perwakilan rakyat berbasis partai (DPR) dan perwakilan daerah (DPD) tetap berada dalam proporsi yang berimbang dan stabil.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena jumlah anggota DPD tidak dikaitkan dengan jumlah pejabat struktural eselon.",
                "C": "Kurang tepat karena ambang batas konstitusional adalah tidak lebih dari sepertiga (1/3), bukan 2/3.",
                "D": "Kurang tepat karena kuota dihitung per provinsi (4 orang per provinsi), bukan dibatasi 100 orang pulau besar.",
                "E": "Kurang tepat karena jumlah kuota diatur secara nasional dalam konstitusi dan undang-undang pemilu."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 39,
        "halaman_sumber": 49,
        "kata_kunci_modul": ["Seluruh anggota DPD adalah anggota MPR"],
        "topik_materi": "Integrasi Anggota DPD Dalam Keanggotaan MPR",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Keterikatan kelembagaan antara Dewan Perwakilan Daerah dengan Majelis Permusyawaratan Rakyat ditegaskan melalui asas bahwa...",
        "pilihan": {
            "A": "Pimpinan DPD secara bergantian memegang jabatan sebagai perdana menteri",
            "B": "Seluruh anggota DPD adalah anggota MPR bersama-sama dengan anggota DPR",
            "C": "Anggota DPD hanya dapat menghadiri sidang paripurna apabila diundang presiden",
            "D": "Anggota DPD memiliki status peninjau pasif tanpa hak suara dalam sidang UUD",
            "E": "Keanggotaan DPD gugur otomatis manakala terpilih menjadi pimpinan komisi majelis"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Kedudukan DPD dalam struktur perwakilan menegaskan bahwa **seluruh anggota DPD adalah anggota MPR**.",
            "alasan_kunci": "Opsi B tepat karena MPR pasca-amandemen terdiri atas anggota DPR dan anggota DPD. Dengan demikian, setiap anggota DPD secara otomatis menyandang status dan hak penuh sebagai anggota MPR dalam menjalankan fungsi konstitutif.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Indonesia tidak mengenal jabatan perdana menteri dalam sistem presidensial.",
                "C": "Kurang tepat karena anggota DPD adalah anggota sah MPR yang berhak hadir penuh tanpa undangan presiden.",
                "D": "Kurang tepat karena anggota DPD memiliki hak bicara dan hak suara yang setara di sidang majelis.",
                "E": "Kurang tepat karena terpilih menjadi pimpinan majelis justru merupakan amanah penugasan kedewanan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 40,
        "halaman_sumber": 49,
        "kata_kunci_modul": ["syarat-syarat dan tata caranya diatur dalam undang-undang"],
        "topik_materi": "Mekanisme Pemberhentian Anggota DPD",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Ketentuan mengenai pemberhentian anggota Dewan Perwakilan Daerah dari masa jabatannya menurut UUD 1945 berlaku aturan bahwa...",
        "pilihan": {
            "A": "Pemberhentian diputuskan sekehendak pimpinan organisasi kemasyarakatan daerah",
            "B": "Anggota DPD dapat diberhentikan dari jabatannya yang syarat-syarat dan tata caranya diatur dalam undang-undang",
            "C": "Gubernur berwenang memecat anggota DPD yang menolak kebijakan pembangunan daerah",
            "D": "Pemberhentian wajib disetujui melalui pemungutan suara tertutup oleh kementerian",
            "E": "Anggota dewan tidak dapat diberhentikan dalam kondisi apa pun hingga akhir hayat"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pasal 22D ayat (4) UUD 1945 menetapkan anggota DPD dapat diberhentikan dari jabatannya, yang **syarat-syarat dan tata caranya diatur dalam undang-undang**.",
            "alasan_kunci": "Opsi B tepat karena konstitusi mendelegasikan pengaturan teknis pemberhentian anggota dewan (seperti karena tindak pidana, pelanggaran kode etik berat, atau berhalangan tetap) kepada undang-undang lembaga perwakilan (UU MD3).",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena ormas daerah tidak memiliki kewenangan yuridis memberhentikan pejabat negara.",
                "C": "Kurang tepat karena gubernur memegang kekuasaan eksekutif dan tidak berwenang memberhentikan wakil legislatif.",
                "D": "Kurang tepat karena pemberhentian pejabat politik legislatif bukan ranah pemungutan suara kementerian.",
                "E": "Kurang tepat karena anggota dewan dapat diberhentikan sesuai mekanisme hukum perundang-undangan yang sah."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    }
]

output_path = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch\draft_p05_sub2_hlm48_49.json"
with open(output_path, "w", encoding="utf-8") as f:
    json.dump(questions, f, indent=2, ensure_ascii=False)

print(f"Sub-Agent 2 (Paket 05) finished! Total questions: {len(questions)}")

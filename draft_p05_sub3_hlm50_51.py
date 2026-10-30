import json

# Sub-Agent 3: Halaman 50 dan 51 (Nomor 41 s/d 60)
# Halaman 50: Nomor 41 - 50
# Halaman 51: Nomor 51 - 60

questions = [
    # --- HALAMAN 50 (Q41 - Q50) ---
    {
        "nomor": 41,
        "halaman_sumber": 50,
        "kata_kunci_modul": ["Dapat mengajukan kepada DPR RUU yang berkaitan dengan otonomi daerah"],
        "topik_materi": "Hak Inisiatif Legislasi DPD Bidang Daerah",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Kewenangan legislatif yang dimiliki oleh Dewan Perwakilan Daerah menurut Pasal 22D ayat (1) UUD 1945 mencakup hak untuk...",
        "pilihan": {
            "A": "Mengesahkan undang-undang perpajakan tanpa melibatkan persetujuan dari DPR",
            "B": "Dapat mengajukan kepada DPR RUU yang berkaitan dengan otonomi daerah dan perimbangan keuangan",
            "C": "Menetapkan struktur tarif bea masuk komoditas perdagangan lintas kepulauan",
            "D": "Membatalkan peraturan daerah secara mandiri tanpa persetujuan menteri dalam negeri",
            "E": "Menerbitkan obligasi daerah otonom tanpa melalui pengawasan kementerian keuangan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas dan wewenang DPD antara lain **dapat mengajukan kepada DPR RUU yang berkaitan dengan otonomi daerah**, hubungan pusat dan daerah, pembentukan, pemekaran, dan penggabungan daerah, pengelolaan SDA, serta perimbangan keuangan pusat dan daerah.",
            "alasan_kunci": "Opsi B tepat karena DPD memiliki hak inisiatif legislasi khusus untuk materi muatan yang berkaitan langsung dengan kepentingan dan pembangunan daerah otonom di seluruh wilayah Indonesia.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pengesahan undang-undang merupakan wewenang DPR bersama Presiden.",
                "C": "Kurang tepat karena penetapan bea masuk merupakan kewenangan pemerintah pusat bidang fiskal.",
                "D": "Kurang tepat karena pembatalan perda tunduk pada mekanisme pengujian yudisial di Mahkamah Agung.",
                "E": "Kurang tepat karena penerbitan obligasi daerah wajib memenuhi kriteria ketat regulasi Kemenkeu."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 42,
        "halaman_sumber": 50,
        "kata_kunci_modul": ["Membahas RUU pada huruf b tersebut bersama-sama DPR"],
        "topik_materi": "Keterlibatan DPD Dalam Pembahasan RUU Tertentu",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Terkait rancangan undang-undang bidang kedaerahan, keterlibatan DPD dalam proses pembentukan regulasi diatur melalui wewenang untuk...",
        "pilihan": {
            "A": "Memegang hak veto mutlak terhadap keputusan akhir sidang paripurna DPR",
            "B": "Membahas RUU pada bidang kedaerahan tersebut bersama-sama DPR sebelum dibahas bersama pemerintah",
            "C": "Menetapkan sanksi pidana kurungan bagi kepala daerah yang tidak hadir rapat",
            "D": "Mengubah susunan fraksi politik dewan perwakilan secara sepihak di parlemen",
            "E": "Mewajibkan presiden mengundangkan rancangan undang-undang dalam tempo 24 jam"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "DPD bertugas **membahas RUU pada bidang kedaerahan tersebut bersama-sama DPR** atas undangan DPR sesuai tata tertib dewan sebelum dibahas bersama pemerintah.",
            "alasan_kunci": "Opsi B tepat karena DPD dilibatkan dalam pembahasan tingkat awal (tripartit) bersama DPR untuk menyelaraskan substansi RUU terkait kedaerahan sebelum pemerintah dan DPR melakukan pembahasan persetujuan bersama.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena DPD tidak memiliki hak veto terhadap keputusan persetujuan RUU di DPR.",
                "C": "Kurang tepat karena DPD tidak memiliki wewenang yuridis menjatuhkan sanksi kurungan pidana.",
                "D": "Kurang tepat karena DPD tidak memiliki kaitan struktural dengan susunan fraksi partai di DPR.",
                "E": "Kurang tepat karena pengundangan undang-undang memiliki tenggat waktu konstitusional 30 hari."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 43,
        "halaman_sumber": 50,
        "kata_kunci_modul": ["Melakukan pengawasan sebagai pertimbangan DPR atas pelaksanaan: Undang-undang mengenai otonomi daerah"],
        "topik_materi": "Fungsi Pengawasan DPD Terhadap Pelaksanaan UU",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pelaksanaan fungsi pengawasan oleh Dewan Perwakilan Daerah menurut UUD 1945 diwujudkan melalui kewenangan untuk...",
        "pilihan": {
            "A": "Menyelenggarakan penyidikan tindak pidana penyalahgunaan keuangan desa",
            "B": "Melakukan pengawasan sebagai pertimbangan DPR atas pelaksanaan undang-undang mengenai otonomi daerah",
            "C": "Menyita berkas laporan pertanggungjawaban dinas perpajakan tingkat kota",
            "D": "Menonaktifkan bupati atau walikota yang terlambat menyampaikan laporan APBD",
            "E": "Membekukan operasional badan usaha milik daerah yang mengalami kerugian kas"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "DPD bertugas **melakukan pengawasan sebagai pertimbangan DPR atas pelaksanaan undang-undang mengenai otonomi daerah**, perimbangan keuangan, SDA, pajak, pendidikan, dan agama.",
            "alasan_kunci": "Opsi B tepat karena hasil pengawasan DPD terhadap implementasi otonomi daerah diserahkan kepada DPR sebagai bahan pertimbangan bagi DPR untuk menindaklanjuti kebijakan pengawasan nasional.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penyidikan pidana keuangan merupakan wewenang aparat kepolisian dan kejaksaan.",
                "C": "Kurang tepat karena penyitaan dokumen merupakan tindakan hukum pro justitia aparat penyidik.",
                "D": "Kurang tepat karena pemberhentian kepala daerah diatur lewat mekanisme DPRD dan Kemendagri.",
                "E": "Kurang tepat karena pengelolaan BUMD berada di bawah ranah pemerintah daerah dan RUPS."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 44,
        "halaman_sumber": 50,
        "kata_kunci_modul": ["Memberikan pertimbangan kepada DPR dalam pemilihan anggota Badan Pemeriksa Keuangan"],
        "topik_materi": "Peran DPD Dalam Pemilihan Anggota BPK",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Wewenang konstitusional DPD dalam proses pembentukan keanggotaan Badan Pemeriksa Keuangan (BPK) adalah...",
        "pilihan": {
            "A": "Menetapkan susunan auditor pelaksana audit forensik di seluruh provinsi",
            "B": "Memberikan pertimbangan kepada DPR dalam pemilihan anggota Badan Pemeriksa Keuangan",
            "C": "Mengesahkan anggaran operasional badan pemeriksa secara mandiri dan final",
            "D": "Menjatuhkan putusan etik profesi akuntan terhadap pimpinan lembaga pemeriksa",
            "E": "Memimpin sidang pleno penyerahan laporan hasil pemeriksaan anggaran tahunan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas DPD salah satunya adalah **memberikan pertimbangan kepada DPR dalam pemilihan anggota Badan Pemeriksa Keuangan**.",
            "alasan_kunci": "Opsi B tepat karena BPK bertugas memeriksa keuangan pusat dan daerah. Pertimbangan tertulis dari DPD membantu DPR menyaring kandidat anggota BPK yang memiliki komitmen integritas terhadap pengawasan perbendaharaan negara di daerah.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penugasan tim audit internal BPK merupakan kewenangan operasional pimpinan BPK.",
                "C": "Kurang tepat karena anggaran operasional BPK ditetapkan dalam APBN bersama DPR dan Presiden.",
                "D": "Kurang tepat karena pengawasan etik auditor BPK dijalankan oleh Majelis Kehormatan Kode Etik BPK.",
                "E": "Kurang tepat karena sidang penyerahan LHP dipimpin oleh pimpinan dewan perwakilan terkait."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 45,
        "halaman_sumber": 50,
        "kata_kunci_modul": ["penyelenggara peradilan yang merdeka untuk menegakkan hukum dan keadilan"],
        "topik_materi": "Prinsip Kemerdekaan Kekuasaan Kehakiman Pada MA",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Kedudukan Mahkamah Agung berdasarkan Pasal 24 ayat (1) UUD 1945 diposisikan sebagai...",
        "pilihan": {
            "A": "Badan pendukung eksekutif dalam penertiban administrasi perpajakan daerah",
            "B": "Pemegang kekuasaan kehakiman dan penyelenggara peradilan yang merdeka untuk menegakkan hukum dan keadilan",
            "C": "Komisi penyelidik independen perkara kejahatan lintas batas kedaulatan negara",
            "D": "Lembaga penasihat hukum resmi bagi jajaran kementerian koordinator pemerintahan",
            "E": "Pengawas kedisiplinan operasional bagi aparatur sipil negara di kementerian"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Mahkamah Agung (MA) berkedudukan sebagai pemegang kekuasaan kehakiman dan **penyelenggara peradilan yang merdeka untuk menegakkan hukum dan keadilan**.",
            "alasan_kunci": "Opsi B tepat karena independensi badan peradilan (*judicial independence*) merupakan syarat mutlak negara hukum guna menjamin tegaknya keadilan tanpa adanya campur tangan eksekutif maupun kekuatan politik mana pun.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penertiban administrasi pajak merupakan kewenangan dirjen pajak kementerian keuangan.",
                "C": "Kurang tepat karena penyelidikan kejahatan transnasional merupakan ranah kepolisian dan interpol.",
                "D": "Kurang tepat karena penasihat hukum pemerintah disediakan oleh Jaksa Agung Muda Perdata dan TUN.",
                "E": "Kurang tepat karena disiplin pegawai ASN berada di bawah kewenangan BKN dan pejabat pembina kepegawaian."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 46,
        "halaman_sumber": 50,
        "kata_kunci_modul": ["pengadilan tertinggi bagi semua peradilan terlepas dari pengaruh Pemerintah"],
        "topik_materi": "Kedudukan MA Sebagai Pengadilan Tertinggi",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Fungsi Mahkamah Agung sebagai puncak hierarki peradilan di Indonesia ditegaskan melalui status yuridis bahwa MA merupakan...",
        "pilihan": {
            "A": "Mitra koordinasi penuntutan umum di bawah instruksi menteri koordinator",
            "B": "Pengadilan tertinggi bagi semua peradilan terlepas dari pengaruh Pemerintah dan pengaruh lainnya",
            "C": "Instansi pembina teknis bagi kepolisian resor dan kejaksaan negeri di daerah",
            "D": "Pengadilan arbitrase dagang khusus untuk penyelesaian utang korporasi asing",
            "E": "Lembaga banding pertama bagi perkara tindak pidana pelanggaran pemilu kepala daerah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Kedudukan MA adalah sebagai lembaga negara yang berfungsi sebagai **pengadilan tertinggi bagi semua peradilan terlepas dari pengaruh Pemerintah dan pengaruh-pengaruh lainnya**.",
            "alasan_kunci": "Opsi B tepat karena MA membawahi empat lingkungan peradilan (umum, agama, militer, dan tata usaha negara) serta memegang otoritas kasasi tertinggi guna menjaga kesatuan penerapan hukum nasional (*unity of jurisprudence*).",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena MA bersifat bebas dan mandiri serta terlepas dari arahan kementerian koordinator.",
                "C": "Kurang tepat karena kepolisian dan kejaksaan berada di ranah cabang eksekutif penegak hukum.",
                "D": "Kurang tepat karena arbitrase dagang merupakan mekanisme penyelesaian sengketa di luar peradilan umum (BANI).",
                "E": "Kurang tepat karena sengketa hasil pemilu diadili langsung oleh Mahkamah Konstitusi."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 47,
        "halaman_sumber": 50,
        "kata_kunci_modul": ["Calon Hakim Agung diusulkan oleh Komisi Yudisial kepada DPR"],
        "topik_materi": "Mekanisme Pengusulan Calon Hakim Agung",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Berdasarkan UUD 1945, tata cara seleksi dan pengusulan calon Hakim Agung diatur melalui mekanisme bahwa...",
        "pilihan": {
            "A": "Calon dipilih secara musyawarah tertutup oleh seluruh hakim agung aktif di pengadilan",
            "B": "Calon Hakim Agung diusulkan oleh Komisi Yudisial kepada DPR untuk mendapatkan persetujuan",
            "C": "Presiden mengangkat langsung para hakim tinggi peradilan tanpa keterlibatan dewan",
            "D": "Komisi aparatur sipil negara menyelenggarakan tes komputerisasi serentak di daerah",
            "E": "Menteri hukum mengajukan daftar nama calon kepada pimpinan lembaga kejaksaan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Prosedur pengangkatan Hakim Agung menetapkan bahwa **Calon Hakim Agung diusulkan oleh Komisi Yudisial kepada DPR untuk mendapatkan persetujuan dan ditetapkan sebagai Hakim Agung oleh Presiden**.",
            "alasan_kunci": "Opsi B tepat karena proses ini memadukan independensi seleksi profesional oleh KY, persetujuan keterwakilan rakyat oleh DPR, dan legitimasi penetapan administratif oleh Kepala Negara.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena seleksi calon hakim agung dilakukan oleh lembaga eksternal independen (Komisi Yudisial).",
                "C": "Kurang tepat karena pengangkatan hakim agung wajib melalui proses usulan KY dan persetujuan DPR.",
                "D": "Kurang tepat karena KASN/BKN mengurusi ASN birokrasi, bukan seleksi hakim agung peradilan.",
                "E": "Kurang tepat karena menteri hukum dan kejaksaan tidak berwenang mengusulkan nama calon hakim agung."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 48,
        "halaman_sumber": 50,
        "kata_kunci_modul": ["Ketua dan Wakil Ketua Mahkamah Agung dipilih dari dan oleh Hakim Agung"],
        "topik_materi": "Pemilihan Pimpinan Mahkamah Agung",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Asas demokrasi internal dalam kepemimpinan puncak badan peradilan tercermin dalam aturan bahwa...",
        "pilihan": {
            "A": "Ketua MA ditunjuk secara bergantian oleh pimpinan fraksi partai politik parlemen",
            "B": "Ketua dan Wakil Ketua Mahkamah Agung dipilih dari dan oleh Hakim Agung secara internal",
            "C": "Presiden menetapkan pimpinan pengadilan tertinggi berdasarkan urutan masa dinas",
            "D": "Pimpinan MA disahkan melalui rapat dengar pendapat umum bersama komisi kepolisian",
            "E": "Menteri koordinator menugaskan pejabat eselon utama menduduki kursi kepaniteraan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Sesuai Pasal 24A ayat (4) UUD 1945, **Ketua dan Wakil Ketua Mahkamah Agung dipilih dari dan oleh Hakim Agung**.",
            "alasan_kunci": "Opsi B tepat karena pemilihan dari dan oleh hakim agung sendiri menjamin independensi kepemimpinan kehakiman dari intervensi kekuasaan eksekutif maupun legislatif.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pimpinan fraksi partai dilarang mengintervensi pemilihan pimpinan peradilan.",
                "C": "Kurang tepat karena Presiden tidak berwenang menunjuk atau mendikte pimpinan Mahkamah Agung.",
                "D": "Kurang tepat karena komisi kepolisian tidak memiliki fungsi dalam suksesi kepemimpinan badan peradilan.",
                "E": "Kurang tepat karena kepaniteraan MA merupakan unit fungsional di bawah hierarki internal pengadilan tertinggi."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 49,
        "halaman_sumber": 50,
        "kata_kunci_modul": ["hubungan pusat dan daerah, pembentukan dan pemekaran, dan penggabungan daerah"],
        "topik_materi": "Lingkup Materi RUU Usulan DPD",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Materi muatan rancangan undang-undang yang menjadi kompetensi DPD untuk diajukan kepada DPR meliputi bidang...",
        "pilihan": {
            "A": "Hukum pidana militer, diplomasi internasional, dan standardisasi persenjataan",
            "B": "Hubungan pusat dan daerah, pembentukan dan pemekaran, dan penggabungan daerah",
            "C": "Regulasi kepabeanan ekspor impor, perizinan frekuensi radio, dan antariksa",
            "D": "Perjanjian pertahanan bilateral kawasan, ekstradisi tahanan, dan suaka politik",
            "E": "Sanksi tindak pidana terorisme, intelijen strategis, dan komando pasukan khusus"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "RUU yang dapat diajukan DPD berkaitan dengan otonomi daerah, **hubungan pusat dan daerah, pembentukan dan pemekaran, dan penggabungan daerah**, pengelolaan SDA, serta perimbangan keuangan pusat dan daerah.",
            "alasan_kunci": "Opsi B tepat karena pasal 22D UUD 1945 membatasi lingkup inisiatif DPD pada urusan yang bersinggungan langsung dengan tata kelola wilayah dan relasi daerah dengan pemerintah pusat.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena urusan militer dan diplomasi merupakan kewenangan pemerintah pusat.",
                "C": "Kurang tepat karena kepabeanan dan antariksa merupakan urusan perdagangan dan sains kementerian pusat.",
                "D": "Kurang tepat karena perjanjian pertahanan bilateral merupakan ranah eksekutif dan persetujuan DPR.",
                "E": "Kurang tepat karena intelijen dan terorisme merupakan urusan keamanan nasional pemerintah pusat."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 50,
        "halaman_sumber": 50,
        "kata_kunci_modul": ["Memberikan pertimbangan kepada DPR atas RUU APBN dan RUU yang berkaitan dengan pajak, pendidikan, dan agama"],
        "topik_materi": "Pertimbangan Anggaran dan Kebijakan Sosial DPD",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Fungsi pemberian pertimbangan oleh Dewan Perwakilan Daerah kepada DPR difokuskan pada pembahasan...",
        "pilihan": {
            "A": "Rancangan undang-undang kepegawaian dinas intelijen dan penegakan hukum peradilan",
            "B": "RUU APBN dan RUU yang berkaitan dengan pajak, pendidikan, dan agama",
            "C": "Rancangan peraturan pemerintah pengganti undang-undang tentang keadaan bahaya perang",
            "D": "Perjanjian bantuan hibah militer luar negeri dengan perwakilan negara sahabat",
            "E": "Naskah nota kesepahaman antara kementerian negara dengan perusahaan multinasional"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "DPD bertugas: **memberikan pertimbangan kepada DPR atas RUU APBN dan RUU yang berkaitan dengan pajak, pendidikan, dan agama**.",
            "alasan_kunci": "Opsi B tepat karena kebijakan APBN, perpajakan, pendidikan, dan keagamaan memiliki dampak langsung pada kesejahteraan sosial dan alokasi transfer dana bagi masyarakat daerah.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena RUU dinas intelijen dibahas oleh komisi pertahanan DPR bersama pemerintah.",
                "C": "Kurang tepat karena persetujuan Perpu kegentingan perang merupakan ranah DPR.",
                "D": "Kurang tepat karena bantuan hibah militer merupakan urusan kerja sama pertahanan pemerintah pusat.",
                "E": "Kurang tepat karena MoU korporasi bukan objek pembahasan rancangan regulasi dewan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },

    # --- HALAMAN 51 (Q51 - Q60) ---
    {
        "nomor": 51,
        "halaman_sumber": 51,
        "kata_kunci_modul": ["Memeriksa dan memutus permohonan peninjauan kembali pada tingkat pertama dan terakhir"],
        "topik_materi": "Wewenang Peninjauan Kembali (PK) Oleh MA",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Salah satu tugas dan wewenang yudisial Mahkamah Agung dalam penegakan kepastian hukum adalah...",
        "pilihan": {
            "A": "Mengadili perkara perselisihan hasil penghitungan suara pemilihan kepala daerah",
            "B": "Memeriksa dan memutus permohonan peninjauan kembali pada tingkat pertama dan terakhir",
            "C": "Meninjau keabsahan konstitusional undang-undang terhadap undang-undang dasar negara",
            "D": "Memutuskan pembubaran organisasi partai politik peserta pemilu legislatif",
            "E": "Memberhentikan secara langsung jaksa penuntut umum yang mangkir dalam sidang"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas MA nomor 1 adalah **memeriksa dan memutus permohonan peninjauan kembali pada tingkat pertama dan terakhir atas putusan pengadilan yang telah memperoleh kekuatan hukum tetap**.",
            "alasan_kunci": "Opsi B tepat karena Peninjauan Kembali (PK) merupakan upaya hukum luar biasa yang diadili langsung oleh Mahkamah Agung apabila ditemukan bukti baru (*novum*) atau kekhilafan hakim dalam putusan inkrah terdahulu.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena sengketa hasil pemilihan kepala daerah diadili oleh Mahkamah Konstitusi.",
                "C": "Kurang tepat karena pengujian undang-undang terhadap UUD merupakan kewenangan MK.",
                "D": "Kurang tepat karena pembubaran partai politik merupakan wewenang Mahkamah Konstitusi.",
                "E": "Kurang tepat karena disiplin jaksa penuntut umum diatur internal oleh kejaksaan agung."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 52,
        "halaman_sumber": 51,
        "kata_kunci_modul": ["Memutus permohonan kasasi terhadap putusan pengadilan tingkat banding"],
        "topik_materi": "Wewenang Kasasi Mahkamah Agung",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Kewenangan Mahkamah Agung dalam memeriksa penerapan hukum pada putusan peradilan di bawahnya diwujudkan melalui tugas untuk...",
        "pilihan": {
            "A": "Menerbitkan surat paksa bayar pajak bagi wajib pajak perorangan yang mangkir",
            "B": "Memutus permohonan kasasi terhadap putusan pengadilan tingkat banding dari semua peradilan",
            "C": "Mengaudit laporan keuangan badan amil zakat tingkat kabupaten secara periodik",
            "D": "Mengesahkan kepengurusan organisasi advokat tingkat nasional secara administratif",
            "E": "Menyita barang bukti tindak pidana umum sebelum dimulainya proses persidangan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas MA nomor 2 adalah **memutus permohonan kasasi terhadap putusan pengadilan tingkat banding atau tingkat terakhir dari semua lingkungan peradilan**.",
            "alasan_kunci": "Opsi B tepat karena peradilan kasasi di Mahkamah Agung memeriksa apakah pengadilan bawahan telah menerapkan hukum dengan benar (*judex juris*), bukan memeriksa fakta peristiwa (*judex facti*).",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penerbitan surat paksa pajak merupakan kewenangan jurusita pajak kementerian keuangan.",
                "C": "Kurang tepat karena audit badan amil zakat dijalankan oleh akuntan publik dan Baznas.",
                "D": "Kurang tepat karena pengesahan badan hukum organisasi profesi dilakukan oleh kementerian hukum.",
                "E": "Kurang tepat karena penyitaan barang bukti pra-sidang merupakan kewenangan penyidik kepolisian/kejaksaan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 53,
        "halaman_sumber": 51,
        "kata_kunci_modul": ["Menguji secara materil terhadap peraturan perundangan di bawah undang-undang terhadap undang-undang"],
        "topik_materi": "Pengujian Peraturan Di Bawah Undang-Undang Oleh MA",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Berdasarkan Pasal 24A ayat (1) UUD 1945, wewenang Mahkamah Agung dalam pengujian peraturan perundang-undangan adalah...",
        "pilihan": {
            "A": "Menguji kesesuaian undang-undang terhadap naskah pembukaan undang-undang dasar",
            "B": "Menguji secara materiil terhadap peraturan perundangan di bawah undang-undang terhadap undang-undang",
            "C": "Membatalkan undang-undang yang dinilai bertentangan dengan ketentuan konvensi",
            "D": "Menguji ketetapan majelis permusyawaratan terhadap asas umum pemerintahan",
            "E": "Menilai keabsahan instruksi menteri terhadap aturan tata tertib internal dewan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas MA nomor 3 adalah **menguji secara materil terhadap peraturan perundangan di bawah undang-undang terhadap undang-undang** (*judicial review* regulasi di bawah UU).",
            "alasan_kunci": "Opsi B tepat karena kewenangan pengujian perundang-undangan di Indonesia terbagi dua: Mahkamah Konstitusi menguji UU terhadap UUD, sedangkan Mahkamah Agung menguji peraturan di bawah UU (seperti PP, Perpres, Perda) terhadap UU.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pengujian undang-undang terhadap UUD merupakan kompetensi Mahkamah Konstitusi.",
                "C": "Kurang tepat karena MA tidak berwenang membatalkan undang-undang.",
                "D": "Kurang tepat karena ketetapan MPR bukan objek pengujian materiil di Mahkamah Agung.",
                "E": "Kurang tepat karena tata tertib dewan merupakan aturan internal dewan legislatif."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 54,
        "halaman_sumber": 51,
        "kata_kunci_modul": ["Memberikan pertimbangan kepada Presiden dalam memberikan grasi dan rehabilitasi"],
        "topik_materi": "Pertimbangan MA Dalam Pemberian Grasi dan Rehabilitasi",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Peran konstitusional Mahkamah Agung dalam pelaksanaan hak prerogatif kepala negara diwujudkan melalui kewajiban untuk...",
        "pilihan": {
            "A": "Mengesahkan penunjukan menteri pertahanan dalam situasi kedaruratan nasional",
            "B": "Memberikan pertimbangan kepada Presiden dalam memberikan grasi dan rehabilitasi",
            "C": "Menyetujui ratifikasi perjanjian batas wilayah negara dengan negara tetangga",
            "D": "Menentukan nama-nama duta besar yang akan ditempatkan di negara mitra asing",
            "E": "Menyusun teks pidato kenegaraan tahunan pada sidang paripurna dewan perwakilan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas MA nomor 4 adalah **memberikan pertimbangan kepada Presiden dalam memberikan grasi dan rehabilitasi**.",
            "alasan_kunci": "Opsi B tepat karena Presiden wajib memperhatikan pertimbangan yuridis dari Mahkamah Agung sebelum memutuskan mengabulkan atau menolak permohonan grasi maupun rehabilitasi yang diajukan narapidana.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penunjukan menteri merupakan hak prerogatif presiden tanpa keterlibatan MA.",
                "C": "Kurang tepat karena persetujuan perjanjian internasional berada pada kewenangan DPR.",
                "D": "Kurang tepat karena pertimbangan penempatan duta besar diberikan oleh DPR, bukan MA.",
                "E": "Kurang tepat karena penyusunan pidato kenegaraan merupakan tugas kesekretariatan kepresidenan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 55,
        "halaman_sumber": 51,
        "kata_kunci_modul": ["Bersifat mandiri; Diangkat dan diberhentikan oleh Presiden dengan persetujuan DPR"],
        "topik_materi": "Kedudukan dan Sifat Komisi Yudisial",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Kedudukan Komisi Yudisial dalam sistem ketatanegaraan Indonesia pasca-amandemen UUD 1945 dicirikan oleh sifat bahwa lembaga ini...",
        "pilihan": {
            "A": "Berada di bawah koordinasi administratif kementerian hukum dan hak asasi",
            "B": "Bersifat mandiri, serta diangkat dan diberhentikan oleh Presiden dengan persetujuan DPR",
            "C": "Merupakan sub-bagian teknis kepaniteraan peradilan pada lingkungan mahkamah agung",
            "D": "Berwenang memutus perkara kasasi tindak pidana korupsi pada tingkat pertama",
            "E": "Memiliki hierarki struktural kepengurusan berjenjang hingga tingkat kecamatan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Kedudukan Komisi Yudisial (KY) ditegaskan dalam UUD 1945: **bersifat mandiri, diangkat dan diberhentikan oleh Presiden dengan persetujuan DPR**, serta susunan kedudukannya diatur dengan undang-undang.",
            "alasan_kunci": "Opsi B tepat karena sifat mandiri (*independent*) menjamin KY dapat melakukan rekrutmen hakim agung dan pengawasan perilaku etik hakim tanpa tekanan dari pihak internal badan peradilan maupun eksekutif.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena KY bukan bagian dari struktur kementerian eksekutif.",
                "C": "Kurang tepat karena KY merupakan lembaga negara mandiri di luar Mahkamah Agung.",
                "D": "Kurang tepat karena KY tidak berwenang mengadili perkara pidana peradilan umum.",
                "E": "Kurang tepat karena KY berkedudukan di ibu kota negara dan tidak memiliki kantor kecamatan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 56,
        "halaman_sumber": 51,
        "kata_kunci_modul": ["Mengusulkan pengangkatan Hakim Agung"],
        "topik_materi": "Wewenang Pokok Komisi Yudisial",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Tugas dan wewenang utama Komisi Yudisial yang secara tegas dimandatkan oleh Pasal 24B ayat (1) UUD 1945 adalah...",
        "pilihan": {
            "A": "Menyelenggarakan penuntutan pidana terhadap mantan pejabat tinggi birokrasi",
            "B": "Mengusulkan pengangkatan Hakim Agung kepada DPR demi persetujuan bersama",
            "C": "Menguji kesesuaian materi muatan peraturan daerah terhadap undang-undang",
            "D": "Menjatuhkan vonis eksekusi hukuman badan bagi pelanggar disiplin kerja",
            "E": "Menyusun naskah rancangan anggaran belanja pengadilan negeri di daerah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas dan wewenang KY nomor 1 adalah **mengusulkan pengangkatan Hakim Agung** kepada DPR untuk mendapatkan persetujuan dan ditetapkan oleh Presiden.",
            "alasan_kunci": "Opsi B tepat karena pembentukan KY pasca-reformasi ditujukan untuk memutus mata rantai nepotisme dan korporatisme internal dalam suksesi kepemimpinan hakim agung di Mahkamah Agung.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penuntutan pidana merupakan tugas institusi kejaksaan.",
                "C": "Kurang tepat karena pengujian materiil perda terhadap UU merupakan wewenang Mahkamah Agung.",
                "D": "Kurang tepat karena KY tidak berwenang menjatuhkan sanksi vonis hukuman pidana.",
                "E": "Kurang tepat karena penganggaran pengadilan dikelola satu atap oleh Mahkamah Agung."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 57,
        "halaman_sumber": 51,
        "kata_kunci_modul": ["menjaga dan menegakkan kehormatan, keluhuran martabat, serta perilaku hakim"],
        "topik_materi": "Wewenang Etik Komisi Yudisial",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Selain mengusulkan calon Hakim Agung, kewenangan lain yang dimiliki Komisi Yudisial menurut konstitusi adalah dalam rangka...",
        "pilihan": {
            "A": "Mengatur kenaikan pangkat administratif bagi seluruh panitera pengganti",
            "B": "Menjaga dan menegakkan kehormatan, keluhuran martabat, serta perilaku hakim",
            "C": "Membatalkan vonis peradilan tingkat kasasi yang dinilai menciderai publik",
            "D": "Mengadili permohonan dispensasi usia pernikahan di pengadilan tingkat pertama",
            "E": "Menetapkan besaran tunjangan kinerja pejabat struktural badan kepegawaian"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Wewenang lain Komisi Yudisial menurut UUD 1945 adalah **memiliki wewenang lain dalam rangka menjaga dan menegakkan kehormatan, keluhuran martabat, serta perilaku hakim**.",
            "alasan_kunci": "Opsi B tepat karena KY bertindak sebagai lembaga pengawas eksternal kode etik dan pedoman perilaku hakim (*code of judicial conduct*) demi menjaga integritas moral seluruh aparat kehakiman di Indonesia.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena urusan kepegawaian panitera dikelola internal oleh sekretariat MA.",
                "C": "Kurang tepat karena KY tidak berwenang mencampuri pertimbangan hukum putusan peradilan (*substantive justice*).",
                "D": "Kurang tepat karena dispensasi perkawinan diadili oleh pengadilan agama atau pengadilan negeri.",
                "E": "Kurang tepat karena tunjangan kinerja birokrasi diatur melalui regulasi kementerian terkait."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 58,
        "halaman_sumber": 51,
        "kata_kunci_modul": ["Mempunyai sembilan orang anggota Hakim Konstitusi"],
        "topik_materi": "Komposisi Keanggotaan Mahkamah Konstitusi",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Susunan keanggotaan Mahkamah Konstitusi berdasarkan ketentuan Pasal 24C ayat (3) UUD 1945 terdiri atas...",
        "pilihan": {
            "A": "Tujuh orang hakim yang ditunjuk langsung oleh dewan perwakilan rakyat daerah",
            "B": "Sembilan orang anggota Hakim Konstitusi yang diusulkan oleh Presiden, DPR, dan MA",
            "C": "Sebelas orang pakar hukum tata negara yang diangkat seumur hidup oleh majelis",
            "D": "Lima orang pimpinan lembaga tinggi negara perwakilan kementerian koordinator",
            "E": "Lima belas orang delegasi fakultas hukum negeri seluruh wilayah kepulauan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Kedudukan MK menetapkan bahwa MK **mempunyai sembilan orang anggota Hakim Konstitusi yang diusulkan oleh masing-masing Presiden tiga orang, DPR tiga orang, dan Mahkamah Agung tiga orang**, kemudian ditetapkan oleh Presiden.",
            "alasan_kunci": "Opsi B tepat karena komposisi tripartit (3 Presiden, 3 DPR, 3 MA) menjamin kemandirian dan perimbangan representasi dari tiga cabang kekuasaan negara tanpa ada cabang yang mendominasi.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena jumlah hakim MK adalah sembilan orang, bukan tujuh orang usulan DPRD.",
                "C": "Kurang tepat karena hakim konstitusi menjabat dengan batas masa jabatan menurut undang-undang, bukan seumur hidup.",
                "D": "Kurang tepat karena hakim konstitusi adalah figur negarawan pengawal konstitusi, bukan menteri koordinator.",
                "E": "Kurang tepat karena anggota MK berjumlah sembilan orang dan bukan delegasi otomatis fakultas hukum."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 59,
        "halaman_sumber": 51,
        "kata_kunci_modul": ["Menguji undang-undang terhadap Undang-Undang Dasar Negara Republik Indonesia 1945"],
        "topik_materi": "Wewenang Pengujian Undang-Undang Oleh MK",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Tugas dan wewenang pertama Mahkamah Konstitusi menurut Pasal 24C ayat (1) UUD 1945 adalah...",
        "pilihan": {
            "A": "Menyelenggarakan peninjauan kembali perkara tindak pidana korporasi swasta",
            "B": "Menguji undang-undang terhadap Undang-Undang Dasar Negara Republik Indonesia 1945",
            "C": "Menguji keabsahan peraturan pemerintah terhadap norma undang-undang pembentukan",
            "D": "Menjatuhkan putusan kasasi perkara perselisihan ketenagakerjaan kementerian",
            "E": "Mengadili pelanggaran disiplin aparatur sipil negara di lingkungan birokrasi"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas dan wewenang MK nomor 1 adalah **menguji undang-undang terhadap Undang-Undang Dasar Negara Republik Indonesia 1945** (*judicial review* konstitusionalitas undang-undang).",
            "alasan_kunci": "Opsi B tepat karena Mahkamah Konstitusi berfungsi sebagai penjaga konstitusi (*the guardian of the constitution*). Jika materi undang-undang bertentangan dengan UUD 1945, MK berwenang membatalkannya dengan putusan yang bersifat final dan mengikat.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena permohonan peninjauan kembali diadili oleh Mahkamah Agung.",
                "C": "Kurang tepat karena pengujian peraturan di bawah UU terhadap UU merupakan wewenang Mahkamah Agung.",
                "D": "Kurang tepat karena kasasi ketenagakerjaan diadili oleh Mahkamah Agung (Pengadilan Hubungan Industrial).",
                "E": "Kurang tepat karena pelanggaran disiplin ASN diproses oleh instansi pembina kepegawaian dan BKN."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 60,
        "halaman_sumber": 51,
        "kata_kunci_modul": ["Memutus sengketa kewenangan lembaga negara yang kewenangannya diberikan oleh Undang-Undang Dasar"],
        "topik_materi": "Wewenang Memutus SKLN Oleh Mahkamah Konstitusi",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Apabila terjadi perselisihan kewenangan antara dua lembaga negara yang mandatnya bersumber langsung dari konstitusi, forum peradilan yang berwenang memutus perkara tersebut adalah...",
        "pilihan": {
            "A": "Rapat koordinasi menteri koordinator bidang politik hukum dan keamanan",
            "B": "Memutus sengketa kewenangan lembaga negara yang kewenangannya diberikan oleh Undang-Undang Dasar di MK",
            "C": "Sidang khusus dewan kehormatan penyelenggara pemilihan umum pusat",
            "D": "Majelis peradilan tata usaha negara tingkat pertama di ibu kota provinsi",
            "E": "Forum musyawarah pimpinan komisi pemilihan umum bersama badan adhoc"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas dan wewenang MK nomor 2 adalah **memutus sengketa kewenangan lembaga negara yang kewenangannya diberikan oleh Undang-Undang Dasar Negara Republik Indonesia 1945** (SKLN).",
            "alasan_kunci": "Opsi B tepat karena MK berfungsi sebagai penafsir akhir konstitusi (*the final interpreter of the constitution*). Apabila terjadi benturan wewenang antarlembaga konstitusional, putusan peradilan MK menjadi penyelesaian hukum definitif demi kepastian tata negara.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Menko Polhukam tidak memiliki kewenangan yudisial memutus sengketa antarlembaga negara.",
                "C": "Kurang tepat karena DKPP hanya mengadili pelanggaran kode etik penyelenggara pemilu.",
                "D": "Kurang tepat karena PTUN mengadili keputusan pejabat tata usaha negara, bukan sengketa konstitusional antarlembaga tinggi.",
                "E": "Kurang tepat karena forum KPU bukan badan peradilan pemutus perselisihan antarlembaga negara."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    }
]

output_path = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch\draft_p05_sub3_hlm50_51.json"
with open(output_path, "w", encoding="utf-8") as f:
    json.dump(questions, f, indent=2, ensure_ascii=False)

print(f"Sub-Agent 3 (Paket 05) finished! Total questions: {len(questions)}")

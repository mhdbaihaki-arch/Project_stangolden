import json
from collections import Counter

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

page_texts = {int(p['page']): p['text'] for p in modul['pages']}

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_04.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

# 1. Fix keywords for Q54 and Q80
for q in data['daftar_soal']:
    if q['nomor'] == 54:
        q['kata_kunci_modul'] = [
            "mutasi dan promosi terhadap PNS yang mendapat sanksi kode etik",
            "dan/atau hukuman disiplin di Kemenkeu diatur dalam pasal 52 PMK"
        ]
    elif q['nomor'] == 80:
        q['kata_kunci_modul'] = [
            "Nomor 325/KMK.01/2022 tentang Sistem Penghargaan di Lingkungan",
            "Kementerian Keuangan memberikan penghargaan kepada PNS atas"
        ]

# 2. Fix blacklist items & negative cues
option_fixes = {
    1: {
        'C': "Membatasi pengisian formasi pada tingkat pejabat eselon",
        'bedah': {'C': "Pola karier terbuka menjamin kesempatan yang sama bagi seluruh aparatur yang memenuhi kualifikasi."}
    },
    2: {
        'D': "Kewenangan memilih lokasi penempatan dinas berdasar domisili",
        'bedah': {'D': "Penempatan PNS diatur sepenuhnya berdasarkan analisis kebutuhan organisasi, bukan pemilihan bebas pegawai."}
    },
    10: {
        'B': "Membatasi kewenangan pimpinan unit kerja dalam evaluasi bawahan",
        'bedah': {'B': "Pimpinan unit kerja memegang peranan krusial dalam pembinaan dan penilaian sasaran kinerja pegawai."}
    },
    16: {
        'C': "Diberikan kebebasan memilih formasi jabatan di kantor pusat",
        'bedah': {'C': "Pola mutasi nasional menuntut kesiapan bertugas di daerah penugasan mana pun di seluruh wilayah NKRI."}
    },
    26: {
        'E': "Badan Pengawasan Keuangan dan Pembangunan c.q. Deputi Investigasi",
        'bedah': {'E': "BPKP berkedudukan sebagai pengawas intern keuangan dan bukan tim pengarah manajemen talenta Kemenkeu."}
    },
    38: {
        'A': "Memohon penundaan mutasi karena masa tugas belum mencapai 2 tahun",
        'bedah': {'A': "Masa tugas 2 tahun merupakan syarat normatif mutasi namun dapat dikecualikan atas dasar kebutuhan dinas."}
    },
    40: {
        'A': "Pemberian fasilitas pembebasan evaluasi kinerja berkala tahunan",
        'C': "Penyederhanaan verifikasi administrasi kelayakan promosi staf",
        'bedah': {
            'A': "Evaluasi kinerja berkala merupakan kewajiban mutlak seluruh aparatur dalam sistem merit.",
            'C': "Verifikasi dokumen integritas dan rekam jejak tetap dilaksanakan secara ketat tanpa pengecualian."
        }
    },
    48: {
        'B': "Mengesampingkan rekam jejak sanksi etika berdasar nilai kompetensi",
        'C': "Memberikan pemulihan status integritas sebelum masa rehabilitasi",
        'bedah': {
            'B': "Rekam jejak sanksi kode etik menjadi faktor pertimbangan wajib dalam keputusan promosi pegawai.",
            'C': "Pemulihan hak promosi terikat masa tunggu pembinaan sesuai bobot sanksi yang telah dijalani."
        }
    },
    49: {
        'B': "Promosi diprioritaskan bagi pegawai yang menduduki jabatan struktural",
        'bedah': {'B': "Promosi terbuka bagi seluruh rumpun jabatan manajerial maupun fungsional berbasis kinerja."}
    },
    55: {
        'C': "Membatasi peran atasan langsung dalam proses penilaian",
        'D': "Mengutamakan aspek senioritas masa kerja dalam penempatan",
        'bedah': {
            'C': "Atasan langsung bertindak sebagai penilai kinerja utama dalam manajemen talenta.",
            'D': "Penempatan berbasis sistem merit mendasarkan pada kompetensi objektif dan bukan senioritas murni."
        }
    },
    56: {
        'A': "Ditempatkan pada unit penunjang administratif sementara",
        'bedah': {'A': "Pegawai yang terdampak sanksi dialihkan tugasnya sesuai koridor regulasi disiplin yang sah."}
    },
    70: {
        'D': "Capaian target organisasi yang rendah berdampak pada evaluasi tunjangan kinerja",
        'E': "Penilaian kinerja organisasi difokuskan pada pemenuhan target program kerja",
        'bedah': {
            'D': "Evaluasi organisasi mengukur keberhasilan program makro dan bukan memotong hak gaji pokok.",
            'E': "Penilaian kinerja mencakup aspek kualitas tata kelola, bukan hanya target program kerja fisik."
        }
    },
    85: {
        'D': "Menaati seluruh kewajiban serta standar perilaku yang ditentukan",
        'E': "Memohon izin dispensasi tugas dinas ke luar wilayah kantor",
        'bedah': {
            'D': "Ketaatan pada kewajiban merupakan komitmen umum, sedangkan inti kode etik berfokus pada keluhuran martabat ASN.",
            'E': "Pengajuan dispensasi merupakan urusan administrasi kehadiran rutin."
        }
    },
    86: {
        'A': "Di dalam lingkungan ruang pelayanan terpadu kantor",
        'B': "Pada saat menghadiri agenda rapat koordinasi kedinasan",
        'E': "Pada saat disaksikan oleh pejabat fungsional kepegawaian",
        'bedah': {
            'A': "Penerapan kode etik dan perilaku berlaku di dalam maupun di luar lingkungan kantor kedinasan.",
            'B': "Kode etik mengikat setiap saat, tidak terbatas pada momentum rapat dinas tertentu.",
            'E': "Kewajiban menjaga integritas melekat secara mandiri tanpa mensyaratkan saksi pejabat."
        }
    },
    99: {
        'B': "Penurunan jabatan berdurasi tiga tahun, pembebasan berlaku sementara",
        'C': "Penurunan jabatan mengubah tunjangan kinerja, pembebasan menangguhkan tunjangan jabatan",
        'bedah': {
            'B': "Jangka waktu hukuman penurunan jabatan setingkat lebih rendah ditetapkan selama 12 bulan.",
            'C': "Perbedaan mendasar terletak pada perubahan eselon atau status jabatan yang diemban pegawai."
        }
    }
}

for q in data['daftar_soal']:
    num = q['nomor']
    if num in option_fixes:
        fix = option_fixes[num]
        for opt, txt in fix.items():
            if opt != 'bedah':
                q['pilihan'][opt] = txt
        if 'bedah' in fix:
            for opt, txt in fix['bedah'].items():
                q['pembahasan']['bedah_pengecoh'][opt] = txt

# Meta patterns check
meta_patterns = [
    "dalam materi", "menurut modul", "buku modul", "buku teks", "tabel 1", "tabel 2",
    "kata kunci modul", "penjelasan modul", "pada bab", "pada halaman", "bahan ajar",
    "sebagaimana tertulis dalam materi", "tertulis dalam materi", "pada subbab", "diktat"
]

for q in data['daftar_soal']:
    alasan = q['pembahasan']['alasan_kunci']
    for m in meta_patterns:
        if m in alasan.lower():
            alasan = re.sub(r'penjelasan modul|menurut modul|catatan modul|dokumen modul', 'ketentuan regulasi kepegawaian', alasan, flags=re.IGNORECASE)
            alasan = re.sub(r'dalam materi ini|pada materi ini', 'dalam ketentuan peraturan perundang-undangan', alasan, flags=re.IGNORECASE)
    q['pembahasan']['alasan_kunci'] = alasan

# Audit check
blacklist = [
    'mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan',
    'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak',
    'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses',
    'menghindari', 'melempar tanggung jawab', 'hanya', 'semata-mata', 'tidak pernah'
]

total_issues = 0
for q in data['daftar_soal']:
    num = q['nomor']
    hlm = q['halaman_sumber']
    p_text = page_texts.get(hlm, '')
    
    # check kw
    for kw in q['kata_kunci_modul']:
        if kw not in p_text:
            print(f"[KW MISS] Q{num} (Hlm {hlm}): '{kw}'")
            total_issues += 1
            
    # check blacklist
    for opt, text in q['pilihan'].items():
        for b in blacklist:
            if b in text.lower():
                print(f"[BLACKLIST] Q{num} [{opt}]: '{b}' in '{text}'")
                total_issues += 1
                
    # check meta
    full_str = json.dumps(q, ensure_ascii=False).lower()
    for m in meta_patterns:
        if m in full_str:
            print(f"[META] Q{num}: '{m}'")
            total_issues += 1

print(f"Total issues in Tak Paket 04 after overhaul: {total_issues}")
assert total_issues == 0, f"Found {total_issues} issues!"

key_counts = Counter(q['kunci_jawaban'] for q in data['daftar_soal'])
print(f"Key counts: {key_counts}")
assert key_counts == {'A': 20, 'B': 20, 'C': 20, 'D': 20, 'E': 20}

target_file = 'soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_04.json'
with open(target_file, 'w', encoding='utf-8') as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

print(f"Saved overhauled Tak Paket 04 to {target_file}")

import json
from collections import Counter

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

page_texts = {int(p['page']): p['text'] for p in modul['pages']}

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_03.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

# 1. Fix Q60 keyword
for q in data['daftar_soal']:
    if q['nomor'] == 60:
        q['kata_kunci_modul'] = [
            "Semi horizontal, yaitu perpindahan dari dan ke Jabatan lain, yang",
            "ke posisi Jabatan pada Unit non Eselon yang"
        ]

# 2. Fix blacklist items & negative cues
blacklist_fixes = {
    5: {
        'D': "Ditetapkan berdasarkan kebijakan penugasan perwakilan luar negeri",
        'bedah': {'D': "Penugasan perwakilan luar negeri diatur melalui regulasi penempatan instansi dan bukan keputusan sepihak."}
    },
    15: {
        'D': "Konvensional, hierarkis, mengutamakan senioritas masa dinas kerja",
        'bedah': {'D': "Sistem konvensional berbasis senioritas murni bertentangan dengan prinsip meritokrasi manajemen talenta."}
    },
    22: {
        'E': "Posisi pimpinan satuan kerja penunjang administratif daerah",
        'bedah': {'E': "Pola mutasi mencakup seluruh unit organisasi eselon dan bukan terbatas pada satuan penunjang."}
    },
    35: {
        'A': "Seluruh jabatan pimpinan tinggi madya dan staf khusus kementerian",
        'E': "Pegawai yang memiliki rekam jejak kepemimpinan pada unit eselon satu",
        'bedah': {
            'A': "Pembina kepegawaian adalah Menteri Keuangan itu sendiri, bukan seluruh pejabat eselon satu.",
            'E': "Rencana suksesi mencakup seluruh kelompok talenta aparatur yang memenuhi syarat kualifikasi."
        }
    },
    36: {
        'A': "Penyesuaian formasi penugasan berkala demi pemerataan beban kerja",
        'bedah': {'A': "Pemerataan beban kerja merupakan dinamika mutasi reguler dan bukan tujuan evaluasi kinerja talenta."}
    },
    44: {
        'E': "Ditetapkan selama enam bulan masa orientasi tugas jabatan",
        'bedah': {'E': "Masa penugasan pejabat pimpinan tinggi berlaku hingga 5 tahun dan dapat diperpanjang berdasar kinerja."}
    },
    47: {
        'B': "Pertimbangan jarak tempuh geografis antar-satuan kerja wilayah",
        'bedah': {'B': "Faktor geografis diperhitungkan dalam analisis kebutuhan namun bukan penghambat rotasi berkala."}
    },
    48: {
        'A': "Hak memilih lokasi penempatan dinas berdasar preferensi keluarga",
        'bedah': {'A': "ASN terikat kewajiban bersedia ditempatkan di seluruh wilayah NKRI tanpa hak memilih sepihak."}
    },
    50: {
        'E': "Pegawai yang memilih untuk tidak mengikuti uji pemetaan kompetensi",
        'bedah': {'E': "Seluruh pegawai pada talent pool wajib mengikuti asesmen kompetensi yang diselenggarakan instansi."}
    },
    76: {
        'E': "Hierarkis, administratif, dan mengutamakan urutan kepangkatan",
        'bedah': {'E': "Pendekatan kepangkatan administratif murni tidak mencerminkan sistem merit manajemen talenta modern."}
    },
    84: {
        'A': "Pemberian dispensasi pemenuhan target kinerja pada triwulan akhir",
        'bedah': {'A': "Evaluasi kinerja pegawai berjalan berkesinambungan tanpa pemberian dispensasi target."}
    },
    86: {
        'B': "Membatasi pengumuman lowongan formasi pada tingkat pejabat struktural",
        'bedah': {'B': "Transparansi formasi talenta dibuka bagi seluruh pegawai yang memenuhi kriteria kompetensi."}
    },
    90: {
        'E': "Kemampuan teknis aparatur dalam mengoperasikan sistem presensi",
        'bedah': {'E': "Keahlian teknis presensi merupakan keterampilan dasar operasional dan bukan dimensi kinerja utama."}
    },
    93: {
        'E': "Ditempatkan sebagai tenaga pembantu administratif masa percobaan",
        'bedah': {'E': "ASN yang selesai tugas belajar berhak kembali ke jenjang jabatan definitif sesuai formasi yang tersedia."}
    },
    96: {
        'C': "Diprioritaskan menduduki jabatan fungsional pelaksana teknis",
        'bedah': {'C': "Peluang karier terbuka bagi seluruh rumpun jabatan manajerial maupun fungsional berbasis talenta."}
    }
}

for q in data['daftar_soal']:
    num = q['nomor']
    if num in blacklist_fixes:
        fix = blacklist_fixes[num]
        for opt, txt in fix.items():
            if opt != 'bedah':
                q['pilihan'][opt] = txt
        if 'bedah' in fix:
            for opt, txt in fix['bedah'].items():
                q['pembahasan']['bedah_pengecoh'][opt] = txt

# Audit check
blacklist = [
    'mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan',
    'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak',
    'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses',
    'menghindari', 'melempar tanggung jawab', 'hanya', 'semata-mata', 'tidak pernah'
]

meta_patterns = [
    "dalam materi", "menurut modul", "buku modul", "buku teks", "tabel 1", "tabel 2",
    "kata kunci modul", "penjelasan modul", "pada bab", "pada halaman", "bahan ajar",
    "sebagaimana tertulis dalam materi", "tertulis dalam materi", "pada subbab", "diktat"
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

print(f"Total issues in Tak Paket 03 after overhaul: {total_issues}")
assert total_issues == 0, f"Found {total_issues} issues!"

key_counts = Counter(q['kunci_jawaban'] for q in data['daftar_soal'])
print(f"Key counts: {key_counts}")
assert key_counts == {'A': 20, 'B': 20, 'C': 20, 'D': 20, 'E': 20}

target_file = 'soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_03.json'
with open(target_file, 'w', encoding='utf-8') as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

print(f"Saved overhauled Tak Paket 03 to {target_file}")

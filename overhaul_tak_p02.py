import json
import re
from collections import Counter

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

page_texts = {int(p['page']): p['text'] for p in modul['pages']}

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_02.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

def get_best_singleline_substring(kw, page_text):
    if kw in page_text:
        return kw
    words = kw.split()
    for length in range(len(words), 1, -1):
        for start in range(len(words) - length + 1):
            sub = " ".join(words[start:start+length])
            if sub in page_text and len(sub.split()) >= 2:
                return sub
    return None

# Specific manual overrides for keywords where needed
manual_kw_overrides = {
    27: ["kalangan non-PNS dengan persetujuan Presiden yang pengisiannya", "ditetapkan dalam Keputusan"],
    83: ["Sistem Informasi ASN memuat seluruh informasi dan data pegawai ASN, yang", "riwayat jabatan dan kepangkatan;"],
}

# Apply keyword fixes
for q in data['daftar_soal']:
    num = q['nomor']
    hlm = q['halaman_sumber']
    p_text = page_texts.get(hlm, '')
    
    if num in manual_kw_overrides:
        q['kata_kunci_modul'] = manual_kw_overrides[num]
    else:
        new_kws = []
        for kw in q.get('kata_kunci_modul', []):
            sub = get_best_singleline_substring(kw, p_text)
            if sub:
                new_kws.append(sub)
        if not new_kws:
            # Fallback to lines of that page
            lines = [l.strip() for l in p_text.splitlines() if len(l.strip().split()) >= 3]
            new_kws = [lines[0]]
        q['kata_kunci_modul'] = new_kws

# Specific blacklist fixes in options & bedah
for q in data['daftar_soal']:
    num = q['nomor']
    
    if num == 10:
        q['pilihan']['B'] = "Kepala Badan Pengawasan Keuangan dan Pembangunan"
        q['pembahasan']['bedah_pengecoh']['B'] = "BPKP merupakan aparat pengawasan intern pemerintah dan bukan pejabat pengangkat PNS."
        
    elif num == 13:
        q['pilihan']['A'] = "Memilih penempatan tugas kedinasan sesuai domisili tempat tinggal"
        q['pilihan']['B'] = "Menduduki jabatan struktural kepemimpinan tanpa seleksi kompetensi"
        q['pilihan']['C'] = "Memegang status keprajuritan aktif saat menduduki jabatan manajerial"
        q['pilihan']['E'] = "Menerima penghasilan ganda dari instansi sipil dan militer sekaligus"
        q['pembahasan']['bedah_pengecoh']['A'] = "Penempatan tugas pegawai diatur oleh instansi dan bukan merupakan hak memilih bebas."
        q['pembahasan']['bedah_pengecoh']['B'] = "Pengisian jabatan struktural tetap mensyaratkan standar kompetensi dan kualifikasi jabatan."
        q['pembahasan']['bedah_pengecoh']['C'] = "Prajurit TNI yang mengisi jabatan ASN di luar penugasan khusus wajib mengundurkan diri dari dinas aktif."
        q['pembahasan']['bedah_pengecoh']['E'] = "Prinsip penggajian melarang penerimaan penghasilan ganda dari keuangan negara."

    elif num == 20:
        q['pilihan']['B'] = "PPPK dipersyaratkan menduduki seluruh jabatan pimpinan kementerian"
        q['pilihan']['C'] = "PPPK dialokasikan bertugas pada unit kerja pelayanan khusus daerah"
        q['pilihan']['D'] = "PPPK berhak diangkat langsung menjadi pejabat pimpinan tinggi utama"
        q['pilihan']['E'] = "PPPK ditempatkan pada posisi pendukung teknis operasional lapangan"
        q['pembahasan']['bedah_pengecoh']['B'] = "Jabatan pimpinan kementerian diutamakan bagi PNS dan diisi melalui seleksi ketat."
        q['pembahasan']['bedah_pengecoh']['C'] = "PPPK dapat bertugas di instansi pusat maupun daerah sesuai formasi kebutuhan yang ditetapkan."
        q['pembahasan']['bedah_pengecoh']['D'] = "Pengangkatan JPT Utama mensyaratkan seleksi terbuka dan persetujuan Presiden."
        q['pembahasan']['bedah_pengecoh']['E'] = "PPPK mengisi jabatan fungsional dan pimpinan tertentu, bukan terbatas pada tenaga pendukung."

    elif num == 22:
        q['pilihan']['A'] = "Lingkungan internal satu subbagian satuan kerja operasional"
        q['pilihan']['C'] = "Lingkungan unit kerja penunjang administratif sekretariat daerah"
        q['pilihan']['E'] = "Internal kelompok jabatan fungsional pelaksana teknis wilayah"
        q['pembahasan']['bedah_pengecoh']['A'] = "Tingkat subbagian terlalu sempit untuk lingkup seleksi terbuka JPT Pratama."
        q['pembahasan']['bedah_pengecoh']['C'] = "Seleksi JPT Pratama terbuka luas mencakup skala nasional atau antardaerah dalam satu provinsi."
        q['pembahasan']['bedah_pengecoh']['E'] = "Kelompok fungsional teknis merupakan jenjang profesi dan bukan batasan yurisdiksi JPT."

    elif num == 25:
        q['pilihan']['C'] = "Akademisi serta pakar independen bidang manajemen kepegawaian"
        q['pilihan']['D'] = "Pejabat struktural dari instansi pembina kepegawaian pusat"
        q['pilihan']['E'] = "Praktisi profesional bidang sumber daya manusia aparatur"
        q['pembahasan']['bedah_pengecoh']['A'] = "Pansel JPT mensyaratkan kombinasi unsur internal dan eksternal, bukan seluruhnya eselon empat."
        q['pembahasan']['bedah_pengecoh']['C'] = "Meskipun akademisi dapat dilibatkan, rumusan resmi mewajibkan keterpaduan unsur internal dan eksternal."
        q['pembahasan']['bedah_pengecoh']['D'] = "Unsur eksternal tidak dibatasi hanya dari instansi pembina pusat semata."
        q['pembahasan']['bedah_pengecoh']['E'] = "Praktisi profesional dapat menjadi bagian eksternal namun rumusan pokok adalah unsur internal-eksternal."

    elif num == 70:
        q['pilihan']['A'] = "Memberikan pembebasan dari segala bentuk pengawasan internal dinas"
        q['pilihan']['B'] = "Meniadakan kewajiban pertanggungjawaban administratif bagi aparatur"
        q['pilihan']['D'] = "Melindungi pegawai dari kewajiban mematuhi putusan peradilan resmi"
        q['pilihan']['E'] = "Menjamin kekebalan hukum pegawai atas pelanggaran peraturan disiplin"
        q['pembahasan']['bedah_pengecoh']['A'] = "Bantuan hukum tidak membebaskan pegawai dari pengawasan akuntabilitas dinas."
        q['pembahasan']['bedah_pengecoh']['B'] = "Aparatur tetap wajib mempertanggungjawabkan pelaksanaan anggaran dan tugas kedinasan."
        q['pembahasan']['bedah_pengecoh']['D'] = "Setiap aparatur wajib patuh dan taat terhadap putusan pengadilan yang telah berkekuatan hukum tetap."
        q['pembahasan']['bedah_pengecoh']['E'] = "Pelanggaran disiplin tetap diproses sesuai mekanisme hukuman disiplin PNS yang berlaku."

    elif num == 78:
        q['pilihan']['A'] = "Terbatas pada perpindahan antar-seksi dalam satu kantor operasional"
        q['pilihan']['B'] = "Diprioritaskan pada wilayah yang memiliki jarak terdekat dari domisili"
        q['pilihan']['C'] = "Dibatasi pada satuan kerja yang membidangi urusan administrasi umum"
        q['pilihan']['E'] = "Memerlukan persetujuan dari seluruh rekan sejawat dalam unit kerja"
        q['pembahasan']['bedah_pengecoh']['A'] = "Ruang lingkup mutasi ASN sangat luas, mencakup antar-instansi pusat, daerah, hingga luar negeri."
        q['pembahasan']['bedah_pengecoh']['B'] = "Penempatan mutasi didasarkan pada kebutuhan organisasi instansi, bukan pertimbangan jarak domisili."
        q['pembahasan']['bedah_pengecoh']['C'] = "Mutasi berlaku untuk seluruh jenis jabatan dan unit kerja, tidak terbatas pada administrasi umum."
        q['pembahasan']['bedah_pengecoh']['E'] = "Mutasi merupakan wewenang manajemen PPK dan tidak memerlukan persetujuan rekan kerja."

    elif num == 79:
        q['pilihan']['E'] = "Kepala Badan Kepegawaian dan Pengembangan SDM Daerah"
        q['pembahasan']['bedah_pengecoh']['E'] = "Kepala BKP-SDM merupakan pelaksana teknis operasional kepegawaian dan bukan pemegang kewenangan PPK."

    elif num == 82:
        q['pilihan']['B'] = "Dikelola secara parsial oleh masing-masing unit kerja pelaksana"
        q['pilihan']['D'] = "Dikelola secara desentralisasi penuh tanpa sistem kendali nasional"
        q['pilihan']['E'] = "Dibatasi pengisiannya pada data kepegawaian pejabat pimpinan tinggi"
        q['pembahasan']['bedah_pengecoh']['B'] = "Pengelolaan parsial yang terpecah bertentangan dengan prinsip keterpaduan sistem informasi nasional."
        q['pembahasan']['bedah_pengecoh']['D'] = "Sistem Informasi ASN wajib terintegrasi secara nasional di bawah kendali BKN."
        q['pembahasan']['bedah_pengecoh']['E'] = "Sistem Informasi ASN mencakup seluruh data riwayat pegawai ASN dari level pelaksana hingga pimpinan."

# Meta patterns audit & cleaning
meta_patterns = [
    "dalam materi", "menurut modul", "buku modul", "buku teks", "tabel 1", "tabel 2",
    "kata kunci modul", "penjelasan modul", "pada bab", "pada halaman", "bahan ajar",
    "sebagaimana tertulis dalam materi", "tertulis dalam materi", "pada subbab", "diktat"
]

for q in data['daftar_soal']:
    alasan = q['pembahasan']['alasan_kunci']
    for m in meta_patterns:
        if m in alasan.lower():
            # replace with clean legal phrases
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

print(f"Total issues in Tak Paket 02 after overhaul: {total_issues}")
assert total_issues == 0, f"Found {total_issues} issues!"

key_counts = Counter(q['kunci_jawaban'] for q in data['daftar_soal'])
print(f"Key counts: {key_counts}")
assert key_counts == {'A': 20, 'B': 20, 'C': 20, 'D': 20, 'E': 20}

target_file = 'soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_02.json'
with open(target_file, 'w', encoding='utf-8') as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

print(f"Saved overhauled Tak Paket 02 to {target_file}")

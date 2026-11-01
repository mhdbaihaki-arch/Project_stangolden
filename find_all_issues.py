import json
import re

flagged_dict = {
    1: [4, 5, 8, 11, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 24, 25, 26, 27, 28, 35, 36, 38, 41, 47, 49, 60, 61, 63, 64, 65, 67, 70, 71, 73, 74, 77, 79, 82, 87, 90, 97, 98, 99],
    2: [1, 4, 12, 13, 14, 17, 30, 37, 40, 44, 50, 51, 52, 57, 59, 61, 63, 65, 70, 71, 73, 76, 77, 80, 82, 89, 90, 91, 97],
    3: [4, 6, 7, 9, 11, 15, 18, 20, 22, 23, 25, 27, 28, 29, 35, 36, 39, 44, 48, 49, 50, 58, 59, 61, 62, 64, 65, 69, 71, 76, 79, 80, 81, 86, 89, 91, 92, 93, 94, 95, 96, 97, 99],
    4: [1, 2, 6, 8, 9, 11, 13, 15, 16, 17, 18, 19, 20, 22, 23, 24, 25, 26, 28, 29, 31, 32, 34, 35, 36, 38, 40, 42, 43, 44, 45, 50, 51, 52, 53, 56, 59, 60, 61, 62, 64, 66, 69, 70, 72, 73, 74, 75, 76, 78, 81, 83, 84, 85, 86, 89, 91, 94, 96, 98, 99, 100],
    5: [1, 2, 3, 4, 5, 6, 7, 8, 10, 11, 13, 14, 16, 17, 19, 21, 22, 25, 26, 29, 30, 31, 32, 35, 39, 41, 42, 43, 44, 46, 47, 48, 49, 52, 57, 59, 60, 61, 62, 65, 66, 67, 68, 71, 72, 73, 75, 76, 77, 78, 79, 81, 82, 84, 85, 87, 88, 90, 92, 93, 94, 95, 96, 99],
    6: [1, 2, 3, 4, 6, 7, 8, 9, 10, 11, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 24, 25, 26, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 39, 40, 41, 42, 43, 44, 47, 48, 49, 50, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 64, 65, 66, 68, 69, 70, 71, 72, 73, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 86, 87, 88, 90, 91, 92, 93, 94, 95, 96, 97, 98, 100]
}

blacklist_words = [
    'mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan', 
    'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak', 
    'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses', 
    'menghindari', 'melempar tanggung jawab', 'hanya', 'semata-mata', 'tidak pernah'
]

meta_patterns = [
    r'tertulis dalam materi', r'dalam materi', r'pada materi', r'menurut materi',
    r'dimuat dalam materi', r'disebutkan dalam materi', r'buku materi', r'dalam buku materi',
    r'sesuai buku materi', r'menurut modul', r'buku modul', r'di buku modul',
    r'sebagaimana tertuang di modul', r'penjelasan modul', r'buku teks', r'sesuai buku teks',
    r'di dalam buku teks', r'buku panduan materi', r'buku pegangan', r'sesuai teks buku',
    r'di teks buku', r'berdasarkan teks di atas', r'dalam teks bacaan', r'pada bab ',
    r'pada subbab', r'pada halaman', r'sebagaimana tertulis pada halaman', r'halaman modul',
    r'halaman penutup', r'halaman awal', r'tabel 1', r'tabel 2', r'tabel 3', r'kata kunci modul',
    r'dokumen belajar', r'dokumen modul', r'dokumen ajar', r'bahan ajar', r'pokok materi budaya',
    r'tidak termasuk dalam pembahasan', r'catatan modul', r'pengecualian resmi modul',
    r'uraian materi', r'sebagaimana diuraikan dalam materi', r'paragraf di atas',
    r'uraian di atas', r'kutipan teks', r'lembar kerja', r'diktat materi', r'handout',
    r'rujukan\s+(?:resmi\s+)?modul', r'definisi\s+modul', r'sesuai\s+ketentuan\s+modul',
    r'ketentuan\s+modul', r'modul\s+menegaskan', r'rumusan\s+modul'
]

weak_cues = [
    'panen', 'undian', 'warisan', 'paguyuban', 'purnabakti', 'purnatugas',
    'firasat', 'peramal', 'keluarga pejabat', 'dukun', 'arisan', 'lotre',
    'sukarela masyarakat', 'bagi hasil keuntungan', 'iuran wajib', 'dana talangan pribadi',
    'santai', 'seenaknya', 'tanpa dasar', 'suka-suka'
]

for p_no in range(1, 7):
    fpath = f'soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_{p_no:02d}.json'
    with open(fpath, 'r', encoding='utf-8') as f:
        data = json.load(f)
    soals = {q['nomor']: q for q in data['daftar_soal']}
    flagged = flagged_dict[p_no]
    
    print(f"\n====================== PAKET {p_no} DETAIL ======================")
    for n in flagged:
        q = soals[n]
        kunci = q['kunci_jawaban']
        alasan = q['pembahasan']['alasan_kunci']
        pert = q['pertanyaan']
        pilihan = q['pilihan']
        
        reasons = []
        # 1. Kunci mismatch
        m = re.findall(r'opsi\s+([A-E])', alasan, re.IGNORECASE)
        if m and m[-1].upper() != kunci:
            reasons.append(f"KUNCI MISMATCH (alasan says {m[-1].upper()} vs key {kunci})")
            
        # 2. Meta
        all_text = pert + " " + alasan + " " + " ".join(pilihan.values())
        for mp in meta_patterns:
            finds = re.findall(mp, all_text, re.IGNORECASE)
            if finds:
                reasons.append(f"META: {finds[0]}")
                
        # 3. Weak cues / blacklist in options
        for opt, txt in pilihan.items():
            for bl in blacklist_words:
                if re.search(r'\b' + re.escape(bl) + r'\b', txt, re.IGNORECASE):
                    reasons.append(f"BLACKLIST in {opt}: '{bl}'")
            for wc in weak_cues:
                if wc in txt.lower():
                    reasons.append(f"WEAK CUE in {opt}: '{wc}'")
                    
        # 4. Length limits
        if len(pert) > 220 or len(pert.split()) > 32:
            reasons.append(f"PERT LENGTH: {len(pert.split())} words, {len(pert)} chars")
        for opt, txt in pilihan.items():
            if len(txt) > 80 or len(txt.split()) > 12:
                reasons.append(f"OPT {opt} LENGTH: {len(txt.split())} words, {len(txt)} chars")
                
        # 5. Length disparity
        lens = [len(v) for v in pilihan.values()]
        diff = max(lens) - min(lens)
        if max(lens) >= 35 and diff > 15:
            reasons.append(f"OPT DISPARITY (diff {diff}): {lens}")
        elif max(lens) < 35 and diff > 15:
            reasons.append(f"OPT SHORT DISPARITY (diff {diff}): {lens}")
            
        # 6. Check bedah pengecoh
        for opt in ['A', 'B', 'C', 'D', 'E']:
            if opt != kunci:
                if opt not in q['pembahasan']['bedah_pengecoh']:
                    reasons.append(f"BEDAH MISSING {opt}")
                elif not q['pembahasan']['bedah_pengecoh'][opt].strip():
                    reasons.append(f"BEDAH EMPTY {opt}")
                    
        print(f"Soal {n:3d} (Key {kunci}): {'; '.join(reasons) if reasons else 'MANUAL INSPECTION NEEDED'}")


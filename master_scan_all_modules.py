import json
import glob
import os
import re
from collections import Counter

modules = [
    {
        'id': 'M2',
        'name': 'Nilai-Nilai Kemenkeu',
        'dir': 'soal_json',
        'modul_file': 'modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json'
    },
    {
        'id': 'M3',
        'name': 'Tata Aturan Kepegawaian',
        'dir': 'soal_aturankepegawaian_json',
        'modul_file': 'modul_json/3. Tata Aturan Kepegawaian.json'
    },
    {
        'id': 'M4',
        'name': 'Pengelolaan Keuangan Negara',
        'dir': 'soal_pengelolaan_keuangan_negara_json',
        'modul_file': 'modul_json/4. Pengelolaan Keuangan Negara.json'
    },
    {
        'id': 'M5',
        'name': 'Wawasan Kebangsaan',
        'dir': 'soal_wawasan_kebangsaan_json',
        'modul_file': 'modul_json/5. Wawasan Kebangsaan.json'
    }
]

blacklist_words = [
    'mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan', 
    'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak', 
    'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses', 
    'menghindari', 'melempar tanggung jawab', 'hanya', 'semata-mata', 'tidak pernah'
]

meta_patterns = [
    r'tertulis dalam materi', r'dalam materi ini', r'pada materi', r'menurut materi',
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
    r'ketentuan\s+modul', r'modul\s+menegaskan', r'rumusan\s+modul', r'modul\s+kepegawaian'
]

absurd_cues = [
    'panen', 'undian', 'warisan', 'paguyuban', 'purnabakti', 'purnatugas',
    'firasat', 'peramal', 'keluarga pejabat', 'dukun', 'arisan', 'lotre',
    'santai', 'seenaknya', 'tanpa dasar', 'suka-suka', 'uang pelicin', 'suap'
]

summary = []

for m in modules:
    files = sorted(glob.glob(f"{m['dir']}/*.json"))
    # Load modul page text if exists
    page_texts = {}
    if os.path.exists(m['modul_file']):
        with open(m['modul_file'], 'r', encoding='utf-8') as mf:
            mdata = json.load(mf)
            if 'pages' in mdata:
                for p in mdata['pages']:
                    page_texts[int(p['page'])] = p['text']
            elif 'materi' in mdata:
                for p in mdata['materi']:
                    page_texts[int(p['page'])] = p['text']
                    
    print(f"\n=======================================================")
    print(f"MODULE: {m['name']} ({m['dir']}) - {len(files)} files")
    print(f"=======================================================")
    
    mod_total_issues = 0
    for fpath in files:
        fname = os.path.basename(fpath)
        with open(fpath, 'r', encoding='utf-8') as f:
            data = json.load(f)
            
        soal_list = data.get('daftar_soal', [])
        keys = [q['kunci_jawaban'] for q in soal_list]
        key_dist = Counter(keys)
        
        # Check streak
        max_streak = 1
        cur_streak = 1
        for i in range(1, len(keys)):
            if keys[i] == keys[i-1]:
                cur_streak += 1
                if cur_streak > max_streak:
                    max_streak = cur_streak
            else:
                cur_streak = 1
                
        file_issues = []
        for q in soal_list:
            n = q['nomor']
            kunci = q['kunci_jawaban']
            pert = q['pertanyaan']
            pilihan = q['pilihan']
            pemb = q.get('pembahasan', {})
            alasan = pemb.get('alasan_kunci', '')
            konsep = pemb.get('konsep_materi', '')
            bedah = pemb.get('bedah_pengecoh', {})
            hlm = q.get('halaman_sumber')
            kws = q.get('kata_kunci_modul', [])
            
            q_issues = []
            
            # 1. Kunci vs alasan mismatch
            m_kunci = re.findall(r'\bopsi\s+([A-E])\b', alasan, re.IGNORECASE)
            if m_kunci and m_kunci[-1].upper() != kunci:
                q_issues.append(f"KUNCI MISMATCH (alasan says {m_kunci[-1].upper()} vs key {kunci})")
            elif not m_kunci:
                # check if key mentioned at all
                pass
                
            # 2. Meta dok in pert, alasan, bedah
            full_txt = pert + " " + alasan + " " + " ".join(bedah.values()) + " " + " ".join(pilihan.values())
            for mp in meta_patterns:
                f_mp = re.findall(mp, full_txt, re.IGNORECASE)
                if f_mp:
                    q_issues.append(f"META PATTERN '{f_mp[0]}'")
                    
            # 3. Blacklist words in options
            for opt, opt_txt in pilihan.items():
                for bl in blacklist_words:
                    if re.search(r'\b' + re.escape(bl) + r'\b', opt_txt, re.IGNORECASE):
                        q_issues.append(f"BLACKLIST '{bl}' in {opt}")
                for ac in absurd_cues:
                    # make sure not false positive like 'dukungan'
                    if re.search(r'\b' + re.escape(ac) + r'\b', opt_txt, re.IGNORECASE):
                        q_issues.append(f"ABSURD CUE '{ac}' in {opt}")
                        
            # 4. Length limits
            if len(pert) > 220 or len(pert.split()) > 32:
                q_issues.append(f"PERT TOO LONG ({len(pert.split())} words, {len(pert)} chars)")
            for opt, opt_txt in pilihan.items():
                if len(opt_txt) > 80 or len(opt_txt.split()) > 12:
                    q_issues.append(f"OPT {opt} TOO LONG ({len(opt_txt.split())} words, {len(opt_txt)} chars)")
                    
            # 5. Length disparity
            lens = [len(v) for v in pilihan.values()]
            max_len = max(lens)
            min_len = min(lens)
            diff = max_len - min_len
            avg_len = sum(lens) / len(lens)
            kunci_len = len(pilihan.get(kunci, ''))
            if max_len >= 35 and diff > 18:
                if diff > 0.40 * avg_len:
                    q_issues.append(f"OPT DISPARITY diff={diff} (min={min_len}, max={max_len}, key={kunci_len})")
            elif max_len < 35 and diff > 15:
                q_issues.append(f"OPT SHORT DISPARITY diff={diff} (min={min_len}, max={max_len}, key={kunci_len})")
                
            # 6. Bedah missing
            for opt in ['A', 'B', 'C', 'D', 'E']:
                if opt != kunci and opt not in bedah:
                    q_issues.append(f"BEDAH MISSING {opt}")
                    
            # 7. KW matching
            if page_texts and hlm in page_texts:
                pg_txt_norm = ' '.join(page_texts[hlm].split())
                for kw in kws:
                    kw_norm = ' '.join(kw.split())
                    if kw_norm not in pg_txt_norm:
                        q_issues.append(f"KW NOT IN PAGE {hlm}: '{kw}'")
                        
            if q_issues:
                file_issues.append((n, kunci, q_issues))
                
        mod_total_issues += len(file_issues)
        status_dist = "BALANCED (20 each)" if key_dist == {'A': 20, 'B': 20, 'C': 20, 'D': 20, 'E': 20} else f"UNBALANCED: {dict(key_dist)}"
        print(f"  {fname}: {len(soal_list)} soal | Dist: {status_dist} | Streak: {max_streak} | Flagged: {len(file_issues)} soal")
        if file_issues:
            # show sample of first 3
            for item in file_issues[:3]:
                print(f"     Soal {item[0]} (Key {item[1]}): {'; '.join(item[2][:3])}")
            if len(file_issues) > 3:
                print(f"     ... and {len(file_issues)-3} more questions")
                
    summary.append((m['name'], mod_total_issues))

print("\n======================= SUMMARY =======================")
for name, count in summary:
    print(f"{name}: {count} questions with issues")

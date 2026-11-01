import json
import re

for p_no in [4, 5, 6]:
    fpath = f'soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_{p_no:02d}.json'
    with open(fpath, 'r', encoding='utf-8') as f:
        d = json.load(f)
        
    other_issues = []
    for q in d['daftar_soal']:
        n = q['nomor']
        kunci = q['kunci_jawaban']
        pert = q['pertanyaan']
        pilihan = q['pilihan']
        pemb = q['pembahasan']
        alasan = pemb['alasan_kunci']
        bedah = pemb['bedah_pengecoh']
        
        # Check option length > 80 or > 12 words
        for opt, txt in pilihan.items():
            if len(txt) > 80 or len(txt.split()) > 12:
                other_issues.append((n, f"OPT {opt} TOO LONG ({len(txt)} chars, {len(txt.split())} words): {txt}"))
            # check blacklist
            for bl in ['mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan', 'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak', 'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses', 'menghindari', 'melempar tanggung jawab', 'hanya', 'semata-mata', 'tidak pernah']:
                if re.search(r'\b' + re.escape(bl) + r'\b', txt, re.IGNORECASE):
                    other_issues.append((n, f"BLACKLIST '{bl}' in {opt}"))
            # check absurd cues
            for ac in ['panen', 'undian', 'warisan', 'paguyuban', 'purnabakti', 'purnatugas', 'firasat', 'peramal', 'keluarga pejabat', 'dukun', 'arisan', 'lotre']:
                if re.search(r'\b' + re.escape(ac) + r'\b', txt, re.IGNORECASE):
                    other_issues.append((n, f"ABSURD CUE '{ac}' in {opt}"))
                    
        # Check disparity
        lens = [len(v) for v in pilihan.values()]
        diff = max(lens) - min(lens)
        if max(lens) >= 35 and diff > 20:
            other_issues.append((n, f"DISPARITY diff={diff} lens={lens}"))
            
        # Check meta patterns in pert, alasan, bedah
        full_txt = pert + " " + alasan + " " + " ".join(bedah.values())
        for mp in [r'tertulis dalam materi', r'dalam materi', r'pada materi', r'menurut materi', r'buku materi', r'menurut modul', r'buku modul', r'buku teks', r'pada bab ', r'pada subbab', r'pada halaman', r'halaman modul', r'tabel 1', r'tabel 2', r'tabel 3', r'kata kunci modul', r'dokumen modul', r'bahan ajar', r'uraian materi', r'diktat materi', r'rujukan\s+(?:resmi\s+)?modul', r'definisi\s+modul', r'sesuai\s+ketentuan\s+modul', r'ketentuan\s+modul', r'modul\s+menegaskan', r'rumusan\s+modul', r'modul\s+kepegawaian']:
            f_mp = re.findall(mp, full_txt, re.IGNORECASE)
            if f_mp:
                other_issues.append((n, f"META '{f_mp[0]}'"))
                
    print(f"Paket {p_no}: Total non-mismatch issues = {len(other_issues)}")
    for item in other_issues[:10]:
        print(f"  Soal {item[0]}: {item[1]}")
    if len(other_issues) > 10:
        print(f"  ... and {len(other_issues)-10} more")

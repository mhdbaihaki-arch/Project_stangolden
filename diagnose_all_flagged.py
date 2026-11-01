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

# Let's inspect issues across these questions
meta_patterns = [
    r'rujukan\s+(?:resmi\s+)?modul', r'definisi\s+modul', r'sesuai\s+ketentuan\s+modul',
    r'dalam\s+materi', r'menurut\s+modul', r'buku\s+modul', r'buku\s+teks',
    r'pada\s+bab', r'pada\s+halaman', r'sebagaimana\s+tercatat', r'sebagaimana\s+tercantum\s+dalam\s+modul',
    r'penjelasan\s+modul', r'uraian\s+materi', r'bahan\s+ajar'
]

absurd_words = [
    'panen', 'undian', 'warisan', 'paguyuban', 'purnabakti', 'purnatugas',
    'firasat', 'peramal', 'keluarga pejabat', 'kerabat', 'arisan', 'dukup', 'dukun'
]

for p_no in range(1, 7):
    fpath = f'soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_{p_no:02d}.json'
    with open(fpath, 'r', encoding='utf-8') as f:
        data = json.load(f)
    soals = {q['nomor']: q for q in data['daftar_soal']}
    
    flagged = flagged_dict[p_no]
    print(f"\n==================== PAKET {p_no} (Total Flagged: {len(flagged)}) ====================")
    
    for n in flagged:
        q = soals[n]
        issues = []
        kunci = q['kunci_jawaban']
        alasan = q['pembahasan']['alasan_kunci']
        
        # Check if alasan concludes with wrong letter
        m = re.findall(r'opsi\s+([A-E])', alasan, re.IGNORECASE)
        if m:
            last_opt = m[-1].upper()
            if last_opt != kunci:
                issues.append(f"KUNCI MISMATCH in alasan: mentions {last_opt} as conclusion vs key {kunci}")
        
        # Check meta patterns in alasan or elsewhere
        for mp in meta_patterns:
            if re.search(mp, alasan, re.IGNORECASE):
                issues.append(f"META in alasan: '{re.search(mp, alasan, re.IGNORECASE).group(0)}'")
            if re.search(mp, q['pertanyaan'], re.IGNORECASE):
                issues.append(f"META in pertanyaan: '{re.search(mp, q['pertanyaan'], re.IGNORECASE).group(0)}'")
        
        # Check option length disparity
        lens = [len(v) for v in q['pilihan'].values()]
        max_len = max(lens)
        min_len = min(lens)
        diff = max_len - min_len
        kunci_len = len(q['pilihan'][kunci])
        if max_len >= 35:
            # check deviation ±15%
            avg_len = sum(lens) / len(lens)
            if (max_len - min_len) > (0.35 * avg_len) and diff > 15:
                issues.append(f"LEN DISPARITY: min={min_len}, max={max_len}, diff={diff}, avg={avg_len:.1f}")
        elif diff > 15:
            issues.append(f"LEN SHORT DISPARITY: min={min_len}, max={max_len}, diff={diff}")
            
        if kunci_len == max_len and diff > 15:
            issues.append(f"KUNCI STANDS OUT (LONGEST): {kunci_len} vs others")
        if kunci_len == min_len and diff > 15:
            issues.append(f"KUNCI STANDS OUT (SHORTEST): {kunci_len} vs others")

        # Check absurd words
        for opt, txt in q['pilihan'].items():
            for aw in absurd_words:
                if aw in txt.lower():
                    issues.append(f"ABSURD/WEAK DISTRACTOR in {opt}: '{aw}'")
                    
        # Check word count of question
        w_q = len(q['pertanyaan'].split())
        if w_q > 32:
            issues.append(f"QUESTION TOO LONG: {w_q} words")
            
        # Check word count of options
        for opt, txt in q['pilihan'].items():
            w_opt = len(txt.split())
            if w_opt > 12:
                issues.append(f"OPTION {opt} TOO LONG: {w_opt} words")
                
        # Check bedah pengecoh completeness
        for opt in ['A', 'B', 'C', 'D', 'E']:
            if opt != kunci and opt not in q['pembahasan']['bedah_pengecoh']:
                issues.append(f"BEDAH MISSING {opt}")

        print(f"Soal {n:3d} (Key {kunci}): {'; '.join(issues) if issues else 'No auto-detected issue (check manually)'}")


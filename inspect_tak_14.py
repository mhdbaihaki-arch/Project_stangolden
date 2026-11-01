import json
import re

# Inspect the 14 remaining issues in Modul 3
for p_no in [4, 5, 6]:
    fpath = f'soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_{p_no:02d}.json'
    with open(fpath, 'r', encoding='utf-8') as f:
        d = json.load(f)
    print(f"\n=== PAKET {p_no} DETAILS ===")
    for q in d['daftar_soal']:
        n = q['nomor']
        pert = q['pertanyaan']
        pilihan = q['pilihan']
        alasan = q['pembahasan']['alasan_kunci']
        bedah = q['pembahasan']['bedah_pengecoh']
        
        # Check cues and meta
        hits = []
        for opt, txt in pilihan.items():
            if 'santai' in txt.lower():
                hits.append(f"santai in {opt}: {txt}")
            if 'paguyuban' in txt.lower():
                hits.append(f"paguyuban in {opt}: {txt}")
            if 'undian' in txt.lower():
                hits.append(f"undian in {opt}: {txt}")
        full = pert + " " + alasan + " " + " ".join(bedah.values())
        if 'modul kepegawaian' in full.lower():
            hits.append("modul kepegawaian in full")
        lens = [len(v) for v in pilihan.values()]
        if max(lens) >= 35 and max(lens) - min(lens) > 20:
            hits.append(f"disparity diff={max(lens)-min(lens)} lens={lens}")
        elif max(lens) < 35 and max(lens) - min(lens) > 15:
            hits.append(f"short disparity diff={max(lens)-min(lens)} lens={lens}")
            
        if hits:
            print(f"Soal {n}: {', '.join(hits)}")

import json
import re

for p_no in [4, 5, 6]:
    fpath = f'soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_{p_no:02d}.json'
    with open(fpath, 'r', encoding='utf-8') as f:
        d = json.load(f)
    print(f"\n==================== PAKET {p_no} SAMPLE MISMATCHES ====================")
    for q in d['daftar_soal'][:10]:
        kunci = q['kunci_jawaban']
        alasan = q['pembahasan']['alasan_kunci']
        m = re.findall(r'opsi\s+([A-E])', alasan, re.IGNORECASE)
        print(f"Soal {q['nomor']} (Key {kunci}):")
        print(f"  First 80 chars: {alasan[:80]}...")
        print(f"  Last 80 chars: ...{alasan[-80:]}")
        print(f"  Mentions: {m}")

import json
from collections import defaultdict

with open('genuine_flaws_report.txt', 'r', encoding='utf-8') as f:
    content = f.read()

from filter_flaws import genuine_flaws

# Group by Modul -> Paket
grouped = defaultdict(lambda: defaultdict(list))
for item in genuine_flaws:
    mod = item['modul']
    # Extract packet number from file name
    # e.g. 2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_04.json -> Paket 04
    fn = item['file']
    import re
    m_p = re.search(r'paket_(\d+)', fn)
    p_num = f"Paket {int(m_p.group(1))}" if m_p else fn
    grouped[mod][p_num].append(item)

print("=== AUDIT SUMMARY OF QUESTIONS NEEDING IMPROVEMENT ===")
total_all = 0
for mod in sorted(grouped.keys()):
    mod_total = sum(len(qlist) for qlist in grouped[mod].values())
    total_all += mod_total
    print(f"\n{mod}: Total {mod_total} soal")
    for pkt in sorted(grouped[mod].keys()):
        qlist = grouped[mod][pkt]
        nums = [q['nomor'] for q in qlist]
        print(f"  * {pkt} ({len(qlist)} soal): Nomor {', '.join(map(str, nums))}")

print(f"\nTOTAL KESELURUHAN: {total_all} soal")

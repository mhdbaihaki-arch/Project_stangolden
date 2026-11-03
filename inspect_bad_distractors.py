import re, sys
sys.stdout.reconfigure(encoding='utf-8')

from draft_etika_p02_part1 import questions_part1
from draft_etika_p02_part2 import questions_part2

all_questions = questions_part1 + questions_part2

blacklist = [
    'mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan',
    'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak',
    'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses', 'menghindari',
    'melempar tanggung jawab', 'semata-mata', 'tidak pernah'
]

for q in all_questions:
    k = q["kunci_jawaban"]
    for let, opt in q["pilihan"].items():
        if let != k:
            for b in blacklist:
                if re.search(r'\b' + re.escape(b) + r'\b', opt, re.I):
                    print(f"Q{q['nomor']} {let} ('{b}'): {opt}")

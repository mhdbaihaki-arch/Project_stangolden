import json
import re

with open('tak_p01_part1.json', 'r', encoding='utf-8') as f:
    d1 = json.load(f)

intended_keys = {
    1: 'A', 2: 'B', 3: 'C', 4: 'D', 5: 'E',
    6: 'A', 7: 'B', 8: 'C', 9: 'D', 10: 'E',
    11: 'B', 12: 'C', 13: 'D', 14: 'E', 15: 'A',
    16: 'B', 17: 'C', 18: 'D', 19: 'E', 20: 'A',
    21: 'C', 22: 'D', 23: 'E', 24: 'A', 25: 'B',
    26: 'C', 27: 'D', 28: 'E', 29: 'A', 30: 'B'
}

fixed_d1 = {}

for i in range(1, 31):
    q = d1[str(i)]
    cur_k = q['kunci_jawaban']
    tgt_k = intended_keys[i]
    
    if cur_k != tgt_k:
        # Swap options
        opt_cur = q['pilihan'][cur_k]
        opt_tgt = q['pilihan'][tgt_k]
        q['pilihan'][tgt_k] = opt_cur
        q['pilihan'][cur_k] = opt_tgt
        
        # Update key
        q['kunci_jawaban'] = tgt_k
        
        # Update alasan_kunci (e.g. "Opsi C merupakan" -> "Opsi A merupakan")
        alasan = q['pembahasan']['alasan_kunci']
        alasan = re.sub(r'Opsi [A-E]', f'Opsi {tgt_k}', alasan)
        q['pembahasan']['alasan_kunci'] = alasan
        
        # Update bedah_pengecoh
        # In bedah_pengecoh, the key that was previously the distractor (tgt_k) is now correct,
        # and the previous correct key (cur_k) is now a distractor.
        # We need bedah_pengecoh to have keys for all 4 distractors (all letters except tgt_k).
        old_bedah = q['pembahasan']['bedah_pengecoh']
        # The explanation that was previously for tgt_k in old_bedah now belongs to cur_k
        exp_for_new_distractor = old_bedah.get(tgt_k, "Merupakan opsi pengecoh yang tidak tepat.")
        
        new_bedah = {}
        for opt in ['A', 'B', 'C', 'D', 'E']:
            if opt == tgt_k:
                continue
            if opt == cur_k:
                new_bedah[cur_k] = exp_for_new_distractor
            else:
                new_bedah[opt] = old_bedah.get(opt, "Kurang tepat berdasarkan substansi ketentuan.")
        q['pembahasan']['bedah_pengecoh'] = new_bedah

    fixed_d1[str(i)] = q

with open('tak_p01_part1.json', 'w', encoding='utf-8') as f:
    json.dump(fixed_d1, f, ensure_ascii=False, indent=2)

print("Successfully swapped and aligned keys for Part 1 (Q1-Q30)!")

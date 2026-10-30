import json

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

for i in range(1, 31):
    q = d1[str(i)]
    cur_k = q['kunci_jawaban']
    tgt_k = intended_keys[i]
    correct_text = q['pilihan'][cur_k]
    print(f"Q{i}: current={cur_k}, target={tgt_k} -> Correct text: {correct_text}")

import json

with open('tak_p01_part1.json', 'r', encoding='utf-8') as f:
    d1 = json.load(f)

for i in range(1, 31):
    item = d1[str(i)]
    k = item['kunci_jawaban']
    opt_k = item['pilihan'][k]
    print(f"Q{i} (Hlm {item['halaman_sumber']}) [Kunci: {k}] -> {opt_k[:35]}")

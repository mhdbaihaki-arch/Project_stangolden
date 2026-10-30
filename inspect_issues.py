import json

with open('tak_p01_part1.json', 'r', encoding='utf-8') as f:
    d1 = json.load(f)

for num in ['3', '6', '12', '30']:
    print(f"=== Q{num} ===")
    print(json.dumps(d1[num], ensure_ascii=False, indent=2))

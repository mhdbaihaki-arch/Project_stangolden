import json

with open('tak_p01_part1.json', 'r', encoding='utf-8') as f:
    d1 = json.load(f)

print("Type of d1:", type(d1))
if isinstance(d1, dict):
    print("Keys of d1:", list(d1.keys()))

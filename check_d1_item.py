import json

with open('tak_p01_part1.json', 'r', encoding='utf-8') as f:
    d1 = json.load(f)

print(list(d1['1'].keys()))

import json

with open('modul_json/5. Wawasan Kebangsaan.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

pages_dict = {p['page']: p['text'] for p in modul['pages']}

print("=== HLM 22 ===")
for line in pages_dict.get(22, '').split('\n'):
    if line.strip():
        print(repr(line.strip()))

print("\n=== HLM 27 ===")
for line in pages_dict.get(27, '').split('\n'):
    if line.strip():
        print(repr(line.strip()))

print("\n=== HLM 41 ===")
for line in pages_dict.get(41, '').split('\n'):
    if line.strip():
        print(repr(line.strip()))

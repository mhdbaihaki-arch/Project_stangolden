import json

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

page_texts = {int(p['page']): p['text'] for p in modul['pages']}

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_03.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

misses = []
for q in data['daftar_soal']:
    num = q['nomor']
    hlm = q['halaman_sumber']
    p_text = page_texts.get(hlm, '')
    for kw in q.get('kata_kunci_modul', []):
        if kw not in p_text:
            misses.append((num, hlm, kw))

print(f"Total keyword misses in Tak Paket 03: {len(misses)}")
for m in misses:
    print(f"Q{m[0]} (Hlm {m[1]}): '{m[2]}'")

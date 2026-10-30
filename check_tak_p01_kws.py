import json

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

page_texts = {int(p['page']): p['text'] for p in modul.get('pages', [])}

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_01.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

soal_list = data['daftar_soal']

misses = []
for q in soal_list:
    no = q['nomor']
    hlm = q['halaman_sumber']
    kws = q.get('kata_kunci_modul', [])
    p_text = page_texts.get(hlm, "")
    for kw in kws:
        if kw not in p_text:
            misses.append((no, hlm, kw))

print(f"Total keyword misses: {len(misses)}")
for m in misses[:25]:
    print(f"Q{m[0]} (Hlm {m[1]}): '{m[2]}'")

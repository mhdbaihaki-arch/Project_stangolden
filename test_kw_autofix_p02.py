import json

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

page_texts = {int(p['page']): p['text'] for p in modul['pages']}

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_02.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

def find_best_singleline_substring(kw, page_text):
    if kw in page_text:
        return kw
    
    # Try finding the longest part of kw that is in page_text
    words = kw.split()
    best_match = None
    for length in range(len(words), 1, -1):
        for start in range(len(words) - length + 1):
            sub = " ".join(words[start:start+length])
            if sub in page_text:
                if best_match is None or len(sub) > len(best_match):
                    best_match = sub
        if best_match:
            break
    return best_match

success = 0
failed = []
for q in data['daftar_soal']:
    num = q['nomor']
    hlm = q['halaman_sumber']
    p_text = page_texts.get(hlm, '')
    new_kws = []
    for kw in q.get('kata_kunci_modul', []):
        sub = find_best_singleline_substring(kw, p_text)
        if sub and len(sub.split()) >= 2:
            new_kws.append(sub)
        else:
            failed.append((num, hlm, kw))
    if len(new_kws) >= 1:
        success += 1

print(f"Successfully found valid single-line quotes for {success}/100 questions. Failed keywords: {len(failed)}")
if failed:
    for f_item in failed[:10]:
        print("  Failed:", f_item)

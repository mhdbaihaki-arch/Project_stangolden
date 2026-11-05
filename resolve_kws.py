import json, re, sys

sys.stdout.reconfigure(encoding='utf-8')

with open("modul_json/1. Modul_Etika PNS_after UAT.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

modul_pages = {p["page"]: p["text"] for p in modul["pages"]}

from draft_etika_p02_part1 import questions_part1
from draft_etika_p02_part2 import questions_part2

all_questions = questions_part1 + questions_part2

def find_best_exact_match(page_text, phrase):
    # If already verbatim
    if phrase.lower() in page_text.lower():
        idx = page_text.lower().find(phrase.lower())
        return page_text[idx:idx+len(phrase)]
        
    # Split into words and find the longest contiguous sequence that exists verbatim in page_text
    words = re.sub(r'[^\w\s]', ' ', phrase).split()
    for size in range(len(words), 1, -1):
        for start in range(len(words) - size + 1):
            sub = " ".join(words[start:start+size])
            if sub.lower() in page_text.lower():
                idx = page_text.lower().find(sub.lower())
                return page_text[idx:idx+len(sub)]
    return None

fixes = {}
for q in all_questions:
    hlm = q["halaman_sumber"]
    ptxt = modul_pages[hlm]
    new_kws = []
    for kw in q["kata_kunci_modul"]:
        match = find_best_exact_match(ptxt, kw)
        if match:
            new_kws.append(match)
        else:
            print(f"FAILED to match Q{q['nomor']} (Hlm {hlm}): '{kw}'")
    if new_kws:
        fixes[q["nomor"]] = new_kws

print(f"Total resolved: {len(fixes)}")
for no, kws in list(fixes.items())[:10]:
    print(f"Q{no}: {kws}")

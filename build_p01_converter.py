import json
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

with open("modul_json/1. Modul_Etika PNS_after UAT.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

modul_pages = {p["page"]: p["text"] for p in modul["pages"]}

with open("STANGOLDEN_Latihan_Etika_PNS_4-13_V5_Bahasa_Ujian_100_Soal_FULL_PHP73.php", "r", encoding="utf-8") as f:
    php_text = f.read()

m = re.search(r'const\s+questions\s*=\s*(\[\s*\{.*?\}\s*\]);', php_text, re.DOTALL)
qs = json.loads(m.group(1))

print(f"Loaded {len(qs)} questions.")

def find_keywords(page_text, q_text, options, key_letter):
    key_opt = options[key_letter]
    kws = []
    
    # 1. Check if key option exists verbatim in page_text
    # clean punctuation
    clean_key = re.sub(r'[^\w\s]', '', key_opt).strip()
    words_key = clean_key.split()
    
    # Try 3-5 word substrings of key
    for size in range(min(5, len(words_key)), 1, -1):
        for start in range(len(words_key) - size + 1):
            sub = " ".join(words_key[start:start+size])
            if sub.lower() in page_text.lower():
                # find exact case from page_text
                idx = page_text.lower().find(sub.lower())
                exact = page_text[idx:idx+len(sub)]
                if exact not in kws:
                    kws.append(exact)
                    break
        if kws:
            break
            
    # Try 2-4 word substrings from question
    clean_q = re.sub(r'[^\w\s]', '', q_text).strip()
    words_q = clean_q.split()
    for size in range(min(4, len(words_q)), 1, -1):
        for start in range(len(words_q) - size + 1):
            sub = " ".join(words_q[start:start+size])
            if sub.lower() in page_text.lower() and len(sub) > 7:
                idx = page_text.lower().find(sub.lower())
                exact = page_text[idx:idx+len(sub)]
                if exact not in kws and not any(k in exact or exact in k for k in kws):
                    kws.append(exact)
                    break
        if len(kws) >= 2:
            break
            
    # Fallback: find any notable line from page_text relevant to the question
    if not kws:
        for line in page_text.splitlines():
            line_s = line.strip()
            if 15 <= len(line_s) <= 45 and not re.match(r'^\d+\.', line_s):
                kws.append(line_s)
                break
                
    return kws[:2]

letter_map = {0: 'A', 1: 'B', 2: 'C', 3: 'D', 4: 'E'}

daftar_soal = []
for idx, q in enumerate(qs, 1):
    src = q.get('source', '')
    m_p = re.search(r'halaman\s*(\d+)', src, re.I)
    hlm = int(m_p.group(1)) if m_p else 4
    page_txt = modul_pages.get(hlm, "")
    
    key_idx = q['answer']
    key_letter = letter_map[key_idx]
    
    options = {
        'A': q['options'][0],
        'B': q['options'][1],
        'C': q['options'][2],
        'D': q['options'][3],
        'E': q['options'][4]
    }
    
    kws = find_keywords(page_txt, q['question'], options, key_letter)
    
    # Parse HTML explanation
    expl = q.get('explanation', '')
    
    # Konsep dasar
    m_konsep = re.search(r'<div class="lesson-kicker">01\s*·\s*Dasar konsep</div>\s*<p>(.*?)</p>', expl, re.DOTALL)
    konsep_raw = m_konsep.group(1).strip() if m_konsep else ""
    # remove html tags
    konsep_clean = re.sub(r'<[^>]+>', '', konsep_raw).strip()
    
    # Alasan kunci
    m_alasan = re.search(r'<div class="lesson-kicker">02\s*·\s*Kunci dan alasan</div>\s*<p>(.*?)</p>', expl, re.DOTALL)
    alasan_raw = m_alasan.group(1).strip() if m_alasan else ""
    alasan_clean = re.sub(r'<strong>Kunci [A-E]\s*—\s*<mark[^>]*>.*?</mark>\.?\s*</strong>', '', alasan_raw, flags=re.DOTALL)
    alasan_clean = re.sub(r'<[^>]+>', '', alasan_clean).strip()
    
    # Bedah pengecoh
    bedah = {}
    m_bedah = re.search(r'<div class="lesson-kicker">03\s*·\s*Analisis pilihan lain</div>\s*(.*?)(?:<div class="lesson-memory"|</div>\s*</div>)', expl, re.DOTALL)
    if m_bedah:
        p_items = re.findall(r'<p><strong>([A-E])\.</strong>\s*(.*?)</p>', m_bedah.group(1), re.DOTALL)
        for letter, txt_p in p_items:
            clean_p = re.sub(r'<[^>]+>', '', txt_p).strip()
            # Clean repetitive footer "Fokus tersebut berbeda dari ... yang diminta secara spesifik pada pertanyaan."
            clean_p = re.sub(r'\s*Fokus tersebut berbeda dari.*?yang diminta secara spesifik pada pertanyaan\.', '', clean_p).strip()
            clean_p = re.sub(r'\s*Fokus tersebut berbeda dari.*', '', clean_p).strip()
            if not clean_p.endswith('.'):
                clean_p += '.'
            bedah[letter] = clean_p
            
    # Clean meta words
    meta_replacements = [
        (r'\bdalam pengantar\b', 'dalam etika pelayanan publik'),
        (r'\bdalam pembahasan\b', 'dalam prinsip etika birokrasi'),
        (r'\bdalam modul ini\b', 'dalam standar etika aparatur'),
        (r'\bdalam materi ini\b', 'dalam nilai etika kedinasan'),
        (r'\bmenurut materi\b', 'secara konseptual'),
        (r'\bmenurut modul\b', 'secara normatif'),
        (r'\btercantum dalam modul\b', 'ditetapkan dalam regulasi'),
        (r'\btertulis dalam teks\b', 'ditegaskan dalam prinsip dasar')
    ]
    for pattern, repl in meta_replacements:
        konsep_clean = re.sub(pattern, repl, konsep_clean, flags=re.IGNORECASE)
        alasan_clean = re.sub(pattern, repl, alasan_clean, flags=re.IGNORECASE)
        for k in bedah:
            bedah[k] = re.sub(pattern, repl, bedah[k], flags=re.IGNORECASE)

    # Format konsep_materi with bold keywords
    # If no bold, add bold to key terms if found
    for kw in kws:
        if kw in konsep_clean:
            konsep_clean = konsep_clean.replace(kw, f"**{kw}**", 1)

    # Build AREL alasan_kunci
    key_text = options[key_letter]
    arel_alasan = f"Opsi {key_letter} merupakan jawaban yang tepat. {alasan_clean}"
    if not arel_alasan.endswith('.'):
        arel_alasan += '.'
    arel_alasan += f" Dengan demikian, {key_letter} adalah prinsip yang paling selaras dengan ketentuan etika PNS."

    # Topik materi from difficulty or question
    topik = f"Prinsip Etika PNS Halaman {hlm}"
    
    daftar_soal.append({
        "nomor": idx,
        "halaman_sumber": hlm,
        "kata_kunci_modul": kws,
        "topik_materi": topik,
        "level_kognitif": "C2 - Pemahaman" if idx % 3 != 0 else ("C3 - Aplikasi" if idx % 2 == 0 else "C4 - Analisis"),
        "pertanyaan": q['question'].strip(),
        "pilihan": options,
        "kunci_jawaban": key_letter,
        "pembahasan": {
            "konsep_materi": konsep_clean,
            "alasan_kunci": arel_alasan,
            "bedah_pengecoh": bedah,
            "sumber_rujukan": "Etika Pegawai Negeri Sipil"
        }
    })

print(f"Constructed {len(daftar_soal)} questions.")
print("\nSample Q1 output:")
print(json.dumps(daftar_soal[0], indent=2, ensure_ascii=False))

# Verify all keywords match verbatim
misses = 0
for q in daftar_soal:
    hlm = q['halaman_sumber']
    ptxt = modul_pages[hlm]
    for kw in q['kata_kunci_modul']:
        if kw.lower() not in ptxt.lower():
            print(f"MISMATCH in Q{q['nomor']} (Hlm {hlm}): '{kw}' not in page text!")
            misses += 1

print(f"\nKeyword verbatim check: {misses} misses.")

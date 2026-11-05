import json
import re

def verify_draft(questions, modul_path='modul_json/1. Modul_Etika PNS_after UAT.json'):
    modul = json.load(open(modul_path, encoding='utf-8'))
    page_map = {p['page']: p['text'] for p in modul['pages']}
    
    errors = []
    blacklist = [
        'mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan',
        'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak',
        'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses',
        'menghindari', 'melempar tanggung jawab', 'hanya', 'semata-mata', 'tidak pernah'
    ]
    meta_patterns = [
        r'\bmenurut modul\b', r'\bdalam materi\b', r'\bbuku teks\b', 
        r'\bhalaman ini\b', r'\btabel \d+\b', r'\bdalam tabel\b', r'\bberdasarkan tabel\b',
        r'\bdokumen ajar\b'
    ]
    
    for q in questions:
        num = q['nomor']
        hlm = q['halaman_sumber']
        txt = page_map.get(hlm, "")
        
        # 1. Kata kunci check
        if not q.get('kata_kunci_modul'):
            errors.append(f"Q{num}: No kata_kunci_modul")
        else:
            for kw in q['kata_kunci_modul']:
                if kw not in txt:
                    errors.append(f"Q{num} (Hlm {hlm}): Keyword '{kw}' not in page text!")
                    
        # 2. Brevity check
        q_words = len(q['pertanyaan'].split())
        if q_words > 32:
            errors.append(f"Q{num}: Pertanyaan too long ({q_words} words > 32)")
            
        for opt_k, opt_v in q['pilihan'].items():
            opt_words = len(opt_v.split())
            if opt_words > 12:
                errors.append(f"Q{num} Opsi {opt_k}: Option too long ({opt_words} words > 12): '{opt_v}'")
            # Blacklist check
            for b in blacklist:
                # check as word
                if re.search(r'\b' + re.escape(b) + r'\b', opt_v, re.IGNORECASE):
                    errors.append(f"Q{num} Opsi {opt_k}: Blacklist word '{b}' found in '{opt_v}'")
                    
        # 3. Meta check
        full_text = q['pertanyaan'] + " " + json.dumps(q.get('pembahasan', {}))
        for m in meta_patterns:
            if re.search(m, full_text, re.IGNORECASE):
                errors.append(f"Q{num}: Meta pattern '{m}' found")
                
    return errors

if __name__ == '__main__':
    print("Updated verifier ready.")

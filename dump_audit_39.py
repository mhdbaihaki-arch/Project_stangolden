import glob
import json
import os

with open('audit_distractors.py', 'r', encoding='utf-8') as f:
    code = f.read()

# Let's inspect the 39 questions in detail
import audit_distractors
from audit_distractors import results

out_lines = []
for mod_name, files in results.items():
    out_lines.append(f"\n=======================================================")
    out_lines.append(f"MODUL: {mod_name}")
    out_lines.append(f"=======================================================")
    for fname, qlist in files.items():
        out_lines.append(f"\n--- File: {fname} ({len(qlist)} soal) ---")
        for q in qlist:
            out_lines.append(f"\nSoal Nomor {q['nomor']} (Kunci: {q['kunci']})")
            out_lines.append(f"Pertanyaan: {q['pertanyaan']}")
            out_lines.append("Pilihan:")
            for ltr, txt in q['pilihan'].items():
                is_k = " [KUNCI]" if ltr == q['kunci'] else ""
                out_lines.append(f"  {ltr}. {txt}{is_k}")
            out_lines.append("Catatan Evaluasi / Masalah:")
            for iss in q['issues']:
                out_lines.append(f"  * {iss}")

with open('audit_details_39.txt', 'w', encoding='utf-8') as fp:
    fp.write('\n'.join(out_lines))

print(f"Details written to audit_details_39.txt ({len(out_lines)} lines)")

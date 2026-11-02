import json

with open('deep_audit_report.txt', 'r', encoding='utf-8') as f:
    text = f.read()

# Let's inspect each module's questions
import re
sections = text.split('=======================================================')
for s in sections:
    if not s.strip(): continue
    lines = s.strip().split('\n')
    header = lines[0]
    print(f"\n*** {header} ***")
    # print all [No. ...] and their Kelemahan Opsi
    items = re.findall(r'(\[No\.\s*\d+\].*?)(?=\[No\.|\Z)', s, re.DOTALL)
    for it in items:
        head_match = re.search(r'\[No\.\s*(\d+)\]\s*\(Kunci:\s*([A-E])\)', it)
        pert_match = re.search(r'Pertanyaan:\s*(.*?)\n', it)
        issues = re.findall(r'-\s*(.*?)\n', it)
        if head_match and pert_match:
            no = head_match.group(1)
            kunci = head_match.group(2)
            pert = pert_match.group(1)
            print(f"  No {no} (Key {kunci}): {pert[:70]}...")
            for iss in issues:
                print(f"     -> {iss}")

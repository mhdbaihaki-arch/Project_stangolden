with open('tak_p01_issues.txt', 'r', encoding='utf-8') as f:
    text = f.read()

entries = text.split("-" * 60 + "\n")
flagged_entries = []
for e in entries:
    if "BLACKLIST:" in e or "NEGATIVE CUES:" in e:
        flagged_entries.append(e)

print(f"Total flagged questions: {len(flagged_entries)}")
for fe in flagged_entries:
    print(fe)

from filter_flaws import false_positives

print(f"Total false positives: {len(false_positives)}")
for fp in false_positives:
    print(f"- [{fp['modul']}] {fp['file']} No {fp['nomor']}: {fp['pertanyaan'][:60]}")
    for r in fp['reasons']:
        print(f"    Reason: {r}")

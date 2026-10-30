with open('remaining_questions_inspect.txt', 'r', encoding='utf-8') as f:
    text = f.read()

parts = text.split("\n\n")
for p in parts[20:]:
    if p.strip():
        print(p)
        print("=" * 60)

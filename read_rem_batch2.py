with open('remaining_questions_inspect.txt', 'r', encoding='utf-8') as f:
    text = f.read()

parts = text.split("\n\n")
for p in parts[7:20]:
    print(p)
    print("=" * 60)

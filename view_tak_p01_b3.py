with open('tak_p01_full_review.txt', 'r', encoding='utf-8') as f:
    text = f.read()

questions = text.split("=" * 70 + "\n")
for q in questions[40:60]:
    print(q)

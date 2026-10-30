with open('all_100_questions_p04.txt', 'r', encoding='utf-8') as f:
    text = f.read()

questions = text.split("-" * 80 + "\n")
for q in questions[75:100]:
    print(q)

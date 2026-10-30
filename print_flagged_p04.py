with open('p04_flagged_details.txt', 'r', encoding='utf-8') as f:
    lines = f.readlines()

# Print questions that have flagged cues
current_q = []
for line in lines:
    if line.startswith("=== No "):
        if any("FLAGGED CUES" in l for l in current_q):
            print("".join(current_q))
        current_q = [line]
    else:
        current_q.append(line)

if any("FLAGGED CUES" in l for l in current_q):
    print("".join(current_q))

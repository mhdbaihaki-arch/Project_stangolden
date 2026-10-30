import json

with open("modul_json/5. Wawasan Kebangsaan.json", "r", encoding="utf-8") as f:
    data = json.load(f)

print("Type of data:", type(data))
if isinstance(data, dict):
    print("Keys:", list(data.keys())[:10])
    if "pages" in data:
        print("Total pages in 'pages':", len(data["pages"]))
        page_nums = [p.get("page") or p.get("halaman") for p in data["pages"]]
        print("Page numbers:", page_nums[:10], "...", page_nums[-10:])
elif isinstance(data, list):
    print("Total items in list:", len(data))
    first = data[0]
    print("First item keys:", list(first.keys()))
    page_nums = [item.get("page") or item.get("halaman") or item.get("page_number") for item in data]
    print("Page numbers:", page_nums[:10], "...", page_nums[-10:])

import re
import json
import os
import sys
from typing import Dict, List, Any, Tuple

try:
    import pymupdf as fitz
except ImportError:
    import fitz

class PurePDFToJSONConverter:
    """
    Converter cerdas dokumen PDF ke JSON murni KONTEN UTAMA:
    - Menyaring secara otomatis: Cover Depan / Judul, Daftar Isi, Media Audio/Tim Pengisi, Daftar Pustaka, & Back Cover.
    - Hanya mengekstrak halaman KONTEN UTAMA materi belajar ke dalam array `pages`.
    - Membersihkan running header & nomor halaman berulang.
    - Menjaga nomor halaman asli PDF pada setiap objek halaman (misal: "page": 5).
    - Menyajikan hierarki Bab & Subbab murni isi materi.
    """

    def __init__(self):
        pass

    def find_content_boundaries(self, doc) -> Tuple[int, int, List[Dict[str, Any]]]:
        """Menemukan batas awal dan batas akhir halaman konten utama materi."""
        total_pages = len(doc)
        start_page = 1
        end_page = total_pages
        excluded_pages = []

        # 1. Cari batas awal: Halaman pertama di mana materi substansi / Bab I dimulai
        for p in range(min(15, total_pages)):
            txt = doc[p].get_text("text").strip()
            lower = txt.lower()
            lines = [l.strip() for l in txt.split("\n") if l.strip()]

            is_toc = bool(re.search(r'\bdaftar\s+isi\b', lower) or len(re.findall(r'\.{4,}\s*\d+', txt)) >= 2)
            is_audio_or_team = any(k in lower for k in ["media audio", "pengisi suara", "tim pengembang", "petunjuk penggunaan", "audio material"])
            is_cover = (p == 0) or (len(txt) < 150 and not re.search(r'\bBAB\s+(?:I|1|SATU)\b', txt))

            if is_toc:
                excluded_pages.append({"page": p + 1, "type": "daftar_isi", "keterangan": "Halaman daftar isi / tabel konten dokumen"})
            elif is_audio_or_team:
                excluded_pages.append({"page": p + 1, "type": "petunjuk_administratif", "keterangan": "Petunjuk media audio / daftar pengisi suara"})
            elif is_cover:
                excluded_pages.append({"page": p + 1, "type": "cover_judul", "keterangan": "Halaman sampul / judul / identitas modul"})
            else:
                # Cek apakah sudah masuk Bab I atau Pendahuluan materi teks
                if re.search(r'\bBAB\s+(?:I|1|SATU)\b', txt, re.IGNORECASE) or (p >= 3 and "pendahuluan" in lower and len(txt) > 300):
                    start_page = p + 1
                    break
                else:
                    excluded_pages.append({"page": p + 1, "type": "halaman_awal_lainnya", "keterangan": "Halaman administratif sebelum materi utama"})

        # 2. Cari batas akhir: Halaman di mana Daftar Pustaka dimulai
        for p in range(int(total_pages * 0.7), total_pages):
            txt = doc[p].get_text("text").strip()
            lower = txt.lower()

            if re.search(r'\bdaftar\s+pustaka\b', lower):
                end_page = p # halaman sebelum daftar pustaka
                for back_p in range(p, total_pages):
                    back_txt = doc[back_p].get_text("text").strip().lower()
                    keterangan = "Daftar pustaka / referensi rujukan"
                    if "terima kasih" in back_txt:
                        keterangan = "Halaman penutup / ucapan terima kasih"
                    elif "profil" in back_txt:
                        keterangan = "Profil penulis / tim penyusun"
                    excluded_pages.append({"page": back_p + 1, "type": "daftar_pustaka_penutup", "keterangan": keterangan})
                break

        return start_page, end_page, excluded_pages

    def extract(self, pdf_path: str) -> Dict[str, Any]:
        doc = fitz.open(pdf_path)
        total_pages = len(doc)
        filename = os.path.basename(pdf_path)

        # 1. Tentukan batas halaman konten utama
        start_page, end_page, excluded_pages = self.find_content_boundaries(doc)

        # 2. Ekstrak hanya halaman konten utama
        raw_content_pages = []
        top_lines_freq = {}
        bottom_lines_freq = {}

        for p_idx in range(start_page - 1, end_page):
            p_num = p_idx + 1
            text = doc[p_idx].get_text("text") or ""
            lines = [l.strip() for l in text.split("\n") if l.strip()]

            if lines:
                first = lines[0]
                last = lines[-1]
                top_lines_freq[first] = top_lines_freq.get(first, 0) + 1
                bottom_lines_freq[last] = bottom_lines_freq.get(last, 0) + 1

            raw_content_pages.append({
                "page": p_num,
                "lines": lines,
                "raw_text": text
            })

        doc.close()

        # 3. Deteksi running header & footer berulang
        threshold = max(3, int(len(raw_content_pages) * 0.2))
        repeat_headers = {l for l, c in top_lines_freq.items() if c >= threshold}
        repeat_footers = {l for l, c in bottom_lines_freq.items() if c >= threshold}

        page_num_regex = re.compile(
            r'^(?:halaman|hal\.?|page)?\s*[-–—]?\s*\d+\s*(?:dari|of|\/)?\s*\d*\s*[-–—]?$',
            re.IGNORECASE
        )

        cleaned_pages = []
        total_word_count = 0

        for rcp in raw_content_pages:
            p_num = rcp["page"]
            clean_lines = []
            total_l = len(rcp["lines"])

            for idx, line in enumerate(rcp["lines"]):
                if page_num_regex.match(line):
                    continue
                if idx < 3 and line in repeat_headers:
                    continue
                if idx >= total_l - 3 and line in repeat_footers:
                    continue
                clean_lines.append(line)

            joined_text = "\n".join(clean_lines)
            joined_text = re.sub(r'(\w+)-\s*\n\s*(\w+)', r'\1\2', joined_text)
            joined_text = re.sub(r'[ \t]+', ' ', joined_text).strip()

            word_count = len(joined_text.split()) if joined_text else 0
            total_word_count += word_count

            cleaned_pages.append({
                "page": p_num,
                "word_count": word_count,
                "text": joined_text
            })

        # 4. Analisis Bab & Subbab dari Konten Utama Murni
        chapter_regex = re.compile(
            r'^(?:BAB|CHAPTER|MODUL)\s+([0-9IVXLCDM]+|SATU|DUA|TIGA|EMPAT|LIMA|ENAM|TUJUH|DELAPAN|SEMBILAN|SEPULUH)\b[\s.:\-–—]*(.*)$',
            re.IGNORECASE
        )
        subchapter_regex = re.compile(
            r'^(?:([0-9]+\.[0-9]+(?:\.[0-9]+)?|[A-Z]\.))\s+([A-Z0-9].*)$'
        )

        chapters = []
        current_chapter = {
            "chapter_number": 1,
            "chapter_title": "PENDAHULUAN / MATERI AWAL",
            "page_start": cleaned_pages[0]["page"] if cleaned_pages else 1,
            "page_end": cleaned_pages[0]["page"] if cleaned_pages else 1,
            "sections": [],
            "content": ""
        }
        current_section = {
            "section_title": "Materi Utama",
            "page": cleaned_pages[0]["page"] if cleaned_pages else 1,
            "content": ""
        }

        chapter_count = 0

        for cp in cleaned_pages:
            p_num = cp["page"]
            lines = cp["text"].split("\n")

            line_idx = 0
            while line_idx < len(lines):
                line = lines[line_idx].strip()
                if not line:
                    line_idx += 1
                    continue

                chap_match = chapter_regex.match(line)
                if chap_match:
                    chap_num_str = chap_match.group(1).upper()
                    chap_name = chap_match.group(2).strip()

                    if not chap_name or len(chap_name) < 2:
                        peek = line_idx + 1
                        while peek < len(lines):
                            nxt = lines[peek].strip()
                            if nxt and not nxt.isdigit() and len(nxt) < 120:
                                chap_name = nxt
                                line_idx = peek
                                break
                            peek += 1

                    if current_section["content"].strip():
                        current_chapter["sections"].append(current_section)
                    if current_chapter["content"].strip():
                        current_chapter["page_end"] = p_num
                        chapters.append(current_chapter)

                    chapter_count += 1
                    full_title = f"BAB {chap_num_str}: {chap_name}".strip(": ") if chap_name else f"BAB {chap_num_str}"
                    current_chapter = {
                        "chapter_number": chapter_count,
                        "chapter_title": full_title,
                        "page_start": p_num,
                        "page_end": p_num,
                        "sections": [],
                        "content": ""
                    }
                    current_section = {
                        "section_title": "Materi Utama",
                        "page": p_num,
                        "content": ""
                    }
                    line_idx += 1
                    continue

                sub_match = subchapter_regex.match(line)
                if sub_match and len(line) < 100:
                    if current_section["content"].strip():
                        current_chapter["sections"].append(current_section)

                    current_section = {
                        "section_title": line,
                        "page": p_num,
                        "content": ""
                    }
                    line_idx += 1
                    continue

                current_section["content"] += line + "\n"
                current_chapter["content"] += line + "\n"
                line_idx += 1

            current_chapter["page_end"] = p_num

        if current_section["content"].strip():
            current_chapter["sections"].append(current_section)
        if current_chapter["content"].strip():
            chapters.append(current_chapter)

        for ch in chapters:
            ch["content"] = ch["content"].strip()
            for sec in ch["sections"]:
                sec["content"] = sec["content"].strip()

        return {
            "metadata": {
                "file_name": filename,
                "total_pages_pdf": total_pages,
                "total_content_pages": len(cleaned_pages),
                "content_page_range": f"Halaman {start_page} s/d Halaman {end_page}",
                "target_total_soal": len(cleaned_pages) * 10,
                "total_words_content": total_word_count,
                "excluded_pages_count": len(excluded_pages)
            },
            "excluded_pages_summary": {
                "front_matter": [p["page"] for p in excluded_pages if p["page"] < start_page],
                "back_matter": [p["page"] for p in excluded_pages if p["page"] > end_page]
            },
            "chapters": chapters,
            "pages": cleaned_pages
        }

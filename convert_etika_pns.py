import fitz
import json
import os
import re
import sys
import shutil

sys.stdout.reconfigure(encoding="utf-8")

PDF_PATH = "(DONE)1. Modul_Etika PNS_after UAT.pdf"
OUTPUT_DIR = "modul_json"
os.makedirs(OUTPUT_DIR, exist_ok=True)

def clean_page_text(raw_text: str) -> str:
    lines = [l.strip() for l in raw_text.splitlines() if l.strip()]
    cleaned_lines = []
    
    for idx, line in enumerate(lines):
        # 1. Skip top page number if first line is just digits
        if idx == 0 and re.match(r'^\d+$', line):
            continue
        # 2. Skip running header
        if 'E-Learning Persiapan Tes Substansi' in line:
            continue
        # 3. Skip review stamp tags (isolated words from UAT annotations)
        if line.lower() in ['baru', 'revisi', 'semua', 'semua baru']:
            continue
        cleaned_lines.append(line)
        
    joined = "\n".join(cleaned_lines)
    # Rejoin hyphenated line breaks (e.g. per-\nbuatan -> perbuatan)
    joined = re.sub(r'(\w+)-\s*\n\s*(\w+)', r'\1\2', joined)
    # Normalize multiple spaces on a single line
    joined = re.sub(r'[ \t]+', ' ', joined)
    return joined.strip()

def build_etika_pns_json():
    doc = fitz.open(PDF_PATH)
    total_pages = len(doc)
    
    start_page = 4
    end_page = 31
    front_matter = [1, 2, 3]
    back_matter = [32, 33]
    
    pages = []
    total_words = 0
    
    for p_idx in range(start_page - 1, end_page):
        p_num = p_idx + 1
        raw_text = doc[p_idx].get_text("text")
        clean_text = clean_page_text(raw_text)
        w_count = len(clean_text.split()) if clean_text else 0
        total_words += w_count
        
        pages.append({
            "page": p_num,
            "word_count": w_count,
            "text": clean_text
        })
        
    doc.close()
    
    # Define chapter / section structure matching book's TOC and headings
    # A. Pengantar (p4)
    # B. Prinsip-Prinsip Moral Pegawai Negeri Sipil (PNS) (p4-6)
    # C. Pengertian Etika (p6)
    # D. Kode Etik PNS (p6-11)
    # E. Kode Etik Instansi dan Kode Etik Profesi (p11-12)
    # F. Implementasi Etika dalam Organisasi Pemerintah (p12-15)
    # G. Implementasi Nilai Dasar PNS, Kode Etik dan Kode Perilaku PNS di lingkungan Kemenkeu (p15-21)
    # H. Penegakan Kode Etik dan Kode Perilaku PNS di lingkungan Kemenkeu (p21-23)
    # I. Implementasi Kode Etik dan Kode Perilaku PNS di lingkungan Kemenkeu pada Era SKF (p23-30)
    # PENUTUP (p31)
    
    chapter_defs = [
        {
            "chapter_number": 1,
            "chapter_title": "A. Pengantar",
            "page_start": 4,
            "page_end": 4,
            "sections": [
                {"section_title": "Pengantar Etika Pegawai Negeri Sipil", "page": 4}
            ]
        },
        {
            "chapter_number": 2,
            "chapter_title": "B. Prinsip-Prinsip Moral Pegawai Negeri Sipil (PNS)",
            "page_start": 4,
            "page_end": 6,
            "sections": [
                {"section_title": "1. Profesionalisme", "page": 4},
                {"section_title": "2. Integritas Moral yang Tinggi", "page": 4},
                {"section_title": "3. Tanggung Jawab terhadap Kepentingan Publik", "page": 5},
                {"section_title": "4. Berpihak kepada Kebenaran dan Kejujuran", "page": 5},
                {"section_title": "5. Bertindak secara Adil", "page": 5},
                {"section_title": "6. Jangan Menghalalkan Segala Cara untuk Mencapai Tujuan", "page": 5},
                {"section_title": "7. Jangan Lakukan pada Orang Lain Apa yang Anda Sendiri Tidak Mau Dilakukan pada Anda", "page": 6}
            ]
        },
        {
            "chapter_number": 3,
            "chapter_title": "C. Pengertian Etika",
            "page_start": 6,
            "page_end": 6,
            "sections": [
                {"section_title": "Konsep dan Definisi Etika", "page": 6}
            ]
        },
        {
            "chapter_number": 4,
            "chapter_title": "D. Kode Etik PNS",
            "page_start": 6,
            "page_end": 11,
            "sections": [
                {"section_title": "Tujuan dan Fungsi Kode Etik PNS", "page": 6},
                {"section_title": "Nilai Dasar dan Kode Perilaku ASN (UU No. 20 Tahun 2023)", "page": 8},
                {"section_title": "1. Etika Pegawai Negeri Sipil dalam Bernegara (PP No. 42 Tahun 2004)", "page": 9},
                {"section_title": "2. Etika Pegawai Negeri Sipil dalam Berorganisasi (PP No. 42 Tahun 2004)", "page": 9},
                {"section_title": "3. Etika Pegawai Negeri Sipil dalam Bermasyarakat (PP No. 42 Tahun 2004)", "page": 10},
                {"section_title": "4. Etika Pegawai Negeri Sipil terhadap Diri Sendiri (PP No. 42 Tahun 2004)", "page": 10},
                {"section_title": "5. Etika Pegawai Negeri Sipil terhadap Sesama Pegawai Negeri Sipil (PP No. 42 Tahun 2004)", "page": 10}
            ]
        },
        {
            "chapter_number": 5,
            "chapter_title": "E. Kode Etik Instansi dan Kode Etik Profesi",
            "page_start": 11,
            "page_end": 12,
            "sections": [
                {"section_title": "Ketentuan Pembentukan Kode Etik Instansi dan Kode Etik Profesi", "page": 11}
            ]
        },
        {
            "chapter_number": 6,
            "chapter_title": "F. Implementasi Etika dalam Organisasi Pemerintah",
            "page_start": 12,
            "page_end": 15,
            "sections": [
                {"section_title": "Prinsip Pelaksanaan Etika Organisasi Pemerintah", "page": 12},
                {"section_title": "1. Etos Kerja", "page": 13},
                {"section_title": "2. Moralitas Pribadi", "page": 13},
                {"section_title": "3. Kepemimpinan yang Bermutu", "page": 14},
                {"section_title": "Faktor Penentu Etika Organisasi (Lingkungan Kerja)", "page": 14}
            ]
        },
        {
            "chapter_number": 7,
            "chapter_title": "G. Implementasi Nilai Dasar PNS, Kode Etik dan Kode Perilaku Pegawai Negeri Sipil di lingkungan Kementerian Keuangan",
            "page_start": 15,
            "page_end": 21,
            "sections": [
                {"section_title": "1. Implementasi Nilai Dasar PNS (SE-12/MK.01/2019)", "page": 15},
                {"section_title": "2. Implementasi Kode Etik dan Kode Perilaku PNS (PMK No. 190/PMK.01/2018)", "page": 16},
                {"section_title": "Pedoman Sikap, Tingkah Laku, dan Perbuatan Pegawai Kemenkeu", "page": 17},
                {"section_title": "Kewajiban dan Larangan Khusus Pegawai Kemenkeu", "page": 18},
                {"section_title": "Peran Atasan Langsung dan Unit Kepatuhan Internal (UKI)", "page": 19}
            ]
        },
        {
            "chapter_number": 8,
            "chapter_title": "H. Penegakan Kode Etik dan Kode Perilaku Pegawai Negeri Sipil di lingkungan Kementerian Keuangan",
            "page_start": 21,
            "page_end": 23,
            "sections": [
                {"section_title": "1. Pelanggaran Kode Etik Pegawai Negeri Sipil dan Tata Cara Pengaduan", "page": 21},
                {"section_title": "2. Pembentukan dan Tata Sidang Majelis Kode Etik", "page": 22},
                {"section_title": "Rekomendasi Putusan Majelis dan Penjatuhan Sanksi Moral", "page": 23}
            ]
        },
        {
            "chapter_number": 9,
            "chapter_title": "I. Implementasi Kode Etik dan Kode Perilaku Pegawai Negeri Sipil di lingkungan Kementerian Keuangan pada Era Sistem Kerja Fleksibel",
            "page_start": 23,
            "page_end": 30,
            "sections": [
                {"section_title": "1. Kode Etik pada Sistem Kerja Fleksibel (KMK No. 416/KMK.01/2023)", "page": 24},
                {"section_title": "Penggunaan Sarana dan Prasarana TIK Secara Etis & Keamanan Siber", "page": 25},
                {"section_title": "2. Panduan Etika dalam Acara Resmi Kedinasan Secara Virtual (SE-42/MK.01/2020)", "page": 26},
                {"section_title": "Tata Tertib dan Ketentuan Khusus Acara Resmi Kedinasan Virtual", "page": 27},
                {"section_title": "3. Nilai-Nilai Kemenkeu pada Pelaksanaan Sistem Kerja Fleksibel", "page": 29}
            ]
        },
        {
            "chapter_number": 10,
            "chapter_title": "PENUTUP",
            "page_start": 31,
            "page_end": 31,
            "sections": [
                {"section_title": "Intisari Moral dan Komitmen Integritas Pegawai Negeri Sipil", "page": 31}
            ]
        }
    ]
    
    # Map page texts to chapters
    page_text_map = {p["page"]: p["text"] for p in pages}
    chapters = []
    
    for cdef in chapter_defs:
        p_start = cdef["page_start"]
        p_end = cdef["page_end"]
        
        chap_text_list = []
        for p in range(p_start, p_end + 1):
            if p in page_text_map:
                chap_text_list.append(page_text_map[p])
        full_chap_content = "\n\n".join(chap_text_list)
        
        sections = []
        for sdef in cdef["sections"]:
            sec_p = sdef["page"]
            sections.append({
                "section_title": sdef["section_title"],
                "page": sec_p,
                "content": page_text_map.get(sec_p, "")
            })
            
        chapters.append({
            "chapter_number": cdef["chapter_number"],
            "chapter_title": cdef["chapter_title"],
            "page_start": p_start,
            "page_end": p_end,
            "sections": sections,
            "content": full_chap_content
        })

    result = {
        "metadata": {
            "file_name": os.path.basename(PDF_PATH),
            "total_pages_pdf": total_pages,
            "total_content_pages": len(pages),
            "content_page_range": f"Halaman {start_page} s/d Halaman {end_page}",
            "target_total_soal": len(pages) * 10,
            "total_words_content": total_words,
            "excluded_pages_count": len(front_matter) + len(back_matter)
        },
        "excluded_pages_summary": {
            "front_matter": front_matter,
            "back_matter": back_matter
        },
        "chapters": chapters,
        "pages": pages
    }
    
    # Output file paths
    # 1. Primary matching filename pattern: 1. Modul_Etika PNS_after UAT.json
    out_primary = os.path.join(OUTPUT_DIR, "1. Modul_Etika PNS_after UAT.json")
    with open(out_primary, "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)
    print(f"✅ Generated primary JSON: {out_primary}")
    
    # 2. Alias: 1. Modul Etika PNS.json
    out_alias = os.path.join(OUTPUT_DIR, "1. Modul Etika PNS.json")
    shutil.copyfile(out_primary, out_alias)
    print(f"✅ Created alias JSON: {out_alias}")

    # 3. Update README_MODUL.md
    readme_path = os.path.join(OUTPUT_DIR, "README_MODUL.md")
    readme_content = f"""# 📚 Data Dokumen Modul (Format JSON Murni)

Seluruh file PDF di bawah ini telah dikonversi secara murni ke dalam format JSON terstruktur.
Struktur JSON memuat:
- `metadata`: Informasi umum dokumen (nama file, total halaman, total kata, total bab)
- `chapters`: Pembagian teks per Bab dan Subbab asli buku modul
- `pages`: Teks bersih per halaman asli buku (running header/footer telah disaring)

| No | Nama File Asli (PDF) | Total Hal. PDF | Hal. Konten Materi | Rentang Halaman Materi | Target Soal (10x) | File JSON |
|---|---|---|---|---|---|---|
| 1 | (DONE)1. Modul_Etika PNS_after UAT.pdf | 33 hal | 28 hal | Halaman 4 s/d Halaman 31 | **280 soal** | `1. Modul_Etika PNS_after UAT.json` |
| 2 | 2. Modul Nilai-Nilai Kemenkeu-UPKP.pdf | 50 hal | 44 hal | Halaman 5 s/d Halaman 48 | **440 soal** | `2. Modul Nilai-Nilai Kemenkeu-UPKP.json` |
| 3 | (DONE) 3. Tata Aturan Kepegawaian.pdf | 74 hal | 67 hal | Halaman 4 s/d Halaman 70 | **670 soal** | `3. Tata Aturan Kepegawaian.json` |
| 4 | 4. Pengelolaan Keuangan Negara.pdf | 80 hal | 71 hal | Halaman 7 s/d Halaman 77 | **710 soal** | `4. Pengelolaan Keuangan Negara.json` |
| 5 | 5. Wawasan Kebangsaan.pdf | 81 hal | 74 hal | Halaman 6 s/d Halaman 79 | **740 soal** | `5. Wawasan Kebangsaan.json` |
"""
    with open(readme_path, "w", encoding="utf-8") as f:
        f.write(readme_content)
    print(f"✅ Updated README index: {readme_path}")
    
    print("\n--- Summary of Extraction ---")
    print(f"File Name: {result['metadata']['file_name']}")
    print(f"Total Pages in PDF: {result['metadata']['total_pages_pdf']}")
    print(f"Content Pages Extracted: {result['metadata']['total_content_pages']} ({result['metadata']['content_page_range']})")
    print(f"Total Word Count: {result['metadata']['total_words_content']:,} words")
    print(f"Chapters Count: {len(result['chapters'])}")
    print(f"Target Total Soal (10x per page): {result['metadata']['target_total_soal']} butir soal")

if __name__ == "__main__":
    build_etika_pns_json()

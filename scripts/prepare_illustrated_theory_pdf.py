"""Prepare a verified import manifest and cropped scenario images from a question PDF.

This helper intentionally crops only the scenario area. The correct answer and
explanation remain in database fields and never appear inside the learner image.
Run it with the bundled Codex Python runtime after rendering the PDF pages with
Poppler at 150 DPI.
"""

from __future__ import annotations

import json
import re
import sys
from collections import Counter
from pathlib import Path

import pdfplumber
from PIL import Image


def clean(value: str) -> str:
    return re.sub(r"\s+", " ", value).strip()


def parse_page(text: str, source_id: int) -> dict[str, object]:
    lines = [clean(line) for line in text.splitlines() if clean(line)]
    header_index = next(
        (index for index, line in enumerate(lines) if re.search(r"Question\s+\d+\s*/\s*\d+\s+Cat.gorie\s*:", line)),
        None,
    )
    if header_index is None:
        raise ValueError(f"Question header missing on PDF page {source_id}.")

    category_match = re.search(r"Cat.gorie\s*:\s*(.+)$", lines[header_index])
    if category_match is None:
        raise ValueError(f"Category missing on PDF page {source_id}.")
    theme = clean(category_match.group(1))

    question_parts: list[str] = []
    explanation_parts: list[str] = []
    options: dict[str, str] = {}
    correct_letters: list[str] = []
    state = "question"
    current_option: str | None = None

    for line in lines[header_index + 1 :]:
        if line.startswith("Document de pratique"):
            break

        answer_match = re.search(r"Bonne r.ponse\s*:\s*([A-D](?:\s*,\s*[A-D])*)", line)
        if answer_match:
            correct_letters = [letter.strip() for letter in answer_match.group(1).split(",")]
            state = "answer"
            current_option = None
            continue

        explanation_match = re.search(r"^Explication\s*:\s*(.*)$", line)
        if explanation_match:
            state = "explanation"
            explanation_parts.append(clean(explanation_match.group(1)))
            current_option = None
            continue

        option_match = re.search(r"\b([A-D])\.\s+(.+)$", line)
        if option_match and state != "explanation":
            current_option = option_match.group(1)
            options[current_option] = clean(option_match.group(2))
            state = "option"
            continue

        if state == "question":
            question_parts.append(line)
        elif state == "option" and current_option is not None:
            options[current_option] = clean(f"{options[current_option]} {line}")
        elif state == "explanation":
            explanation_parts.append(line)

    if set(options) != {"A", "B", "C", "D"} or not correct_letters or not question_parts or not explanation_parts:
        raise ValueError(f"Incomplete question structure on PDF page {source_id}.")

    return {
        "source_id": source_id,
        "theme": theme,
        "question": clean(" ".join(question_parts)),
        "options": options,
        "correct_letters": correct_letters,
        "explanation": clean(" ".join(explanation_parts)),
        "media_path": f"theory-diagrams/pdf-illustrated/cdr-pdf-{source_id:04d}.png",
    }


def main() -> None:
    if len(sys.argv) != 5:
        raise SystemExit(
            "Usage: prepare_illustrated_theory_pdf.py PDF RENDERED_PAGES IMAGE_OUTPUT MANIFEST_OUTPUT"
        )

    pdf_path = Path(sys.argv[1])
    rendered_pages = Path(sys.argv[2])
    image_output = Path(sys.argv[3])
    manifest_output = Path(sys.argv[4])
    image_output.mkdir(parents=True, exist_ok=True)
    manifest_output.parent.mkdir(parents=True, exist_ok=True)

    records: list[dict[str, object]] = []
    with pdfplumber.open(pdf_path) as pdf:
        for page_number, page in enumerate(pdf.pages, start=1):
            text = page.extract_text() or ""
            record = parse_page(text, page_number)
            rendered = rendered_pages / f"code-route-{page_number:02d}.png"
            if not rendered.is_file():
                raise ValueError(f"Rendered PDF page missing: {rendered}")

            # PDF pages were rendered at 150 DPI. This rectangle contains the
            # scenario prompt and diagram, while excluding answer options.
            with Image.open(rendered) as image:
                crop = image.crop((150, 150, 1100, 670))
                crop.save(image_output / f"cdr-pdf-{page_number:04d}.png", optimize=True)
            records.append(record)

    source_ids = [record["source_id"] for record in records]
    if len(records) != len(set(source_ids)):
        raise ValueError("Source IDs are not unique.")

    with manifest_output.open("w", encoding="utf-8") as handle:
        json.dump(records, handle, ensure_ascii=False, indent=2)

    print(json.dumps({"records": len(records), "themes": dict(Counter(record["theme"] for record in records))}, ensure_ascii=False))


if __name__ == "__main__":
    main()

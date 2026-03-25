#!/usr/bin/env python3
"""
scripts/transcribe_faster_whisper.py

PHP → Python bridge для faster-whisper.
Виводить розпізнаний текст у stdout (один рядок на сегмент).

Використання:
  python3 transcribe_faster_whisper.py \
      --model-dir /models/faster-whisper-large-v3 \
      --lang auto \
      --device cpu \
      --file /app/storage/input/call_42.ogg
"""

import sys
import argparse


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="faster-whisper transcriber bridge")
    parser.add_argument("--model-dir", required=True, help="Шлях до директорії моделі")
    parser.add_argument("--lang",      default="auto", help="Мова (uk/ru/auto)")
    parser.add_argument("--device",    default="cpu",  help="cpu або cuda")
    parser.add_argument("--file",      required=True,  help="Шлях до аудіо файлу")
    return parser.parse_args()


def transcribe(model_dir: str, lang: str, device: str, audio_path: str) -> str:
    from faster_whisper import WhisperModel

    # Визначаємо кількість воркерів залежно від пристрою
    cpu_threads  = 4
    num_workers  = 1

    model = WhisperModel(
        model_dir,
        device=device,
        cpu_threads=cpu_threads,
        num_workers=num_workers,
        local_files_only=True,       # модель вже завантажена в образі
    )

    # Якщо lang='auto' — передаємо None, faster-whisper автовизначить
    language = None if lang == "auto" else lang

    segments, _info = model.transcribe(
        audio_path,
        language=language,
        beam_size=5,
        vad_filter=True,             # відфільтрує тишу
    )

    lines = [segment.text.strip() for segment in segments if segment.text.strip()]
    return " ".join(lines)


def main() -> None:
    args = parse_args()

    try:
        text = transcribe(args.model_dir, args.lang, args.device, args.file)
        # Виводимо ТІЛЬКИ текст у stdout — PHP читає саме його
        print(text, end="")
        sys.exit(0)
    except Exception as exc:  # noqa: BLE001
        # Повідомлення про помилку — в stderr, щоб не змішувалось з текстом
        print(f"ERROR: {exc}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
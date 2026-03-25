FROM php:8.4-cli-bookworm

ENV DEBIAN_FRONTEND=noninteractive
ENV PYTHONUNBUFFERED=1

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
    python3 \
    python3-pip \
    ffmpeg \
    git \
    build-essential \
    cmake \
    curl \
    unzip \
    libsndfile1 \
    # залежності для phpredis
    autoconf \
    && rm -rf /var/lib/apt/lists/*

# PHP ext: redis
RUN pecl install redis \
 && docker-php-ext-enable redis

# PHP ext: pdo_mysql 
RUN docker-php-ext-install pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# whisper.cpp
RUN git clone --depth 1 https://github.com/ggml-org/whisper.cpp.git /opt/whisper.cpp \
 && cmake -S /opt/whisper.cpp -B /opt/whisper.cpp/build \
 && cmake --build /opt/whisper.cpp/build -j"$(nproc)"

# Python lib ─────────────────────────────────────────────────────────
RUN pip3 install --no-cache-dir --break-system-packages \
    faster-whisper==1.1.1 \
    requests==2.32.3 \
    soundfile==0.12.1 \
    numpy==1.26.4 \
    pydub==0.25.1 \
    huggingface_hub==0.24.7

# whisper.cpp model (ggml)
RUN mkdir -p /models/whisper.cpp \
 && curl -L https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-large-v3.bin \
    -o /models/whisper.cpp/ggml-large-v3.bin

# faster-whisper model
RUN python3 -c "\
from huggingface_hub import snapshot_download; \
snapshot_download( \
    repo_id='Systran/faster-whisper-large-v3', \
    local_dir='/models/faster-whisper-large-v3', \
    local_dir_use_symlinks=False \
)"

RUN mkdir -p /app/storage/input /app/storage/logs

ENV WHISPER_CPP_BIN=/opt/whisper.cpp/build/bin/whisper-cli
ENV WHISPER_CPP_MODEL=/models/whisper.cpp/ggml-large-v3.bin
ENV FASTER_WHISPER_MODEL_DIR=/models/faster-whisper-large-v3
# Available: whisper_cpp | faster_whisper
ENV TRANSCRIBER=whisper_cpp
ENV WHISPER_CPP_LANG=auto
ENV WHISPER_CPP_THREADS=4
ENV FASTER_WHISPER_LANG=auto
ENV FASTER_WHISPER_DEVICE=cpu

COPY composer.json composer.lock* /app/
RUN composer install --no-interaction --optimize-autoloader

CMD ["php", "bin/worker.php", "transcription", "--loop"]
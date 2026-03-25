# Audio Transcriber

Automatic transcription system.

Reads audio file URLs (.ogg / .wav) from the database, enqueues them via Redis, downloads, transcribes using the selected engine, and saves the text back to the database.

---

## Requirements

- Docker and Docker Compose

---

## Installation & Setup

### 1. Clone the repository

### 2. Create a .env file

```bash
cp .env.example .env
```

Fill it with your credentials ([Configuration](#configuration)).

### 3. Build and start the container

```bash
docker-compose up -d --build
```

### 4. Install PHP dependencies

```bash
docker exec -it audio-transcriber composer install
```

### 5. Fill the queue

```bash
docker exec -it audio-transcriber php bin/console.php dispatch:transcription
```

### 6. Start the worker

```bash
# One record (for testing):
docker exec -it audio-transcriber php bin/worker.php transcription

# Infinite loop:
docker exec -it audio-transcriber php bin/worker.php transcription --loop
```

---

## Configuration

`.env.example`:

```dotenv
# DB
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=ringostat
DB_USERNAME=root
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# Transcriber
# whisper_cpp | faster_whisper
TRANSCRIBER=faster_whisper

# whisper.cpp
WHISPER_CPP_LANG=auto          # auto | uk | ru | en
WHISPER_CPP_THREADS=4

# faster-whisper
FASTER_WHISPER_LANG=auto       # auto | uk | ru | en
FASTER_WHISPER_DEVICE=cpu      # cpu | cuda
```

---
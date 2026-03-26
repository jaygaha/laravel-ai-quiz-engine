<div align="center">

# QuizForge

**An AI-Powered Exam & Quiz Engine built with Laravel 12**

A full-stack SaaS-style application where teachers create and publish exams, students take them with real-time scoring, and an AI layer generates questions, provides hints, and personalises the learning experience — all powered by the Laravel AI SDK and Laravel Boost.

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-4.x-4E56A6?logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![Tests](https://github.com/jaygaha/laravel-ai-quiz-engine/actions/workflows/ci.yml/badge.svg)](https://github.com/jaygaha/laravel-ai-quiz-engine/actions/workflows/ci.yml)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

</div>

---

## Table of Contents

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [Features](#features)
- [Prerequisites](#prerequisites)
- [Quick Start (Laravel Sail)](#quick-start-laravel-sail)
- [Manual Setup (without Docker)](#manual-setup-without-docker)
- [Environment Reference](#environment-reference)
- [Running Tests](#running-tests)
- [CI/CD Pipeline](#cicd-pipeline)
- [Project Structure](#project-structure)
- [Development Roadmap](#development-roadmap)
- [Contributing](#contributing)

---

## Overview

QuizForge is a production-ready SaaS application built on the modern Laravel stack. It features multi-role auth, exam CRUD, AI-powered question generation and grading via the **Laravel AI SDK**, real-time WebSocket broadcasting via **Laravel Reverb**, PDF export, server-enforced exam timer, **pgvector** semantic embeddings for personalised recommendations and RAG-enhanced grading, and per-user AI cost controls.

The project is also a reference implementation for:

- **Laravel Boost** — an MCP dev-time AI server that acts as a Laravel expert inside your editor (Cursor / Claude Code), enabling rapid scaffolding with full context of your schema, logs, routes, and 17 000+ doc pages.
- **Laravel AI SDK** — the official runtime SDK for AI features (agents, structured output, embeddings, queued generation, streaming).

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.3+ |
| Framework | Laravel 12 |
| Reactive UI | Livewire 4 + Flux UI 2 (free) |
| CSS | Tailwind CSS 4 + Vite 7 |
| Font | Lexend (Google Fonts) |
| Authentication | Laravel Fortify (2FA, email verification) |
| Real-time | Laravel Reverb (WebSocket) + Laravel Echo |
| PDF | barryvdh/laravel-dompdf |
| Database | PostgreSQL 17+ with `pgvector` (semantic embeddings + similarity search) |
| Queue / Cache | Database driver (Redis-swappable) |
| Testing | Pest 4 + pest-plugin-laravel |
| Code Style | Laravel Pint |
| Dev Tooling | Laravel Sail (Docker), Laravel Boost (MCP), Laravel Pail |
| CI/CD | GitHub Actions |
| AI | Laravel AI SDK v0.3+ — Anthropic (primary), Gemini, OpenAI, Ollama (local dev) |

---

## Features

### Implemented

**Authentication & Accounts**
- Role-based registration — users sign up as **Teacher** or **Student**
- Full Fortify authentication: login, registration, password reset, email verification
- Two-factor authentication (TOTP + recovery codes)
- Profile management and account deletion

**Teacher**
- Create, edit, and delete exams with title, description, and optional time limit
- Draft / Published workflow — students only see published exams
- Manage questions per exam: Multiple Choice, True / False, Short Answer
- Drag-free manual ordering of questions
- **AI question generation** — "Generate with AI" panel with topic, type, count (1–10), and difficulty inputs; review and confirm/discard before saving; batches > 5 are queued; generation history (last 5 per exam)
- **Question bank** — cross-exam question browser with search, exam/type filters, and "Add to exam" copy action
- **Bulk CSV import** — upload a CSV file to import questions in batch; malformed rows are skipped
- **Exam preview** — preview unpublished exams as a student would see them (submission blocked)
- **Results dashboard** — per-exam stats (average score, pass rate), student results table, "Export Results (PDF)" button
- **Live submission counter** — real-time WebSocket updates as students complete the exam (via Laravel Reverb)

**Student**
- Dashboard listing all published exams available to take
- Exam-taking interface with per-question navigation and **server-enforced countdown timer**
- **"Review Later" flags** — bookmark questions mid-exam for a second pass; filter tabs (All / Flagged); flags persist server-side
- Automatic answer scoring on submission (exact-match for MC/TF; **AI-graded for Short Answer**)
- **Real-time hints** — "Get a Hint" button streams a Socratic hint for short-answer questions via `wire:stream`
- Results page with per-question feedback, AI score badge, explanation, and improvement suggestion
- **PDF result card** — download a personal result PDF via signed email link (queued generation, 24h expiry)
- **Live leaderboard** — top-10 scores with medals, "You are #N" rank card, real-time updates via Echo

**AI Intelligence Layer (RAG + pgvector)**
- **Semantic embeddings** — questions and attempts are auto-embedded via queued jobs (`GenerateQuestionEmbeddingJob`, `GenerateAttemptEmbeddingJob`)
- **Similarity search** — `QuestionSimilarityService` uses pgvector cosine distance (`whereVectorSimilarTo`) for semantic question retrieval
- **RAG-enhanced grading** — `AutoGraderAgent` uses `SimilaritySearch` tool to find similar past questions before grading for consistency
- **Personalised recommendations** — student results page shows "Recommended Practice" questions from other exams based on incorrect answers
- **Struggled topics** — teacher results page shows questions with lowest correct-answer rates across all attempts
- **Backfill command** — `php artisan questions:backfill-embeddings` generates embeddings for existing questions

**AI Cost Controls**
- Per-user rate limiting (configurable per-minute limit via `AI_RATE_LIMIT_PER_MINUTE`)
- Token usage tracking — `ai_usages` table logs agent, model, tokens, and estimated cost per request
- **AI Usage dashboard** — teacher view with daily spend (7 days), per-model breakdown, total cost
- Daily budget hard stop — cache flag prevents further AI calls when `AI_DAILY_BUDGET` exceeded; graceful UI messages

**Platform**
- Light-mode-only Bento-grid UI (Laravel.com aesthetic)
- Teal primary palette with Coral accent for achievement elements
- Fully responsive — sidebar collapses to mobile drawer
- Custom error pages (401, 403, 404, 419, 429, 500, 503) matching the QuizForge theme
- Settings: profile, security (2FA + recovery codes)
- Docker services: web, PostgreSQL (pgvector), Reverb (WebSocket), queue worker, Mailpit (local email)

### Planned

- Production hardening — Redis queue/cache, caching, security, observability

---

## Prerequisites

| Requirement | Version | Notes |
|---|---|---|
| PHP | 8.3+ | 8.4 recommended |
| Composer | 2.x | |
| Node.js | 20+ | 22 LTS recommended |
| Docker | 24+ | Only required for Sail setup |
| PostgreSQL | 16+ | 17 recommended; 18 used in Sail |

> **Laravel Boost** (optional, dev-time only): requires your editor to support MCP servers. Works best with Cursor or Claude Code. See [Boost setup](#laravel-boost-setup).

---

## Quick Start (Laravel Sail)

Sail runs the full stack (PHP 8.5 + PostgreSQL 18 + Reverb WebSocket + queue worker + Mailpit) in Docker with zero local database setup.

**1. Clone and enter the project**

```bash
git clone https://github.com/jaygaha/laravel-ai-quiz-engine.git
cd laravel-ai-quiz-engine
```

**2. Install PHP dependencies via the Sail helper (no local PHP needed)**

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

**3. Copy and configure the environment file**

```bash
cp .env.example .env
```

Edit `.env` and set any values you want to override (the defaults work out-of-the-box with Sail).

**4. Start Sail**

```bash
./vendor/bin/sail up -d
```

**5. Generate the application key**

```bash
./vendor/bin/sail artisan key:generate
```

**6. Run migrations and seed demo data**

```bash
./vendor/bin/sail artisan migrate --seed
```

**7. Install Node dependencies and build assets**

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

**8. Open the app**

```
http://localhost
```

**9. Access Mailpit (local email testing)**

```
http://localhost:8025
```

> **Tip — development mode with hot reload:**
> ```bash
> composer run dev
> ```
> This starts `php artisan serve`, `queue:listen`, `reverb:start`, `pail` (log tail), and `vite` concurrently.

---

## Manual Setup (without Docker)

Requires PHP 8.3+, Composer, Node 20+, and a running PostgreSQL instance.

```bash
# 1. Clone
git clone https://github.com/jaygaha/laravel-ai-quiz-engine.git
cd laravel-ai-quiz-engine

# 2. PHP dependencies
composer install

# 3. Environment
cp .env.example .env
# Edit .env — set DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 4. App key
php artisan key:generate

# 5. Create the database, then migrate
php artisan migrate --seed

# 6. Node dependencies + assets
npm install
npm run build

# 7. Serve
php artisan serve
```

Visit `http://localhost:8000`.

---

## CSV Import Format

The bulk question import expects a CSV file with the following columns:

```csv
question,type,options,correct_answer
"What is the capital of France?",multiple_choice,"Paris|London|Berlin|Rome",Paris
"PHP is a compiled language",true_false,,false
"Explain polymorphism",short_answer,,"Objects of different types responding to the same interface"
```

- **type** — `multiple_choice`, `true_false`, or `short_answer`
- **options** — pipe-separated (`|`) for multiple choice; leave empty for other types
- **correct_answer** — required for all types
- Malformed or incomplete rows are silently skipped

---

## Environment Reference

| Variable | Default | Description |
|---|---|---|
| `APP_NAME` | `LaravelAIQuiz` | Displayed in the UI and emails |
| `APP_ENV` | `local` | `local`, `staging`, or `production` |
| `APP_DEBUG` | `true` | Set to `false` in production |
| `APP_URL` | `http://localhost` | Full public URL |
| `APP_PORT` | `8040` | Host port for the web container |
| `DB_CONNECTION` | `pgsql` | Database driver |
| `DB_HOST` | `pgsql` | `127.0.0.1` when not using Sail |
| `DB_DATABASE` | `ai_quiz` | Database name |
| `DB_USERNAME` | `sail` | Database user |
| `DB_PASSWORD` | `password` | Database password |
| `SESSION_DRIVER` | `database` | Session backend |
| `QUEUE_CONNECTION` | `database` | Queue backend (`redis` recommended for production) |
| `CACHE_STORE` | `database` | Cache backend |
| `MAIL_MAILER` | `log` | `log` locally; use `smtp` with Mailpit in Sail |
| `BROADCAST_CONNECTION` | `reverb` | WebSocket broadcasting via Laravel Reverb |

> **Reverb WebSocket** — run `php artisan reverb:install` to generate `REVERB_APP_ID`, `REVERB_APP_KEY`, and `REVERB_APP_SECRET`.

> **AI providers** — set at least one: `ANTHROPIC_API_KEY`, `GEMINI_API_KEY`, `OPENAI_API_KEY`. For local dev, Ollama works offline with no key (`OLLAMA_BASE_URL=http://localhost:11434`).

> **AI cost controls** — `AI_RATE_LIMIT_PER_MINUTE=30` (per-user), `AI_DAILY_BUDGET=5.00` (USD cap).

---

## Running Tests

The test suite uses **Pest 4** against a live PostgreSQL instance (same engine as production).

```bash
# Run the full suite
./vendor/bin/sail artisan test --compact

# Run a specific file
./vendor/bin/sail artisan test --compact tests/Feature/ExamCrudTest.php

# Run a specific test by name
./vendor/bin/sail artisan test --compact --filter="teacher can create exam"

# Generate a coverage report (requires Xdebug)
./vendor/bin/sail php -d xdebug.mode=coverage \
    vendor/bin/pest --coverage --min=80
```

> Without Sail, replace `./vendor/bin/sail` with direct commands and ensure `DB_HOST=127.0.0.1` is set.

**183 tests, 335 assertions, 91.7% code coverage** — covers auth, CRUD, AI agents, timer, broadcasting, PDF export, pgvector embeddings, similarity search, recommendations, and AI usage tracking.

---

## CI/CD Pipeline

GitHub Actions runs on every push and pull request.

```
push / pull_request
        │
        ├── code-quality   Pint --test (style check, annotates PR diff)
        ├── security-audit  composer audit + npm audit
        ├── build-assets    Vite production build → uploaded as artifact
        ├── tests           Pest on PHP 8.3 × 8.4 against PostgreSQL
        │                   (Xdebug coverage + 80% threshold on PHP 8.4)
        └── ci-gate         Single status check for branch-protection rules
```

**No secrets are required** — `livewire/flux` (free edition) resolves from the public GitHub registry. The `cd.yml` deployment workflow exists but is fully commented out until a deployment target is configured.

See `.github/workflows/ci.yml` and `.github/workflows/cd.yml` for full pipeline configuration.

---

## Project Structure

```
app/
├── Ai/Agents/           # QuestionGeneratorAgent, AutoGraderAgent, HintAgent
├── Console/Commands/    # BackfillQuestionEmbeddingsCommand
├── Enums/               # QuestionType, UserRole
├── Events/              # AttemptSubmittedEvent (ShouldBroadcast)
├── Jobs/                # PDF export, embedding generation, CSV import, AI question processing
├── Listeners/           # LogAiUsageListener (token tracking)
├── Models/              # User, Exam, Question, Attempt, AiUsage
├── Observers/           # QuestionObserver (auto-embed on create/update)
└── Services/            # QuestionSimilarityService (pgvector search)

resources/views/pages/
├── auth/                # Login, register, password reset, 2FA
├── student/             # Dashboard, take-exam, exam-results, leaderboard
└── teacher/             # Exam CRUD, questions, results, question-bank, AI usage

tests/Feature/           # 183 Pest tests (auth, CRUD, AI, timer, broadcasting, PDF, pgvector)
```

---

## Development Roadmap

| Phase | Description | Status |
|---|---|---|
| 1 | Auth scaffolding, role system, design system, CI/CD | ✅ Shipped |
| 2 | Database models, exam CRUD, student exam flow | ✅ Shipped |
| 3 | AI agents — question generation, auto-grading, hint streaming | ✅ Shipped |
| 4 | Real-time broadcasting, PDF export, timer, leaderboard, question bank | ✅ Shipped |
| 5 | pgvector RAG, personalised recommendations, AI cost controls | ✅ Shipped |
| 6 | Production hardening — Redis, caching, security, deploy | 📋 Planned |

See [`PLAN.md`](PLAN.md) for the roadmap and [`docs/BACKLOG.md`](docs/BACKLOG.md) for planned features.

---

## Laravel Boost Setup

[Laravel Boost](https://laravel.com/ai/boost) is a dev-time MCP server that gives your AI coding assistant full context of this application — schema, routes, logs, and 17 000+ pages of Laravel docs.

**Enable in Claude Code:**

```bash
# Install the MCP server (already in composer.json as a dev dependency)
php artisan boost:install
```

Then open Claude Code → Settings → MCP and enable the `laravel-boost` server.

**Enable in Cursor:**

Open Command Palette → `MCP: Open Settings` → add the `laravel-boost` entry from `php artisan boost:install --cursor`.

Once active, your AI assistant can read the live schema, run queries, inspect error logs, and search the docs — dramatically accelerating development.

---

## Contributing

1. Fork the repository and create a feature branch: `git checkout -b feature/your-feature`
2. Write or update Pest tests for your changes
3. Run the test suite: `./vendor/bin/pest --compact`
4. Ensure Pint passes: `./vendor/bin/pint --test`
5. Open a pull request — the CI gate must be green before merging

---

## License

This project is open-sourced under the [MIT License](LICENSE).

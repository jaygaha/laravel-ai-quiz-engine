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

QuizForge is a **phased learning project** that demonstrates how to build a production-ready SaaS application on the modern Laravel stack. The core exam engine (multi-role auth, exam CRUD, student exam-taking, auto-scoring) is fully implemented. Future phases layer the **Laravel AI SDK** on top for structured-output question generation, RAG-based auto-grading, semantic question search via `pgvector`, and real-time streaming.

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
| Database | PostgreSQL 17+ (`pgvector` ready) |
| Queue / Cache | Database driver (Redis-swappable) |
| Testing | Pest 4 + pest-plugin-laravel |
| Code Style | Laravel Pint |
| Dev Tooling | Laravel Sail (Docker), Laravel Boost (MCP), Laravel Pail |
| CI/CD | GitHub Actions |
| AI (planned) | Laravel AI SDK, OpenAI / Anthropic / Gemini |

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

**Student**
- Dashboard listing all published exams available to take
- Exam-taking interface with per-question navigation and optional countdown timer
- Automatic answer scoring on submission
- Results page with per-question feedback and overall score

**Platform**
- Light-mode-only Bento-grid UI (Laravel.com aesthetic)
- Teal primary palette with Coral accent for achievement elements
- Fully responsive — sidebar collapses to mobile drawer
- Settings: profile, security (2FA + recovery codes)

### Planned (AI Phase)

- AI question generation via structured-output agents (OpenAI / Anthropic)
- Short-answer auto-grading with explanation feedback
- Semantic similarity search on question bank (`pgvector` + embeddings)
- Personalised question recommendations based on past attempt embeddings
- Streaming question generation in the teacher UI
- Real-time student score broadcasts (Laravel Reverb)

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

Sail runs the full stack (PHP 8.5 + PostgreSQL 18) in Docker with zero local database setup.

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

> **Tip — development mode with hot reload:**
> ```bash
> composer run dev
> ```
> This starts `php artisan serve`, `queue:listen`, `pail` (log tail), and `vite` concurrently.

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

## Environment Reference

| Variable | Default | Description |
|---|---|---|
| `APP_NAME` | `LaravelAIQuiz` | Displayed in the UI and emails |
| `APP_ENV` | `local` | `local`, `staging`, or `production` |
| `APP_DEBUG` | `true` | Set to `false` in production |
| `APP_URL` | `http://localhost` | Full public URL |
| `DB_CONNECTION` | `pgsql` | Database driver |
| `DB_HOST` | `pgsql` | `127.0.0.1` when not using Sail |
| `DB_DATABASE` | `ai_quiz` | Database name |
| `DB_USERNAME` | `sail` | Database user |
| `DB_PASSWORD` | `password` | Database password |
| `SESSION_DRIVER` | `database` | Session backend |
| `QUEUE_CONNECTION` | `database` | Queue backend (`redis` recommended for production) |
| `CACHE_STORE` | `database` | Cache backend |
| `MAIL_MAILER` | `log` | `log` locally, `smtp`/`ses` in production |
| `BROADCAST_CONNECTION` | `log` | Set to `reverb` when real-time features land |

> **AI phase variables (coming in Phase 3):**
> ```env
> OPENAI_API_KEY=sk-...
> ANTHROPIC_API_KEY=sk-ant-...
> AI_DEFAULT_PROVIDER=openai
> ```

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

**Test coverage areas:**

| Suite | Files | What's tested |
|---|---|---|
| Auth | 6 files | Registration, login, password reset, email verification, 2FA |
| Core | 3 files | Dashboard routing, role middleware, exam CRUD |
| Student | 1 file | Exam taking, answer submission, score calculation |
| Settings | 2 files | Profile update, security settings |

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
.
├── app/
│   ├── Actions/Fortify/       # User creation and password reset logic
│   ├── Concerns/              # Shared validation rule traits
│   ├── Enums/
│   │   ├── QuestionType.php   # MultipleChoice | TrueFalse | ShortAnswer
│   │   └── UserRole.php       # Teacher | Student
│   ├── Http/
│   │   ├── Controllers/       # Minimal — UI is Livewire-first
│   │   └── Middleware/
│   │       └── EnsureUserRole.php
│   ├── Livewire/Actions/
│   │   └── Logout.php
│   ├── Models/
│   │   ├── Attempt.php        # Student exam attempt + answers JSON + score
│   │   ├── Exam.php           # Teacher's exam (draft/published)
│   │   ├── Question.php       # Per-exam question (3 types)
│   │   └── User.php           # Teacher or Student with 2FA
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── FortifyServiceProvider.php
│
├── database/
│   ├── factories/             # Exam, Question, Attempt, User factories
│   ├── migrations/            # 8 migrations — all schema-complete
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── ExamSeeder.php
│
├── resources/
│   ├── css/app.css            # Tailwind 4 + full Bento design system
│   ├── js/app.js
│   └── views/
│       ├── components/        # Blade components (logo, auth-header, etc.)
│       ├── layouts/           # App (sidebar) + Auth (simple / card) layouts
│       ├── pages/
│       │   ├── auth/          # Login, register, password reset, 2FA
│       │   ├── settings/      # Profile, security (⚡ Livewire SFCs)
│       │   ├── student/       # Dashboard, take-exam, exam-results (⚡)
│       │   └── teacher/exams/ # Index, create, edit, questions (⚡)
│       └── partials/          # Head, settings-heading
│
├── routes/
│   ├── web.php                # Public, teacher (role-gated), student (role-gated)
│   └── settings.php           # Auth-only settings routes
│
├── tests/
│   └── Feature/               # 13 feature test files
│
├── .github/
│   ├── workflows/
│   │   ├── ci.yml             # Full CI pipeline
│   │   └── cd.yml             # Deployment pipeline (commented out)
│   └── dependabot.yml         # Automated dependency updates
│
├── compose.yaml               # Laravel Sail: PHP 8.5 + PostgreSQL 18
├── phpunit.xml                # PHPUnit / Pest config with test env overrides
├── vite.config.js             # Vite 7 + Tailwind CSS 4 + laravel-vite-plugin
└── PLAN.md                    # Phased development plan
```

---

## Development Roadmap

| Phase | Description | Status |
|---|---|---|
| 0 | Prerequisites & tooling | ✅ Complete |
| 1 | Project setup, auth scaffolding, CI/CD | ✅ Complete |
| 2 | Database models, migrations, factories, role system | ✅ Complete |
| 3 | Laravel AI SDK — question generation, auto-grading, embeddings | 🔜 Next |
| 4 | Frontend polish, streaming UI, real-time broadcasting | 🔜 Planned |
| 5 | Advanced AI — RAG, personalisation, pgvector, cost controls | 🔜 Planned |
| 6 | Production hardening — rate limiting, queues, deploy | 🔜 Planned |

See [`PLAN.md`](PLAN.md) for the detailed implementation plan with task breakdowns per phase.

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

Once active, your AI assistant can read the live schema, run queries, inspect error logs, and search the docs — dramatically accelerating Phase 3 and beyond.

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

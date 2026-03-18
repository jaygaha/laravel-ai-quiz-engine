# QuizForge — Phased Development Plan

A production-ready SaaS exam engine built on Laravel 12, Livewire 4, and the Laravel AI SDK. The plan is split into seven phases — each independently shippable and tested. Phases 0–2 are complete. Phases 3–6 deliver the AI features and production hardening that elevate the project from a working CRUD app to a differentiated product.

**Legend:** ✅ Complete · 🔜 Next · 📋 Planned · ⏸ Deferred

---

## Phase 0 — Prerequisites ✅ Complete

Everything needed before a single line of application code.

### Tasks
- [x] PHP 8.3+ confirmed (8.5 running in Sail container)
- [x] Laravel 12.x installed
- [x] PostgreSQL configured (v18 in Sail, CI uses v17)
- [x] Docker + Laravel Sail working
- [x] Editor tooling: Claude Code with `laravel-boost` MCP server enabled
- [x] GitHub repository initialised with `.gitignore`

### Notes
`pgvector` support is prepared at the infra level (PostgreSQL selected as the database engine specifically for its `pgvector` extension) but the extension itself and vector columns are deferred to Phase 5 when embeddings are needed.

---

## Phase 1 — Project Setup + Auth Scaffolding ✅ Complete

A deployable, authenticated skeleton with role-aware routing and a polished design system.

### Tasks

**Core setup**
- [x] Laravel 12 project created from the official Livewire starter kit
- [x] Livewire 4 + Flux UI v2 (free edition) installed
- [x] Tailwind CSS 4 + Vite 7 configured (`vite.config.js`)
- [x] Lexend font loaded from Google Fonts; `--font-sans` token set in `@theme`
- [x] Laravel Fortify installed and configured as the auth backend
- [x] Laravel Sail configured with PHP 8.5 + PostgreSQL 18 (`compose.yaml`)
- [x] Laravel Boost installed as a dev dependency
- [x] Laravel Pint configured; `composer lint` and `composer lint:check` scripts added

**Authentication**
- [x] Registration with role selection via horizontal radio buttons (Teacher / Student)
- [x] Login with "Remember me"
- [x] Password reset via email
- [x] Email verification gate
- [x] Two-factor authentication (TOTP setup, QR code, recovery codes)
- [x] `FortifyServiceProvider` customised for role-aware registration and redirects
- [x] `CreateNewUser` action creates user with selected `UserRole` enum value

**Role-based routing**
- [x] `EnsureUserRole` middleware created and registered as `role` alias in `bootstrap/app.php`
- [x] `/dashboard` route redirects to `teacher.exams.index` or `student.dashboard` by role
- [x] Teacher routes namespaced under `/teacher`, guarded by `role:teacher`
- [x] Student routes namespaced under `/student`, guarded by `role:student`

**Design system**
- [x] Light-mode-only Bento grid UI (matches Laravel.com aesthetic)
- [x] Color palette: Off-white `#F8F9FA`, Teal `#0D9488`, Charcoal `#1F2937`, Coral `#FF9494`
- [x] Bento component classes: `.bento-card`, `.bento-flat`, `.bento-teal`, `.bento-canvas`, `.bento-category`
- [x] Form element overrides: 12px radius, 1.5px border, teal focus glow
- [x] Teal accent override in `@layer theme` — ensures teal remains the primary colour regardless of Flux defaults
- [x] Auth layout: split panel (teal brand panel left, form right on desktop)
- [x] App layout: sticky sidebar with role badge, mobile drawer, glass nav bar
- [x] Frosted-glass mobile header (`.glass-bar`)
- [x] `.quiz-option` CSS-native radio card (`:has(input:checked)` teal selection)

**Settings pages**
- [x] Profile update (name, email)
- [x] Security page (password change, 2FA management + recovery codes)
- [x] Account deletion with modal confirmation

**CI/CD**
- [x] GitHub Actions CI pipeline (`ci.yml`): Pint, security audit, Vite build, Pest matrix (PHP 8.3 + 8.4 + PostgreSQL service)
- [x] Commented-out CD pipeline (`cd.yml`) with four deployment strategies documented
- [x] Dependabot configured for Composer, npm, and GitHub Actions
- [x] Old `lint.yml` / `tests.yml` replaced and removed

---

## Phase 2 — Database Models, CRUD & Core Exam Engine ✅ Complete

The full data layer and both role-specific user journeys, with comprehensive tests.

### Tasks

**Enums**
- [x] `UserRole` enum: `Teacher`, `Student` with `label()` helper
- [x] `QuestionType` enum: `MultipleChoice`, `TrueFalse`, `ShortAnswer` with `label()` and `hasOptions()` helpers

**Models + migrations**
- [x] `User` — extended with `role` (cast to `UserRole`), `isTeacher()`, `isStudent()`, `initials()`, `TwoFactorAuthenticatable` trait, `exams()` and `attempts()` relationships
- [x] `Exam` — `user_id`, `title`, `description`, `time_limit`, `published_at`; `isPublished()`, `teacher()`, `user()` (alias for factory compatibility), `questions()` (ordered), `attempts()`
- [x] `Question` — `exam_id`, `question`, `type` (cast to `QuestionType`), `options` (JSON array), `correct_answer`, `order`; `exam()` relationship
- [x] `Attempt` — `exam_id`, `user_id`, `answers` (JSON), `score`, `started_at`, `completed_at`; `isCompleted()`, `exam()`, `student()` relationships
- [x] All migrations run and verified against live PostgreSQL schema

**Factories + seeders**
- [x] `UserFactory` with Teacher and Student states
- [x] `ExamFactory` — published and draft states
- [x] `QuestionFactory` — all three question types
- [x] `AttemptFactory` — completed and in-progress states
- [x] `DatabaseSeeder` creates demo teacher and students
- [x] `ExamSeeder` seeds sample published exams with questions

**Teacher Livewire pages** (`resources/views/pages/teacher/exams/`)
- [x] `⚡index` — lists teacher's own exams; publish / unpublish toggle; delete with confirmation
- [x] `⚡create` — form to create exam (title, description, time limit, publish immediately option)
- [x] `⚡edit` — same form pre-populated; update or delete exam
- [x] `⚡questions` — split-panel: add question form (type-aware, dynamic options) + live question list with reorder and delete

**Student Livewire pages** (`resources/views/pages/student/`)
- [x] `⚡dashboard` — grid of all published exams; "Start" or "Resume" CTA based on existing incomplete attempt
- [x] `⚡take-exam` — question-by-question interface; optional countdown timer using `time_limit`; answers stored progressively; auto-submit on timer expiry
- [x] `⚡exam-results` — score display (pass/warn/fail colour coding); per-question correct/incorrect breakdown; "Review later" flag UI

**Authorization**
- [x] Teachers can only view/edit/delete their own exams (enforced in component `mount()`)
- [x] Students can only access published exams (enforced via `isPublished()` check)
- [x] Attempt ownership verified before showing results

**Tests**
- [x] `AuthenticationTest` — login, logout, invalid credentials
- [x] `RegistrationTest` — registers with role, redirects correctly
- [x] `EmailVerificationTest` — resend, verify flow
- [x] `PasswordResetTest` — request link, reset with token
- [x] `PasswordConfirmationTest` — confirm before sensitive action
- [x] `TwoFactorChallengeTest` — TOTP and recovery code paths
- [x] `DashboardTest` — role-based redirect from `/dashboard`
- [x] `RoleMiddlewareTest` — teacher cannot access student routes and vice-versa
- [x] `ExamCrudTest` — create, update, publish, delete exam; question add/remove/reorder
- [x] `StudentExamTest` — take exam, submit answers, verify score, view results
- [x] `ProfileUpdateTest` — update name, update email triggers re-verification
- [x] `SecurityTest` — change password, 2FA toggle

---

## Phase 3 — Laravel AI SDK Integration 🔜 Next

This phase transforms the app from a standard quiz platform into an AI-powered learning tool. All heavy generation runs in queued jobs.

### Prerequisites
```bash
composer require laravel/ai
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
php artisan migrate  # publishes AI SDK tables if any
```

Add to `.env`:
```env
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
AI_DEFAULT_PROVIDER=openai
```

### Tasks

**Agent scaffolding**
- [ ] `php artisan make:agent QuestionGeneratorAgent`
- [ ] `php artisan make:agent AutoGraderAgent`
- [ ] `php artisan make:agent HintAgent`
- [ ] `php artisan make:tool SimilaritySearchTool`

**QuestionGeneratorAgent** — structured-output agent
- [ ] `instructions()` defines an expert exam author persona
- [ ] `schema()` returns a typed array of questions (question text, options, correct answer, difficulty 1–5, explanation)
- [ ] Accepts: topic string, question count, question type, difficulty level
- [ ] Returns: `array<Question>` validated against the schema
- [ ] Provider failover: OpenAI primary, Anthropic fallback
- [ ] Integration: "Generate with AI" button on `teacher.exams.questions` page streams results into the question list
- [ ] Queued generation path: `->queue()->then(fn($result) => ...)` for large batches

**AutoGraderAgent** — structured-output agent for Short Answer questions
- [ ] Scores a student's free-text answer against the correct answer and question context
- [ ] Returns: `score` (0–100), `is_correct` (bool), `explanation` (string), `suggestion` (string)
- [ ] Called during attempt submission for all `ShortAnswer` questions; result persisted to `attempts.answers` JSON alongside the raw answer
- [ ] Falls back to exact-match scoring if AI call fails (graceful degradation)

**HintAgent** — streaming agent
- [ ] Accepts: question text, student's current answer (may be null), exam subject
- [ ] Streams a Socratic hint — never reveals the answer directly
- [ ] Student UI: "Hint" button on the exam-taking page; streams token-by-token into a `<flux:callout>`

**Provider configuration**
- [ ] Register providers in `config/ai.php`
- [ ] Per-agent provider selection configurable via `.env`
- [ ] Cost guard: `max_tokens` and `temperature` set per agent

**Tests**
- [ ] Use `AI::fake()` mock for all agent tests — no real API calls in CI
- [ ] `QuestionGeneratorTest` — valid schema output, handles malformed responses, queued path
- [ ] `AutoGraderTest` — correct, incorrect, and partial scores; fallback on AI failure
- [ ] `HintTest` — streaming response; no answer leakage

---

## Phase 4 — Frontend Polish + Real-time Features 📋 Planned

Streaming UI, broadcasting, PDF export, and the full Livewire reactivity upgrade.

### Tasks

**Streaming question generation UI (Teacher)**
- [ ] Livewire component streams AI-generated questions token-by-token into the question list
- [ ] Show per-question "generating…" skeleton cards during stream
- [ ] Allow teacher to edit/discard generated questions before saving
- [ ] Generation history (last 5 AI batches per exam, re-usable)

**Real-time student features (requires Laravel Reverb)**
- [ ] `composer require laravel/reverb`
- [ ] Countdown timer driven by server-side `started_at` + `time_limit` (prevents client-side manipulation)
- [ ] `AttemptProgressEvent` broadcast: teacher sees live submission count during active exam window
- [ ] Real-time class leaderboard (opt-in, teacher-configurable per exam)

**Results PDF export**
- [ ] `composer require barryvdh/laravel-dompdf`
- [ ] Teacher: export full exam results table (all students, all scores)
- [ ] Student: export own result card (score, per-question breakdown, AI explanations)
- [ ] Queued PDF generation → email delivery for large exports

**UI enhancements**
- [ ] Question bank view — teacher sees all their past questions, reusable across exams
- [ ] Bulk question import from CSV (header: `question,type,options,correct_answer`)
- [ ] Exam preview mode for teachers (read-only student view before publishing)
- [ ] "Review Later" per-question flag fully wired (currently UI-only)

**Tests**
- [ ] Broadcasting tests with `Event::fake()`
- [ ] PDF generation tests (assert file exists, correct content)
- [ ] CSV import tests with valid and malformed files

---

## Phase 5 — Advanced AI (RAG, Personalisation, pgvector) 📋 Planned

The intelligence layer: semantic question retrieval, personalised recommendations, and embedding-based analytics.

### Tasks

**pgvector setup**
- [ ] Enable `pgvector` extension in PostgreSQL: `CREATE EXTENSION vector;`
- [ ] Migration: add `embedding vector(1536)` column to `questions` table
- [ ] Migration: add `embedding vector(1536)` column to `attempts` table (for answer embeddings)
- [ ] `SimilaritySearchTool` queries `questions` by cosine distance

**Question bank embeddings**
- [ ] `GenerateQuestionEmbeddingJob` — queued, generates and stores embedding when a question is created or updated
- [ ] `QuestionObserver` triggers the job on `created` and `updated` events
- [ ] `SimilaritySearchTool`: given a query string, returns the top-N most similar questions

**Personalised recommendations**
- [ ] Embed each completed `Attempt.answers` JSON for the subject/topic
- [ ] Post-results page: "Practice more on these topics" — semantic search surfaces similar questions from the question bank
- [ ] Teacher analytics: "Most struggled topics" — cluster low-scoring attempt embeddings by topic

**AutoGraderAgent upgrade (RAG path)**
- [ ] `SimilaritySearchTool` attached to `AutoGraderAgent`
- [ ] Agent retrieves semantically similar past answers to calibrate scoring consistency

**Rate limiting + cost control**
- [ ] Per-user AI request rate limiter (Redis-backed, configurable per tier)
- [ ] Token budget tracking: `ai_usage` table logs model, tokens, and cost estimate per request
- [ ] Admin view: daily token spend per user and per model
- [ ] Hard stop: AI endpoints return `429` when budget exceeded; graceful UI message

**AI mock for CI**
- [ ] `AI::fake()` used in all existing and new AI-related tests — zero real API calls
- [ ] `AiFakeServiceProvider` registered in `AppServiceProvider` when `APP_ENV=testing`

---

## Phase 6 — Production Hardening + Deployment 📋 Planned

The final push from "it works" to "it scales safely."

### Tasks

**Queue reliability**
- [ ] Switch `QUEUE_CONNECTION` from `database` to `redis` for production
- [ ] Configure `queue:work` with `--tries=3 --backoff=60` for AI jobs
- [ ] `GenerateQuestionEmbeddingJob` and all AI agent jobs implement `ShouldQueue`
- [ ] Failed job table monitoring; Slack notification on repeated failures

**Caching**
- [ ] Switch `CACHE_STORE` from `database` to `redis` for production
- [ ] Cache published exam lists (invalidated on publish/unpublish)
- [ ] Cache question lists per exam (invalidated on question add/remove)

**Security hardening**
- [ ] `APP_DEBUG=false`, `APP_ENV=production` in production `.env`
- [ ] HTTPS-only: `Session::secure()`, `cookie.secure=true`
- [ ] Content Security Policy header added via middleware
- [ ] `composer audit` and `npm audit` checked before each deploy (already in CI)
- [ ] Database credentials rotated from Sail defaults

**Performance**
- [ ] `php artisan optimize` on deploy (config, route, view, event cache)
- [ ] Vite build artifact deployed from CI — never built on the server
- [ ] Eager loading audit: confirm no N+1 queries in exam/question/attempt lists
- [ ] Add indexes: `attempts.user_id`, `attempts.exam_id`, `questions.exam_id` (verify in migration)

**Observability**
- [ ] Laravel Telescope in `local` and `staging`; disabled in production
- [ ] Log aggregation: configure `LOG_CHANNEL=stack` pointing to a cloud log service
- [ ] Health check endpoint (`/up`) verified in post-deploy pipeline
- [ ] Uptime monitor pointed at `/up`

**Deployment (activate `cd.yml`)**
- [ ] Choose and configure one deployment strategy (Forge / Vapor / SSH / Docker)
- [ ] Set up `staging` and `production` GitHub Environments with required reviewers on production
- [ ] Zero-downtime migration pattern: `php artisan down` → migrate → `php artisan up`
- [ ] Post-deploy health check: `curl --fail https://quizforge.jaygaha.com.np/up`
- [ ] Slack / Discord deploy notification on success and failure

**Multi-tenancy (optional — post-MVP)**
- [ ] Evaluate [Tenancy for Laravel](https://tenancyforlaravel.com/) vs schema-per-tenant vs row-level isolation
- [ ] If adopted: all AI quota and usage tables scoped per tenant

---

## Boost Prompts by Phase

These prompts are optimised for use inside Cursor or Claude Code with the `laravel-boost` MCP server active. Boost reads your live schema, routes, and logs before responding.

| Phase | Prompt |
|---|---|
| 3 | *"Check current schema with the DB tool, then scaffold `QuestionGeneratorAgent` using Laravel AI SDK structured output. Show me the schema definition, the Livewire integration on the questions page, and write a Pest test using `AI::fake()`."* |
| 3 | *"Implement `AutoGraderAgent` for Short Answer scoring. Read the Attempt model and the AI SDK docs for structured output, then wire it into the attempt submission flow. Include graceful fallback to exact-match scoring."* |
| 4 | *"Set up Laravel Reverb for real-time broadcasting. Add a `AttemptProgressEvent`, broadcast it when a student submits, and add a Livewire listener on the teacher's exam index page."* |
| 5 | *"Add `pgvector` to the Question model. Create a migration for the `embedding` column, a queued job to generate embeddings via the AI SDK, and a `SimilaritySearchTool` that queries by cosine distance. Write a Pest test using `AI::fake()`."* |
| 5 | *"Using Boost DB tool, find all exam endpoints that could have N+1 query problems, then add eager loading and confirm with a query count assertion in the existing Pest tests."* |
| 6 | *"Read the latest error log with the Boost log tool. Identify the root cause and suggest a fix."* |
| 6 | *"Audit the application for missing indexes. Check foreign keys in migrations and suggest an index migration."* |

---

## Resources

| Resource | URL |
|---|---|
| Laravel AI SDK Docs | https://laravel.com/docs/12.x/ai |
| Laravel Boost Docs | https://laravel.com/ai/boost |
| Livewire 4 Docs | https://livewire.laravel.com |
| Flux UI Components | https://fluxui.dev |
| pgvector | https://github.com/pgvector/pgvector |
| Pest 4 Docs | https://pestphp.com |
| Laravel Reverb | https://reverb.laravel.com |

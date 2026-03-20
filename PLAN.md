# QuizForge — Phased Development Plan

A production-ready SaaS exam engine built on Laravel 12, Livewire 4, and the Laravel AI SDK. The plan is split into seven phases — each independently shippable and tested. Phases 0–3 are complete. Phase 4 is in progress on branch `feat/phase-4-frontend-realtime`. Phases 5–6 deliver the advanced AI and production hardening that elevate the project from a working AI app to a differentiated product.

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

## Phase 3 — Laravel AI SDK Integration ✅ Complete

This phase transforms the app from a standard quiz platform into an AI-powered learning tool. All heavy generation runs in queued jobs. Uses the official **`laravel/ai` v0.3+** SDK.

### Prerequisites
```bash
./vendor/bin/sail composer require laravel/ai
./vendor/bin/sail artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
./vendor/bin/sail artisan migrate  # creates agent_conversations + agent_conversation_messages tables
```

Add to `.env`:
```env
# Production priority: Anthropic → Gemini → OpenAI
ANTHROPIC_API_KEY=sk-ant-...   # Primary — Claude Sonnet (generation) / Haiku (grading, hints)
GEMINI_API_KEY=...              # Secondary fallback
OPENAI_API_KEY=sk-...           # Tertiary fallback

# Local dev fallback — no key required, works fully offline
# Locally available models:
#   qwen2.5-coder:14b  → QuestionGeneratorAgent (best structured output)
#   qwen3:8b           → AutoGraderAgent (best reasoning)
#   gemma3:4b          → HintAgent (fastest streaming)
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_API_KEY=
```

### SDK Key Patterns

**Create agents via Artisan:**
```bash
php artisan make:agent QuestionGeneratorAgent --structured
php artisan make:agent AutoGraderAgent --structured
php artisan make:agent HintAgent
```

**Structured output agent pattern:**
```php
#[Provider(Lab::Anthropic)]
#[Model('claude-sonnet-4-6')]
#[MaxTokens(2048)]
#[Temperature(0.7)]
class QuestionGeneratorAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string { ... }
    public function schema(JsonSchema $schema): array { ... }
}

$response = (new QuestionGeneratorAgent)->prompt($userPrompt);
$questions = $response['questions'];
```

**Streaming pattern (Livewire `wire:stream`):**
```php
public function streamHint(): void
{
    $stream = (new HintAgent)->stream($this->buildPrompt());
    foreach ($stream as $event) {
        $this->stream(to: 'hint', content: $event->text ?? '');
    }
}
```

**Queued pattern:**
```php
(new QuestionGeneratorAgent)
    ->queue($prompt)
    ->then(fn (AgentResponse $r) => /* persist to DB */);
```

**Testing (zero real API calls):**
```php
QuestionGeneratorAgent::fake(['questions' => [...]]);
QuestionGeneratorAgent::assertPrompted(fn ($p) => $p->contains('PHP'));
```

### Tasks

**1 — Install & configure**
- [x] `composer require laravel/ai` (v0.3+)
- [x] Publish config + run migrations (`agent_conversations`, `agent_conversation_messages` tables)
- [x] Add provider keys to `.env.example` with placeholder comments
- [x] Update `config/ai.php` — default provider set to `anthropic`; Anthropic, Gemini, OpenAI, Ollama all configured
- [x] Add `OLLAMA_BASE_URL` + `OLLAMA_API_KEY` to `.env.example` for local dev (no key needed, set base URL to `http://localhost:11434`)

**2 — QuestionGeneratorAgent** (`app/Ai/Agents/QuestionGeneratorAgent.php`)
- [x] `php artisan make:agent QuestionGeneratorAgent --structured`
- [x] `instructions()` — expert exam-author persona, language + format constraints
- [x] `schema()` — returns array of `{ question, type, options[], correct_answer, explanation, difficulty (1–5) }` using `$schema->object([...])`
- [x] Provider: `#[Provider(Lab::Anthropic)]` `#[Model('claude-sonnet-4-6')]` — primary; `resolvedProviders()` adds Gemini → OpenAI → Ollama (`qwen2.5-coder:14b`) in order
- [x] Integration: "Generate with AI" collapsible panel on `⚡questions.blade.php`
  - Inputs: topic, question type, count (1–10), difficulty
  - On submit: calls agent → appends generated questions to the live list for teacher review before saving
- [x] Queued path via `->queue()` for batches > 5 questions; `ProcessGeneratedQuestionsJob` dispatched in `then()` callback

**3 — AutoGraderAgent** (`app/Ai/Agents/AutoGraderAgent.php`)
- [x] `php artisan make:agent AutoGraderAgent --structured`
- [x] `instructions()` — objective grader persona, partial-credit aware
- [x] `schema()` — `{ score (0–100), is_correct (bool), explanation, suggestion }`
- [x] Provider: `#[Provider(Lab::Anthropic)]` `#[Model('claude-haiku-4-5-20251001')]`
- [x] Wired into `submitExam()` on `⚡take-exam.blade.php`:
  - Exact-match for `MultipleChoice` + `TrueFalse` (unchanged)
  - AI grading for `ShortAnswer` — result stored as `{ raw_answer, ai_score, ai_explanation, ai_suggestion, ai_graded: true }` in `attempts.answers` JSON
  - Graceful fallback: if agent throws, falls back to exact-match scoring
- [x] `⚡exam-results.blade.php` shows AI score badge, explanation, and suggestion for short-answer questions

**4 — HintAgent** (`app/Ai/Agents/HintAgent.php`)
- [x] `php artisan make:agent HintAgent`
- [x] `instructions()` — Socratic tutor, never reveals the answer, guides via leading questions
- [x] Provider: `#[Provider(Lab::Anthropic)]` `#[Model('claude-haiku-4-5-20251001')]` `#[Timeout(30)]`
- [x] Streaming via `HintAgent->stream()` iterating `TextDelta` events + Livewire `$this->stream()` to `wire:stream="hint-{id}"` target
- [x] "Get a Hint" button on `⚡take-exam.blade.php` — only for `ShortAnswer` questions; Alpine shows hint area on click

**5 — Tests** (zero real API calls in CI)
- [x] `tests/Feature/Ai/QuestionGeneratorTest.php` — 6 tests: sync generation, queued dispatch, confirm single/all, job creates questions, job skips incomplete entries
- [x] `tests/Feature/Ai/AutoGraderTest.php` — 5 tests: AI grades and stores structured result, correct/incorrect score thresholds, results page displays AI badge and suggestion
- [x] `tests/Feature/Ai/HintTest.php` — 4 tests: hint text stored in `$hints`, agent was prompted, invalid question returns early, streaming events confirmed

---

## Phase 4 — Frontend Polish + Real-time Features ✅ Complete

Streaming UI, broadcasting, PDF export, exam timer enforcement, and the full Livewire reactivity upgrade.

### Prerequisites
```bash
composer require laravel/reverb barryvdh/laravel-dompdf
php artisan reverb:install
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

Add to `.env`:
```env
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

BROADCAST_CONNECTION=reverb
```

---

### 1 — Exam Timer (Server-enforced Countdown) ✅ Complete

**Goal:** Prevent client-side manipulation of time limits. The countdown is derived entirely from `attempt.started_at + exam.time_limit`. Auto-submits when the deadline passes.

- [x] Add `timeRemaining` computed property to `⚡take-exam`: `$exam->time_limit * 60 - now()->diffInSeconds($attempt->started_at)`
- [x] Add `checkTimer()` Livewire action that calls `submitExam()` when `timeRemaining <= 0`
- [x] Wire Alpine.js `setInterval` (every second) to decrement a local `seconds` counter initialised from PHP-rendered `$this->timeRemaining`
- [x] When Alpine counter hits zero, call `$wire.checkTimer()` — server validates real elapsed time
- [x] Show warning callout at ≤ 5 minutes and ≤ 1 minute remaining
- [x] Flash "Time's up — your exam has been automatically submitted." on auto-submit
- [x] Guard `submitExam()` with idempotency check (`isCompleted()`) and skip validation on auto-submit
- [x] Hide timer UI entirely when `$exam->time_limit` is null (untimed exams)

**Tests** (`tests/Feature/ExamTimerTest.php`) — 6 tests
- [x] `timeRemaining` returns zero when exam has no time limit
- [x] `timeRemaining` returns correct seconds on mount
- [x] `checkTimer()` does nothing when time has not expired
- [x] `checkTimer()` auto-submits and marks attempt completed when time expired
- [x] Auto-submit produces a valid score and `completed_at` timestamp
- [x] `submitExam()` is idempotent — double submit redirects without re-grading

---

### 2 — "Review Later" Flag ✅ Complete

**Goal:** Students can flag individual questions mid-exam for a second pass. Flags stored server-side in `attempt.answers` JSON so they survive page refresh.

**Data shape** — `answers` JSON now uses canonical nested shape:
```json
{
  "42": { "value": "Paris", "flagged": true },
  "43": { "value": "London", "flagged": false }
}
```
- [x] No migration needed — flag lives inside the existing `answers` JSON column
- [x] `normalizeAnswers()` in `mount()` upgrades all three legacy shapes (missing, plain string, old AI-graded array) to `{ value, flagged }`
- [x] `wire:model` changed to `answers.{id}.value`; `submitExam()` reads `$answerData['value']` for grading and preserves `flagged` through AI-grading result
- [x] `toggleFlag(int $questionId)` — flips `answers[$id]['flagged']` and immediately persists to DB (doubles as auto-save)
- [x] Bookmark icon button on every question card; teal filled when flagged, outline when not
- [x] "All Questions" / "Review Later" filter tabs; `filteredQuestions` computed property; empty state when no flags set
- [x] `flaggedCount` computed property drives the badge count on the tab
- [x] `⚡exam-results.blade.php`: bookmark SVG badge on flagged questions; `correctCount()` handles all three answer shapes
- [x] Flagged cards in take-exam get a subtle teal tint border

**Tests** (`tests/Feature/ReviewLaterTest.php`) — 9 tests
- [x] `toggleFlag()` persists flagged state to database
- [x] Toggling twice unflags the question
- [x] Flagged questions survive a page reload
- [x] `flaggedCount` reflects number of flagged questions
- [x] `filteredQuestions` returns only flagged when filter = 'flagged'
- [x] `submitExam()` grades `answers[$id]['value']` correctly with new shape
- [x] `submitExam()` preserves flag state in saved answers
- [x] Results page shows bookmark badge on flagged questions
- [x] Results `correctCount()` handles nested answer shape

---

### 3 — Streaming Question Generation UI ✅ Complete

**Goal:** Replace the static "pending cards appear all at once" pattern with live token-by-token streaming so the teacher sees questions materialise in real time.

- [x] Add `streamGenerateWithAi()` Livewire action alongside existing `generateWithAi()`; uses `QuestionGeneratorAgent->stream()` + `wire:stream="ai-stream"` target
- [x] While streaming, accumulate raw text into `$this->aiStreamBuffer`; parse complete JSON objects as they arrive using a lightweight streaming JSON extractor (handles both `{"questions":[...]}` structured output and bare top-level object formats)
- [x] Each time a complete question object is parsed, append it to `pendingAiQuestions` (Livewire re-renders incrementally)
- [x] Show a skeleton card (`animate-pulse`) while the next question is streaming; replace with the real card once parsed
- [x] Keep existing sync `generateWithAi()` path for the queued (>5) route — streaming only applies to the ≤5 path
- [x] AI generation history: store last 5 batches per exam in `session()` (keyed by `exam_id`); "Re-use" button re-populates `pendingAiQuestions` from a previous session entry
- [x] `x-transition` enter animations on question cards as they arrive

**Tests** (`tests/Feature/Ai/QuestionGeneratorStreamTest.php`)
- [x] `streamGenerateWithAi()` populates `pendingAiQuestions` from faked streaming response
- [x] `streamGenerateWithAi()` parses multiple questions from a single stream
- [x] `aiStreaming` resets to `false` after completion
- [x] Generation history stored in session after successful generation
- [x] `loadFromHistory()` restores `pendingAiQuestions` from session
- [x] Session history is capped at 5 entries
- [x] `aiError` is cleared on a successful generation

---

### 4 — Laravel Reverb Broadcasting ✅ Complete

**Goal:** Teacher sees a live submission counter tick up as students complete the exam. No polling — pure WebSocket event.

- [x] `laravel/reverb` installed; `laravel-echo` + `pusher-js` added via npm; Echo bootstrapped in `resources/js/app.js`
- [x] `AttemptSubmittedEvent` implements `ShouldBroadcast`; broadcasts on public channel `exam.{examId}`; payload: `{ student_count, latest_student_name }`
- [x] Event dispatched in `submitExam()` guarded by `config('broadcasting.default') === 'reverb'` — safe fallback when Reverb is not configured
- [x] Teacher `⚡index` page: Alpine Echo listener per exam row; dispatches Livewire `exam-attempt-submitted` event; `handleAttemptSubmitted()` updates `$liveSubmissions[examId]`; shows "● live" animated pill next to count
- [x] `php artisan reverb:start` added as fifth process to `composer run dev`
- [x] `leaderboard_enabled` boolean column migration for `exams` table
- [x] Teacher toggle (Flux switch) in `⚡edit` for live leaderboard
- [x] Student `⚡leaderboard` page (route `student/exams/{exam}/leaderboard`): top-10 by score, tie-break by `completed_at`; subscribes via Echo; refreshes on each event
- [x] "You are #N" rank card shown to the authenticated student if they have a completed attempt
- [x] "View Leaderboard" button on `⚡exam-results` page (only rendered when `leaderboard_enabled`)
- [x] Reverb env vars documented in `.env.example`

**Tests** (`tests/Feature/BroadcastingTest.php`)
- [x] `AttemptSubmittedEvent` is dispatched when `submitExam()` completes (with `reverb` driver)
- [x] Event payload contains correct `student_count` (pre-existing + new)
- [x] Event is NOT dispatched when `BROADCAST_CONNECTION` is not `reverb`
- [x] Leaderboard page shows attempts ordered by score descending
- [x] Leaderboard 404 when `leaderboard_enabled = false`
- [x] `myRank` computed property returns correct position
- [x] Leaderboard 404 for unpublished exam

---

### 5 — Results PDF Export ✅ Complete

**Goal:** Students download their own result card; teachers export a full class results table.

- [x] `barryvdh/laravel-dompdf` installed
- [x] `resources/views/pdf/student-result.blade.php` — score card, per-question breakdown, AI explanations, flagged badges; inline CSS only (DejaVu Sans, print-safe)
- [x] `resources/views/pdf/exam-results.blade.php` — summary stats + full student table; A4 landscape
- [x] `ExportStudentResultJob` — queued; generates PDF via `Pdf::loadView()`, stores in `storage/local/exports/`, emails signed download URL (24 h expiry)
- [x] `ExportExamResultsJob` — same pattern for teacher; emails teacher
- [x] `ExportReadyMail` mailable with `resources/views/emails/export-ready.blade.php` (Laravel mail component template)
- [x] `PdfDownloadController` — validates signed URL (via `signed` middleware), streams PDF, deletes file after send
- [x] Download route `GET /exports/download` with `->middleware('signed')` named `exports.download`
- [x] "Download Result (PDF)" button on `⚡exam-results.blade.php` → dispatches `ExportStudentResultJob`
- [x] New teacher page `⚡results.blade.php` (`teacher/exams/{exam}/results`) with stats (avg score, pass rate) + results table + "Export Results (PDF)" button → dispatches `ExportExamResultsJob`
- [x] Chart-bar icon button added to teacher index per exam row → links to results page
- [x] Flash callout "Your PDF is being generated — you'll receive an email shortly."

**Tests** (`tests/Feature/PdfExportTest.php`)
- [x] `ExportStudentResultJob::handle()` creates a PDF file in storage
- [x] Generated PDF file is not empty
- [x] Job sends email with signed download URL to student
- [x] Signed download URL valid within 24 h returns 200
- [x] Expired signed URL returns 403
- [x] `ExportExamResultsJob` creates PDF and emails teacher
- [x] Student results page dispatches `ExportStudentResultJob` via queue
- [x] Teacher results page dispatches `ExportExamResultsJob` via queue
- [x] Teacher results page computes correct `averageScore` and `passRate`

---

### 6 — UI Enhancements ✅ Complete

**Question Bank**
- [x] New teacher page `⚡question-bank` (route: `teacher/questions`); sidebar link added
- [x] Lists all questions across all teacher's exams; columns: question text, type badge, exam name, difficulty
- [x] Filter by exam (dropdown) and question type (select)
- [x] Search with `wire:model.live.debounce.300ms`
- [x] "Add to exam" action — modal with exam selector; creates a copy of the question in the target exam
- [x] Pagination (15 per page)

**Bulk CSV Import**
- [x] "Import CSV" button in `⚡questions`; opens `flux:modal`
- [x] File upload via `#[Validate('file|mimes:csv,txt|max:512')]` Livewire property
- [x] Expected CSV format: `question,type,options,correct_answer`
- [x] `ImportQuestionsFromCsvJob` — queued; parses CSV row by row, skips malformed rows
- [x] Flash status after upload dispatches job

**Exam Preview Mode**
- [x] `#[Url(as: 'preview')]` property on `⚡take-exam`; teacher authenticated, bypasses `isPublished()` check
- [x] "Preview Mode" callout at the top with "End Preview" button
- [x] `submitExam()` is blocked in preview mode; submit button replaced with warning callout
- [x] "Preview" button in `⚡edit` and `⚡questions`

**Tests** (`tests/Feature/UiEnhancementsTest.php`)
- [x] Question bank lists questions from all teacher exams
- [x] Question bank hides other teacher's questions
- [x] "Add to exam" duplicates the question into the target exam
- [x] Add-to-exam requires a target exam selection
- [x] CSV import dispatches `ImportQuestionsFromCsvJob`
- [x] `ImportQuestionsFromCsvJob` imports valid rows and skips malformed ones
- [x] `ImportQuestionsFromCsvJob` skips rows with fewer columns than header
- [x] Preview mode accessible to teacher on unpublished exam
- [x] Non-owner gets 403 on preview
- [x] `submitExam()` is blocked in preview mode
- [x] Draft exam returns 404 without preview param

---

### Summary Table

| Sub-feature | New files | Changed files |
|---|---|---|
| Timer | `ExamTimerTest` | `⚡take-exam` |
| Review Later | `ReviewLaterTest` | `⚡take-exam`, `⚡exam-results` |
| Streaming gen UI | `QuestionGeneratorStreamTest` | `⚡questions` |
| Reverb broadcast | `AttemptSubmittedEvent`, `⚡leaderboard`, migration, `BroadcastingTest` | `⚡take-exam`, `⚡index` |
| PDF export | `student-result.blade`, `exam-results.blade`, 2 jobs, `PdfExportTest` | `⚡exam-results`, teacher results view |
| Question bank | `⚡question-bank`, `UiEnhancementsTest` | sidebar nav |
| CSV import | `ImportQuestionsFromCsvJob` | `⚡questions` |
| Exam preview | — | `⚡take-exam`, `⚡edit`, `⚡questions` |

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

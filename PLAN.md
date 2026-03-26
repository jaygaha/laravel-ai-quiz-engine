# QuizForge — Development Plan

A production-ready SaaS exam engine built on Laravel 12, Livewire 4, and the Laravel AI SDK. Phases 0–5 are complete and shipped. Phase 6 is the active roadmap.

**Legend:** ✅ Complete · 📋 Planned

---

## Phase 0 — Prerequisites ✅ Complete

PHP 8.3+, Laravel 12, PostgreSQL, Docker + Sail, Claude Code with Laravel Boost MCP.

---

## Phase 1 — Project Setup + Auth Scaffolding ✅ Complete

Livewire 4 + Flux UI starter kit, Tailwind CSS 4, Fortify auth (login, registration, password reset, email verification, 2FA), role-based routing (Teacher / Student), Bento-grid design system, settings pages (profile, security, account deletion), GitHub Actions CI/CD pipeline, Dependabot.

---

## Phase 2 — Database Models, CRUD & Core Exam Engine ✅ Complete

`User`, `Exam`, `Question`, `Attempt` models with factories and seeders. Teacher exam CRUD (create, edit, publish/unpublish, delete), question management (add, edit, delete, reorder). Student dashboard, exam-taking interface, auto-scoring, results page. Full authorization: teachers own exams, students see only published. 13 test files covering auth, CRUD, roles, and student flows.

---

## Phase 3 — Laravel AI SDK Integration ✅ Complete

Three AI agents powered by `laravel/ai` SDK with multi-provider failover (Anthropic → Gemini → OpenAI → Ollama):

- **QuestionGeneratorAgent** — structured output, generates questions by topic/type/difficulty, sync + queued paths
- **AutoGraderAgent** — structured output, scores short-answer questions with explanation and suggestion, graceful fallback to exact-match
- **HintAgent** — streaming Socratic hints via `wire:stream` for short-answer questions

All tests use `Agent::fake()` — zero real API calls in CI. 15 AI-specific tests.

---

## Phase 4 — Frontend Polish + Real-time Features ✅ Complete

- **Exam timer** — server-enforced countdown, auto-submit, idempotency guard
- **Review Later flags** — bookmark questions mid-exam, persisted in answers JSON, filter tabs
- **Streaming AI generation** — token-by-token question generation with skeleton loading cards, generation history (session-stored, re-usable)
- **Laravel Reverb broadcasting** — live submission counter on teacher index, `AttemptSubmittedEvent`, live leaderboard with rank card
- **PDF export** — student result card + teacher class results via queued DomPDF jobs, signed download URLs (24h expiry), email delivery
- **Question bank** — cross-exam browser with search, filters, "Add to exam" copy action, pagination
- **CSV import** — bulk question upload with validation, graceful handling of malformed rows
- **Exam preview** — teachers preview unpublished exams as students would see them

35+ tests across timer, flags, streaming, broadcasting, PDF, and UI enhancements.

---

## Phase 5 — Advanced AI (RAG, Personalisation, pgvector) ✅ Complete

- **pgvector embeddings** — 1536-dimension HNSW-indexed vector columns on questions and attempts, auto-generated via queued jobs, `QuestionObserver` for create/update, backfill command
- **Similarity search** — `QuestionSimilarityService` using `whereVectorSimilarTo()` for semantic question retrieval
- **RAG-enhanced grading** — `AutoGraderAgent` upgraded with `HasTools` + `SimilaritySearch` tool for grading calibration
- **Personalised recommendations** — student results show practice questions from other exams; teacher results show most-struggled questions
- **AI cost controls** — per-user rate limiting, `ai_usages` table tracking tokens and cost, daily budget hard stop, teacher AI Usage dashboard
- **CI safety** — global `Embeddings::fake()` in test suite
- **Infrastructure** — `pgvector/pgvector:pg18` (Sail), `pgvector/pgvector:pg17` (CI)

24 new tests across 5 test files. 183 total tests, 91.7% coverage.

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

# Feature Backlog

Quick wins and nice-to-have features to implement incrementally. Each item is small enough to ship in a single PR.

---

## Teacher Features

### ~~Exam Clone / Duplicate~~ ✅ Shipped

Deep-copies exam + all questions as a new draft with "(Copy)" suffix via `duplicateExam()` on the exams index. Redirects to edit page. 4 tests (ownership, independence, order, authorization).

### Bulk Delete Questions
**Effort:** ~15 min | **Priority:** Medium

Currently questions can only be deleted one at a time. Add checkbox selection and a "Delete Selected" button on the questions page.

- Add `selectedQuestions` array property with checkbox per question row
- "Select All / Deselect All" toggle
- Confirmation modal before bulk delete
- Test: verify selected questions are removed, others remain

### Exam Archive / Soft Filter
**Effort:** ~15 min | **Priority:** Medium

Old exams clutter the teacher's exam list. Add an "Archive" toggle that hides exams without deleting them.

- Add `archived_at` nullable timestamp column to exams
- Toggle button on exam index: "Archive" / "Unarchive"
- Filter toggle: "Show archived" (default off)
- Archived exams are hidden from student dashboard
- Test: verify archive toggle, filter, and student visibility

---

## Student Features

### ~~Attempt History Page~~ ✅ Shipped

Dedicated `/student/attempts` page listing all completed attempts. Search by exam name, sort by score or date, paginated (15/page). Sidebar "My Attempts" nav item with `clock` icon. 7 tests (listing, filtering, sorting, pagination, ownership, access control).

### ~~Dashboard Statistics Card~~ ✅ Shipped

Three bento stat cards at the top of the student dashboard (hidden until first attempt): Exams Taken, Average Score, Best Score. Pure computed properties — no migrations. 5 tests (zero state, completed-only, average, best, ownership).

---

## UX Improvements

### ~~Toast Notifications~~ ✅ Shipped

Custom Alpine toast system via `$this->dispatch('toast', ...)` with `@teleport('body')`. Replaces `session()->flash()` across 6 actions. Variants: success, warning, danger. Auto-dismiss after 4 seconds.

### Keyboard Shortcuts
**Effort:** ~20 min | **Priority:** Low

Add keyboard navigation for the exam-taking interface to improve accessibility.

- `Ctrl+Enter` to submit exam
- Arrow keys or `J/K` to navigate between questions
- `F` to toggle flag on current question
- `H` to request hint (short-answer only)
- Small "Keyboard shortcuts" help tooltip

### Empty State Illustrations
**Effort:** ~10 min | **Priority:** Low

Add friendly empty states when lists are empty (no exams, no attempts, no questions) instead of blank space.

- SVG illustrations or Heroicon + descriptive text
- Call-to-action buttons: "Create your first exam", "Browse available exams"
- Consistent pattern across all list views

---

## Data & Analytics

### Export Exam as JSON / Share Template
**Effort:** ~20 min | **Priority:** Medium

Let teachers export an exam (with questions) as a JSON file and import it into another account or instance.

- "Export as JSON" button on exam edit page
- JSON schema: exam metadata + questions array
- "Import from JSON" option alongside CSV import
- Useful for sharing exam templates between teachers

### ~~Student Progress Over Time~~ ✅ Shipped

Line chart on the student dashboard (visible once 2+ completed attempts exist) showing score trends over time. Chart.js via CDN, Alpine.js init, teal line with per-point pass/warn/fail colouring, y-axis 0–100%. 5 Pest tests (empty state, completed-only, date sort, fields, ownership).

---

## Notes

- Features are listed roughly by priority within each section
- Effort estimates assume familiarity with the existing codebase
- Each feature should include Pest tests and follow existing conventions
- Run `vendor/bin/pint --dirty` after implementation

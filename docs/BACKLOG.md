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

### Attempt History Page
**Effort:** ~20 min | **Priority:** High

The student dashboard only shows the 5 most recent attempts. A dedicated history page would list all past attempts with sorting and filtering.

- New route: `GET /student/attempts` -> `pages::student.attempts`
- Add sidebar nav item: "My Attempts" with `clock` icon
- Table: exam title, score, date, duration, status
- Sortable by date and score
- Filter by exam name (search input)
- Pagination (15 per page)
- Test: verify listing, sorting, and access control

### Dashboard Statistics Card
**Effort:** ~10 min | **Priority:** Low

Show a stats summary at the top of the student dashboard: total exams taken, average score, best score, and streak count.

- Computed properties on dashboard component
- Bento-grid stat cards matching existing design
- No new models or migrations needed

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

### Student Progress Over Time
**Effort:** ~30 min | **Priority:** Medium

A chart showing score trends across attempts for a student, helping them track improvement.

- New section on student dashboard or dedicated page
- Line chart: score (y-axis) vs attempt date (x-axis)
- Group by exam or show all attempts
- Use a lightweight chart library (Chart.js via CDN or Alpine plugin)

---

## Notes

- Features are listed roughly by priority within each section
- Effort estimates assume familiarity with the existing codebase
- Each feature should include Pest tests and follow existing conventions
- Run `vendor/bin/pint --dirty` after implementation

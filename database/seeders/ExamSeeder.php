<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates two teachers, three students, five exams (four published, one draft),
     * a realistic mix of all three question types, and completed + in-progress
     * attempts so every part of the UI has something to display on first boot.
     *
     * Login credentials (all passwords: "password"):
     *   teacher@quizforge.app   — owns Laravel & PHP exams
     *   teacher2@quizforge.app  — owns Web Dev & DB exams
     *   student@quizforge.app   — has completed and in-progress attempts
     *   student2@quizforge.app  — has one completed attempt
     *   student3@quizforge.app  — has not attempted anything yet
     */
    public function run(): void
    {
        // ── Users ────────────────────────────────────────────────────────────

        $teacher1 = User::factory()->teacher()->create([
            'name' => 'Lord Laure',
            'email' => 'teacher@quizforge.app',
        ]);

        $teacher2 = User::factory()->teacher()->create([
            'name' => 'Torpe Sir',
            'email' => 'teacher2@quizforge.app',
        ]);

        $student1 = User::factory()->student()->create([
            'name' => 'Gyaje',
            'email' => 'student@quizforge.app',
        ]);

        $student2 = User::factory()->student()->create([
            'name' => 'Lakuri',
            'email' => 'student2@quizforge.app',
        ]);

        // A student who has not attempted anything — tests the "no history" state
        User::factory()->student()->create([
            'name' => 'Torii',
            'email' => 'student3@quizforge.app',
        ]);

        // ── Exam 1: Laravel Framework Essentials (teacher1, published, timed) ──

        $laravelExam = Exam::factory()->published()->for($teacher1)->create([
            'title' => 'Laravel Framework Essentials',
            'description' => 'Test your knowledge of Laravel 12 core concepts including routing, Eloquent ORM, middleware, and the service container.',
            'time_limit' => 30,
        ]);

        $laravelQuestions = $this->seedQuestions($laravelExam, [
            [
                'question' => 'Which Artisan command creates a new Eloquent model along with a migration?',
                'type' => QuestionType::MultipleChoice,
                'options' => ['php artisan make:model -m', 'php artisan model:create --migration', 'php artisan make:migration model', 'php artisan create:model -migrate'],
                'correct_answer' => 'php artisan make:model -m',
            ],
            [
                'question' => 'In Laravel, route model binding automatically resolves Eloquent models injected into route closures or controller methods.',
                'type' => QuestionType::TrueFalse,
                'options' => ['True', 'False'],
                'correct_answer' => 'True',
            ],
            [
                'question' => 'Which method on an Eloquent query builder retrieves the first matching record or throws a ModelNotFoundException?',
                'type' => QuestionType::MultipleChoice,
                'options' => ['firstOrFail()', 'findOrFail()', 'first()', 'findFirst()'],
                'correct_answer' => 'firstOrFail()',
            ],
            [
                'question' => 'What is the primary purpose of the Laravel service container?',
                'type' => QuestionType::ShortAnswer,
                'options' => null,
                'correct_answer' => 'dependency injection',
            ],
            [
                'question' => 'Laravel middleware can only be applied to individual routes, not to route groups.',
                'type' => QuestionType::TrueFalse,
                'options' => ['True', 'False'],
                'correct_answer' => 'False',
            ],
            [
                'question' => 'Which Eloquent relationship method would you use to define a "post has many comments" relationship?',
                'type' => QuestionType::MultipleChoice,
                'options' => ['hasMany()', 'belongsToMany()', 'hasOne()', 'morphMany()'],
                'correct_answer' => 'hasMany()',
            ],
        ]);

        // ── Exam 2: PHP Fundamentals (teacher1, published, no time limit) ──

        $phpExam = Exam::factory()->published()->for($teacher1)->create([
            'title' => 'PHP Fundamentals',
            'description' => 'Core PHP language concepts including types, functions, OOP principles, and modern PHP 8.x features.',
            'time_limit' => null,
        ]);

        $phpQuestions = $this->seedQuestions($phpExam, [
            [
                'question' => 'What will `var_dump((int) "42abc")` output in PHP?',
                'type' => QuestionType::MultipleChoice,
                'options' => ['int(42)', 'int(0)', 'string(5) "42abc"', 'NULL'],
                'correct_answer' => 'int(42)',
            ],
            [
                'question' => 'PHP 8.0 introduced named arguments, allowing you to pass arguments to a function by specifying the parameter name.',
                'type' => QuestionType::TrueFalse,
                'options' => ['True', 'False'],
                'correct_answer' => 'True',
            ],
            [
                'question' => 'Which PHP keyword is used to enforce a class cannot be instantiated directly?',
                'type' => QuestionType::MultipleChoice,
                'options' => ['abstract', 'final', 'static', 'interface'],
                'correct_answer' => 'abstract',
            ],
            [
                'question' => 'What operator was introduced in PHP 7.0 for null coalescing?',
                'type' => QuestionType::ShortAnswer,
                'options' => null,
                'correct_answer' => '??',
            ],
            [
                'question' => 'In PHP, interfaces can contain method implementations.',
                'type' => QuestionType::TrueFalse,
                'options' => ['True', 'False'],
                'correct_answer' => 'False',
            ],
        ]);

        // ── Exam 3: Web Development Basics (teacher2, published, timed) ──

        $webExam = Exam::factory()->published()->for($teacher2)->create([
            'title' => 'Web Development Basics',
            'description' => 'HTML5 semantics, CSS layout models, and HTTP fundamentals every web developer should know.',
            'time_limit' => 20,
        ]);

        $this->seedQuestions($webExam, [
            [
                'question' => 'Which HTTP status code indicates that a resource has been permanently moved to a new URL?',
                'type' => QuestionType::MultipleChoice,
                'options' => ['301', '302', '404', '200'],
                'correct_answer' => '301',
            ],
            [
                'question' => 'The CSS `box-sizing: border-box` property includes padding and border in the element\'s total width and height.',
                'type' => QuestionType::TrueFalse,
                'options' => ['True', 'False'],
                'correct_answer' => 'True',
            ],
            [
                'question' => 'Which HTML5 element is most appropriate for the main navigation links of a website?',
                'type' => QuestionType::MultipleChoice,
                'options' => ['<nav>', '<menu>', '<header>', '<aside>'],
                'correct_answer' => '<nav>',
            ],
            [
                'question' => 'What does CSS Flexbox\'s `justify-content: space-between` do?',
                'type' => QuestionType::ShortAnswer,
                'options' => null,
                'correct_answer' => 'distributes items with space between them',
            ],
        ]);

        // ── Exam 4: Database Design Principles (teacher2, published, timed) ──

        $dbExam = Exam::factory()->published()->for($teacher2)->create([
            'title' => 'Database Design Principles',
            'description' => 'Relational database concepts, normalisation, SQL query optimisation, and indexing strategies.',
            'time_limit' => 45,
        ]);

        $this->seedQuestions($dbExam, [
            [
                'question' => 'Which normal form eliminates partial dependencies on a composite primary key?',
                'type' => QuestionType::MultipleChoice,
                'options' => ['Second Normal Form (2NF)', 'First Normal Form (1NF)', 'Third Normal Form (3NF)', 'Boyce-Codd Normal Form (BCNF)'],
                'correct_answer' => 'Second Normal Form (2NF)',
            ],
            [
                'question' => 'A database index always speeds up both read and write operations.',
                'type' => QuestionType::TrueFalse,
                'options' => ['True', 'False'],
                'correct_answer' => 'False',
            ],
            [
                'question' => 'What type of SQL JOIN returns all rows from the left table and matching rows from the right table, with NULLs for non-matching right rows?',
                'type' => QuestionType::MultipleChoice,
                'options' => ['LEFT JOIN', 'INNER JOIN', 'FULL OUTER JOIN', 'CROSS JOIN'],
                'correct_answer' => 'LEFT JOIN',
            ],
            [
                'question' => 'In the context of ACID properties, what does "Isolation" guarantee?',
                'type' => QuestionType::ShortAnswer,
                'options' => null,
                'correct_answer' => 'concurrent transactions do not interfere with each other',
            ],
            [
                'question' => 'A foreign key constraint ensures referential integrity between two tables.',
                'type' => QuestionType::TrueFalse,
                'options' => ['True', 'False'],
                'correct_answer' => 'True',
            ],
        ]);

        // ── Exam 5: Advanced Laravel Patterns (teacher1, DRAFT) ─────────────

        $draftExam = Exam::factory()->draft()->for($teacher1)->create([
            'title' => 'Advanced Laravel Patterns (Draft)',
            'description' => 'Service layer architecture, repository pattern, event-driven design, and queue strategies. Work in progress.',
            'time_limit' => 60,
        ]);

        $this->seedQuestions($draftExam, [
            [
                'question' => 'Which Laravel feature allows you to dispatch a class-based event and have multiple listeners react to it?',
                'type' => QuestionType::MultipleChoice,
                'options' => ['Event broadcasting', 'Event::dispatch()', 'Queued listeners', 'Both Event::dispatch() and Queued listeners'],
                'correct_answer' => 'Both Event::dispatch() and Queued listeners',
            ],
            [
                'question' => 'Laravel queues can be configured to use different connections for different job types.',
                'type' => QuestionType::TrueFalse,
                'options' => ['True', 'False'],
                'correct_answer' => 'True',
            ],
        ]);

        // ── Attempts ─────────────────────────────────────────────────────────
        // Build realistic answer maps using actual question IDs so the results
        // page has meaningful per-question feedback to display.

        // student1: completed the Laravel exam with a passing score (5/6 correct)
        $laravelAnswers = $laravelQuestions->mapWithKeys(fn (Question $q, int $i) => [
            $q->id => $i === 2 ? 'first()' : $q->correct_answer, // wrong on question 3
        ])->all();

        Attempt::factory()->completed()->for($laravelExam)->for($student1, 'student')->create([
            'answers' => $laravelAnswers,
            'score' => 83,
        ]);

        // student1: completed the PHP exam with a low score (2/5 correct)
        $phpAnswers = $phpQuestions->mapWithKeys(fn (Question $q, int $i) => [
            $q->id => match ($i) {
                0 => 'int(42)',         // correct
                1 => 'False',          // wrong
                2 => 'final',          // wrong
                3 => '??',             // correct
                default => 'True',     // wrong
            },
        ])->all();

        Attempt::factory()->completed()->for($phpExam)->for($student1, 'student')->create([
            'answers' => $phpAnswers,
            'score' => 40,
        ]);

        // student1: in-progress Laravel exam attempt — demonstrates "Resume" button
        Attempt::factory()->for($laravelExam)->for($student1, 'student')->create([
            'answers' => null,
            'score' => null,
            'started_at' => now()->subMinutes(10),
            'completed_at' => null,
        ]);

        // student2: completed the Laravel exam with a perfect score
        $perfectAnswers = $laravelQuestions->mapWithKeys(fn (Question $q) => [
            $q->id => $q->correct_answer,
        ])->all();

        Attempt::factory()->completed()->for($laravelExam)->for($student2, 'student')->create([
            'answers' => $perfectAnswers,
            'score' => 100,
        ]);
    }

    /**
     * Create a list of questions for an exam with sequential ordering.
     *
     * @param  array<int, array{question: string, type: QuestionType, options: array<string>|null, correct_answer: string}>  $definitions
     * @return Collection<int, Question>
     */
    private function seedQuestions(Exam $exam, array $definitions): Collection
    {
        return collect($definitions)->map(function (array $definition, int $index) use ($exam): Question {
            return Question::create([
                'exam_id' => $exam->id,
                'question' => $definition['question'],
                'type' => $definition['type'],
                'options' => $definition['options'],
                'correct_answer' => $definition['correct_answer'],
                'order' => $index + 1,
            ]);
        });
    }
}

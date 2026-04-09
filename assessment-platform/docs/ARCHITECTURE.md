# Assessment Platform — Architecture Conventions

## Directory Overview

```
app/
├── Actions/                # Single-purpose action classes
│   ├── Quizzes/
│   ├── Questions/
│   ├── QuestionBank/
│   ├── Attempts/
│   ├── Rlhf/
│   ├── Invitations/
│   ├── Proctoring/
│   ├── Roles/
│   └── Users/
├── Contracts/              # Interfaces for services
│   ├── AiProviders/
│   ├── CodeRunners/
│   └── Storage/
├── Data/                   # Spatie Laravel Data DTOs
│   ├── Quizzes/
│   ├── Questions/
│   ├── Rlhf/
│   └── Attempts/
├── Enums/                  # PHP 8.3 backed enums
├── Events/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Dashboard controllers (Inertia)
│   │   ├── Candidate/      # Candidate-facing quiz controllers
│   │   └── Api/            # JSON APIs (proctoring, RLHF status)
│   ├── Middleware/
│   ├── Requests/           # Form Requests grouped by domain
│   │   ├── Admin/
│   │   └── Candidate/
│   └── Resources/          # JSON resources for API responses
├── Jobs/
│   ├── Rlhf/               # RLHF generation jobs
│   └── Coding/             # Coding submission runner jobs
├── Listeners/
├── Models/
├── Notifications/
├── Observers/
├── Policies/
├── Providers/
├── Services/               # Stateful services (orchestrators, integrations)
│   ├── AiProviders/
│   │   └── Anthropic/
│   ├── CodeRunners/
│   ├── Proctoring/
│   └── Scoring/
├── Support/                # Helpers, value objects, traits
└── View/

tests/
├── Feature/
│   ├── Admin/
│   ├── Candidate/
│   └── Api/
└── Unit/
    ├── Actions/
    └── Services/

resources/js/
├── Pages/
│   ├── Admin/
│   └── Candidate/
├── Components/
├── Layouts/
├── Hooks/
├── Lib/
└── Types/
```

## Layer Responsibilities

### Controllers (Thin)

Controllers do three things and nothing more:

1. Accept a validated Form Request.
2. Delegate to an Action.
3. Return an Inertia response or JSON.

```php
final class CreateQuizController extends Controller
{
    public function __invoke(StoreQuizRequest $request, CreateQuiz $action): RedirectResponse
    {
        $quiz = $action->handle($request->toData());

        return redirect()->route('admin.quizzes.show', $quiz);
    }
}
```

Controllers never validate inline, query the database directly, or contain business logic.

### Actions (Single-Responsibility)

Each Action class has one public `handle()` method. Dependencies are constructor-injected. Actions are the primary unit of business logic and the main target for unit tests.

```php
final class CreateQuiz
{
    public function __construct(
        private QuizScoringService $scoring,
    ) {}

    public function handle(QuizData $data): Quiz
    {
        $quiz = Quiz::create($data->toArray());

        $this->scoring->initializeDefaults($quiz);

        QuizCreated::dispatch($quiz);

        return $quiz;
    }
}
```

We use `handle()` instead of `__invoke()` so Actions can be explicitly called in tests without ambiguity and can be easily mocked or spied on.

### Services (Stateful / Orchestration)

Services hold long-lived stateful logic: API client wrappers, orchestration across multiple Actions, caching strategies, and integration adapters. Actions call Services, not the other way around.

```php
final class AnthropicProvider implements AiProviderContract
{
    public function __construct(
        private Client $client,
    ) {}

    public function generateQuestion(QuestionPromptData $prompt): GeneratedQuestionData
    {
        // API call, retry logic, response mapping
    }
}
```

### Contracts (Interfaces)

All external integrations are coded against interfaces in `app/Contracts/`. Concrete implementations live in `app/Services/`. Bindings are registered in a dedicated Service Provider.

```php
// app/Contracts/AiProviders/AiProviderContract.php
interface AiProviderContract
{
    public function generateQuestion(QuestionPromptData $prompt): GeneratedQuestionData;
}

// app/Providers/AppServiceProvider.php
$this->app->bind(AiProviderContract::class, AnthropicProvider::class);
```

This allows swapping providers without modifying business logic and simplifies testing with fakes.

### Form Requests

All validation lives in Form Request classes, grouped by domain under `app/Http/Requests/Admin/` and `app/Http/Requests/Candidate/`. Controllers never call `$request->validate()`.

```php
final class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Quiz::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'passing_score' => ['required', 'numeric', 'between:0,100'],
        ];
    }

    public function toData(): QuizData
    {
        return QuizData::from($this->validated());
    }
}
```

Form Requests include a `toData()` method that converts validated input into a Spatie Data DTO for passing to Actions.

### DTOs (Spatie Laravel Data)

DTOs are used at boundaries between layers. They provide type safety, validation, and serialization.

```php
final class QuizData extends Data
{
    public function __construct(
        public string $title,
        public int $duration_minutes,
        public float $passing_score,
        public ?string $description = null,
    ) {}
}
```

### Models

Models follow these conventions:

- Use `protected $fillable` to whitelist mass-assignable attributes.
- Define casts via the `casts()` method (Laravel 13 syntax), not the `$casts` property.
- Use `$hidden` to exclude secrets from serialization.
- Use `encrypted` casts for sensitive fields.
- Type-hint all relationship return types.

```php
final class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'duration_minutes',
        'passing_score',
        'is_published',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'passing_score' => 'decimal:2',
            'settings' => 'array',
            'access_token' => 'encrypted',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }
}
```

### Enums

Use PHP 8.3 backed enums for all fixed sets of values.

```php
enum QuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case Coding = 'coding';
    case FreeText = 'free_text';
    case TrueFalse = 'true_false';
}
```

### Events & Listeners

Events are dispatched from Actions, never from Controllers. Listeners handle side effects (notifications, logging, cache invalidation). Register event-listener mappings via model `$dispatchesEvents` or explicit `Event::listen()` in providers.

### Jobs

Jobs live under domain-specific subdirectories:

- `Jobs/Rlhf/` — AI-powered question generation and RLHF feedback processing.
- `Jobs/Coding/` — Sandboxed code execution and evaluation.

Jobs are dispatched from Actions or Listeners, processed via Laravel Horizon.

### Policies

Every model that requires authorization has a corresponding Policy. Policies are auto-discovered by Laravel. Controllers authorize via Form Requests or `$this->authorize()`.

### Tests

- **Feature tests** (`tests/Feature/`) test full HTTP request cycles through controllers.
- **Unit tests** (`tests/Unit/Actions/`, `tests/Unit/Services/`) test Actions and Services in isolation.
- All tests use Pest syntax.
- Feature tests use `RefreshDatabase` (configured in `tests/Pest.php`).
- Use factories for model creation in tests.

### Frontend (Inertia + React + TypeScript)

- `Pages/Admin/` — Admin dashboard pages.
- `Pages/Candidate/` — Candidate-facing quiz pages.
- `Components/` — Reusable UI components.
- `Layouts/` — Page layout wrappers.
- `Hooks/` — Custom React hooks.
- `Lib/` — Utility functions and API helpers.
- `Types/` — TypeScript type definitions and interfaces.

## General Rules

1. Use Laravel 13 shorthand helpers and short array syntax everywhere.
2. Prefer `final` classes for Actions, Services, DTOs, and Form Requests.
3. Avoid service locator patterns; always use constructor injection.
4. Never put business logic in Models — they define structure, relationships, and scopes only.
5. Keep migrations reversible with explicit `down()` methods.
6. Run `vendor/bin/pint --dirty` before committing PHP changes.
7. Run `php artisan test --compact` to validate changes.

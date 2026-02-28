<laravel-boost-guidelines>
=== .ai/architecture rules ===

# Application Architecture

## Tech Stack

**Backend**: PHP 8.5, Laravel 12, Inertia.js v2, Prism (LLM integration)
**Frontend**: React 19, TypeScript, Tailwind CSS v4, Wayfinder, Radix UI
**Database**: MySQL
**Testing**: Pest v4
**Code Quality**: PHPStan, Laravel Pint, ESLint, Prettier, Husky pre-commit hooks

## Directory Structure

### Backend (`/app`)

```
app/
├── Casts/               # Custom Eloquent casts
├── Enums/               # DayActivities, LlmModels, PromptType, VenueType
├── Http/
│   ├── Controllers/     # ProjectController, DayController, Settings
│   │   ├── Api/         # AddressLookupController, RegenerationController
│   │   └── Manage/      # CRUD management controllers (Countries, States, Cities, Venues, Addresses, Projects, Prompts, DayTravel, DayAccommodation, DayActivity)
│   ├── Middleware/      # Inertia, Appearance handling
│   └── Requests/
│       └── Manage/      # Form request validation for management controllers
├── Models/              # Eloquent models (see Domain Models below)
├── Providers/           # AppServiceProvider, FortifyServiceProvider
├── Services/
│   ├── LLM/             # LLM generation service and generators
│   ├── AddressLookupService.php  # Google Places autocomplete and geo record creation
│   └── ProjectVersionService.php  # Creates new project versions on date changes
├── Traits/              # LlmCallable trait
└── View/Components/     # Blade layout component
```

### Frontend (`/resources/js`)

```
resources/js/
├── actions/             # Wayfinder-generated TypeScript route actions
├── components/
│   ├── address-lookup.tsx  # Google Places address autocomplete
│   └── ui/              # Shadcn/Radix UI components
├── hooks/               # Custom React hooks
├── layouts/             # App, Auth, Settings layouts
├── pages/               # Inertia page components
│   └── manage/          # Management CRUD pages (countries, states, cities, venues, addresses, projects, prompts, project/{travel,accommodations,activities})
├── routes/              # Wayfinder-generated named routes
├── app.tsx              # React entry point
└── lib/utils.ts         # Utility functions
```

## Domain Models

### Travel Planning

| Model | Purpose | Key Relationships |
|-------|---------|-------------------|
| `Project` | Complete trip with dates | Has many `ProjectVersion` |
| `ProjectVersion` | Versioned itinerary snapshot | Has many `Day` |
| `Day` | Single day in itinerary | Has one `DayTravel`, many `DayActivity`, one `DayAccommodation` |
| `DayTravel` | City-to-city movement | Belongs to start/end `City` |
| `DayActivity` | Activity during a day | Belongs to `Venue` or `City` |
| `DayAccommodation` | Lodging for a day | Belongs to `Venue` |

### Geography

| Model | Purpose | Key Relationships |
|-------|---------|-------------------|
| `Country` | Country container | Has many `State`, `City` |
| `State` | State/prefecture | Belongs to `Country`, has many `City` |
| `City` | City with timezone | Belongs to `Country`, `State`, has many `Venue` |
| `Venue` | Specific location | Belongs to `City`, has type (`VenueType` enum), morphOne `Address` |
| `Address` | Physical address with coordinates | MorphTo `addressable`, belongs to `Country`, `State`, `City` |

### Prompt Management

| Model | Purpose | Key Relationships |
|-------|---------|-------------------|
| `Prompt` | Prompt entity with slug, type, and active version | Has many `PromptVersion`, belongs to self (system prompt) |
| `PromptVersion` | Immutable versioned prompt content | Belongs to `Prompt` |

### AI Interactions

| Model | Purpose |
|-------|---------|
| `LlmCall` | Stores every LLM API call with prompt version references, responses, and token counts |

## LLM Service Architecture

### Service Layer (`app/Services/LLM/`)

**GenerateRequiredLLMInteractions**
- Orchestrates what needs AI generation for a project
- Analyzes project structure and builds interaction manifest
- Returns structured data for UI consumption

**BaseLlmGenerator** (Abstract)
- Base class for all LLM generators
- Manages Prism integration with caching via hash comparison
- Loads prompt templates from database (`Prompt`/`PromptVersion` models) and renders with `Blade::render()`
- Stores `LlmCall` records with prompt version references and token counts

**Concrete Generators**
- `CitySightseeing`: City sightseeing suggestions
- `TravelDomestic`: Domestic Japan travel recommendations
- `TravelInternational`: International flight suggestions

### LlmCallable Trait

Models that receive LLM-generated content use the `LlmCallable` trait:
- Provides `llmCall()` polymorphic relationship
- `latestLlmCall()` retrieves most recent interaction
- `latestLlmCallByGenerator()` fetches by generator class

## Frontend Architecture

### Inertia + React

- Server-side routing via Laravel controllers
- React components receive props via Inertia
- Wayfinder generates TypeScript route actions for type safety

### Key Components

| Component | Purpose |
|-----------|---------|
| `AppLayout` | Main authenticated layout |
| `AppShell` | Navigation wrapper |
| `AppSidebar` | Persistent sidebar navigation with Manage and Project sections |
| `AppHeader` | Top bar with user menu |
| `ProjectSelector` | Sidebar dropdown for selecting active project (session-based) |

### Custom Hooks

- `use-appearance`: Dark/light mode management
- `use-mobile`: Mobile device detection
- `use-two-factor-auth`: 2FA state and actions

## Authentication

- **Framework**: Laravel Fortify
- **Features**: Registration, login, 2FA with recovery codes, email verification
- **Middleware**: `HandleInertiaRequests` shares user, app state, projects list, and selected project ID

## Routes

### Web Routes

| Route | Controller | Purpose |
|-------|------------|---------|
| `/` | - | Home page |
| `/dashboard` | - | Authenticated dashboard |
| `/project/{project}` | `ProjectController@show` | Project overview |
| `/project/{project}/day/{day}` | `DayController` | Day details |
| `/settings/*` | Settings controllers | Profile, password, 2FA |
| `/manage/countries` | `CountryController` | Country CRUD |
| `/manage/states` | `StateController` | State CRUD |
| `/manage/cities` | `CityController` | City CRUD |
| `/manage/venues` | `VenueController` | Venue CRUD |
| `/manage/addresses` | `AddressController` | Address CRUD |
| `/manage/prompts` | `PromptController` | Prompt management with versioning |
| `/manage/projects` | `ProjectManagementController` | Project CRUD with version management |
| `/manage/set-project` | `SetProjectController` | Set active project in session |
| `/manage/project/{project}/travel` | `DayTravelManagementController` | Day travel CRUD |
| `/manage/project/{project}/accommodations` | `DayAccommodationManagementController` | Day accommodation CRUD |
| `/manage/project/{project}/activities` | `DayActivityManagementController` | Day activity CRUD |
| `/api/address-lookup/autocomplete` | `AddressLookupController` | Google Places autocomplete |
| `/api/address-lookup/place/{placeId}` | `AddressLookupController` | Google Place details with geo record creation |

## Key Patterns

### Service Pattern
```php
GenerateRequiredLLMInteractions::make()
    ->project($project)
    ->run()
    ->getInteractions();
```

### Generator Pattern
```php
CitySightseeing::make()
    ->activity($dayActivity)
    ->call();
```

### Database Prompts
LLM prompts are stored in the database as `Prompt` and `PromptVersion` records. Templates use Blade syntax and are rendered with `Blade::render()` using dynamic variables for city, date, activity type, etc. Prompts are versioned — edits create new immutable versions, and the active version can be reverted.

=== .ai/overview rules ===

# Japan 26

This app is a companion app to help in the planning of a holiday to Japan in 2026.

Through the use of AI services and data this app will help plan the trip. It will provide recommendations on:

- Flights (International and Domestic)
- Intercity transport (trains, planes and buses)
- Hotels
- Sightseeing activities
- Local transport options (trains and buses) from point A to point B

## Core Concepts

### Projects
A project represents a complete trip with a start date and end date. Projects can have multiple versions (ProjectVersion) allowing iteration on travel plans.

### Days
Each day in a project's itinerary contains:
- **Travel**: Optional movement between cities (domestic or international)
- **Activities**: Sightseeing, wrestling events, dining experiences
- **Accommodation**: Where to stay (when not traveling overnight)

### Activity Types
The app supports these activity types via the `DayActivities` enum:
- `SIGHTSEEING`: Tourist attractions and experiences
- `WRESTLING`: Japanese wrestling events (a focus of the trip)
- `EATING`: Restaurant and dining recommendations

### Geographic Hierarchy
- **Country** → **State** → **City** → **Venue**
- Venues have types (hotels, restaurants, wrestling venues, train stations)

## AI-Powered Features

The app uses Prism with OpenRouter (Google Gemini models) to generate:
- City sightseeing suggestions based on interests and dates
- Domestic travel recommendations between Japanese cities
- International travel suggestions for arrival/departure

All LLM interactions are cached and stored in `LlmCall` records for cost optimization and auditability.

=== .ai/tests rules ===

# Tests

## Backend

PEST tests are recommended but not required during development. You should always look to add tests however you may be instructed not to or to limit the scope / detail of tests in certain situations.

## Frontend

We currently do not have any frontend tests. No need to write these at this time.

=== .ai/code-styling rules ===

# Code Styling

## Pre Commit Hooks

We use Husky to run pre-commit hooks to ensure code quality before changes are committed.

## Laravel Pint

We use [Laravel Pint](https://laravel.com/docs/10.x/pint) for PHP code styling and formatting.

## PHPStan

We use [PHPStan](https://phpstan.org/) for static analysis of our PHP code to catch potential errors and enforce coding standards.

## Laravel

### Factories

Factory definitions values should always be closures. This ensures that values are not generated unless they are needed.

### Models

All models shall be unguarded. Where the AppServiceProvider doesn't already have `Model::unguard();` added to the boot method, add it.

### If Else Statements

As a general rule the use of `if else` statements are discouraged when an early return can be used or when code can be abstract the logical `private` or `protected` methods on the same class is preferred.

### Validation

#### Emails

When validating email addresses please use the `email` validation rule with `strict` and `spoof` validation styles.

```php
$rules => [
    'email' => 'email:strict,spoof'
];
```
#### Enums

Instead of using the `in` validation rule, use the `enum` rule where possible. Where the array of values is static there is a natural ability to use the `enum` rule. If a suitable enum class doesn't exist, create one.

### Route Files

Use `Route::group()` to group routes together whenever possible. Use nested `Route::group()` when necessary up to a depth of 3.

### Don't Repeat Yourself

Follow DRY principles. 

When common code is present in a single class, always look for interoperates to abstract logic into `protected` or `private` methods.

When there is reasonable chunks of common / reusable code between classes, consider whether abstraction into a service or action class or a trait. 

## React / Typescript

### Element attribute function values

Where the logic of an element attribute is complex or more than 3 lines, it should be extracted into a separate function.

Keep functions on a single line when possible.

### Line breaks

Prefer to have a single line break between statements.

```typescript
const handleFormSuccess = (title: string) => {
    const uppercaseTitle = title.toUpperCase();
    if(title.length > 10) {
        return uppercaseTitle;
    }
    return title;
};
```

should be written as:

```typescript
const handleFormSuccess = (title: string) => {
    const uppercaseTitle = title.toUpperCase();
    
    if(title.length > 10) {
        return uppercaseTitle;
    }
    
    return title;
};
```

### Markdown

The React app supports GitHub flavored markdown.

=== .ai/git rules ===

# Git Guidelines

## Branch Naming

Branches must follow this format:
```
YYYYMMDD-summary-words
```

- Date in YYYYMMDD format (e.g., 20260114 for 14 January 2026)
- Summary is 1-4 words, lowercase, hyphen-separated
- Examples: `20260114-smoke-tests`, `20260115-add-channel-filter`

### Commit Messages

- Never reference Claude, AI assistants, or any AI tools in commit messages
- Commit messages should be concise and no more than 72 characters on a single line.

### Pull Requests

PRs should include:
- **Title**: Concise summary of changes (max 50 characters) prefixed with the same "YYYYMMDD - " from the branch name
- **Summary**: Brief description of changes (bullet points)
- **Test plan**: How to verify the changes work
- **Screenshots**: If UI changes are involved

## Protected branches

You must never commit directly to the `main` branch. 

Development must always be committed to a new branch of work that is not `main`.

## Commit Files

Include all changed files including ones your didnt change in the commit.

=== .ai/documentation rules ===

# Documentation Guidelines

## Keeping Documentation Current

When making updates to the codebase, review and update the following guideline files as necessary:

- `.ai/guidelines/architecture.md` - Update when:
  - Adding new models or changing model relationships
  - Creating new services, controllers, or significant classes
  - Adding new frontend components, hooks, or pages
  - Changing the directory structure
  - Adding new routes
  - Modifying authentication or middleware
  - Introducing new patterns or conventions

- `.ai/guidelines/overview.md` - Update when:
  - Adding new core concepts or domain entities
  - Changing the purpose or scope of the application
  - Adding new activity types or feature categories
  - Modifying AI/LLM capabilities

Documentation updates should be concise and follow the existing format in each file.

## After Updating Guidelines

Whenever any file in the `.ai/guidelines/` directory is created or modified, the pre-commit hook will automatically run:

```bash
herd php artisan boost:install
```

This ensures Laravel Boost recompiles the guidelines into the CLAUDE.md and `.junie/` files. These are automatically staged with the commit.

=== .ai/development rules ===

# Development Guidelines

Local development typically occurs on a Mac machine. When running on a Mac, it can be safely assumed that Laravel Herd is installed for managing local PHP environments.

Because of this we should prefix Artisan and Composer commands with `herd` to ensure they run in the correct environment.

For example, to run a fresh migration and seed the database, we would run `herd php artisan migrate:fresh --seed`.

When developing locally we use the command `npm run dev` to start the frontend development server with Vite.

We only run `npm run build` when we want to create a production build of the frontend assets.

## Wayfinder

When running `wayfinder:generate` manually, always include the `--with-form` flag to generate form variants. Without it, routes that use `.form()` (such as the login page) will break.

```bash
herd php artisan wayfinder:generate --with-form
```

The Vite plugin already has `formVariants: true` configured in `vite.config.ts`, so the dev server handles this automatically. This flag is only needed when running the artisan command directly.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.17
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/horizon (HORIZON) - v5
- laravel/prompts (PROMPTS) - v0
- laravel/socialite (SOCIALITE) - v5
- laravel/wayfinder (WAYFINDER) - v0
- larastan/larastan (LARASTAN) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/react (INERTIA) - v2
- react (REACT) - v19
- tailwindcss (TAILWINDCSS) - v4
- @laravel/vite-plugin-wayfinder (WAYFINDER) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== herd rules ===

## Laravel Herd

- The application is served by Laravel Herd and will be available at: `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate URLs for the user to ensure valid URLs.
- You must not run any commands to make the site available via HTTP(S). It is always available through Laravel Herd.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

## Inertia

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (`vite.config.js`).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use the `search-docs` tool for accurate guidance on all things Inertia.

<code-snippet name="Inertia Render Example" lang="php">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>

=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 and v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Deferred props.
- Infinite scrolling using merging props and `WhenVisible`.
- Lazy loading data on scroll.
- Polling.
- Prefetching.

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing/animated skeleton.

### Inertia Form General Guidance
- The recommended way to build forms when using Inertia is with the `<Form>` component - a useful example is below. Use the `search-docs` tool with a query of `form component` for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use the `search-docs` tool with a query of `useForm helper` for guidance.
- `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use the `search-docs` tool with a query of `form component resetting` for guidance.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version-specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== wayfinder/core rules ===

## Laravel Wayfinder

Wayfinder generates TypeScript functions and types for Laravel controllers and routes which you can import into your client-side code. It provides type safety and automatic synchronization between backend routes and frontend code.

### Development Guidelines
- Always use the `search-docs` tool to check Wayfinder correct usage before implementing any features.
- Always prefer named imports for tree-shaking (e.g., `import { show } from '@/actions/...'`).
- Avoid default controller imports (prevents tree-shaking).
- Run `php artisan wayfinder:generate` after route changes if Vite plugin isn't installed.

### Feature Overview
- Form Support: Use `.form()` with `--with-form` flag for HTML form attributes — `<form {...store.form()}>` → `action="/posts" method="post"`.
- HTTP Methods: Call `.get()`, `.post()`, `.patch()`, `.put()`, `.delete()` for specific methods — `show.head(1)` → `{ url: "/posts/1", method: "head" }`.
- Invokable Controllers: Import and invoke directly as functions. For example, `import StorePost from '@/actions/.../StorePostController'; StorePost()`.
- Named Routes: Import from `@/routes/` for non-controller routes. For example, `import { show } from '@/routes/post'; show(1)` for route name `post.show`.
- Parameter Binding: Detects route keys (e.g., `{post:slug}`) and accepts matching object properties — `show("my-post")` or `show({ slug: "my-post" })`.
- Query Merging: Use `mergeQuery` to merge with `window.location.search`, set values to `null` to remove — `show(1, { mergeQuery: { page: 2, sort: null } })`.
- Query Parameters: Pass `{ query: {...} }` in options to append params — `show(1, { query: { page: 1 } })` → `"/posts/1?page=1"`.
- Route Objects: Functions return `{ url, method }` shaped objects — `show(1)` → `{ url: "/posts/1", method: "get" }`.
- URL Extraction: Use `.url()` to get URL string — `show.url(1)` → `"/posts/1"`.

### Example Usage

<code-snippet name="Wayfinder Basic Usage" lang="typescript">
    // Import controller methods (tree-shakable)...
    import { show, store, update } from '@/actions/App/Http/Controllers/PostController'

    // Get route object with URL and method...
    show(1) // { url: "/posts/1", method: "get" }

    // Get just the URL...
    show.url(1) // "/posts/1"

    // Use specific HTTP methods...
    show.get(1) // { url: "/posts/1", method: "get" }
    show.head(1) // { url: "/posts/1", method: "head" }

    // Import named routes...
    import { show as postShow } from '@/routes/post' // For route name 'post.show'
    postShow(1) // { url: "/posts/1", method: "get" }
</code-snippet>

### Wayfinder + Inertia
If your application uses the `<Form>` component from Inertia, you can use Wayfinder to generate form action and method automatically.
<code-snippet name="Wayfinder Form Component (React)" lang="typescript">

<Form {...store.form()}><input name="title" /></Form>

</code-snippet>

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== pest/core rules ===

## Pest
### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest {name}`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests that have a lot of duplicated data. This is often the case when testing validation rules, so consider this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>

=== pest/v4 rules ===

## Pest 4

- Pest 4 is a huge upgrade to Pest and offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within Pest 4 browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>

=== inertia-react/core rules ===

## Inertia + React

- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="react">

import { Link } from '@inertiajs/react'
<Link href="/">Home</Link>

</code-snippet>

=== inertia-react/v2/forms rules ===

## Inertia v2 + React Forms

<code-snippet name="`<Form>` Component Example" lang="react">

import { Form } from '@inertiajs/react'

export default () => (
    <Form action="/users" method="post">
        {({
            errors,
            hasErrors,
            processing,
            wasSuccessful,
            recentlySuccessful,
            clearErrors,
            resetAndClearErrors,
            defaults
        }) => (
        <>
        <input type="text" name="name" />

        {errors.name && <div>{errors.name}</div>}

        <button type="submit" disabled={processing}>
            {processing ? 'Creating...' : 'Create User'}
        </button>

        {wasSuccessful && <div>User created successfully!</div>}
        </>
    )}
    </Form>
)

</code-snippet>

=== tailwindcss/core rules ===

## Tailwind CSS

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc.).
- Think through class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child carefully to limit repetition, and group elements logically.
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing; don't use margins.

<code-snippet name="Valid Flex Gap Spacing Example" lang="html">
    <div class="flex gap-8">
        <div>Superior</div>
        <div>Michigan</div>
        <div>Erie</div>
    </div>
</code-snippet>

### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

=== tailwindcss/v4 rules ===

## Tailwind CSS 4

- Always use Tailwind CSS v4; do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.

<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>

### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option; use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |

=== prism-php/prism rules ===

## Prism

- Prism is a Laravel package for integrating Large Language Models (LLMs) into applications with a fluent, expressive and eloquent API.
- IMPORTANT: Activate `developing-with-prism` skill when working with Prism features.
</laravel-boost-guidelines>

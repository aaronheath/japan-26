# Application Architecture

## Tech Stack

**Backend**: PHP 8.4, Laravel 12, Inertia.js v2, Prism (LLM integration)
**Frontend**: React 19, TypeScript, Tailwind CSS v4, Wayfinder, Radix UI
**Database**: MySQL
**Testing**: Pest v4
**Code Quality**: PHPStan, Laravel Pint, ESLint, Prettier, Husky pre-commit hooks

## Directory Structure

### Backend (`/app`)

```
app/
├── Casts/               # Custom Eloquent casts
├── Enums/               # DayActivities, LlmModels
├── Http/
│   ├── Controllers/     # ProjectController, DayController, Settings
│   ├── Middleware/      # Inertia, Appearance handling
│   └── Requests/        # Form validation classes
├── Models/              # Eloquent models (see Domain Models below)
├── Providers/           # AppServiceProvider, FortifyServiceProvider
├── Services/
│   └── LLM/             # LLM generation service and generators
├── Traits/              # LlmCallable trait
└── View/Components/     # Blade layout component
```

### Frontend (`/resources/js`)

```
resources/js/
├── actions/             # Wayfinder-generated TypeScript route actions
├── components/
│   └── ui/              # Shadcn/Radix UI components
├── hooks/               # Custom React hooks
├── layouts/             # App, Auth, Settings layouts
├── pages/               # Inertia page components
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
| `Venue` | Specific location | Belongs to `City`, has type (hotel, restaurant, etc.) |

### AI Interactions

| Model | Purpose |
|-------|---------|
| `LlmCall` | Stores every LLM API call with prompts, responses, and token counts |

## LLM Service Architecture

### Service Layer (`app/Services/LLM/`)

**GenerateRequiredLLMInteractions**
- Orchestrates what needs AI generation for a project
- Analyzes project structure and builds interaction manifest
- Returns structured data for UI consumption

**BaseLlmGenerator** (Abstract)
- Base class for all LLM generators
- Manages Prism integration with caching via hash comparison
- Handles prompt rendering from Blade views (`resources/views/prompts/`)
- Stores `LlmCall` records with token counts

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
| `AppSidebar` | Persistent sidebar navigation |
| `AppHeader` | Top bar with user menu |

### Custom Hooks

- `use-appearance`: Dark/light mode management
- `use-mobile`: Mobile device detection
- `use-two-factor-auth`: 2FA state and actions

## Authentication

- **Framework**: Laravel Fortify
- **Features**: Registration, login, 2FA with recovery codes, email verification
- **Middleware**: `HandleInertiaRequests` shares user and app state

## Routes

### Web Routes

| Route | Controller | Purpose |
|-------|------------|---------|
| `/` | - | Home page |
| `/dashboard` | - | Authenticated dashboard |
| `/project/{project}` | `ProjectController@show` | Project overview |
| `/project/{project}/day/{day}` | `DayController` | Day details |
| `/settings/*` | Settings controllers | Profile, password, 2FA |

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

### Blade View Prompts
LLM prompts are Blade views in `resources/views/prompts/` with dynamic variables for city, date, activity type, etc.


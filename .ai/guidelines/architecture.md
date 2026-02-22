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


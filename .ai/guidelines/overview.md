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

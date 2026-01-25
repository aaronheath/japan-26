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

This ensures Laravel Boost recompiles the guidelines into the CLAUDE.md file. The updated CLAUDE.md is automatically staged with the commit.

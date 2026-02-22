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

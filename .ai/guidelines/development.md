# Development Guidelines

Local development typically occurs on a Mac machine. When running on a Mac, it can be safely assumed that Laravel Herd is installed for managing local PHP environments.

Because of this we should prefix Artisan and Composer commands with `herd` to ensure they run in the correct environment.

For example, to run a fresh migration and seed the database, we would run `herd php artisan migrate:fresh --seed`.

When developing locally we use the command `npm run dev` to start the frontend development server with Vite.

We only run `npm run build` when we want to create a production build of the frontend assets.

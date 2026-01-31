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

### If Else Statements

As a general rule the use of `if else` statements are discouraged when an early return can be used or when code can be abstract the logical `private` or `protected` methods on the same class is preferred.

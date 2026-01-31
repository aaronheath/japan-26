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

### Route Files

Use `Route::group()` to group routes together whenever possible. Use nested `Route::group()` when necessary up to a depth of 3.

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

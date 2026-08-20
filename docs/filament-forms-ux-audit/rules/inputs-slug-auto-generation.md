# Rule: Slug Auto-Generation

## Severity
Medium

## Problem
Slug fields on Tour, BlogPost, Destination, Category, and similar resources are plain TextInputs with no auto-generation. The admin has to manually type a URL-safe slug, which is error-prone (spaces, uppercase, special characters) and wastes time. Most admins expect the slug to be derived from the title automatically.

## Detection
- A `slug` field that is a plain TextInput with no auto-fill behavior
- A `name`/`title` field exists alongside a `slug` field with no connection between them
- Slug fields that are editable on create but should auto-populate

## Recommendation
On create forms, auto-generate the slug from the title/name field. Use `->hiddenOnCreate()` to hide it during creation, or disable it and auto-fill with `->helperText('Auto-generated from title')`. On edit forms, allow manual override. At minimum, add helper text explaining the behavior.

## Example
```php
use Filament\Forms\Components\TextInput;

TextInput::make('name')
    ->required()
    ->live(onBlur: true)
    ->afterStateUpdated(function (string $state, callable $set, string $operation) {
        if ($operation === 'create') {
            $set('slug', str($state)->slug());
        }
    }),

TextInput::make('slug')
    ->required()
    ->unique(ignoreRecord: true)
    ->helperText('Auto-generated from name. Edit only if needed.')
    ->dehydrated(),
```

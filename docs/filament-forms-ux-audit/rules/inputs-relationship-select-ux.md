# Rule: Relationship Select UX

## Severity
Medium

## Problem
Relationship fields become slow and error-prone when admins have to scroll long option lists or guess how records are labeled. This is especially painful for users, categories, customers, and other shared entities with large datasets.

## Detection
- `Select` fields backed by large relationships with no `->searchable()`
- Relationship fields where labels are ambiguous or hard to distinguish
- Long option lists loaded without clear reason

## Recommendation
For relationship-backed selects, make the lookup fast and obvious. Use `->relationship()` with a meaningful title attribute or label callback. Add `->searchable()` when the option set is more than small. Use `->preload()` only when the option count is reasonable and the form benefits from immediate access.

## Example
```php
use Filament\Forms\Components\Select;

Select::make('customer_id')
    ->relationship('customer', 'name')
    ->searchable()
    ->preload(),
```

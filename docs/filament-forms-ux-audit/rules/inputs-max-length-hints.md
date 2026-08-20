# Rule: Max Length Hints

## Severity
Low

## Problem
Short-string fields like postcodes, phone numbers, license plates, and codes have no visible length constraint. The admin could paste a full address into a postcode field or enter a long string that gets silently truncated at the database level, causing data loss or errors.

## Detection
- TextInput for fields where the value has a known maximum length (postcodes, codes, plates, PINs)
- Fields backed by database columns with a specific `varchar` length
- Any field where overly long input is never valid

## Recommendation
Add `->maxLength()` to constrain input length. Filament shows a character counter when `->maxLength()` is set, giving the admin a visual cue. Pair with `->placeholder()` to show the expected format.

## Example
```php
use Filament\Forms\Components\TextInput;

TextInput::make('postcode')
    ->maxLength(10)
    ->placeholder('e.g. SW1A 1AA'),

TextInput::make('license_plate')
    ->maxLength(8)
    ->placeholder('e.g. AB12 CDE'),

TextInput::make('short_description')
    ->maxLength(160)
    ->helperText('Shown in listing cards, keep under 160 characters.'),
```

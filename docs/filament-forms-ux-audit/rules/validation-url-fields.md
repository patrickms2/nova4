# Rule: URL Fields

## Severity
Medium

## Problem
URL fields like `cta_url`, `website`, or `portfolio_link` that are plain TextInputs with no validation or type hint. The admin can enter "google.com", "not a url", or a phone number and the form will accept it. This leads to broken links in the frontend.

## Detection
- TextInput for fields named `*_url`, `*_link`, `website`, `portfolio`, or similar
- No `->url()` validation rule on URL-like fields
- No prefix or suffix icon hinting that a URL is expected

## Recommendation
Add `->url()` for validation (or `->activeUrl()` for live reachability checks). Add `->prefix('https://')` or `->suffixIcon(Heroicon::Link)` to make the expected format visually obvious.

## Example
```php
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

TextInput::make('cta_url')
    ->url()
    ->prefix('https://')
    ->suffixIcon(Heroicon::Link)
    ->placeholder('https://example.com/page'),

TextInput::make('website')
    ->url()
    ->suffixIcon(Heroicon::Link),
```

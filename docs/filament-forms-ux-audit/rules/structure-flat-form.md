# Rule: Flat Form Structure

## Severity
High

## Problem
Forms with 8+ fields in a flat list with no visual grouping are overwhelming. The admin sees a wall of inputs with no hierarchy, making it hard to scan, find specific fields, or understand the form's structure. This is especially painful on complex resources (18+ fields) where everything runs together with no mental anchor points.

## Detection
- More than 8 fields with no `Section` components
- All fields at the same visual level in a single column or 2-column grid
- No logical separation between unrelated field groups

## Recommendation
Group fields into Sections by domain meaning. Common groupings:
- **Personal Info**: name, email, phone
- **Location**: city, postcode, address
- **Professional**: status, rate, experience, qualifications
- **Content**: bio, description (full-width)
- **Identity**: name, slug, brand, model, year
- **Specs**: body type, fuel, transmission, engine, hp
- **Physical**: color, seats, doors, trunk
- **Registration**: plate, VIN, mileage

**When proposing sections, also consider visual height balance.** Filament renders sections side-by-side in the default 2-column page layout. If one section would be much taller than the others (e.g., it contains a RichEditor or 5+ fields), stack shorter sections vertically on the opposite side using a parent `Grid::make(2)` with a nested `Grid::make(1)` for the stacked column. See [Section Height Balance](structure-section-height-balance.md) for details and examples. Do not flag height imbalance as a separate finding — include it in the same sectioning recommendation.

For very large forms (16+ fields), use `Section` within Tabs to avoid a long scrolling page.

## Example
```php
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

Section::make('Personal Info')
    ->columns(2)
    ->schema([
        TextInput::make('name')->required(),
        TextInput::make('email')->email()->required(),
        TextInput::make('phone')
            ->placeholder('+44 7700 900000'),
    ]),

Section::make('Location')
    ->columns(2)
    ->schema([
        TextInput::make('city'),
        TextInput::make('postcode')
            ->maxLength(10)
            ->placeholder('e.g. SW1A 1AA'),
    ]),

Section::make('Bio')
    ->schema([
        Textarea::make('bio')
            ->rows(4)
            ->placeholder('Brief professional background...')
            ->columnSpanFull(),
    ]),
```

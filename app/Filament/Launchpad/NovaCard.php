<?php

namespace App\Filament\Launchpad;

use Filament\Launchpad\Launchpad\BaseCardPreset;
use Filament\Support\Icons\Heroicon;

class NovaCard extends BaseCardPreset
{
    // Derivado automaticamente do nome da classe (via BaseCardPreset):
    //   key() -> "nova"   (guardado no card criado, campo library_key)
    //
    // O titulo por omissao seria "Nova" (headline do nome da classe
    // sem o sufixo "Card") — sobrepoe-o explicitamente abaixo, ou apaga a
    // linha para usar essa omissao.
    protected string $title = 'Nova';


    // Sobrepoe so as propriedades que precisares — tudo o resto fica com
    // omissao sensata (null / 'shortcut' / 'none'):
    //
     protected ?string $subtitle = 'Ponto de Venda';
     protected ?string $icon = Heroicon::OutlinedBanknotes;
    //
    // Tipo do card: 'kpi' | 'shortcut' (omissao) | 'widget'
    protected string $type = 'shortcut';

    // --- Exemplo de preset "kpi" (liga a uma KpiSource viva) ---
    //
     protected ?string $kpiValue = '0';           // valor fixo — usado so quando nao ha kpi_source, ou como fallback
     protected ?string $unit = 'MT';
     protected ?string $trend = '+0% vs ontem';
     protected ?string $trendColor = 'success';   // success | danger | warning | gray
     protected ?string $badge = null;
     protected ?string $kpiSource = 'vendas_hoje'; // key() de uma KpiSource registada (ver make:launchpad-kpi)

    // --- Exemplo de preset "widget" (renderiza um widget Filament registado) ---
    //
    // protected string $type = 'widget';
    // protected ?string $widgetKey = 'vendas-chart'; // key registada via LaunchpadPlugin::widgets()

    // --- Alvo ao clicar (ignorado quando type='widget') ---
    //
    // protected string $targetType = 'url';         // none (omissao) | url | resource | page
     protected ?string $targetValue = '/admin/sales';
}

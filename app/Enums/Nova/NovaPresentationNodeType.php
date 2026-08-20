<?php

declare(strict_types=1);

namespace App\Enums\Nova;

enum NovaPresentationNodeType: string
{
    case Capability = 'capability';
    case Navigation = 'navigation';
    case Subnavigation = 'subnavigation';
    case Relation = 'relation';

    case ListView = 'list_view';
    case RecordView = 'record_view';
    case Page = 'page';

    case Stats = 'stats';
    case Widget = 'widget';
    case Tabs = 'tabs';
    case SavedView = 'saved_view';
    case Search = 'search';
    case Filters = 'filters';

    case Table = 'table';
    case Kanban = 'kanban';
    case Tree = 'tree';
    case Calendar = 'calendar';
    case Roster = 'roster';
    case Cards = 'cards';
    case Timeline = 'timeline';
    case Map = 'map';

    case Form = 'form';
    case Infolist = 'infolist';

    case HeaderActions = 'header_actions';
    case Action = 'action';
    case QuickNavigation = 'quick_navigation';

    case Custom = 'custom';
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Canvas layout metadata for one Live System Graph node.
 *
 * This table stores presentation-only data (node position on the canvas).
 * It never stores semantic NOVA Definition data. See NOVA_GRAPH.md.
 */
class NovaGraphLayout extends Model
{
    protected $table = 'nova_graph_layouts';

    protected $fillable = [
        'workspace_id',
        'node_address',
        'x',
        'y',
    ];

    protected function casts(): array
    {
        return [
            'x' => 'integer',
            'y' => 'integer',
        ];
    }
}

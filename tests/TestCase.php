<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * The agentic chatbot plugin reads its own DB connection from the
     * environment; in tests it must use the default sqlite connection.
     * The callback runs right after the app boots, before RefreshDatabase
     * executes the migrations.
     */
    protected function refreshApplication()
    {
        parent::refreshApplication();

        config([
            'filament-agentic-chatbot.database.connection' => null,
            'filament-agentic-chatbot.vector.backend' => 'database',
            'agent-graph.database.connection' => null,
        ]);
    }
}

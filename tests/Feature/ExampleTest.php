<?php

namespace Tests\Feature;
use Guava\FilamentMcp\Testing\TestsMcp;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
 use TestsMcp;
    public function test_the_application_returns_a_successful_response(): void
    {
        $plainTextToken = "11d5475e9b0d265d06c8479c144605bc94d15bdaf1539b35e6b9ec77947a9a67";
        $data = $this->mcp('app')
            ->asToken($plainTextToken)
            ->callJson('list_reservations');
        expect($this->mcp('app')->toolNames())
            ->toContain('list_docs')
            ->not->toContain('list_posts');
    }
}

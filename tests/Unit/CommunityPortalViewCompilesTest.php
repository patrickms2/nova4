<?php

namespace Tests\Unit;

use Illuminate\View\Compilers\BladeCompiler;
use Tests\TestCase;

class CommunityPortalViewCompilesTest extends TestCase
{
    public function test_community_portal_view_compiles_to_valid_php(): void
    {
        $viewPath = resource_path('views/livewire/community-portal.blade.php');

        /** @var BladeCompiler $compiler */
        $compiler = $this->app->make('blade.compiler');
        $compiler->compile($viewPath);

        $compiledPath = $compiler->getCompiledPath($viewPath);

        $this->assertFileExists($compiledPath);

        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($compiledPath).' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, implode(PHP_EOL, $output));
    }

    public function test_community_camera_capture_view_compiles_to_valid_php(): void
    {
        $viewPath = resource_path('views/components/community-camera-capture.blade.php');

        /** @var BladeCompiler $compiler */
        $compiler = $this->app->make('blade.compiler');
        $compiler->compile($viewPath);

        $compiledPath = $compiler->getCompiledPath($viewPath);

        $this->assertFileExists($compiledPath);

        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($compiledPath).' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, implode(PHP_EOL, $output));
    }
}

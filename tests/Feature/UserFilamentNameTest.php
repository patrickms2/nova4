<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Tests\TestCase;

class UserFilamentNameTest extends TestCase
{
    public function test_filament_user_name_is_string_when_name_is_null(): void
    {
        $user = new User([
            'email' => 'test@example.com',
        ]);
        $user->name = null;
        $user->first_name = null;
        $user->last_name = null;

        $this->assertSame('test@example.com', Filament::getUserName($user));
    }

    public function test_filament_user_name_uses_first_and_last_name(): void
    {
        $user = new User;
        $user->first_name = 'Patrick';
        $user->last_name = 'MS';

        $this->assertSame('Patrick MS', Filament::getUserName($user));
    }
}

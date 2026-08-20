<?php

namespace Tests\Feature\Community;

use Tests\TestCase;

class ComunigestFrontTest extends TestCase
{
    public function test_commercial_front_explains_the_complete_community_ecosystem(): void
    {
        $response = $this->get(route('comunigest.front'));

        $response
            ->assertOk()
            ->assertSee('El sistema operativo de tu administración de fincas')
            ->assertSee('Administrador de fincas')
            ->assertSee('Comunidades')
            ->assertSee('Empleados')
            ->assertSee('Propietarios')
            ->assertSee('APP ADMIN')
            ->assertSee('APP EMPLEADO')
            ->assertSee('APP PROPIETARIO')
            ->assertSee('Planes inteligentes')
            ->assertSee('Documentos sin clasificar a mano')
            ->assertSee('Solicitar demostración');
    }

    public function test_commercial_front_links_to_the_existing_community_login(): void
    {
        $response = $this->get(route('comunigest.front'));

        $response
            ->assertOk()
            ->assertSee(url('/comunigest/login'), escape: false);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class AppShellTest extends TestCase
{
    public function test_serves_the_spa_shell(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertSee('id="root"', false);
    }
}

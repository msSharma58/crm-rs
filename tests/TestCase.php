<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Backend CI does not build frontend assets; SPA blade uses @vite.
        $this->withoutVite();
    }
}

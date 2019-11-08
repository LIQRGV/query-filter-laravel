<?php
namespace Tests\LIQRGV\QueryFilter;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }
}


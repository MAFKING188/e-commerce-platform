<?php

namespace Modules\CatalogDelivery\Tests;

use Modules\CatalogDelivery\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use PHPUnit\Framework\Attributes\Test;

class ExceptionHandlerTest extends TestCase
{
    #[Test]
    public function report_uses_catalog_errors_channel_for_guest(): void
    {
        $handler = new Handler(app());
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => null);

        $e = new \Exception('Test error');

        $this->expectsLogging('catalog-errors');
        $handler->report($request, $e);
    }

    #[Test]
    public function report_uses_default_laravel_log_for_admin(): void
    {
        $handler = new Handler(app());
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => new \stdClass());
        $request->user()->role = 'admin';

        $e = new \Exception('Test error');

        $this->expectsLogging('laravel');
        $handler->report($request, $e);
    }

    #[Test]
    public function render_returns_catalogdelivery_validation_view(): void
    {
        $handler = new Handler(app());
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => null);

        $e = new ValidationException(['test' => ['error']]);

        $result = $handler->render($request, $e);

        $this->assertStringContainsString('catalogdelivery::errors.validation', (string) $result);
    }

    #[Test]
    public function render_returns_catalogdelivery_404_view_for_auth_exception(): void
    {
        $handler = new Handler(app());
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => null);

        $e = new AuthorizationException('Unauthorized');

        $result = $handler->render($request, $e);

        $this->assertStringContainsString('catalogdelivery::errors.404', (string) $result);
    }

    #[Test]
    public function render_returns_catalogdelivery_500_view_for_generic_error(): void
    {
        $handler = new Handler(app());
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => null);

        $e = new \Exception('Internal error');

        $result = $handler->render($request, $e);

        $this->assertStringContainsString('catalogdelivery::errors.500', (string) $result);
    }

    protected function expectsLogging(string $channel): void
    {
        $log = $this->mock('log');
        $log->expects->channel($channel)->once()->method('error');
        $this->app->instance('log', $log);
    }
}
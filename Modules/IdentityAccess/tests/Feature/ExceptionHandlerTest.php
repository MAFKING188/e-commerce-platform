<?php

namespace Modules\IdentityAccess\Tests;

use Modules\IdentityAccess\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use PHPUnit\Framework\Attributes\Test;

class ExceptionHandlerTest extends TestCase
{
    #[Test]
    public function render_returns_identity_validation_view(): void
    {
        $handler = new Handler(app());

        $e = new ValidationException(['test' => ['error']]);

        $result = $handler->render(null, $e);

        $this->assertStringContainsString('identity::errors.validation', (string) $result);
    }

    #[Test]
    public function render_returns_identity_404_view_for_auth_exception(): void
    {
        $handler = new Handler(app());

        $e = new AuthorizationException('Unauthorized');

        $result = $handler->render(null, $e);

        $this->assertStringContainsString('identity::errors.404', (string) $result);
    }

    #[Test]
    public function render_returns_identity_500_view_for_generic_error(): void
    {
        $handler = new Handler(app());

        $e = new \Exception('Internal error');

        $result = $handler->render(null, $e);

        $this->assertStringContainsString('identity::errors.500', (string) $result);
    }

    #[Test]
    public function report_logs_to_identity_errors_for_guest(): void
    {
        $kernel = app()->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        $handler = new Handler(app());

        $e = new \Exception('Test error');

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => null);

        $reportedChannel = null;
        $stored = [];

        $log = new class {
            public function error($message, array $context = []) {
                $stored[] = ['message' => $message, 'channel' => 'identity-errors'];
            }
        };

        app()->instance('log', $log);

        $handler->report($e);

        $this->assertContains('identity-errors', array_column($stored, 'channel'));
    }

    #[Test]
    public function render_returns_correct_view_types(): void
    {
        $handler = new Handler(app());

        $validationResult = $handler->render(null, new ValidationException(['test' => ['error']]));
        $authResult = $handler->render(null, new AuthorizationException('Unauthorized'));
        $genericResult = $handler->render(null, new \Exception('Error'));

        $this->assertStringContainsString('identity::errors.validation', (string) $validationResult);
        $this->assertStringContainsString('identity::errors.404', (string) $authResult);
        $this->assertStringContainsString('identity::errors.500', (string) $genericResult);
    }
}
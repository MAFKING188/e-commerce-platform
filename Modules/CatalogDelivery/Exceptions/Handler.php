<?php

namespace Modules\CatalogDelivery\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as FoundationHandler;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends FoundationHandler
{
    public function report(Throwable $e): void
    {
        $request = $this->getRequest();

        $role = $this->detectUserRole($request);

        if ($role !== 'admin') {
            $this->channel($request, $e, 'catalog-errors');
        } else {
            parent::report($e);
        }
    }

    protected function getRequest(): ?Request
    {
        return $this->app->make(Request::class);
    }

    protected function detectUserRole(?Request $request): string
    {
        if ($request && $request->user()?->hasRole('admin')) {
            return 'admin';
        }

        if ($request && ($request->user()?->hasRole('partner') || $request->isGuest())) {
            return 'guest_or_partner';
        }

        return 'guest';
    }

    protected function channel(?Request $request, \Exception $e, string $channel): void
    {
        if ($request) {
            $this->app->make('log')->channel($channel)->error($e->getMessage());
        } else {
            parent::report($e);
        }
    }

    public function render($request, Throwable $e): Response|string
    {
        if ($e instanceof ValidationException) {
            return response()->view('catalogdelivery::errors.validation', [], 422);
        }

        if ($e instanceof AuthorizationException) {
            return response()->view('catalogdelivery::errors.404', [], 403);
        }

        $code = $e->getCode() ?: 500;

        return response()->view(
            'catalogdelivery::errors.' . $code,
            [],
            $code
        );
    }
}
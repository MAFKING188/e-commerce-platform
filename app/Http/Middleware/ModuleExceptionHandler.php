<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Foundation\Exceptions\Handler as BaseHandler;

class ModuleExceptionHandler
{
    public function handle(Request $request, Closure $next)
    {
        $moduleHandler = $this->findModuleHandler($request);

        if (!$moduleHandler) {
            return $next($request);
        }

        return $moduleHandler->handle($request, function ($e) use ($moduleHandler) {
            return $moduleHandler->render($request, $e);
        });
    }

    private function findModuleHandler(Request $request)
    {
        $path = $request->path();

        // Partner hub routes
        if (str_starts_with($path, 'partner')) {
            return app('Modules\PartnerHub\Exceptions\Handler');
        }

        // Admin/identity routes
        if (str_starts_with($path, 'admin') || str_starts_with($path, 'identity')) {
            return app('Modules\IdentityAccess\Exceptions\Handler');
        }

        // Shop/catalog routes
        if (str_starts_with($path, 'shop') || str_starts_with($path, 'product') || str_starts_with($path, 'collection')) {
            return app('Modules\CatalogDelivery\Exceptions\Handler');
        }

        // Checkout/order routes
        if (str_starts_with($path, 'checkout') || str_starts_with($path, 'order')) {
            return app('Modules\MarketplacePipeline\Exceptions\Handler');
        }

        // Default: fall back to base application Handler
        return null;
    }
}
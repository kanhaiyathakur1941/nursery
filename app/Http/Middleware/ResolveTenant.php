<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(private CurrentTenant $currentTenant) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = trim((string) $request->header('X-Tenant-ID', ''));

        if ($tenantId === '' || ! ctype_digit($tenantId)) {
            abort(Response::HTTP_BAD_REQUEST, 'A valid X-Tenant-ID header is required.');
        }

        $tenant = Tenant::query()->find($tenantId);

        if (! $tenant instanceof Tenant) {
            abort(Response::HTTP_BAD_REQUEST, 'A valid X-Tenant-ID header is required.');
        }

        $this->currentTenant->set($tenant);

        return $next($request);
    }
}

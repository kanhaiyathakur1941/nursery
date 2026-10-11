<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\Context;
use RuntimeException;

class CurrentTenant
{
    private const CONTEXT_KEY = 'tenant_id';

    public function set(Tenant $tenant): void
    {
        Context::add(self::CONTEXT_KEY, $tenant->id);
    }

    public function isset(): bool
    {
        return Context::has(self::CONTEXT_KEY);
    }

    public function id(): int
    {
        if (! $this->isset()) {
            throw new RuntimeException('No tenant is set for this operation.');
        }

        return (int) Context::get(self::CONTEXT_KEY);
    }
}

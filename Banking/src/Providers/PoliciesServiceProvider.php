<?php

declare(strict_types=1);

namespace App\Banking\Providers;

use App\Banking\UserInterface\Policies\AccountBalancePolicy;
use App\Banking\UserInterface\Policies\AccountPolicy;
use App\Banking\UserInterface\Policies\TransactionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class PoliciesServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Gate::define('account', AccountPolicy::class . '@access');
        Gate::define('transaction', TransactionPolicy::class . '@access');
        Gate::define('account-balance', AccountBalancePolicy::class . '@access');
    }
}

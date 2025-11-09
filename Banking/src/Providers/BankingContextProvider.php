<?php

declare(strict_types=1);

namespace App\Banking\Providers;

use App\Banking\Repositories\Account\AccountRepository;
use App\Banking\Repositories\Account\AccountViewRepository;
use App\Banking\Repositories\Account\Impl\AccountDatabaseRepositoryImpl;
use App\Banking\Repositories\AccountBalance\AccountBalanceRepository;
use App\Banking\Repositories\AccountBalance\AccountBalanceViewRepository;
use App\Banking\Repositories\AccountBalance\Impl\AccountBalanceDatabaseRepositoryImpl;
use App\Banking\Repositories\Bank\BankRepository;
use App\Banking\Repositories\Bank\BankViewRepository;
use App\Banking\Repositories\Bank\Impl\BankDatabaseRepositoryImpl;
use App\Banking\Repositories\Currency\CurrencyRepository;
use App\Banking\Repositories\Currency\CurrencyViewRepository;
use App\Banking\Repositories\Currency\Impl\CurrencyDatabaseRepositoryImpl;
use App\Banking\Repositories\Transaction\Impl\TransactionDatabaseRepositoryImpl;
use App\Banking\Repositories\Transaction\TransactionRepository;
use App\Banking\Repositories\Transaction\TransactionViewRepository;
use App\Banking\Repositories\TransactionCategory\Impl\TransactionCategoryDatabaseRepositoryImpl;
use App\Banking\Repositories\TransactionCategory\TransactionCategoryRepository;
use App\Banking\Repositories\TransactionCategory\TransactionCategoryViewRepository;
use App\Banking\Repositories\TransactionCode\Impl\TransactionCodeDatabaseRepositoryImpl;
use App\Banking\Repositories\TransactionCode\TransactionCodeRepository;
use App\Banking\Repositories\TransactionCode\TransactionCodeViewRepository;
use App\Banking\Repositories\UserTransactionPattern\Impl\UserTransactionPatternDatabaseRepositoryImpl;
use App\Banking\Repositories\UserTransactionPattern\UserTransactionPatternRepository;
use App\Banking\Repositories\UserTransactionPattern\UserTransactionPatternViewRepository;
use App\Banking\Services\Account\AccountService;
use App\Banking\Services\Account\Impl\AccountServiceImpl;
use App\Banking\Services\AccountBalance\AccountBalanceService;
use App\Banking\Services\AccountBalance\impl\AccountBalanceServiceImpl;
use App\Banking\Services\ParseTransactions\Impl\TransactionsFileParseServiceImpl;
use App\Banking\Services\ParseTransactions\Impl\TransactionsHtmlParseServiceImpl;
use App\Banking\Services\ParseTransactions\Impl\TransactionsParseServiceImpl;
use App\Banking\Services\ParseTransactions\TransactionsFileParseService;
use App\Banking\Services\ParseTransactions\TransactionsHtmlParseService;
use App\Banking\Services\ParseTransactions\TransactionsParseService;
use App\Banking\Services\Transaction\Impl\TransactionServiceImpl;
use App\Banking\Services\Transaction\TransactionService;
use App\Banking\Services\TransactionCode\Impl\TransactionCodeServiceImpl;
use App\Banking\Services\TransactionCode\TransactionCodeService;
use App\Banking\Services\UserTransactionPattern\Impl\UserTransactionPatternServiceImpl;
use App\Banking\Services\UserTransactionPattern\UserTransactionPatternService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

final class BankingContextProvider extends ServiceProvider
{
    private $configs = [
        'banking.serializer' => 'serializer',
    ];

    private function registerConfigs(): void
    {
        foreach ($this->configs as $key => $file) {
            $this->mergeConfigFrom(
                dirname(__DIR__, 2) . '/configs/' . $file . '.php',
                $key
            );
        }
    }

    private function registerServiceProvider(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(SerializerBuilderProvider::class);
        $this->app->register(PoliciesServiceProvider::class);
        $this->app->register(TranslationServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
    }

    private function registerFacades(): void
    {
    }

    private function registerServices(): void
    {
        $this->app->bind(AccountService::class, AccountServiceImpl::class);
        $this->app->bind(AccountBalanceService::class, AccountBalanceServiceImpl::class);
        $this->app->bind(TransactionService::class, TransactionServiceImpl::class);
        $this->app->bind(TransactionCodeService::class, TransactionCodeServiceImpl::class);
        $this->app->bind(TransactionsParseService::class, TransactionsParseServiceImpl::class);
        $this->app->bind(TransactionsFileParseService::class, TransactionsFileParseServiceImpl::class);
        $this->app->bind(TransactionsHtmlParseService::class, TransactionsHtmlParseServiceImpl::class);
        $this->app->bind(UserTransactionPatternService::class, UserTransactionPatternServiceImpl::class);
    }

    private function registerRepositories(): void
    {
        $this->app->bind(AccountRepository::class, AccountDatabaseRepositoryImpl::class);
        $this->app->bind(AccountViewRepository::class, AccountDatabaseRepositoryImpl::class);
        $this->app->bind(TransactionRepository::class, TransactionDatabaseRepositoryImpl::class);
        $this->app->bind(TransactionViewRepository::class, TransactionDatabaseRepositoryImpl::class);
        $this->app->bind(BankRepository::class, BankDatabaseRepositoryImpl::class);
        $this->app->bind(BankViewRepository::class, BankDatabaseRepositoryImpl::class);
        $this->app->bind(TransactionCategoryRepository::class, TransactionCategoryDatabaseRepositoryImpl::class);
        $this->app->bind(TransactionCategoryViewRepository::class, TransactionCategoryDatabaseRepositoryImpl::class);
        $this->app->bind(TransactionCodeRepository::class, TransactionCodeDatabaseRepositoryImpl::class);
        $this->app->bind(TransactionCodeViewRepository::class, TransactionCodeDatabaseRepositoryImpl::class);
        $this->app->bind(AccountBalanceRepository::class, AccountBalanceDatabaseRepositoryImpl::class);
        $this->app->bind(AccountBalanceViewRepository::class, AccountBalanceDatabaseRepositoryImpl::class);
        $this->app->bind(CurrencyRepository::class, CurrencyDatabaseRepositoryImpl::class);
        $this->app->bind(CurrencyViewRepository::class, CurrencyDatabaseRepositoryImpl::class);
        $this->app->bind(UserTransactionPatternRepository::class, UserTransactionPatternDatabaseRepositoryImpl::class);
        $this->app->bind(UserTransactionPatternViewRepository::class, UserTransactionPatternDatabaseRepositoryImpl::class);
    }

    public function register(): void
    {
        $this->registerConfigs();
        $this->registerServices();
        $this->registerRepositories();
        $this->registerFacades();
        $this->registerServiceProvider();
    }
}

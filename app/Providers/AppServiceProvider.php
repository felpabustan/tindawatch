<?php

namespace App\Providers;

use App\Contracts\Repositories\ActivityLogRepositoryInterface;
use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\EwalletProviderRepositoryInterface;
use App\Contracts\Repositories\EwalletTransactionRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\SaleRepositoryInterface;
use App\Contracts\Repositories\StoreRepositoryInterface;
use App\Contracts\Repositories\TeamRepositoryInterface;
use App\Repositories\ActivityLogRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\EwalletProviderRepository;
use App\Repositories\EwalletTransactionRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SaleRepository;
use App\Repositories\StoreRepository;
use App\Repositories\TeamRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        SaleRepositoryInterface::class => SaleRepository::class,
        ProductRepositoryInterface::class => ProductRepository::class,
        CategoryRepositoryInterface::class => CategoryRepository::class,
        CustomerRepositoryInterface::class => CustomerRepository::class,
        EwalletProviderRepositoryInterface::class => EwalletProviderRepository::class,
        EwalletTransactionRepositoryInterface::class => EwalletTransactionRepository::class,
        StoreRepositoryInterface::class => StoreRepository::class,
        TeamRepositoryInterface::class => TeamRepository::class,
        ActivityLogRepositoryInterface::class => ActivityLogRepository::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

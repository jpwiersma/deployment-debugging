<?php

namespace App\Providers;

use App\Diagnostics\Checks\CacheCheck;
use App\Diagnostics\Checks\ComposerRequirementsCheck;
use App\Diagnostics\Checks\ConfigSummaryCheck;
use App\Diagnostics\Checks\DatabaseCheck;
use App\Diagnostics\Checks\DeploymentCheck;
use App\Diagnostics\Checks\EnvironmentCheck;
use App\Diagnostics\Checks\FilesystemCheck;
use App\Diagnostics\Checks\PhpCheck;
use App\Diagnostics\Checks\QueueCheck;
use App\Diagnostics\Checks\SchedulerCheck;
use App\Diagnostics\Checks\WebServerCheck;
use App\Diagnostics\DiagnosticRunner;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DiagnosticRunner::class, function () {
            return (new DiagnosticRunner)
                ->register(new ConfigSummaryCheck)
                ->register(new ComposerRequirementsCheck)
                ->register(new EnvironmentCheck)
                ->register(new PhpCheck)
                ->register(new DatabaseCheck)
                ->register(new CacheCheck)
                ->register(new QueueCheck)
                ->register(new FilesystemCheck)
                ->register(new DeploymentCheck)
                ->register(new WebServerCheck)
                ->register(new SchedulerCheck);
        });
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

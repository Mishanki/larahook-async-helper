<?php

namespace Larahook\AsyncHelper;


use App\Core\Errors;
use App\Exceptions\ApplicationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Bootstrap\SetRequestForConsole;

class AsyncHelper
{
    /**
     * @param $function
     * @param array $args
     */
    public static function call($function, array $args = []): void
    {
        if (!\function_exists('pcntl_fork')) {
            throw new ApplicationException('Cannot fork process, pcnt_fork not available', Errors::PCNTL_ASYNC_ERROR->value);
        }
        if (app()->runningUnitTests()) {
            \call_user_func_array($function, $args);

            return;
        }

        $pid = pcntl_fork();
        if ($pid === 0) {
            ob_start();
            posix_setsid();
            self::createLaravelAppInChild();
            \call_user_func_array($function, $args);
            ob_end_clean();
            posix_kill(posix_getpid(), SIGKILL);
            exit(0);
        }
    }

    /**
     * @return Application
     */
    private static function createLaravelAppInChild(): Application
    {
        $app = require base_path('bootstrap/app.php');
        $app->make(Application::class)->bootstrapWith([
            LoadEnvironmentVariables::class,
            LoadConfiguration::class,
            HandleExceptions::class,
            RegisterProviders::class,
            SetRequestForConsole::class,
        ]);

        \DB::reconnect();

        return $app;
    }
}

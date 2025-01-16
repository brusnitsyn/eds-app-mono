<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->autoBindFacades();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    private function autoBindFacades(): void
    {
        $facadeFiles = File::files(app_path('Facades'));

        if (count($facadeFiles) == 0) { return; }

        foreach ($facadeFiles as $facadeFile) {
            $facadeFileName = pathinfo($facadeFile, PATHINFO_FILENAME);
            $serviceFiles = collect(File::files(app_path('Services')));
            $serviceFileIndex = $serviceFiles->map(function ($file) {
                return $file->getFilename();
            })->search($facadeFileName . "Service.php");

            if ($serviceFileIndex !== false) {
                $serviceFile = $serviceFiles->get($serviceFileIndex);
                $serviceFileName = pathinfo($serviceFile, PATHINFO_FILENAME);
                $this->app->bind(Str::lower($facadeFileName) . '.facade', "App\\Services\\$serviceFileName");
            }
        }
    }
}

<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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
        Carbon::setLocale('pt_BR');

        // Hospedagem compartilhada termina o TLS antes de chegar no PHP, sem
        // nos dizer isso via cabeçalho de proxy confiável — sem isso, URLs
        // assinadas (Livewire\Features\SupportFileUploads\FilePreviewController,
        // usado na prévia de foto) às vezes são geradas com esquema http,
        // fazendo a assinatura não bater quando o navegador acessa via https
        // e o Livewire responde 401. Força https só onde ele realmente roda.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}

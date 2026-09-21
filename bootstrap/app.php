<?php

use App\Http\Middleware\EnsureProfileOnboarded;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Where the 'guest' middleware sends already-authenticated users.
        $middleware->redirectUsersTo('/shifts');
        $middleware->alias(['onboarded' => EnsureProfileOnboarded::class]);

        // Hospedagem compartilhada termina o TLS antes do PHP (via hCDN/LiteSpeed
        // na Hostinger) — sem confiar no X-Forwarded-Proto que essa camada manda,
        // $request->url() "acha" que a requisição chegou em http, mesmo com
        // URL::forceScheme('https') forçando o lado da geração (AppServiceProvider).
        // Isso quebra a validação de URLs assinadas (ex.: prévia de foto do
        // Livewire): assina com https, valida com http, nunca bate — 401. Não dá
        // pra saber o IP exato do proxy de antemão, então confia em qualquer um.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();

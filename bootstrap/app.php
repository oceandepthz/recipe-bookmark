<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // リバースプロキシ（TLS終端）背後で HTTPS 判定・セキュアCookie・リダイレクトを正しく扱う。
        // 信頼するプロキシは「私的IPレンジ」に限定する（'*' は使わない）。これにより、
        // インターネット側クライアント（公開IP）が送る X-Forwarded-* は信頼されず IP 偽装を防げる。
        // 前段プロキシが下記レンジ外にある場合はその IP/CIDR を追加すること。
        // X-Forwarded-Host は信頼しない（生成URLのホスト注入対策。URLは APP_URL に固定）。
        $middleware->trustProxies(
            at: ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '127.0.0.1', '::1'],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();

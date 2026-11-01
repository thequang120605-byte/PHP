<?php
use Illuminate\Foundation\Application;use Illuminate\Foundation\Configuration\{Exceptions,Middleware};
return Application::configure(basePath:dirname(__DIR__))->withRouting(web:__DIR__.'/../routes/web.php',commands:__DIR__.'/../routes/console.php',health:'/up')->withMiddleware(function(Middleware $m){$m->alias(['active'=>\App\Http\Middleware\Active::class,'admin'=>\App\Http\Middleware\Admin::class]);})->withExceptions(function(Exceptions $e){})->create();

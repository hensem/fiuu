<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RouteContextMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();
        $controller = optional($route)->getControllerClass();
        $method = optional($route)->getActionMethod();

        // stash into request attributes for later use by the trait
        $request->attributes->set('route_controller', $controller);
        $request->attributes->set('route_method', $method);

        return $next($request);
    }
}
?>
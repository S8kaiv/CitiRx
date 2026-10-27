<?php

use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;

function citirxAdminRoutes(): Collection
{
    return collect(
        app('router')->getRoutes()->getRoutes(),
    )
        ->filter(
            fn (IlluminateRoute $route): bool => $route->uri() === 'admin'
                || str_starts_with(
                    $route->uri(),
                    'admin/',
                ),
        )
        ->values();
}

test('every administrator route is named and protected', function (): void {
    $routes = citirxAdminRoutes();

    Assert::assertNotEmpty(
        $routes,
        'No administrator routes were registered.',
    );

    $routes->each(function (IlluminateRoute $route): void {
        $description = implode(
            '|',
            $route->methods(),
        ).' '.$route->uri();

        $middleware = $route->gatherMiddleware();
        $name = $route->getName();

        Assert::assertNotNull(
            $name,
            "{$description} must have a route name.",
        );

        Assert::assertStringStartsWith(
            'admin.',
            $name,
            "{$description} must use the admin route-name prefix.",
        );

        Assert::assertContains(
            'auth',
            $middleware,
            "{$description} is missing auth middleware.",
        );

        Assert::assertContains(
            'verified',
            $middleware,
            "{$description} is missing verified middleware.",
        );

        Assert::assertContains(
            'admin',
            $middleware,
            "{$description} is missing administrator middleware.",
        );
    });
});

test('administrator mutation routes never accept get requests', function (): void {
    $routes = citirxAdminRoutes();

    $routes
        ->filter(
            fn (IlluminateRoute $route): bool => collect(
                $route->methods(),
            )->intersect([
                'POST',
                'PUT',
                'PATCH',
                'DELETE',
            ])->isNotEmpty(),
        )
        ->each(function (IlluminateRoute $route): void {
            Assert::assertNotContains(
                'GET',
                $route->methods(),
                "{$route->getName()} must not mutate through GET.",
            );

            Assert::assertNotContains(
                'HEAD',
                $route->methods(),
                "{$route->getName()} must not mutate through HEAD.",
            );
        });
});

test('system health remains read only', function (): void {
    $route = app('router')
        ->getRoutes()
        ->getByName(
            'admin.system-health.index',
        );

    Assert::assertNotNull($route);

    expect($route->methods())
        ->toBe([
            'GET',
            'HEAD',
        ]);
});

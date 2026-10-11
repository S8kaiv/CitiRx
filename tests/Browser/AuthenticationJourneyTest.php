<?php

use App\Models\User;

it(
    'allows login and prevents dashboard restoration after logout',
    function () {
        $user = User::factory()->create([
            'first_name' => 'Browser',
            'middle_name' => null,
            'last_name' => 'Student',
            'email' => 'browser.student@citirx.test',
            'password' => 'password',
            'role' => 'student',
        ]);

        $page = visit('/login');

        $page
            ->assertPathIs('/login')
            ->assertSee('Welcome back')
            ->fill('email', $user->email)
            ->fill('password', 'password')
            ->click('Sign In')
            ->assertPathIs('/dashboard')
            ->assertSee('Student Dashboard')
            ->assertSee(
                'Welcome back, Browser Student!',
            );

        $page
            ->click('button[title="Log Out"]')
            ->assertPathIs('/login')
            ->assertSee('Welcome back');

        /*
         * Reproduce the previously reported bug:
         * press Back after logging out.
         */
        $page->script(
            'window.history.back();',
        );

        $page
            ->wait(2)
            ->assertPathIs('/login')
            ->assertSee('Welcome back')
            ->assertDontSee('Student Dashboard');
    },
);

it(
    'renders critical public pages without JavaScript errors',
    function () {
        $pages = visit([
            '/login',
            '/register',
            '/forgot-password',
        ]);

        $pages
            ->assertNoSmoke()
            ->assertNoJavaScriptErrors();
    },
);

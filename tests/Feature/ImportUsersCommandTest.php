<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportUsersCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_command_imports_users_from_valid_json_url()
    {
        $mockUsers = [
            [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'username' => 'johndoe',
                'address' => [
                    'street' => 'Kulas Light',
                    'suite' => 'Apt. 556',
                    'city' => 'Gwenborough',
                    'zipcode' => '92998-3874',
                    'geo' => [
                        'lat' => '-37.3159',
                        'lng' => '81.1496',
                    ],
                ],
                'phone' => '1-770-736-8031 x56442',
                'website' => 'hildegard.org',
                'company' => [
                    'name' => 'Romaguera-Crona',
                    'catchPhrase' => 'Multi-layered client-server neural-net',
                    'bs' => 'harness real-time e-markets',
                ],
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'username' => 'janesmith',
                'address' => [
                    'street' => 'Kulas Light',
                    'suite' => 'Apt. 556',
                    'city' => 'Gwenborough',
                    'zipcode' => '92998-3874',
                    'geo' => [
                        'lat' => '-37.3159',
                        'lng' => '81.1496',
                    ],
                ],
                'phone' => '1-770-736-8031 x56442',
                'website' => 'hildegard.org',
                'company' => [
                    'name' => 'Romaguera-Crona',
                    'catchPhrase' => 'Multi-layered client-server neural-net',
                    'bs' => 'harness real-time e-markets',
                ],
            ],
            [
                'id' => 3,
                'name' => 'Bob Johnson',
                'email' => 'bob@example.com',
                'username' => 'bobjohnson',
                'address' => [
                    'street' => 'Kulas Light',
                    'suite' => 'Apt. 556',
                    'city' => 'Gwenborough',
                    'zipcode' => '92998-3874',
                    'geo' => [
                        'lat' => '-37.3159',
                        'lng' => '81.1496',
                    ],
                ],
                'phone' => '1-770-736-8031 x56442',
                'website' => 'hildegard.org',
                'company' => [
                    'name' => 'Romaguera-Crona',
                    'catchPhrase' => 'Multi-layered client-server neural-net',
                    'bs' => 'harness real-time e-markets',
                ],
            ],
        ];

        Http::fake([
            'https://example.com/users.json' => Http::response($mockUsers, 200),
        ]);

        $this->artisan('users:import', [
            'url' => 'https://example.com/users.json',
            'limit' => 2,
        ])->assertSuccessful();

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'bob@example.com']);
    }

    /** @test */
    public function test_command_fails_when_limit_is_zero()
    {
        Http::fake([
            'https://example.com/users.json' => Http::response([], 200),
        ]);

        $this->artisan('users:import', [
            'url' => 'https://example.com/users.json',
            'limit' => 0,
        ])->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    /** @test */
    public function test_command_fails_when_limit_is_negative()
    {
        Http::fake([
            'https://example.com/users.json' => Http::response([], 200),
        ]);

        $this->artisan('users:import', [
            'url' => 'https://example.com/users.json',
            'limit' => -5,
        ])->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    /** @test */
    public function test_command_fails_when_url_returns_non_successful_status()
    {
        Http::fake([
            'https://example.com/users.json' => Http::response(null, 404),
        ]);

        $this->artisan('users:import', [
            'url' => 'https://example.com/users.json',
            'limit' => 5,
        ])->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    /** @test */
    public function test_command_fails_when_json_is_not_an_array()
    {
        Http::fake([
            'https://example.com/users.json' => Http::response(['error' => 'Invalid format'], 200),
        ]);

        $this->artisan('users:import', [
            'url' => 'https://example.com/users.json',
            'limit' => 5,
        ])->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    /** @test */
    public function test_command_handles_http_timeout_gracefully()
    {
        Http::fake([
            'https://example.com/users.json' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            },
        ]);

        $this->artisan('users:import', [
            'url' => 'https://example.com/users.json',
            'limit' => 5,
        ])->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}

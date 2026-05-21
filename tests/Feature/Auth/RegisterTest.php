<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class RegisterTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_user_register_with_valid_data()
    {
        $payload = [
            'email' => 'test@example.com',
            'password' => '1234456781234',
            'password_confirmation' => '1234456781234'
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'email',
                    'created_at',
                ],
                'token',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => $payload['email'],
        ]);
    }

    public function test_user_register_with_invalid_data()
    {
        $payload = [
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'short',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_user_register_with_existing_email()
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $payload = [
            'email' => 'existing@example.com',
            'password' => '12345678910',
            'password_confirmation' => '12345678910',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_user_register_with_password_confirmation_mismatch()
    {
        $payload = [
            'email' => 'test@example.com',
            'password' => '12345678910',
            'password_confirmation' => '98765432',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }
}

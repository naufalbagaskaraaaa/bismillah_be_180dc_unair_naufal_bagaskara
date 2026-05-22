<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    /**
     * A basic feature test example.
     */
    public function test_user_login_with_correct_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('123456781234')
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => '123456781234'
        ]);

        $response->assertStatus(200)->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token'
            ]
        ]);
    }

    public function test_user_login_with_incorrect_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('123456781234')
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'hidup_jokiwi'
        ]);

        $response->assertStatus(401)->assertJsonPath('message', 'invalid token');
    }

    public function test_jwt_token_contains_valid_payload_claims()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $payload = JWTAuth::setToken($token)->getPayload();

        $this->assertArrayHasKey('sub', $payload->toArray()); // sub = user id
        $this->assertArrayHasKey('exp', $payload->toArray()); // exp = expired at
        $this->assertEquals($user->id, $payload->get('sub'));
    }

    public function test_login_rejects_unregistered_email()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'tuyul@example.com',
            'password' => '123456781234'
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'invalid token');
    }

    public function test_login_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'sembarang@example.com'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_expired_token_is_rejected_on_protected_endpoint()
    {
        $user = User::factory()->create();

        $token = JWTAuth::fromUser($user);

        $this->travel(61)->minutes(); // memajukan waktu 61 menit agar token expired default TTL di lebih dari 60 menit

        $response = $this->withToken($token)->getJson('/api/protected-endpoint'); // akses api mennggunakan api yang kadaluarsa 

        $response->assertStatus(401);
    }
}

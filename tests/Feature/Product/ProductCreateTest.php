<?php

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ProductCreateTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    protected function authenticateUser() // helper method untuk mengautentikasi user dan mendapatkan token
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        return [$user, $token];
    }

    public function test_create_product_without_token()
    {
        $response = $this->postJson('/api/v1/products', [
            'name' => 'Sepatu',
            'price' => 50000
        ]);

        $response->assertStatus(401);
    }

    public function test_create_product_with_valid_data()
    {
        [$user, $token] = $this->authenticateUser();

        $response = $this->withToken($token)->postJson('/api/v1/products', [
            'name' => 'kanjut',
            'price' => 20000
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.owner_id', $user->id)
            ->assertJsonPath('data.name', 'kanjut')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'price', 'owner_id', 'created_at']
            ]);

        $this->assertDatabaseHas('products', ['name' => 'kanjut', 'owner_id' => $user->id]);
    }

    public function test_create_product_with_zero_or_negative_price()
    {
        [$user, $token] = $this->authenticateUser();

        $response = $this->withToken($token)->postJson('/api/v1/products', [
            'name' => 'Barang Gratis',
            'price' => 0
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['price']);

        $responseNegative = $this->withToken($token)->postJson('/api/v1/products', [
            'name' => 'Barang Minus',
            'price' => -100
        ]);

        $responseNegative->assertStatus(422)->assertJsonValidationErrors(['price']);
    }

    public function test_cannot_create_product_without_name()
    {
        [$user, $token] = $this->authenticateUser();

        $response = $this->withToken($token)->postJson('/api/v1/products', [
            'price' => 30000
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    }
}

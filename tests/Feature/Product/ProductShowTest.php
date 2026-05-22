<?php

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ProductShowTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticateUser()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        return [$user, $token];
    }

    public function test_show_fails_without_token()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['owner_id' => $user->id]);
        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(401);
    }

    public function test_show_returns_422_for_invalid_uuid()
    {
        [$user, $token] = $this->authenticateUser();

        $response = $this->withToken($token)->getJson('/api/v1/products/123');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id']);
    }

    public function test_show_returns_404_if_product_not_found()
    {
        [$user, $token] = $this->authenticateUser();

        $randomUuid = (string) \Illuminate\Support\Str::uuid();
        $response = $this->withToken($token)->getJson("/api/v1/products/{$randomUuid}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Product not found'
            ]);
    }

    public function test_show_returns_200_with_correct_structure()
    {
        [$user, $token] = $this->authenticateUser();

        $product = Product::factory()->create([
            'owner_id' => $user->id,
            'name' => 'Produk Khusus',
            'price' => 150000
        ]);

        $response = $this->withToken($token)->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'price',
                    'owner_id',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonPath('data.name', 'Produk Khusus')
            ->assertJsonPath('data.price', 150000);
    }
}

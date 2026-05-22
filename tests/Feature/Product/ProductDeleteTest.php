<?php

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ProductDeleteTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    protected function authHeaders(User $user): array
    // helper method untuk mendapatkan header otentikasi pake token JWT
    {
        $token = JWTAuth::fromUser($user);

        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    public function test_owner_can_soft_delete_product_successfully(): void
    {
        $owner = User::factory()->create();

        $product = Product::create([
            'name' => 'To Be Deleted',
            'price' => 10000,
            'owner_id' => $owner->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($owner))
            ->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->getJson("/api/v1/products/{$product->id}")
            ->assertStatus(404);

        $this->getJson('/api/v1/products')
            ->assertStatus(200)
            ->assertJsonMissing([
                'id' => $product->id,
            ]);
    }

    public function test_non_owner_cannot_delete_product(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $product = Product::create([
            'name' => 'Protected Product',
            'price' => 15000,
            'owner_id' => $owner->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($otherUser))
            ->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Forbidden: You can only delete your own products',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    }

    public function test_delete_returns_404_when_product_not_found(): void
    {
        $owner = User::factory()->create();
        $missingId = (string) Str::uuid();

        $response = $this->withHeaders($this->authHeaders($owner))
            ->deleteJson("/api/v1/products/{$missingId}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Product not found',
            ]);
    }

    public function test_delete_returns_422_when_id_is_not_valid_uuid(): void
    {
        $owner = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($owner))
            ->deleteJson('/api/v1/products/not-a-uuid');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'The id must be a valid UUID.',
            ])
            ->assertJsonValidationErrors(['id']);
    }

    public function test_delete_returns_401_when_token_missing(): void
    {
        $owner = User::factory()->create();

        $product = Product::create([
            'name' => 'No Token Product',
            'price' => 12000,
            'owner_id' => $owner->id,
        ]);

        $this->deleteJson("/api/v1/products/{$product->id}")
            ->assertStatus(401);
    }

    public function test_soft_deleted_product_does_not_appear_in_index()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $product1 = Product::factory()->create(['owner_id' => $user->id]);
        $product2 = Product::factory()->create(['owner_id' => $user->id]);

        $this->withToken($token)
            ->deleteJson("/api/v1/products/{$product1->id}")
            ->assertStatus(200);

        $response = $this->withToken($token)
            ->getJson('/api/v1/products');

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $productIds = collect($responseData)->pluck('id')->toArray();

        $this->assertNotContains($product1->id, $productIds);
        $this->assertContains($product2->id, $productIds);
    }
}

<?php

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;;

class ProductUpdateTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */

    protected function makeExpiredToken(User $user): string
    // helper method untuk membuat token experied
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];

        $payload = [
            'sub' => $user->id,
            'iat' => now()->subHour()->timestamp,
            'exp' => now()->subMinute()->timestamp,
            'jti' => (string) \Illuminate\Support\Str::uuid(),
        ];

        $base64UrlEncode = function (string $data): string {
            return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        };

        $segments = [
            $base64UrlEncode(json_encode($header)),
            $base64UrlEncode(json_encode($payload)),
        ];

        $signature = hash_hmac(
            'sha256',
            implode('.', $segments),
            config('jwt.secret'),
            true
        );

        $segments[] = $base64UrlEncode($signature);

        return implode('.', $segments);
    }
    protected function tokenFor(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    protected function authHeaders(User $user): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->tokenFor($user),
            'Accept'        => 'application/json',
        ];
    }

    public function test_owner_can_update_full_data_successfully(): void
    {
        $owner = User::factory()->create();

        $product = Product::query()->create([
            'id'        => (string) Str::uuid(),
            'owner_id'  => $owner->id,
            'name'      => 'ayam',
            'price'     => 10000,
        ]);

        $payload = [
            'name'  => 'buku',
            'price' => 25000,
        ];

        $response = $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/v1/products/{$product->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product updated successfully',
            ])
            ->assertJsonPath('data.name', 'buku')
            ->assertJsonPath('data.price', 25000);

        $this->assertDatabaseHas('products', [
            'id'    => $product->id,
            'name'  => 'buku',
            'price' => 25000,
        ]);
    }

    public function test_owner_can_partially_update_price_only(): void
    {
        $owner = User::factory()->create();

        $product = Product::query()->create([
            'id'        => (string) Str::uuid(),
            'owner_id'  => $owner->id,
            'name'      => 'naufal',
            'price'     => 10000,
        ]);

        $response = $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/v1/products/{$product->id}", [
                'price' => 15000,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product updated successfully',
            ])
            ->assertJsonPath('data.name', 'naufal')
            ->assertJsonPath('data.price', 15000);

        $this->assertDatabaseHas('products', [
            'id'    => $product->id,
            'name'  => 'naufal',
            'price' => 15000,
        ]);
    }

    public function test_non_owner_cannot_update_product(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $product = Product::query()->create([
            'id'        => (string) Str::uuid(),
            'owner_id'  => $owner->id,
            'name'      => 'Protected Product',
            'price'     => 12000,
        ]);

        $response = $this->withHeaders($this->authHeaders($otherUser))
            ->patchJson("/api/v1/products/{$product->id}", [
                'name' => 'domas',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Forbidden: You can only update your own products',
            ]);

        $this->assertDatabaseHas('products', [
            'id'   => $product->id,
            'name' => 'Protected Product',
        ]);
    }

    public function test_update_returns_404_when_product_not_found(): void
    {
        $owner = User::factory()->create();
        $missingId = (string) Str::uuid();

        $response = $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/v1/products/{$missingId}", [
                'name' => 'paisal',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Product not found',
            ]);
    }

    public function test_update_returns_422_when_id_is_not_valid_uuid(): void
    {
        $owner = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($owner))
            ->patchJson('/api/v1/products/not-a-uuid', [
                'name' => 'alfin',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'The id must be a valid UUID.',
            ])
            ->assertJsonValidationErrors(['id']);
    }

    public function test_update_returns_422_when_price_is_zero_or_negative(): void
    {
        $owner = User::factory()->create();

        $product = Product::query()->create([
            'id'        => (string) Str::uuid(),
            'owner_id'  => $owner->id,
            'name'      => 'nanas',
            'price'     => 10000,
        ]);

        $response = $this->withHeaders($this->authHeaders($owner))
            ->patchJson("/api/v1/products/{$product->id}", [
                'price' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonValidationErrors(['price']);
    }

    public function test_update_returns_401_when_token_missing(): void
    {
        $owner = User::factory()->create();

        $product = Product::query()->create([
            'id'        => (string) Str::uuid(),
            'owner_id'  => $owner->id,
            'name'      => 'nanas',
            'price'     => 10000,
        ]);

        $response = $this->patchJson("/api/v1/products/{$product->id}", [
            'name' => 'No Token',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_returns_401_when_token_expired(): void
    {
        $owner = User::factory()->create();

        $product = Product::query()->create([
            'id'       => (string) Str::uuid(),
            'owner_id' => $owner->id,
            'name'     => 'Valid Product',
            'price'    => 10000,
        ]);

        $expiredToken = $this->makeExpiredToken($owner);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $expiredToken,
            'Accept'        => 'application/json',
        ])->patchJson("/api/v1/products/{$product->id}", [
            'name' => 'Expired Token',
        ]);

        $response->assertStatus(401);
    }
}

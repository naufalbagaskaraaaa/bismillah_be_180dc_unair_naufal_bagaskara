<?php

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ProductIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticateUser()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        return [$user, $token];
    }

    public function test_access_index_without_token()
    {
        $response = $this->getJson('/api/v1/products');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [],
            'pagination' => [
                'total',
                'per_page',
                'current_page',
                'total_pages',
            ]
        ]);
    }

    public function test_index_validates_limit_more_than_100()
    {
        [$user, $token] = $this->authenticateUser();

        $response = $this->withToken($token)->getJson('/api/v1/products?limit=101');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['limit']);
    }

    public function test_index_returns_strict_pagination_structure()
    {
        [$user, $token] = $this->authenticateUser();

        Product::factory()->count(15)->create(['owner_id' => $user->id]);

        $response = $this->withToken($token)->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'price', 'owner_id', 'created_at']
                ],
                'pagination' => [
                    'total',
                    'per_page',
                    'current_page',
                    'total_pages'
                ]
            ]);

        $this->assertEquals(15, $response->json('pagination.total'));
        $this->assertEquals(10, $response->json('pagination.per_page'));
        $this->assertEquals(2, $response->json('pagination.total_pages'));
        $this->assertCount(10, $response->json('data'));
    }

    public function test_index_search_filter_is_case_insensitive()
    {
        [$user, $token] = $this->authenticateUser();

        Product::factory()->create(['name' => 'kanjut', 'owner_id' => $user->id, 'price' => 1000]);
        Product::factory()->create(['name' => 'bakugan', 'owner_id' => $user->id, 'price' => 1000]);

        $response = $this->withToken($token)->getJson('/api/v1/products?search=kanjut');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('kanjut', $response->json('data.0.name'));
    }

    public function test_index_validates_page_less_than_1()
    {
        [$user, $token] = $this->authenticateUser();

        $response = $this->withToken($token)->getJson('/api/v1/products?page=0');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation Error'
            ])
            ->assertJsonValidationErrors(['page']);
    }

    public function test_index_can_sort_by_price_asc()
    {
        [$user, $token] = $this->authenticateUser();

        Product::factory()->create(['name' => 'Produk A', 'price' => 500, 'owner_id' => $user->id]);
        Product::factory()->create(['name' => 'Produk C', 'price' => 100, 'owner_id' => $user->id]);
        Product::factory()->create(['name' => 'Produk B', 'price' => 300, 'owner_id' => $user->id]);

        $response = $this->withToken($token)->getJson('/api/v1/products?sort_by=price&sort_order=asc');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('Produk C', $data[0]['name']);
        $this->assertEquals('Produk B', $data[1]['name']);
        $this->assertEquals('Produk A', $data[2]['name']);
    }

    public function test_index_uses_default_pagination_and_sorting()
    {
        [$user, $token] = $this->authenticateUser();

        Product::factory()->create(['name' => 'Tertua', 'created_at' => now()->subDays(2), 'owner_id' => $user->id]);
        Product::factory()->create(['name' => 'Terbaru', 'created_at' => now(), 'owner_id' => $user->id]);

        $response = $this->withToken($token)->getJson('/api/v1/products');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('Terbaru', $data[0]['name']); // ini untuk memastikan yang data yang paling baru muncul duluan
        $this->assertEquals('Tertua', $data[1]['name']);
    }
}

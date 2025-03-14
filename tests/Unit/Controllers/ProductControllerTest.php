<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Product_image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->createPermissions();

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo([
            'view_products',
            'create_products',
            'edit_products',
            'delete_products'
        ]);

        $this->category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category'
        ]);

        Sanctum::actingAs($this->admin);
    }

    private function createPermissions()
    {
        $permissions = [
            'view_products',
            'create_products',
            'edit_products',
            'delete_products'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }

    public function test_can_list_products()
    {
        Product::create([
            'name' => 'Test Product 1',
            'slug' => 'test-product-1',
            'price' => 99.99,
            'stock' => 10,
            'status' => 'available',
            'category_id' => $this->category->id
        ]);

        Product::create([
            'name' => 'Test Product 2',
            'slug' => 'test-product-2',
            'price' => 149.99,
            'stock' => 5,
            'status' => 'available',
            'category_id' => $this->category->id
        ]);

        $response = $this->getJson('/api/v1/admin/products');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'price',
                            'stock',
                            'status',
                            'category_id',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);
    }

    public function test_can_create_product()
    {
        $image = UploadedFile::fake()->image('product.jpg');

        $productData = [
            'name' => 'New Product',
            'slug' => 'new-product',
            'price' => 199.99,
            'stock' => 15,
            'status' => 'available',
            'category_id' => $this->category->id,
            'images' => [$image],
            'primary_image' => 0
        ];

        $response = $this->postJson('/api/v1/admin/products', $productData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                        'stock',
                        'status',
                        'category_id',
                        'created_at',
                        'updated_at'
                    ]
                ]);

        $this->assertDatabaseHas('products', [
            'name' => $productData['name'],
            'slug' => $productData['slug'],
            'price' => $productData['price'],
            'stock' => $productData['stock'],
            'status' => $productData['status'],
            'category_id' => $productData['category_id']
        ]);
    }

    public function test_can_show_product()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 99.99,
            'stock' => 10,
            'status' => 'available',
            'category_id' => $this->category->id
        ]);

        $response = $this->getJson("/api/v1/admin/products/{$product->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'data' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                        'stock',
                        'status',
                        'category_id',
                        'created_at',
                        'updated_at'
                    ]
                ]);
    }

    public function test_can_update_product()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 99.99,
            'stock' => 10,
            'status' => 'available',
            'category_id' => $this->category->id
        ]);

        $updatedData = [
            'name' => 'Updated Product',
            'slug' => 'updated-product',
            'price' => 149.99,
            'stock' => 20,
            'status' => 'available',
            'category_id' => $this->category->id
        ];

        $response = $this->putJson("/api/v1/admin/products/{$product->id}", $updatedData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                        'stock',
                        'status',
                        'category_id',
                        'updated_at',
                        'created_at'
                    ]
                ]);

        $this->assertDatabaseHas('products', $updatedData);
    }

    public function test_can_delete_product()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 99.99,
            'stock' => 10,
            'status' => 'available',
            'category_id' => $this->category->id
        ]);

        $response = $this->deleteJson("/api/v1/admin/products/{$product->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Product deleted successfully'
                ]);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_cannot_create_product_with_invalid_data()
    {
        $invalidData = [
            'name' => '',
            'price' => 'not-a-number',
            'stock' => -1,
            'status' => 'invalid-status',
            'category_id' => 999
        ];

        $response = $this->postJson('/api/v1/admin/products', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'price', 'stock', 'category_id', 'status', 'images', 'primary_image']);
    }

    public function test_cannot_access_products_without_permission()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/products');

        $response->assertStatus(403);
    }
}

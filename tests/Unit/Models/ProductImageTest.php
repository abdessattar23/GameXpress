<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Product_image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    private $product;
    private $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category'
        ]);

        $this->product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 99.99,
            'stock' => 10,
            'status' => 'active',
            'category_id' => $this->category->id
        ]);
    }

    public function test_can_create_product_image()
    {
        $image = Product_image::create([
            'product_id' => $this->product->id,
            'image_url' => 'products/test-image.jpg',
            'is_primary' => true
        ]);

        $this->assertInstanceOf(Product_image::class, $image);
        $this->assertEquals('products/test-image.jpg', $image->image_url);
        $this->assertTrue($image->is_primary);
    }

    public function test_product_can_have_multiple_images()
    {
        Product_image::create([
            'product_id' => $this->product->id,
            'image_url' => 'products/primary.jpg',
            'is_primary' => true
        ]);

        Product_image::create([
            'product_id' => $this->product->id,
            'image_url' => 'products/secondary1.jpg',
            'is_primary' => false
        ]);

        Product_image::create([
            'product_id' => $this->product->id,
            'image_url' => 'products/secondary2.jpg',
            'is_primary' => false
        ]);

        $this->assertEquals(3, $this->product->images()->count());
        $this->assertEquals(1, $this->product->images()->where('is_primary', true)->count());
    }

    public function test_can_update_product_image()
    {
        $image = Product_image::create([
            'product_id' => $this->product->id,
            'image_url' => 'products/old-image.jpg',
            'is_primary' => false
        ]);

        $image->update([
            'image_url' => 'products/new-image.jpg',
            'is_primary' => true
        ]);

        $this->assertEquals('products/new-image.jpg', $image->fresh()->image_url);
        $this->assertTrue($image->fresh()->is_primary);
    }

    public function test_can_delete_product_image()
    {
        $image = Product_image::create([
            'product_id' => $this->product->id,
            'image_url' => 'products/test-image.jpg',
            'is_primary' => true
        ]);

        $imageId = $image->id;
        $image->delete();

        $this->assertDatabaseMissing('product_images', ['id' => $imageId]);
    }

    public function test_deleting_product_deletes_associated_images()
    {
        Product_image::create([
            'product_id' => $this->product->id,
            'image_url' => 'products/image1.jpg',
            'is_primary' => true
        ]);

        Product_image::create([
            'product_id' => $this->product->id,
            'image_url' => 'products/image2.jpg',
            'is_primary' => false
        ]);

        $productId = $this->product->id;
        $this->product->delete();

        $this->assertDatabaseMissing('product_images', ['product_id' => $productId]);
    }

    public function test_can_handle_image_upload()
    {
        $file = UploadedFile::fake()->image('product.jpg');

        $path = $file->store('products', 'public');

        $image = Product_image::create([
            'product_id' => $this->product->id,
            'image_url' => $path,
            'is_primary' => true
        ]);

        $this->assertTrue(Storage::disk('public')->exists($path));
        $this->assertEquals($path, $image->image_url);
    }
}

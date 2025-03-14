<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Product_image;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created()
    {
        $category = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics'
        ]);

        $productData = [
            'name' => 'Laptop',
            'slug' => 'laptop',
            'price' => 999.99,
            'stock' => 10,
            'status' => 'active',
            'category_id' => $category->id
        ];

        $product = Product::create($productData);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals($productData['name'], $product->name);
        $this->assertEquals($productData['price'], $product->price);
        $this->assertEquals($productData['stock'], $product->stock);
    }

    public function test_product_belongs_to_category()
    {
        $category = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics'
        ]);

        $product = Product::create([
            'name' => 'Laptop',
            'slug' => 'laptop',
            'price' => 999.99,
            'stock' => 10,
            'status' => 'active',
            'category_id' => $category->id
        ]);

        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
    }

    public function test_product_has_many_images()
    {
        $category = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics'
        ]);

        $product = Product::create([
            'name' => 'Laptop',
            'slug' => 'laptop',
            'price' => 999.99,
            'stock' => 10,
            'status' => 'active',
            'category_id' => $category->id
        ]);

        $image = Product_image::create([
            'product_id' => $product->id,
            'image_url' => 'images/laptop.jpg',
            'is_primary' => true
        ]);

        $this->assertTrue($product->images()->exists());
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $product->images);
        $this->assertCount(1, $product->images);
    }

    public function test_product_can_be_soft_deleted()
    {
        $category = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics'
        ]);

        $product = Product::create([
            'name' => 'Laptop',
            'slug' => 'laptop',
            'price' => 999.99,
            'stock' => 10,
            'status' => 'active',
            'category_id' => $category->id
        ]);

        $product->delete();

        $this->assertSoftDeleted($product);
    }

    public function test_product_fillable_attributes()
    {
        $product = new Product;
        $fillable = ['name', 'slug', 'price', 'stock', 'status', 'category_id'];

        $this->assertEquals($fillable, $product->getFillable());
    }
}

<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_be_created()
    {
        $categoryData = [
            'name' => 'Electronics',
            'slug' => 'electronics'
        ];

        $category = Category::create($categoryData);

        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals($categoryData['name'], $category->name);
        $this->assertEquals($categoryData['slug'], $category->slug);
    }

    public function test_category_has_many_products()
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

        $this->assertTrue($category->products()->exists());
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $category->products);
        $this->assertCount(1, $category->products);
    }

    public function test_category_can_be_soft_deleted()
    {
        $category = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics'
        ]);

        $category->delete();

        $this->assertSoftDeleted($category);
    }

    public function test_category_fillable_attributes()
    {
        $category = new Category;
        $fillable = ['name', 'slug'];

        $this->assertEquals($fillable, $category->getFillable());
    }
}

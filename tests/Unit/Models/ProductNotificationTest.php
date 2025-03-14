<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

class ProductNotificationTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category'
        ]);

        Notification::fake();
    }

    public function test_low_stock_notification_is_sent()
    {
        $product = Product::create([
            'name' => 'Low Stock Product',
            'slug' => 'low-stock-product',
            'price' => 99.99,
            'stock' => 5,
            'status' => 'active',
            'category_id' => $this->category->id
        ]);

        $product->stock = 2;
        $product->save();

        Notification::assertSentTo(
            $this->admin,
            LowStockNotification::class,
            function ($notification) use ($product) {
                return $notification->product->id === $product->id;
            }
        );
    }

    public function test_normal_stock_does_not_trigger_notification()
    {
        $product = Product::create([
            'name' => 'Normal Stock Product',
            'slug' => 'normal-stock-product',
            'price' => 99.99,
            'stock' => 20,
            'status' => 'active',
            'category_id' => $this->category->id
        ]);

        $product->price = 89.99;
        $product->save();

        Notification::assertNothingSent();
    }
}

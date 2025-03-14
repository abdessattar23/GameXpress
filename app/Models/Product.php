<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\Log;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'stock',
        'status',
        'category_id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function($product) {
            $product->images()->delete();
        });

        static::updating(function($product) {
            if ($product->isDirty('stock') && $product->stock <= 5) {
                Log::info('Low stock for product: ' . $product->name);
                $admin = User::first();
                if ($admin) {
                    $admin->notify(new LowStockNotification($product));
                }
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(Product_image::class);
    }
}

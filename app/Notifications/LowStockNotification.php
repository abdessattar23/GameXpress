<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class LowStockNotification extends Notification
{
    use Queueable;

    public $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Low Stock Alert')
            ->line('The product ' . $this->product->name . ' is running low on stock.')
            ->line('Current stock: ' . $this->product->stock)
            ->action('View Product', url('/products/' . $this->product->id))
            ->line('Please update the inventory as soon as possible.');

        // Log the email notification
        Log::info('Low stock notification email sent', [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'current_stock' => $this->product->stock,
            'recipient' => $notifiable->email
        ]);

        return $message;
    }
}

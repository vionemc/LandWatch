<?php

namespace App\Notifications;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

use function array_keys;
use function array_map;
use function count;
use function env;
use function ucfirst;

class SubscriptionSatisfied extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private Subscription $subscription, private array $listings)
    {
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail(User $notifiable): MailMessage
    {
        $subscriptionId = $this->subscription->id;
        $subscriptionConditions = implode(', ', array_map(
            static fn(string $value, string $key) => ucfirst($key) . $value,
            $this->subscription->filters,
            array_keys($this->subscription->filters)
        ));

        $title = $this->subscription->name ?? (string) $subscriptionId;

        $message = (new MailMessage())
            ->from(env('MAIL_USERNAME'))
            ->replyTo(env('MAIL_USERNAME'))
            ->subject("Subscription \"$title\" satisfied!")
            ->salutation("Regards,\r\nCheapLandSearch.com")
            ->line('Conditions:')
            ->line("\t$subscriptionConditions")
            ->action('Click to view all listings', route('subscription.listings', ['subscription' => $subscriptionId]))
            ->line('are satisfied by ' . count($this->listings) . ' listings created or updated today:');

        foreach ($this->listings as $listing) {
            $listingId = $listing['id'];
            $url = URL::route('listing', ['listing' => $listingId]);
            $message->line("Listing: $url.");
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
//    public function toArray($notifiable)
//    {
//        return [
//            'subscription_id' => $this->subscription->id,
//        ];
//    }
}

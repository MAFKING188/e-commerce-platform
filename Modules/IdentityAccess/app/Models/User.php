<?php

namespace Modules\IdentityAccess\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Address;
use Modules\MarketplacePipeline\Models\Cart;
use Modules\MarketplacePipeline\Models\Order;
use Database\Factories\UserFactory;
use Modules\CatalogDelivery\Models\Review;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'avatars',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function avatarUrl(): ?string
    {
        return $this->avatars ? asset('storage/avatars/' . $this->avatars) : null;
    }

    public function memberTier(): string
    {
        $spent = (float) $this->orders()
            ->where('status', 'completed')
            ->sum('total_price');

        return match (true) {
            $spent >= 10000 => 'Benefactor',
            $spent >= 2500 => 'Patron',
            $spent >= 500 => 'Collector',
            default => 'Member',
        };
    }

    public function isVerifiedMember(): bool
    {
        if ($this->status !== 'active' || ! $this->avatars) {
            return false;
        }

        $hasAddress = $this->addresses()->where('is_primary', true)->exists();
        $hasCompletedOrder = $this->orders()->where('status', 'completed')->exists();

        return $hasAddress && $hasCompletedOrder;
    }

    public function activityTimeline(int $limit = 10)
    {
        $orders = $this->orders()->get()->map(fn ($o) => [
            'type' => 'order',
            'at' => $o->created_at,
            'title' => 'Order #' . $o->id . ' placed',
            'detail' => '$' . number_format($o->total_price, 2),
        ]);

        $reviews = $this->reviews()->with('product')->get()->map(fn ($r) => [
            'type' => 'review',
            'at' => $r->created_at,
            'title' => 'Reviewed ' . ($r->product->name ?? 'a piece'),
            'detail' => str_repeat('★', $r->rating),
        ]);

        $archives = $this->wishlists()->with('product')->get()->map(fn ($w) => [
            'type' => 'archive',
            'at' => $w->created_at,
            'title' => 'Archived ' . ($w->product->name ?? 'a piece'),
            'detail' => null,
        ]);

        return $orders->concat($reviews)->concat($archives)
            ->sortByDesc('at')
            ->take($limit)
            ->values();
    }
}

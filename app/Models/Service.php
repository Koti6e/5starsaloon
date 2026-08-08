<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'service_code',
        'short_description',
        'detailed_description',
        'price_type',
        'price',
        'minimum_price',
        'maximum_price',
        'price_on_request',
        'currency_code',
        'is_package',
        'is_salon_service_available',
        'discounted_price',
        'duration_minutes',
        'image',
        'gallery_images',
        'is_featured',
        'is_home_service_available',
        'home_service_price',
        'home_service_visit_charge',
        'pricing_note',
        'included_services',
        'regular_total',
        'savings_amount',
        'gender_applicability',
        'status',
        'display_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'minimum_price' => 'decimal:2',
            'maximum_price' => 'decimal:2',
            'discounted_price' => 'decimal:2',
            'home_service_price' => 'decimal:2',
            'home_service_visit_charge' => 'decimal:2',
            'regular_total' => 'decimal:2',
            'savings_amount' => 'decimal:2',
            'gallery_images' => 'array',
            'included_services' => 'array',
            'is_featured' => 'boolean',
            'price_on_request' => 'boolean',
            'is_package' => 'boolean',
            'is_salon_service_available' => 'boolean',
            'is_home_service_available' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function packageItems(): HasMany
    {
        return $this->hasMany(ServicePackageItem::class, 'package_id')->orderBy('display_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ServiceImage::class)->orderBy('sort_order');
    }

    public function coverImage(): ?ServiceImage
    {
        return $this->images->firstWhere('is_cover', true) ?: $this->images->first();
    }

    public function coverImageUrl(): string
    {
        $cover = $this->relationLoaded('images')
            ? $this->coverImage()
            : $this->images()->where('is_cover', true)->first();

        return $cover?->image_path ?: ($this->image ?: $this->placeholderImagePath());
    }

    public function placeholderImagePath(): string
    {
        $name = Str::lower($this->name.' '.$this->category?->name);

        return match (true) {
            $this->is_package => 'images/services/svg/package.svg',
            str_contains($name, 'home') => 'images/services/svg/elite-home-service.svg',
            str_contains($name, 'beard'), str_contains($name, 'shave') => 'images/services/svg/beard-trim.svg',
            str_contains($name, 'wash') => 'images/services/svg/hair-wash.svg',
            str_contains($name, 'colour'), str_contains($name, 'color'), str_contains($name, 'henna'), str_contains($name, 'garnier'), str_contains($name, 'loreal'), str_contains($name, 'l’oréal') => 'images/services/svg/hair-colour.svg',
            str_contains($name, 'spa'), str_contains($name, 'dandruff'), str_contains($name, 'growth') => 'images/services/svg/hair-spa.svg',
            str_contains($name, 'gold') => 'images/services/svg/gold-facial.svg',
            str_contains($name, 'diamond') => 'images/services/svg/diamond-facial.svg',
            str_contains($name, 'facial'), str_contains($name, 'cleanup'), str_contains($name, 'skin') => 'images/services/svg/facial.svg',
            str_contains($name, 'massage'), str_contains($name, 'oil') => 'images/services/svg/oil-massage.svg',
            str_contains($name, 'tan'), str_contains($name, 'bleach') => 'images/services/svg/de-tan.svg',
            str_contains($name, 'piercing') => 'images/services/svg/ear-piercing.svg',
            str_contains($name, 'interior') => 'images/services/svg/salon-interior.svg',
            default => 'images/services/svg/haircut.svg',
        };
    }

    public function publicCategoryName(): string
    {
        return $this->is_package || $this->category?->slug === 'combo-packages'
            ? 'SMART SAVER PACKAGES'
            : (string) $this->category?->name;
    }

    public function publicBookingLabel(): string
    {
        return $this->is_package ? 'Book This Package' : 'Book Appointment';
    }

    public function packageBadge(): string
    {
        $badges = ['Best Value', 'Popular', 'Customer Favourite', 'Save More', 'Recommended'];

        return $badges[((int) $this->id) % count($badges)];
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereHas('category', fn (Builder $category) => $category->where('is_active', true));
    }

    public function currentPrice(): ?string
    {
        return $this->discounted_price ?: $this->price;
    }

    public function displayPrice(): string
    {
        return match ($this->price_type) {
            'starting_from' => 'Starting from '.Money::inr($this->minimum_price ?? $this->price),
            'range' => Money::inr($this->minimum_price).' – '.Money::inr($this->maximum_price),
            'contact' => 'Contact for Price',
            default => Money::inr($this->discounted_price ?: $this->price),
        };
    }

    public function priceBadge(): string
    {
        if ($this->is_package) {
            return 'Package';
        }

        return match ($this->price_type) {
            'starting_from' => 'Starting From',
            'range' => 'Price Range',
            'contact' => 'Contact for Price',
            default => 'Fixed Price',
        };
    }

    public function hasEstimatedPrice(): bool
    {
        return in_array($this->price_type, ['starting_from', 'range', 'contact'], true);
    }
}

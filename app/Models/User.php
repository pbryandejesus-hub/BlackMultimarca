<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'cedula', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Ids de los productos favoritos, cargados una sola vez por pedido para no consultar en cada tarjeta.
     *
     * @var list<int>|null
     */
    private ?array $favoriteProductIdsCache = null;

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function favoriteProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    public function hasFavorited(Product $product): bool
    {
        $this->favoriteProductIdsCache ??= $this->favoriteProducts()->pluck('products.id')->all();

        return in_array($product->id, $this->favoriteProductIdsCache, true);
    }

    /**
     * Agrega o quita el producto de favoritos. Devuelve si quedó como favorito.
     */
    public function toggleFavorite(Product $product): bool
    {
        $changes = $this->favoriteProducts()->toggle($product);
        $this->favoriteProductIdsCache = null;

        return $changes['attached'] !== [];
    }
}

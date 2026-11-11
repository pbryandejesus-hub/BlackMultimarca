<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'slug', 'subtitle', 'size_guide', 'image_path', 'position', 'is_active'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('position')->orderBy('name');
    }

    /**
     * Tabla de la guía de talles: una fila por línea y columnas separadas por "|". La primera fila son los títulos.
     *
     * @return list<list<string>>
     */
    public function sizeGuideRows(): array
    {
        $rows = collect(preg_split('/\R/', (string) $this->size_guide))
            ->map(fn (string $line) => array_map('trim', explode('|', trim($line, " \t|"))))
            ->filter(fn (array $cells) => implode('', $cells) !== '')
            ->values()
            ->all();

        return count($rows) > 1 ? $rows : [];
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}

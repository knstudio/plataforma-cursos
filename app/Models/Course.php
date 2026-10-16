<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

#[Fillable(['title', 'slug', 'short_description', 'description', 'cover_image', 'sales_url', 'is_published', 'sort_order'])]
#[RouteKey('slug')]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Module, $this>
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasManyThrough<Lesson, Module, $this>
     */
    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Module::class);
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * @param  Builder<Course>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Published lessons in watch order (module order, then lesson order).
     *
     * @return Collection<int, Lesson>
     */
    public function orderedLessons(): Collection
    {
        return $this->modules()
            ->with(['lessons' => fn ($lessons) => $lessons->published()])
            ->get()
            ->flatMap(fn (Module $module) => $module->lessons);
    }

    /**
     * Where a student without access goes to buy: the sales page when there is one,
     * otherwise the checkout of the cheapest active product that sells only this course
     * (falling back to any active product containing it).
     */
    public function purchaseUrl(): ?string
    {
        if (filled($this->sales_url)) {
            return $this->sales_url;
        }

        $product = $this->products
            ->where('is_active', true)
            ->sortBy([
                fn (Product $a, Product $b) => $a->courses_count <=> $b->courses_count,
                fn (Product $a, Product $b) => (float) $a->price <=> (float) $b->price,
            ])
            ->first();

        return $product ? route('checkout.show', $product) : null;
    }

    public function coverUrl(): string
    {
        return $this->cover_image
            ? Storage::disk('public')->url($this->cover_image)
            : asset('assets/images/logo/balloon.png');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faq extends Model
{
    protected $fillable = [
        'store_id',
        'title',
        'active',
        'show_on_homepage',
    ];

    protected $casts = [
        'active' => 'boolean',
        'show_on_homepage' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(FaqQuestion::class);
    }

    public function productBindings(): HasMany
    {
        return $this->hasMany(FaqProductBinding::class);
    }

    public function categoryBindings(): HasMany
    {
        return $this->hasMany(FaqCategoryBinding::class);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeHomepage($query)
    {
        return $query->where('show_on_homepage', true);
    }
}

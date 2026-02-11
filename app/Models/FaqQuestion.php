<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaqQuestion extends Model
{
    protected $fillable = [
        'faq_id',
        'question',
        'answer',
        'order',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obter o FAQ pai desta pergunta
     */
    public function faq(): BelongsTo
    {
        return $this->belongsTo(Faq::class);
    }
}

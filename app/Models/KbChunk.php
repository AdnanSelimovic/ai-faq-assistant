<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KbChunk extends Model
{
    use HasFactory;

    /**
     * Get the document that owns the chunk.
     */
    public function document()
    {
        return $this->belongsTo(KbDocument::class, 'document_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }
}

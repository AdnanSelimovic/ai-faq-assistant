<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KbDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'source_type',
        'source_ref',
        'status',
        'meta',
    ];


    /**
     * Get the chunks for the document.
     */
    public function chunks()
    {
        return $this->hasMany(KbChunk::class, 'document_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }
}

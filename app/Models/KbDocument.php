<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KbDocument extends Model
{
    use HasFactory;

    /**
     * Get the chunks for the document.
     */
    public function chunks()
    {
        return $this->hasMany(KbChunk::class);
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentView extends Model
{
    use HasFactory;

    protected $table = 'document_views';

    protected $fillable = [
        'username',
        'document_id',
        'first_viewed_at',
        'last_viewed_at',
        'view_count',
    ];

    protected $casts = [
        'first_viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
    ];

    /**
     * Relationship: A document view belongs to a document (Announcement).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document()
    {
        return $this->belongsTo(Announcement::class, 'document_id');
    }
}

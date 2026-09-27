<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;
    
    protected $table = 'course_details';
    
    protected $fillable = [
      'course_id', 'title', 'lampiran', 'type', 'content', 'order', 'created_by', 'updated_by', 'quiz_id'
    ];
    
    protected $casts = [
        'details' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'content' => 'string',
    ];
    
      public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at->format('Y-m-d H:i:s');
    }
    
    public static function uploadContent($file)
    {
        return $file->store('uploads/content', 'public');
    }

    public static function uploadLampiran($file, $filePath = '')
    {
        return $file->store("uploads/lampiran$filePath", 'public');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

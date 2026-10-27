<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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

    protected static function booted()
    {
        static::saving(function ($model) {
            if (array_key_exists('quiz_id', $model->attributes)) {
                if (empty($model->attributes['quiz_id']) || !Schema::hasColumn('course_details', 'quiz_id')) {
                    unset($model->attributes['quiz_id']);
                }
            }
        });
    }
    
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

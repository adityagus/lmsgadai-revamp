<?php

namespace App\Models;

use App\Models\AksesCabang;
use App\Models\DocumentPosition;
use App\Models\DocumentRegion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;
    public $table = 'documents';
    
    protected $fillable = [
      'title',
      'submenu_id',
      'url', 
      'type',
      'content',
      'no_surat',
      'file',
      'tgl_berlaku',
      'regional_id',
    ];
    
    protected static function booted()
  {
    static::creating(function ($model) {
      $model->created_by = session('auth.user');
    });

    static::updating(function ($model) {
      $model->updated_by = session('auth.user');
    });

    static::deleting(function ($model) {
      $model->deleted_by = session('auth.user');
      $model->save();
    });
  }
    
    public $casts = [
      'created_at' => 'datetime',
      'updated_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];
    // Relasi: Announcement punya satu Menu
    
    public static function count_pengumuman() {
        return self::count();
    }
    
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'submenu_id', 'id');
    }
    
    public function document_position()
    {
        return $this->hasMany(DocumentPosition::class, 'document_id', 'id');
    }
    
    public function akses_cabang(){
        return $this->hasMany(AksesCabang::class, 'document_id', 'id');
    }
    
    public function document_regional()
    {
        return $this->hasMany(DocumentRegion::class, 'document_id', 'id');
    }

    /**
     * Scope a query to only include announcements accessible by the user's role and region.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $jbt
     * @param string|null $cabang
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, $jbt, $cabang)
    {
        if ($cabang !== null && $cabang !== '') {
            return $query->whereHas('document_position', function ($q) use ($jbt) {
                $q->where('kd_jbt', $jbt);
            })
            ->whereHas('document_regional', function ($q) use ($cabang) {
                $q->where('regional_id', $cabang);
            });
        }

        return $query;
    }
}
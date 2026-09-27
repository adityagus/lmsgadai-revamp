<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;
    
    public $table = 'submenu';
    
    public function announcements()
    {
        return $this->hasMany(Announcement::class, 'submenu_id');
    }

    public function getNameById($id){
      // get name menu by id
      return self::find($id)->name;
    }
    
}

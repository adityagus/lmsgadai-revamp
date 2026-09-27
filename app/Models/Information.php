<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session as FacadesSession;

class Information extends Model
{
  use HasFactory;

  protected $table = 'menu';

  public static function getMenuAreaAll()
  {
    return self::where('parent_id', 0)
      ->orderBy('menu_order')
      ->get();
  }
  
  public function viewMenu2()
{
    // Ambil data menu dengan parent_id = 0
    $menu1 = DB::table('menu')
                ->where('parent_id', 0)
                ->get();

    // Variabel untuk menyimpan menu kedua
    $menu2 = [];

    $position = session('set_userdata');
    foreach ($menu1 as $m) {
        // Ambil data menu kedua dengan join yang sesuai
        
        $menu2[$m->id] = DB::table('menu')
            ->join('akses_menus', 'akses_menus.id_menu', '=', 'menu.id')
            ->join('menu_areas', 'akses_menus.id_menu', '=', 'menu_areas.id_menu')
            ->where('akses_menus.akses', 1)
            ->where('akses_menus.id_jbt', $position) // mengambil id_jbt dari session
            ->where('menu.parent_id', $m->id)
            ->orderBy('menu_order', 'ASC')
            ->get();
    }
    return $menu2;
}
  
}

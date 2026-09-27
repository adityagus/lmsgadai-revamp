<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Master extends Model
{
  use HasFactory;

  public static function getJabatan()
  {
    $query = DB::connection('db2')
      ->table('master.position as jb')
      ->select('jb.position_code as kd_jabatan', 'jb.position_name as nm_jabatan', 'jb.employee_status as status_karyawan', 'jb.is_active as jabatan_active')
      ->where('jb.is_active', true)
      ->whereIn('jb.employee_status', ['INTERNAL'])
      ->orderBy('jb.position_name', 'asc')
      ->get();

    return $query;
  }

  public static function getCabang()
  {
    try {
      $query = DB::connection('db2')
        ->table('master.branch as cb')
        ->leftJoin('master.region as area', 'cb.region_id', '=', 'area.region_id')
        ->select('area.region_id as kd_area', 'area.region_name as nm_area', DB::raw('RIGHT(cb.branch_code, 4) as kd_cabang'), 'cb.branch_name as nm_cabang', 'cb.is_active as cabang_active', 'area.is_active as area_active')
        ->orderBy('area.region_id', 'asc')
        ->orderBy('cb.branch_id', 'asc')
        ->where('cb.is_active', 'true')
        ->where('area.is_active', 'true')
        ->get();


      // dd($query);
      // Group berdasarkan area
      $grouped = $query->groupBy('kd_area');

      $result = [];
      foreach ($grouped as $kd_area => $items) {
        $areaName = $items[0]->nm_area; // ambil nama area sekali saja
        $areaKode = $items[0]->kd_area; // ambil kd_area sekali saja

        $children = [];
        foreach ($items as $item) {
          $children[] = [
            "id_area" => $item->kd_cabang,
            "nm_area" => $item->nm_cabang,
            "children" => []
          ];
        }

        $result[] = [
          "id_area" => $item->kd_cabang,
          "nm_area" => $areaName,
          "children" => $children
        ];
      }

      return $result;
    } catch (ConnectionException $error) {
      // throw new Exception('Internal Server Error');
      return json_encode(['error' => 'Internal Server Error']);
    }
  }
}

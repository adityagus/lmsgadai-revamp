<?php

namespace App\Repositories;

use App\Contracts\Repositories\MasterRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\ConnectionException;

class MasterRepository implements MasterRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function getAreas()
    {
        return DB::table('mst_area')->get();
    }

    /**
     * @inheritDoc
     */
    public function getTypesByIdMenu($id_menu)
    {
        return DB::table('submenu')->where('id_menu', $id_menu)->get();
    }

    /**
     * @inheritDoc
     */
    public function getJabatan()
    {
        return DB::connection('db2')
            ->table('master.position as jb')
            ->select('jb.position_code as kd_jabatan', 'jb.position_name as nm_jabatan', 'jb.employee_status as status_karyawan', 'jb.is_active as jabatan_active')
            ->where('jb.is_active', true)
            ->whereIn('jb.employee_status', ['INTERNAL'])
            ->orderBy('jb.position_name', 'asc')
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function getWilayah()
    {
        $companies = DB::connection('db2')
            ->table('master.company as w')
            ->select('w.company_id as kd_wilayah', 'w.company_name as nm_wilayah')
            ->where('w.company_is_active', 'true')
            ->orderBy('w.company_id', 'asc')
            ->get();

        $branches = DB::connection('db2')
            ->table('master.branch as cb')
            ->select('cb.company_id', 'cb.branch_code as kd_cabang', 'cb.branch_name as nm_cabang')
            ->where('cb.is_active', 'true')
            ->orderBy('cb.company_id', 'asc')
            ->orderBy('cb.branch_code', 'asc')
            ->get()
            ->groupBy('company_id');

        return $companies->map(function ($comp) use ($branches) {
            $compBranches = $branches->get($comp->kd_wilayah, collect());
            return [
                'kd_wilayah' => $comp->kd_wilayah,
                'nm_wilayah' => $comp->nm_wilayah,
                'branches' => $compBranches->pluck('kd_cabang')->toArray()
            ];
        });
    }

    /**
     * @inheritDoc
     */
    public function getCabang()
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

            $grouped = $query->groupBy('kd_area');

            $result = [];
            foreach ($grouped as $kd_area => $items) {
                $areaName = $items[0]->nm_area;

                $children = [];
                foreach ($items as $item) {
                    $children[] = [
                        "id_area" => $item->kd_cabang,
                        "nm_area" => $item->nm_cabang,
                        "children" => []
                    ];
                }

                $result[] = [
                    "id_area" => $kd_area,
                    "nm_area" => $areaName,
                    "children" => $children
                ];
            }

            return $result;
        } catch (ConnectionException $error) {
            return ['error' => 'Internal Server Error'];
        }
    }
}

<?php

namespace App\Contracts\Repositories;

interface MasterRepositoryInterface
{
    /**
     * Get all areas.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAreas();

    /**
     * Get submenus by menu ID.
     *
     * @param int|string $id_menu
     * @return \Illuminate\Support\Collection
     */
    public function getTypesByIdMenu($id_menu);

    /**
     * Get active jabatan/positions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getJabatan();

    /**
     * Get active wilayah/regions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getWilayah();

    /**
     * Get active cabang/branches grouped by area.
     *
     * @return array
     */
    public function getCabang();
}

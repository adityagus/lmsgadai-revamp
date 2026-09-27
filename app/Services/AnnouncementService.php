<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Menu;
use App\Contracts\FileUploadServiceInterface;
use App\Models\DocumentRegion;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnnouncementService
{
    /**
     * @var FileUploadServiceInterface
     */
    protected $fileUploadService;
    /**
     * @var Menu
     */
    protected $menu;

    /**
     * @var DocumentRegion
     */
    protected $documentRegional;

    /**
     * AnnouncementService Constructor.
     *
     * @param FileUploadServiceInterface $fileUploadService
     */
    public function __construct(FileUploadServiceInterface $fileUploadService, Menu $menu, DocumentRegion $documentRegional)
    {
        $this->fileUploadService = $fileUploadService;
        $this->menu = $menu;
        $this->documentRegional = $documentRegional;
    }

    /**
     * Get announcements list by category and permissions.
     *
     * @param int|string $category
     * @param string|null $jbt
     * @param string|null $cabang
     * @return \Illuminate\Support\Collection
     */
    public function getAnnouncementsByCategory($category, $jbt, $cabang)
    {
        if ($cabang === null || $cabang === "") {
            return $this->menu->where('id_menu', $category)
                ->withCount(['announcements as count_tipe_announcement'])
                ->get();
        }

        $menus = $this->menu->where('id_menu', $category)
            ->with(['announcements' => function ($q) use ($jbt, $cabang) {
                if ($cabang !== null) {
                    $q
                        ->whereHas('document_position', function ($qp) use ($jbt) {
                            $qp->where('kd_jbt', $jbt);
                        })
                        ->whereHas('document_regional', function ($qr) use ($cabang) {
                            $qr->where('regional_id', $cabang);
                        });
                }
            }])
            ->get();

        return $menus->map(function ($menu) {
            $menu->count_tipe_announcement = $menu->announcements->count();
            unset($menu->announcements);
            return $menu;
        });
    }

    /**
     * Get details for an announcement submenu.
     *
     * @param int|string $menu_id
     * @param string|null $jbt
     * @param string|null $cabang
     * @return array
     */
    public function getAnnouncementDetails($menu_id, $jbt, $cabang)
    {
        $announcementTitle = Menu::select('id', 'name', 'icon')->findOrFail($menu_id);
        // dd($announcementTitle);
        $query = Announcement::with('menu:id', 'document_regional')
            ->where('submenu_id', $menu_id)
            ->orderBy('tgl_berlaku', 'desc')
            ->distinct();

        if ($cabang == null) {
            $announcements = $query->select('id', 'submenu_id', 'title', 'no_surat', 'url', 'tgl_berlaku', 'created_at', 'created_by', 'updated_at', 'updated_by', 'content', 'type')
                ->get();
        } else {
            if ($cabang != null) {
                $query
                    ->whereHas('document_position', function ($qp) use ($jbt) {
                        $qp->where('kd_jbt', $jbt);
                    })
                    ->whereHas('document_regional', function ($qr) use ($cabang) {
                        $qr->where('regional_id', $cabang);
                    });
            }
            $announcements = $query->select('id', 'submenu_id', 'title', 'no_surat', 'url', 'tgl_berlaku', 'created_at', 'content', 'type')
                ->get();
        }

        return [
            'detail' => [
                'title' => $announcementTitle->name ?? 'Pengumuman',
                'icon' => $announcementTitle->icon ?? 'https://unpkg.com/heroicons@2.0.13/24/solid/document.svg'
            ],
            'items' => $announcements
        ];
    }

    /**
     * Store new announcement.
     *
     * @param array $data
     * @param \Illuminate\Http\UploadedFile $file
     * @return Announcement
     */
    public function createAnnouncement(array $data, $file)
    {
        $mainPath = $this->menu->getNameById($data['submenu_id']) ?: '';
        $fileName = time() . '_' . $file->getClientOriginalName();

        $publicPath = $this->fileUploadService->upload($file, $mainPath, $fileName);

        $insertData = $data;
        $insertData['created_at'] = Carbon::now('Asia/Jakarta');
        $insertData['url'] = $publicPath;

        unset($insertData['dokumen']);
        unset($insertData['regionals_id']);
        unset($insertData['kd_jabatan']);

        $announcement = Announcement::create($insertData);

        $regions = array_unique($data['regionals_id'] ?? []);
        $regionRows = [];
        foreach ($regions as $regionId) {
            $regionRows[] = [
                'document_id' => $announcement->id,
                'regional_id' => $regionId,
            ];
        }

        $jabatan = array_unique($data['kd_jabatan'] ?? []);
        $jabatanRows = [];
        foreach ($jabatan as $jbt) {
            $jabatanRows[] = [
                'document_id' => $announcement->id,
                'kd_jbt' => $jbt,
                'created_by' => 'Created by ' . session('auth.user')
            ];
        }

        if (!empty($jabatanRows)) {
            DB::table('document_position')->insert($jabatanRows);
        }

        if (!empty($regionRows)) {
            DB::table('document_region')->insert($regionRows);
        }

        $announcement->user = 'Created by ' . session('auth.user');

        return $announcement;
    }

    /**
     * Update an announcement.
     *
     * @param int $id
     * @param array $data
     * @param \Illuminate\Http\UploadedFile|null $file
     * @return Announcement
     */
    public function updateAnnouncement($id, array $data, $file = null)
    {
        $announcement = Announcement::findOrFail($id);
        // dd($announcement);

        if ($file) {
            if ($announcement->url) {
                $this->fileUploadService->delete('public/aktif/' . $announcement->url);
            }

            $mainPath = $this->menu->getNameById($data['submenu_id']) ?: '';
            $fileName = time() . '_' . $file->getClientOriginalName();
            $publicPath = $this->fileUploadService->upload($file, $mainPath, $fileName);
            $data['url'] = $publicPath;
        }

        $updateData = $data;
        unset($updateData['document_id']);
        unset($updateData['regionals_id']);
        unset($updateData['kd_jabatan']);
        unset($updateData['dokumen']);
        $updateData['updated_at'] = Carbon::now('Asia/Jakarta');

        $announcement->update($updateData);

        $this->documentRegional->where('document_id', $id)->delete();

        $regions = array_unique($data['regionals_id'] ?? []);
        $regionRows = [];
        foreach ($regions as $regionId) {
            $regionRows[] = [
                'document_id' => $id,
                'regional_id' => $regionId,
            ];
        }
        if (!empty($regionRows)) {
            $this->documentRegional->insert($regionRows);
        }

        DB::table('document_position')->where('document_id', $announcement->id)->delete();
        $jabatan = array_unique($data['kd_jabatan'] ?? []);
        $jabatanRows = [];
        foreach ($jabatan as $jbt) {
            $jabatanRows[] = [
                'document_id' => $announcement->id,
                'kd_jbt' => $jbt,
            ];
        }
        if (!empty($jabatanRows)) {
            DB::table('document_position')->insert($jabatanRows);
        }

        return $announcement;
    }

    /**
     * Soft delete and move file to tidakaktif folder.
     *
     * @param int $id
     * @return void
     */
    public function deleteAnnouncement($id)
    {
        $announcement = Announcement::findOrFail($id);

        if ($announcement->url) {
            $currentPath = 'public/aktif/' . $announcement->url;
            $newPath = 'public/tidakaktif/' . $announcement->url;
            $this->fileUploadService->move($currentPath, $newPath);
        }

        $announcement->delete();
    }

    /**
     * Restore announcement and move file back to aktif folder.
     *
     * @param int $id
     * @return Announcement
     */
    public function restoreAnnouncement($id)
    {
        $announcement = Announcement::onlyTrashed()->findOrFail($id);

        if ($announcement->url) {
            $currentPath = 'public/tidakaktif/' . $announcement->url;
            $newPath = 'public/aktif/' . $announcement->url;
            $this->fileUploadService->move($currentPath, $newPath);
        }

        $announcement->restore();
        return $announcement;
    }

    /**
     * Permanently delete announcement and its file.
     *
     * @param int $id
     * @return void
     */
    public function hardDeleteAnnouncement($id)
    {
        $announcement = Announcement::onlyTrashed()->findOrFail($id);

        if ($announcement->url) {
            $this->fileUploadService->delete('public/tidakaktif/' . $announcement->url);
        }

        $announcement->forceDelete();
    }
}

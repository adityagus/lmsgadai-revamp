<?php

namespace App\Http\Controllers\api;

use App\Contracts\Repositories\MasterRepositoryInterface;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MasterController extends Controller
{
    /**
     * @var MasterRepositoryInterface
     */
    protected $masterRepository;

    /**
     * MasterController Constructor.
     *
     * @param MasterRepositoryInterface $masterRepository
     */
    public function __construct(MasterRepositoryInterface $masterRepository)
    {
        $this->masterRepository = $masterRepository;
    }

    /**
     * Get areas.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAreas()
    {
        $areas = $this->masterRepository->getAreas();
        return response()->json($areas);
    }

    /**
     * Get submenus (types) by menu ID.
     *
     * @param int|string $id_menu
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTypesByIdMenu($id_menu)
    {
        $types = $this->masterRepository->getTypesByIdMenu($id_menu);
        return response()->json($types);
    }

    /**
     * Get active jabatan.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJabatan()
    {
        $result = $this->masterRepository->getJabatan();
        return response()->json($result);
    }

    /**
     * Get active wilayah.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWilayah()
    {
        $result = $this->masterRepository->getWilayah();
        return response()->json($result);
    }

    /**
     * Get active cabang grouped by area.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCabang()
    {
        $result = $this->masterRepository->getCabang();
        return response()->json($result);
    }

    /**
     * Get all categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategories()
    {
        $categories = Category::select('id', 'name', 'description')->get();

        if ($categories->isEmpty()) {
            return response()->json(['message' => 'No categories found'], 404);
        }

        return response()->json($categories, 200);
    }

    /**
     * Add new category.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $payload = [
            'name' => trim($validated['name']),
            'description' => trim((string) ($validated['description'] ?? '')),
        ];

        $nextId = (int) (\Illuminate\Support\Facades\DB::table('categories')->max('id') ?? 0) + 1;
        $payload['id'] = $nextId;

        if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'courses')) {
            $payload['courses'] = '';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'update_at')) {
            $payload['update_at'] = now();
        }

        \Illuminate\Support\Facades\DB::table('categories')->insert($payload);
        $category = \Illuminate\Support\Facades\DB::table('categories')->where('id', $nextId)->first();

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category
        ], 201);
    }

    /**
     * Update an existing category.
     *
     * @param Request $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCategory(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $category = \Illuminate\Support\Facades\DB::table('categories')->where('id', $id)->first();
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $payload = [
            'name' => trim($validated['name']),
            'description' => trim((string) ($validated['description'] ?? '')),
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'update_at')) {
            $payload['update_at'] = now();
        }

        \Illuminate\Support\Facades\DB::table('categories')->where('id', $id)->update($payload);
        $updated = \Illuminate\Support\Facades\DB::table('categories')->where('id', $id)->first();

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $updated
        ], 200);
    }

    public function deleteCategory($id)
    {
        $deleted = \Illuminate\Support\Facades\DB::table('categories')->where('id', $id)->delete();
        if (!$deleted) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        return response()->json(['message' => 'Category deleted successfully']);
    }
}

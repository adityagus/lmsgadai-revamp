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
            'description' => 'required|string|max:500',
        ]);

        $category = Category::create($validated);

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
            'description' => 'required|string|max:500',
        ]);

        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ], 200);
    }
}

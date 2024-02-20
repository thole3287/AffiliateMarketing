<?php

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Nested Categories API",
 *      description="API for managing nested categories",
 *      @OA\Contact(
 *          email="your-email@example.com",
 *          name="Your Name"
 *      ),
 * )
 */

/**
 * @OA\Tag(
 *     name="categories",
 *     description="Operations related to categories"
 * )
 */

/**
 * @OA\Schema(
 *     schema="Category",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="parent_id",
 *         type="integer",
 *         format="int64",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         readOnly=true
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         readOnly=true
 *     ),
 * )
 */

/**
 * @OA\Get(
 *      path="/api/categories",
 *      operationId="getCategoriesList",
 *      tags={"categories"},
 *      summary="Get list of categories with nested structure",
 *      description="Returns a list of categories with their children.",
 *      @OA\Response(
 *          response=200,
 *          description="Successful operation",
 *          @OA\JsonContent(
 *              type="object",
 *              @OA\Property(
 *                  property="categories",
 *                  type="array",
 *                  @OA\Items(ref="#/components/schemas/Category")
 *              )
 *          )
 *      )
 * )
 */

/**
 * @OA\Get(
 *      path="/api/categories/{id}",
 *      operationId="getCategoryById",
 *      tags={"categories"},
 *      summary="Get a specific category by ID",
 *      description="Returns a specific category with its children.",
 *      @OA\Parameter(
 *          name="id",
 *          in="path",
 *          description="ID of the category",
 *          required=true,
 *          @OA\Schema(
 *              type="integer",
 *              format="int64"
 *          )
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Successful operation",
 *          @OA\JsonContent(ref="#/components/schemas/Category")
 *      ),
 *      @OA\Response(
 *          response=404,
 *          description="Category not found",
 *      )
 * )
 */

/**
 * @OA\Post(
 *      path="/api/categories",
 *      operationId="createCategory",
 *      tags={"categories"},
 *      summary="Create a new category",
 *      description="Creates a new category and returns the created category.",
 *      @OA\RequestBody(
 *          required=true,
 *          @OA\JsonContent(
 *              required={"name"},
 *              @OA\Property(property="name", type="string"),
 *              @OA\Property(property="parent_id", type="integer", format="int64", nullable=true),
 *          )
 *      ),
 *      @OA\Response(
 *          response=201,
 *          description="Category created successfully",
 *          @OA\JsonContent(ref="#/components/schemas/Category")
 *      ),
 *      @OA\Response(
 *          response=422,
 *          description="Validation error",
 *      )
 * )
 */

/**
 * @OA\Put(
 *      path="/api/categories/{id}",
 *      operationId="updateCategory",
 *      tags={"categories"},
 *      summary="Update a category by ID",
 *      description="Updates an existing category and returns the updated category.",
 *      @OA\Parameter(
 *          name="id",
 *          in="path",
 *          description="ID of the category",
 *          required=true,
 *          @OA\Schema(
 *              type="integer",
 *              format="int64"
 *          )
 *      ),
 *      @OA\RequestBody(
 *          required=true,
 *          @OA\JsonContent(
 *              required={"name"},
 *              @OA\Property(property="name", type="string"),
 *              @OA\Property(property="parent_id", type="integer", format="int64", nullable=true),
 *          )
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Category updated successfully",
 *          @OA\JsonContent(ref="#/components/schemas/Category")
 *      ),
 *      @OA\Response(
 *          response=404,
 *          description="Category not found",
 *      ),
 *      @OA\Response(
 *          response=422,
 *          description="Validation error",
 *      )
 * )
 */

/**
 * @OA\Delete(
 *      path="/api/categories/{id}",
 *      operationId="deleteCategory",
 *      tags={"categories"},
 *      summary="Delete a category by ID",
 *      description="Deletes an existing category.",
 *      @OA\Parameter(
 *          name="id",
 *          in="path",
 *          description="ID of the category",
 *          required=true,
 *          @OA\Schema(
 *              type="integer",
 *              format="int64"
 *          )
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Category deleted successfully",
 *      ),
 *      @OA\Response(
 *          response=404,
 *          description="Category not found",
 *      )
 * )
 */

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;


class CategoryController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public function index()
    {
        $categories = Category::with('children')->whereNull('parent_id')->get();
        return response()->json(['categories' => $categories], 200);
    }

    // Get a specific category by ID
    public function show($id)
    {
        $category = Category::with('children')->find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json(['category' => $category], 200);
    }

    public function store(Request $request)
    {
        // dd($request->input('name'));
        $request->validate([
            'name' => 'required|unique:categories',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // Kiểm tra loại và kích thước file ảnh
            'image_url' => 'nullable|url', // Kiểm tra định dạng URL
        ]);

        // Kiểm tra xem người dùng đã cung cấp file ảnh hay URL
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/images/categories', $imageName);
            $imageUrl = asset('storage/images/categories/' . $imageName);
        } elseif ($request->filled('image_url')) {
            $imageUrl = $request->input('image_url');
        } else {
            $imageUrl = null;
        }

        $category = Category::create([
            'name' => $request->input('name'),
            'parent_id' => $request->input('parent_id'),
            'image' => $imageUrl,
        ]);

        return response()->json(['category' => $category], 201);
    }

    // Update a category by ID
    public function update(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'name' => 'required|unique:categories,name,',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'image_url' => 'nullable|url',
        ]);

        $category = Category::findOrFail($id);

        // Kiểm tra xem người dùng đã cung cấp file ảnh hay URL
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/images/category_image', $imageName);
            $imageUrl = asset('storage/images/category_image/' . $imageName);
        } elseif ($request->filled('image_url')) {
            $imageUrl = $request->input('image_url');
        } else {
            $imageUrl = null;
        }

        // Cập nhật thông tin của danh mục
        $category->update([
            'name' => $request->input('name'),
            'parent_id' => $request->input('parent_id'),
            'image' => $imageUrl,
        ]);

        return response()->json(['category' => $category], 200);
    }

    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $hasChildren = Category::where('parent_id', $id)->exists();
        if ($hasChildren) {
            return response()->json(['message' => 'Cannot delete category with children'], 422);
        }

        if ($category->image) {
            $imagePath = public_path('storage/images/category_image/') . basename($category->image);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully'], 200);
    }
}

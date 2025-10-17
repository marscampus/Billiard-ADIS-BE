<?php

namespace App\Http\Controllers\apimenuresto;

use Illuminate\Http\Request;
use App\Models\menuresto\MenuItem;
use App\Models\menuresto\Categories;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Categories::all();
        return response()->json($categories);
    }

    public function getMenuItemsByCategory($kode)
    {
        // $category = Categories::find($kode);

        // if (!$category) {
        //     return response()->json(['message' => 'Category not found'], 404);
        // }

        $menuItems = MenuItem::where('GOLONGAN', $kode)->get();

        return response()->json(['menuItems' => $menuItems]);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:4096', // ukuran maksimum 4MB dalam KB
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $category = new Categories();
            $category->id = $request->id;
            $category->name = $request->name;
            $category->icon = $request->icon;
            $category->description = $request->description;
            $category->save();

            return response()->json(['message' => 'Category created successfully'], 201);
        } catch (\Throwable $th) {
            return response()->json(["message" => "Bad Request"], 400);
        }
    }

    public function destroy($id)
    {

        try {
            $category = Categories::find($id);

            if (!$category) {
                return response()->json(['message' => 'Category not found'], 404);
            }

            // Check if the category is used in any menu
            $menuCount = MenuItem::where('category_id', $id)->count();

            if ($menuCount > 0) {
                return response()->json([
                    'message' => 'Cannot delete this category',
                    'reason' => "Category ini memiliki $menuCount menu",
                ], 400);
            }

            $category->delete();

            return response()->json(['message' => 'Category deleted successfully'], 200);
        } catch (\Throwable $th) {
            return response()->json(["message" => "Bad Request"], 400);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:4096', // ukuran maksimum 4MB dalam KB
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }
        try {
            $category = Categories::find($request->id);

            if (!$category) {
                return response()->json(['message' => 'Category not found'], 404);
            }

            if ($request->has('name')) {
                $category->name = $request->name;
            }

            if ($request->has('description')) {
                $category->description = $request->description;
            }

            if ($request->has('icon')) {
                $category->icon = $request->icon;
            }

            $category->save();

            return response()->json(['message' => 'Category updated successfully'], 200);
        } catch (\Throwable $th) {
            return response()->json(["message" => "Bad Request"], 400);
        }
    }
}

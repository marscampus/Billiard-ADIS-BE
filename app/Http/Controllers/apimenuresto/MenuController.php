<?php

namespace App\Http\Controllers\apimenuresto;

use App\Models\menuresto\MenuItem;
use App\Models\menuresto\Categories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    function generateKodeBarang()
    {
        $prefix = 'BRG';  // You can change this prefix as needed
        $lastMenuItem = MenuItem::orderBy('kode_menu', 'desc')->first();

        if (!$lastMenuItem) {
            // If no menu items exist, start with MENU0000000001
            $nextNumber = 1;
        } else {
            $lastNumber = substr($lastMenuItem->kode_menu, 3); // Remove the prefix
            $nextNumber = intval($lastNumber) + 1;
        }

        // Keep incrementing until we find an unused kode_menu
        do {
            $kodeMenu = $prefix . str_pad($nextNumber, 12, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (MenuItem::where('kode_menu', $kodeMenu)->exists());

        return $kodeMenu;
    }
    public function store(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string',
            'price' => 'required|numeric',
            'diskon_persen' => 'nullable|numeric',
            'diskon_rupiah' => 'nullable|numeric',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $kodeBarang = $this->generateKodeBarang();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $menuItem = MenuItem::create([
            'id' => 0,
            'kode_menu' => $kodeBarang,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'price' => $request->price,
            'diskon_persen' => $request->diskon_persen,
            'diskon_rupiah' => $request->diskon_rupiah,
            'image' => $request->image,
            'description' => $request->description,
        ]);

        return response()->json(['menuItem' => $menuItem], 201);
    }

    public function index()
    {
        $menuItems = MenuItem::orderBy('ID', 'desc')->all();
        return response()->json($menuItems);
    }

    public function destroy($kode_menu)
    {
        $menuItem = MenuItem::where('kode_menu', $kode_menu)->firstOrFail();
        $menuItem->delete();

        return response()->json(['message' => 'Menu item deleted successfully'], 200);
    }

    public function update(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'kode_menu' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string',
            'price' => 'required|numeric',
            'diskon_persen' => 'nullable|numeric',
            'diskon_rupiah' => 'nullable|numeric',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the menu item
        $menuItem = MenuItem::where('kode_menu', $request->kode_menu)->first();
        if (!$menuItem) {
            return response()->json(['message' => $request->kode_menu], 404);
        }

        // Update the menu item
        if ($request->has('category_id')) {
            $menuItem->category_id = $request->category_id;
        }
        if ($request->has('name')) {
            $menuItem->name = $request->name;
        }
        if ($request->has('price')) {
            $menuItem->price = $request->price;
        }
        if ($request->has('image')) {
            $menuItem->image = $request->image;
        }
        if ($request->has('description')) {
            $menuItem->description = $request->description;
        }
        if ($request->has('diskon_persen')) {
            $menuItem->diskon_persen = $request->diskon_persen;
        }
        if ($request->has('diskon_rupiah')) {
            $menuItem->diskon_rupiah = $request->diskon_rupiah;
        }
        $menuItem->save();
        return response()->json(['message' => 'Menu item updated successfully', 'menuItem' => $menuItem], 200);
    }
}

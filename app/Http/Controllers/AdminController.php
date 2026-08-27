<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Support\Str;
// use Intervention\Image\Laravel\Facades\Image as InterventionImage;
// use Intervention\Image\Laravel\Facades\Image;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.index');
    }

    public function brands()
    {
        $query = Brand::query();

        if ($search = request()->input('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        
        if(request()->filled('status')) {
            $query->where('status', request()->input('status'));
        }

        $brands = $query->orderBy('id', 'DESC')->paginate(10)->withQueryString();
        return view('admin.brands', compact('brands'));
    }

    public function brandAdd()
    {
        return view('admin.brand-add');
    }

    public function brandStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:brands,slug',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'nullable|boolean',
        ]);

        $brand = new Brand();
        $brand->name = $request->name;
        $brand->slug = $request->slug ? Str::slug($request->slug) : Str::slug($request->name);
        $brand->status = $request->has('status') ? 1 : 0;

        if ($request->hasFile('image')) {
            $imageFile = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $imageFile->extension();
            
            // 1. Pindahkan gambar original ke folder upload
            $request->image->move(public_path('uploads/brands'), $imageName);
            $brand->image = $imageName;

            // 2. Ambil path absolut gambar yang baru disimpan
            $imagePath = public_path('uploads/brands/' . $imageName);

            // 3. Buat thumbnail gambar
            $this->generateThumbnailImage($imagePath, $imageName, 'uploads/brands');
            //  $this->generateThumbnailImage(public_path('uploads/brands/' . $imageName), $imageName,'uploads/brands');
        } 

        $brand->save();

        return redirect()->route('admin.brands')->with('success', 'Brand added successfully.');
    }

    public function generateThumbnailImage($imagePath, $imageName, $folder, $width = 124, $height = 124)
    {
        $thumbnailPath = public_path($folder . '/thumbnails');

        if (!file_exists($thumbnailPath)) {
            mkdir($thumbnailPath, 0755, true);
        }

        // Image::decode($image)->resize($width, $height)->save($thumbnailPath . '/' . $imageName);

        // Intervention Image v3: Gunakan Image::read() dengan parameter string path file
        // $img = Image::read($imagePath);
        // $img->cover($width, $height);
        // $img->save($thumbnailPath . '/' . $imageName);

        $manager = new ImageManager(new Driver());

        $imageData = file_get_contents($imagePath);

        $img = $manager->decode($imageData);

        $img->cover($width, $height);

        $img->save($thumbnailPath . '/' . $imageName);
    }

    public function brandEdit($id)
    {
        $brand = Brand::findOrFail($id);
        return view('admin.brand-edit', compact('brand'));
    }

    public function brandUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:brands,slug,' . $id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'nullable|boolean',
        ]);

        $brand = Brand::findOrFail($id);
        $brand->name = $request->name;
        $brand->slug = $request->slug ? Str::slug($request->slug) : Str::slug($request->name);
        $brand->status = $request->has('status') ? 1 : 0;

        if ($request->hasFile('image')) {
            // // Hapus gambar lama jika ada
            // if ($brand->image && file_exists(public_path('uploads/brands/' . $brand->image))) {
            //     unlink(public_path('uploads/brands/' . $brand->image));
            //     // Hapus thumbnail lama jika ada
            //     if (file_exists(public_path('uploads/brands/thumbnails/' . $brand->image))) {
            //         unlink(public_path('uploads/brands/thumbnails/' . $brand->image));
            //     }
            // }

            // // Simpan gambar baru
            // $imageFile = $request->file('image');
            // $imageName = time() . '_' . uniqid() . '.' . $imageFile->extension();
            // $request->image->move(public_path('uploads/brands'), $imageName);
            // $brand->image = $imageName;

            // // Buat thumbnail untuk gambar baru
            // $this->generateThumbnailImage(public_path('uploads/brands/' . $imageName), $imageName, 'uploads/brands');

            // delete old image and thumbnail if exists
            if ($brand->image) {
                @unlink(public_path('uploads/brands/' . $brand->image));
                @unlink(public_path('uploads/brands/thumbnails/' . $brand->image));
            }

            $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
            $this->generateThumbnailImage($request->image, $imageName, 'uploads/brands', 124, 124);
            $request->image->move(public_path('uploads/brands'), $imageName);

            $brand->image = $imageName;
        }

        $brand->save();

        return redirect()->route('admin.brands')->with('success', 'Brand updated successfully.');
    }

    public function brandDelete($id)
    {
        $brand = Brand::findOrFail($id);

        // Hapus gambar dan thumbnail jika ada
        if ($brand->image) {
            @unlink(public_path('uploads/brands/' . $brand->image));
            @unlink(public_path('uploads/brands/thumbnails/' . $brand->image));
        }

        $brand->delete();

        return back()->with('success', 'Brand deleted successfully.');
    }

    public function categories()
    {
        $query = Category::query();

        if ($search = request()->input('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        
        if(request()->filled('status')) {
            $query->where('status', request()->input('status'));
        }

        $categories = $query->orderBy('id', 'DESC')->paginate(10)->withQueryString();
        return view('admin.categories', compact('categories'));
    }

    public function categoryAdd()
    {
        $parentCategories = Category::where('parent_id', null)->orderBy('name', 'asc')->get();
        return view('admin.category-add', compact('parentCategories'));
    }

    public function categoryStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'nullable|boolean',
        ]);

        $category = new Category();
        $category->name = $request->name;
        $category->slug = $request->slug ? Str::slug($request->slug) : Str::slug($request->name);
        $category->parent_id = $request->parent_id;
        $category->status = $request->has('status') ? 1 : 0;

        if ($request->hasFile('image')) {
            $imageFile = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $imageFile->extension();
            
            // Pindahkan gambar original ke folder upload
            $request->image->move(public_path('uploads/categories'), $imageName);
            $category->image = $imageName;

            // Buat thumbnail gambar
            $this->generateThumbnailImage(public_path('uploads/categories/' . $imageName), $imageName, 'uploads/categories');
        } 

        $category->save();

        return redirect()->route('admin.categories')->with('success', 'Category added successfully.');
    }

    public function categoryEdit($id)
    {
        $category = Category::findOrFail($id);
        $parentCategories = Category::where('parent_id', null)->where('id', '!=', $category->id)->orderBy('name', 'asc')->get();
        return view('admin.category-edit', compact('category', 'parentCategories'));
    }

    public function categoryUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $id,
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'nullable|boolean',
        ]);

        $category = Category::findOrFail($id);
        $category->name = $request->name;
        $category->slug = $request->slug ? Str::slug($request->slug) : Str::slug($request->name);
        $category->parent_id = $request->parent_id;
        $category->status = $request->has('status') ? 1 : 0;

        if ($request->hasFile('image')) {
            // delete old image and thumbnail if exists
            if ($category->image) {
                @unlink(public_path('uploads/categories/' . $category->image));
                @unlink(public_path('uploads/categories/thumbnails/' . $category->image));
            }

            $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
            $this->generateThumbnailImage($request->image, $imageName, 'uploads/categories', 124, 124);
            $request->image->move(public_path('uploads/categories'), $imageName);

            $category->image = $imageName;
        }

        $category->save();

        return redirect()->route('admin.categories')->with('success', 'Category updated successfully.');
    }

    public function categoryDelete($id)
    {
        $category = Category::findOrFail($id);

        // Hapus gambar dan thumbnail jika ada
        if ($category->image) {
            @unlink(public_path('uploads/categories/' . $category->image));
            @unlink(public_path('uploads/categories/thumbnails/' . $category->image));
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }



}

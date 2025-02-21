<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Intervention\Image\Laravel\Facades\Image;

class AdminController extends Controller
{
    public function index()
    {
        return view("admin.index");
    }

    public function brands()
{
        $brands = Brand::orderBy('id','DESC')->paginate(10);
        return view("admin.brands",compact('brands'));
}
    public function add_brand()
    {
        return view("admin.brand-add");
    }

    public function brand_store(Request $request){
        $request->validate([
            'name'=>'required',
            'slug'=>'required|unique:brands,slug',
            'image'=>'mimes:jpeg,png,jpg|max:2048'
        ]);

        $brand = new Brand();
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        $image = $request->file('image');
        $file_extention = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp.'.'.$file_extention;
        $this->GenerateBrandThumbailsImage($image,$file_name);
        $brand->image = $file_name;
        $brand->save();
        return redirect()->route('admin.brands')->with('status','Brand added successfully');
    }

    public function brand_edit($id){
        $brand = Brand::find($id);
        return view("admin.brand-edit",compact('brand'));
    }

    public function brand_update(Request $request){
        $request->validate([
            'name'=>'required',
            'slug'=>'required|unique:brands,slug',
            'image'=>'mimes:jpeg,png,jpg|max:2048'
        ]);

        $brand = Brand::find($request->id);
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        if($request->hasFile('image')){
            if(File::exists(public_path('uploads/brands')."/".$brand->image)){
                File::delete(public_path('uploads/brands').'/'.$brand->image);
            }
            $image = $request->file('image');
            $file_extention = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp.'.'.$file_extention;
            $this->GenerateBrandThumbailsImage($image,$file_name);
            $brand->image = $file_name;
        }

        $brand->save();
        return redirect()->route('admin.brands')->with('status','Brand added successfully');
    }

    public function GenerateBrandThumbailsImage($image, $imageName){
        $destinationPath = public_path('uploads/brands');
        $img = Image::read($image->path());
        $img->cover(124, 124, "top");
        $img->resize(124, 124, function ($constrain){
            $constrain->aspectRatio();
        }) -> save($destinationPath.'/'.$imageName);
    }

    public function brand_delete($id){
        $brand = Brand::find($id);
        if(File::exists(public_path('uploads/brands')."/".$brand->image)){
            File::delete(public_path('uploads/brands')."/".$brand->image);
        }
        $brand->delete();
        return redirect()->route('admin.brands')->with('status','Brand deleted successfully');
    }

    public function categories(){
        $categories = Category::orderBy('id','DESC')->paginate(10);
        return view("admin.categories",compact('categories'));
    }
    public function category_add(Request $request){
        return view("admin.category-add");
    }

    public function category_store(Request $request){
        $request->validate([
            'name'=>'required',
            'slug'=>'required|unique:categories,slug',
            'image'=>'mimes:jpeg,png,jpg|max:2048'
        ]);

        $category = new Category();
        $category->name = $request->name;
        $category->slug = Str::slug($request->name);
        $image = $request->file('image');
        $file_extention = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp.'.'.$file_extention;
        $this->GenerateCategoryThumbailsImage($image,$file_name);
        $category->image = $file_name;
        $category->save();
        return redirect()->route('admin.categories')->with('status','Category added successfully');
    }

    public function GenerateCategoryThumbailsImage($image,$imageName){
        $destinationPath = public_path('uploads/categories');
        $img = Image::read($image->path());
        $img->cover(124, 124, "top");
        $img->resize(124, 124, function ($constrain){
            $constrain->aspectRatio();
        }) -> save($destinationPath.'/'.$imageName);
    }

    public function category_edit($id){
        $category = Category::find($id);
        return view("admin.category-edit",compact('category'));
    }

    public function category_update(Request $request){
        $request->validate([
            'name'=>'required',
            'slug'=>'required|unique:categories,slug',
            'image'=>'mimes:jpeg,png,jpg|max:2048'
        ]);

        $category = Category::find($request->id);
        $category->name = $request->name;
        $category->slug = Str::slug($request->name);
        if($request->hasFile('image')){
            if(File::exists(public_path('uploads/categories')."/".$category->image)){
                File::delete(public_path('uploads/categories').'/'.$category->image);
            }
            $image = $request->file('image');
            $file_extention = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp.'.'.$file_extention;
            $this->GenerateCategoryThumbailsImage($image,$file_name);
            $category->image = $file_name;
        }

        $category->save();
        return redirect()->route('admin.categories')->with('status','Category added successfully');
    }

    public function category_delete($id){
        $category = Category::find($id);
        if(File::exists(public_path('uploads/categories')."/".$category->image)){
            File::delete(public_path('uploads/categories')."/".$category->image);
        }
        $category->delete();
        return redirect()->route('admin.categories')->with('status','category deleted successfully');
    }
}

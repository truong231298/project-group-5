<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Slide;
use App\Models\Transaction;
use App\Models\User;
use App\Rules\EnglishWord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Intervention\Image\Laravel\Facades\Image;

class AdminController extends Controller
{
    public function index()
    {
        $orders = Order::orderBy('id', 'desc')->get()->take(10);
        $dashboardDatas = DB::select("SELECT
                                                SUM(total) AS TotalAmount,
                                                SUM(IF(status = 'ordered', total, 0)) AS TotalOrderedAmount,
                                                SUM(IF(status = 'delivered', total, 0)) AS TotalDeliveredAmount,
                                                SUM(IF(status = 'canceled', total, 0)) AS TotalCanceledAmount,
                                                COUNT(*) AS Total,
                                                SUM(IF(status = 'ordered', 1, 0)) AS TotalOrdered,
                                                SUM(IF(status = 'delivered', 1, 0)) AS TotalDelivered,
                                                SUM(IF(status = 'canceled', 1, 0)) AS TotalCanceled
                                            FROM orders
                                            ");

        $monthlyDatas = DB::select("SELECT
                                                M.id AS MonthNo,
                                                M.Name AS MonthName,
                                                IFNULL(D.TotalAmount, 0) AS TotalAmount,
                                                IFNULL(D.TotalOrderedAmount, 0) AS TotalOrderedAmount,
                                                IFNULL(D.TotalDeliveredAmount, 0) AS TotalDeliveredAmount,
                                                IFNULL(D.TotalCanceled, 0) AS TotalCanceledAmount
                                            FROM month_names AS M
                                            LEFT JOIN (
                                                SELECT
                                                    MONTH(created_at) AS MonthNo,
                                                    SUM(total) AS TotalAmount,
                                                    SUM(IF(status = 'ordered', total, 0)) AS TotalOrderedAmount,
                                                    SUM(IF(status = 'delivered', total, 0)) AS TotalDeliveredAmount,
                                                    SUM(IF(status = 'canceled', total, 0)) AS TotalCanceled
                                                FROM Orders
                                                WHERE YEAR(created_at) = YEAR(NOW())
                                                GROUP BY YEAR(created_at), MONTH(created_at), DATE_FORMAT(created_at, '%b')
                                                ORDER BY MONTH(created_at)
                                            ) D ON D.MonthNo = M.id;
                                            ");

        $AmountM = implode(',',collect($monthlyDatas)->pluck('TotalAmount')->toArray());
        $OrderedAmountM = implode(',',collect($monthlyDatas)->pluck('TotalOrderedAmount')->toArray());
        $DeliveredAmountM = implode(',',collect($monthlyDatas)->pluck('TotalDeliveredAmount')->toArray());
        $CanceledAmountM = implode(',',collect($monthlyDatas)->pluck('TotalCanceledAmount')->toArray());

        $TotalAmount = collect($monthlyDatas)->sum('TotalAmount');
        $TotalOrderedAmount = collect($monthlyDatas)->sum('TotalOrderedAmount');
        $TotalDeliveredAmount = collect($monthlyDatas)->sum('TotalDeliveredAmount');
        $TotalCanceledAmount = collect($monthlyDatas)->sum('TotalCanceledAmount');

        return view("admin.index", compact("orders", "dashboardDatas", "AmountM", "OrderedAmountM", "DeliveredAmountM", "CanceledAmountM", "TotalAmount", "TotalOrderedAmount", "TotalDeliveredAmount", "TotalCanceledAmount"));
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
            'name'  => 'required|unique:brands,name,' . $request->id . '|min:2|max:20',
            'slug'  => 'required|unique:brands,slug,' . $request->id . '|regex:/^[a-z0-9-]+$/|min:2|max:20',
            'image' => 'required|mimes:jpeg,png,jpg|max:2048'
        ], [
            'name.unique' => 'The name "' . $request->name . '" is already taken. Please choose another.',
            'slug.unique' => 'The slug "' . $request->slug . '" is already taken. Please choose another.',
            'slug.regex'  => 'The slug may only contain lowercase letters, numbers, and hyphens.',
        ]);

        $brand = new Brand();
        $brand->name = $request->name;

        // Auto-generate a unique slug from the name
        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $count = 1;
        while (Brand::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }
        $brand->slug = $slug;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $file_extention = $file->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extention;
            $this->GenerateBrandThumbailsImage($file, $file_name);
            $brand->image = $file_name;
        }

        $brand->save();
        return redirect()->route('admin.brands')->with('status', 'Brand added successfully');
    }

    public function brand_edit($id){
        $brand = Brand::find($id);
        return view("admin.brand-edit",compact('brand'));
    }

    public function brand_update(Request $request){
        $request->validate([
            'name'  => 'required|unique:brands,name,' . $request->id . '|min:2|max:20',
            'slug'  => 'nullable|unique:brands,slug,' . $request->id . '|regex:/^[a-z0-9-]+$/|min:2|max:20',
            'image' => 'nullable|mimes:jpeg,png,jpg|max:2048'
        ], [
            'name.unique' => 'The brand name "' . $request->name . '" is already taken. Please choose another.',
            'slug.unique' => 'The brand slug "' . ($request->slug ?? Str::slug($request->name)) . '" is already taken. Please choose another.',
            'slug.regex'  => 'The slug may only contain lowercase letters, numbers, and hyphens.',
        ]);


        $brand = Brand::find($request->id);

        // Assign the name directly as validation enforces uniqueness
        $brand->name = $request->name;

        // Generate a slug from the name and adjust it if necessary
        if ($request->filled('slug')) {
            $slug = Str::slug($request->slug);
        } else {
            $slug = Str::slug($request->name);
        }

        $originalSlug = $slug;
        $count = 1;
        while (Brand::where('slug', $slug)->where('id', '!=', $brand->id)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }
        $brand->slug = $slug;

        // Process the image only if a new file is uploaded
        if($request->hasFile('image')){
            // Delete the existing image if it exists
            if(File::exists(public_path('uploads/brands') . "/" . $brand->image)){
                File::delete(public_path('uploads/brands') . '/' . $brand->image);
            }
            $file = $request->file('image');
            $file_extention = $file->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extention;
            $this->GenerateBrandThumbailsImage($file, $file_name);
            $brand->image = $file_name;
        }

        $brand->save();
        return redirect()->route('admin.brands')->with('status', 'Brand updated successfully');
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
            'name'  => 'required|unique:categories,name,' . $request->id . '|min:2|max:20',
            'slug'  => 'required|unique:categories,slug,' . $request->id . '|regex:/^[a-z0-9-]+$/|min:2|max:20',
            'image' => 'required|mimes:jpeg,png,jpg|max:2048'
        ], [
            'name.unique' => 'The name "' . $request->name . '" is already taken. Please choose another.',
            'slug.unique' => 'The slug "' . $request->slug . '" is already taken. Please choose another.',
            'slug.regex'  => 'The slug may only contain lowercase letters, numbers, and hyphens.',
        ]);

        $category = new Category();
        $category->name = $request->name;

        // Auto-generate a unique slug from the name
        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $count = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }
        $category->slug = $slug;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $file_extention = $file->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extention;
            $this->GenerateCategoryThumbailsImage($file, $file_name);
            $category->image = $file_name;
        }

        $category->save();
        return redirect()->route('admin.categories')->with('status', 'Category added successfully');
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
            'name'  => 'required|unique:categories,name,' . $request->id . '|min:2|max:20',
            'slug'  => 'nullable|unique:categories,slug,' . $request->id . '|regex:/^[a-z0-9-]+$/|min:2|max:20',
            'image' => 'nullable|mimes:jpeg,png,jpg|max:2048'
        ], [
            'name.unique' => 'The category name "' . $request->name . '" is already taken. Please choose another.',
            'slug.unique' => 'The category slug "' . ($request->slug ?? Str::slug($request->name)) . '" is already taken. Please choose another.',
            'slug.regex'  => 'The slug may only contain lowercase letters, numbers, and hyphens.',
        ]);


        $category = Category::find($request->id);

        // Assign the name directly as validation enforces uniqueness
        $category->name = $request->name;

        // Generate a slug from the name and adjust it if necessary
        if ($request->filled('slug')) {
            $slug = Str::slug($request->slug);
        } else {
            $slug = Str::slug($request->name);
        }
        $originalSlug = $slug;
        $count = 1;
        while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }
        $category->slug = $slug;

        // Process the image only if a new file is uploaded
        if($request->hasFile('image')){
            // Delete the existing image if it exists
            if(File::exists(public_path('uploads/categories') . "/" . $category->image)){
                File::delete(public_path('uploads/categories') . '/' . $category->image);
            }
            $file = $request->file('image');
            $file_extention = $file->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extention;
            $this->GenerateCategoryThumbailsImage($file, $file_name);
            $category->image = $file_name;
        }

        $category->save();
        return redirect()->route('admin.categories')->with('status', 'Category updated successfully');
    }

    public function category_delete($id){
        $category = Category::find($id);
        if(File::exists(public_path('uploads/categories')."/".$category->image)){
            File::delete(public_path('uploads/categories')."/".$category->image);
        }
        $category->delete();
        return redirect()->route('admin.categories')->with('status','category deleted successfully');
    }

    public function products(){
        $products = Product::orderBy('created_at','DESC')->paginate(10);
        return view("admin.products",compact('products'));
    }

    public function product_add(Request $request)
    {
        $categories = Category::select('id','name')->orderBy('name')->get();
        $brands = Brand::select('id','name')->orderBy('name')->get();
        return view("admin.product-add",compact('categories','brands'));
    }

    public function product_store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^(?!\d+$)(?!.*[\s-]{2,})[a-zA-Z0-9\s\-]{3,50}$/',
                new EnglishWord() // Custom validation rule
            ],
            'slug'=>'required|unique:products,slug',
            'short_description'=>'required',
            'description'=>'required',
            'regular_price' => 'required|numeric|min:0.01|max:500',
            'sale_price' => 'nullable|numeric|gt:0|lt:regular_price', // Must be > 0 and < regular_price
            'SKU'=>'required',
            'stock_status'=>'required',
            'featured'=>'required',
            'quantity' => 'required|integer|min:1|max:100',
            'category_id'=>'required',
            'brand_id'=>'required',
            'image'=>'required|mimes:jpeg,png,jpg|max:2048'
        ],[
            'name.required' => 'Product name is required.',
            'name.string' => 'Product name must be a string.',
            'name.min' => 'Product name must be at least 3 characters.',
            'name.max' => 'Product name cannot be more than 50 characters.',
            'name.regex' => 'Product name can only contain letters, numbers, spaces, and hyphens.',
            'regular_price.min' => 'The regular price must be greater than 0.',
            'regular_price.max' => 'The regular price must limit 500$.',
            'sale_price.gt' => 'The sale price must be greater than 0.',
            'sale_price.lt' => 'The sale price must be lower than the regular price.',
            'quantity.min' => 'The quantity must be at least 1.',
            'quantity.max' => 'The quantity must be limit 100.',
        ]);
        $product = new Product();
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        $current_timestamp = Carbon::now()->timestamp;
        if($request->hasFile('image')){
            $image = $request->file('image');
            $imageName = $current_timestamp.'.'.$image->getClientOriginalExtension();
            $this->GenrateProductThumbnailImage($image,$imageName);
            $product->image = $imageName;
        }

        $gallery_arr = array();
        $gallery_image = '';
        $counter = 1;

        if($request->hasFile('images')){
            $allowedfileExtensions = array('jpg', 'png', 'gif');
            $file = $request->file('images');
            foreach($file as $file){
                $filename = $file->getClientOriginalName();
                $gextension = $file->getClientOriginalExtension();
                $gcheck = in_array($gextension, $allowedfileExtensions);
                if($gcheck){
                    $gfileName = $current_timestamp.'-'.$counter.$gextension;
                    $this->GenrateProductThumbnailImage($file,$gfileName);
                    array_push($gallery_arr,$gfileName);
                    $counter++;
                }
            }
            $gallery_image = implode(',', $gallery_arr);

        }
        $product->images = $gallery_image;
        $product->save();
        return redirect()->route('admin.products')->with('status','Product added successfully');
    }

    public function GenrateProductThumbnailImage($image,$imageName){
        $destinationPathThumbnail = public_path('uploads/products/thumbnails');
        $destinationPath = public_path('uploads/products');
        $img = Image::read($image->path());
        $img->cover(540, 689, "top");
        $img->resize(540, 689, function ($constrain){
            $constrain->aspectRatio();
        }) -> save($destinationPath.'/'.$imageName);
        $img->resize(104, 104, function ($constrain){
            $constrain->aspectRatio();
        }) -> save($destinationPathThumbnail.'/'.$imageName);
    }

    public function product_update(Request $request, $id)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^(?!\d+$)(?!.*[\s-]{2,})[a-zA-Z0-9\s\-]{3,50}$/',
                new EnglishWord() // Custom validation rule
            ],
            'slug'=>'required|unique:products,slug',
            'short_description'=>'required',
            'description'=>'required',
            'regular_price' => 'required|numeric|min:0.01|max:500',
            'sale_price' => 'nullable|numeric|gt:0|lt:regular_price', // Must be > 0 and < regular_price
            'SKU'=>'required',
            'stock_status'=>'required',
            'featured'=>'required',
            'quantity' => 'required|integer|min:1|max:100',
            'category_id'=>'required',
            'brand_id'=>'required',
            'image'=>'required|mimes:jpeg,png,jpg|max:2048'
        ],[
            'name.required' => 'Product name is required.',
            'name.string' => 'Product name must be a string.',
            'name.min' => 'Product name must be at least 3 characters.',
            'name.max' => 'Product name cannot be more than 50 characters.',
            'name.regex' => 'Product name can only contain letters, numbers, spaces, and hyphens.',
            'regular_price.min' => 'The regular price must be greater than 0.',
            'regular_price.max' => 'The regular price must limit 500$.',
            'sale_price.gt' => 'The sale price must be greater than 0.',
            'sale_price.lt' => 'The sale price must be lower than the regular price.',
            'quantity.min' => 'The quantity must be at least 1.',
            'quantity.max' => 'The quantity must be limit 100.',
        ]);

        $product = Product::findOrFail($id);
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/products'), $imageName);
            $product->image = $imageName;
        }

        $product->save();
        return redirect()->route('admin.products')->with('status', 'Product updated successfully');
    }

    public function product_delete($id){
        $product = Product::find($id);
        if(File::exists(public_path('uploads/products/').'/'.$product->image)){
            File::delete(public_path('uploads/products/').'/'.$product->image);
        }

        if(File::exists(public_path('uploads/products/thumbnails/').'/'.$product->image)){
            File::delete(public_path('uploads/products/thumbnails/').'/'.$product->image);
        }

        foreach(explode(',',$product->images) as $ofile){
            if(File::exists(public_path('uploads/products/').'/'.$ofile)){
                File::delete(public_path('uploads/products/').'/'.$ofile);
            }

            if(File::exists(public_path('uploads/products/thumbnails/').'/'.$ofile)){
                File::delete(public_path('uploads/products/thumbnails/').'/'.$ofile);
            }
        };

        $product->delete();
        return redirect()->route('admin.products')->with('status','Product deleted successfully');
    }

    public function coupons()
    {
        $coupons = Coupon::orderBy('expiry_date','DESC')->paginate(12);
        return view("admin.coupons",compact('coupons'));
    }

    public function coupon_add()
    {
        return view("admin.coupon-add");
    }

    public function coupon_store(Request $request){
        $request->validate([
            'code' => 'required|string|unique:coupons,code|max:20',
            'type' => 'required|in:fixed,percent',
            'value' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->type === 'percent' && $value > 100) {
                        $fail('Percentage discount cannot exceed 100%.');
                    }
                    if ($request->type === 'fixed' && $value > 1000) {
                        $fail('Fixed discount cannot exceed $1000.');
                    }
                },
            ],
            'code.unique' => 'The coupon code already exists. Please use a different code.',
            'cart_value' => 'required|numeric|min:0|max:10000',
            'expiry_date' => 'required|date|after:today',
        ]);
        $coupon = new Coupon();
        $coupon->code = $request->code;
        $coupon->type = $request->type;
        $coupon->value = $request->value;
        $coupon->cart_value = $request->cart_value;
        $coupon->expiry_date = $request->expiry_date;
        $coupon->save();
        return redirect()->route('admin.coupons')->with('status','Coupon added successfully');
    }

    public function coupon_edit($id)
    {
        $coupon = Coupon::find($id);
        return view("admin.coupon-edit",compact('coupon'));
    }

    public function coupon_update(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:coupons,code|max:20',
            'type' => 'required|in:fixed,percent',
            'value' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->type === 'percent' && $value > 100) {
                        $fail('Percentage discount cannot exceed 100%.');
                    }
                    if ($request->type === 'fixed' && $value > 1000) {
                        $fail('Fixed discount cannot exceed $1000.');
                    }
                },
            ],
            'code.unique' => 'The coupon code already exists. Please use a different code.',
            'cart_value' => 'required|numeric|min:0|max:10000',
            'expiry_date' => 'required|date|after:today',
        ]);
        $coupon = Coupon::find($request->id);
        $coupon->code = $request->code;
        $coupon->type = $request->type;
        $coupon->value = $request->value;
        $coupon->cart_value = $request->cart_value;
        $coupon->expiry_date = $request->expiry_date;
        $coupon->save();
        return redirect()->route('admin.coupons')->with('status','Coupon updated successfully');
    }

    public function coupon_delete($id)
    {
        $coupon = Coupon::find($id);
        $coupon->delete();
        return redirect()->route('admin.coupons')->with('status','Coupon deleted successfully');
    }

    public function orders()
    {
        $orders = Order::orderBy('created_at','DESC')->paginate(12);
        return view("admin.orders",compact('orders'));
    }

    public function order_details($order_id)
    {
        $order = Order::find($order_id);
        $orderItems = OrderItem::where('order_id',$order_id)->orderBy('id')->paginate(12);
        $transaction = Transaction::where('order_id',$order_id)->first();
        return view("admin.order-details",compact('order','orderItems','transaction'));
    }

    public function update_order_status(Request $request)
    {
        $order = Order::find($request->order_id);
        $order->status = $request->order_status;
        if($request->order_status == 'delivered'){
            $order->delivered_date = Carbon::now();
        }else if ($request->order_status == 'canceled'){
            $order->canceled_date = Carbon::now();
        }

        $order->save();
        if($request->order_status == 'delivered'){
            $transaction = Transaction::where('order_id', $request->order_id)->first();
            $transaction->status = 'approved';
            $transaction->save();
        }
        return back()->with('status','Order status updated successfully');
    }

    public function slides()
    {
        $slides = Slide::orderBy('id','DESC')->paginate(12);
        return view("admin.slides",compact('slides'));
    }

    public function slide_add()
    {
        return view("admin.slide-add");
    }

    public function slide_store(Request $request)
    {
        $request->validate([
            'tagline'=>'required',
            'title'=>'required',
            'subtitle'=>'required',
            'link'=>'required',
            'status'=>'required',
            'image'=>'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        $slide = new Slide();
        $slide->tagline = $request->tagline;
        $slide->title = $request->title;
        $slide->subtitle = $request->subtitle;
        $slide->link = $request->link;
        $slide->status = $request->status;

        $image = $request->file('image');
        $file_extention = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp.'.'.$file_extention;
        $this->GenerateSlideThumbailsImage($image,$file_name);
        $slide->image = $file_name;
        $slide->save();
        return redirect()->route('admin.slides')->with('status','Slide added successfully');
    }

    public function GenerateSlideThumbailsImage($image,$imageName){
        $destinationPath = public_path('uploads/slides');
        $img = Image::read($image->path());
        $img->cover(400, 690, "top");
        $img->resize(400, 690, function ($constrain){
            $constrain->aspectRatio();
        }) -> save($destinationPath.'/'.$imageName);
    }

    public function slide_edit($id){
        $slide = Slide::find($id);
        return view('admin.slide-edit',compact('slide'));
    }

    public function slide_update(Request $request)
    {
        $request->validate([
            'tagline'=>'required',
            'title'=>'required',
            'subtitle'=>'required',
            'link'=>'required',
            'status'=>'required',
            'image'=>'mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        $slide = Slide::find($request->id);
        $slide->tagline = $request->tagline;
        $slide->title = $request->title;
        $slide->subtitle = $request->subtitle;
        $slide->link = $request->link;
        $slide->status = $request->status;

        if($request->hasFile('image')){
            if(File::exists(public_path('uploads/slides').'/'.$slide->image)){
                File::delete(public_path('uploads/slides').'/'.$slide->image);
            }
            $image = $request->file('image');
            $file_extention = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp.'.'.$file_extention;
            $this->GenerateSlideThumbailsImage($image,$file_name);
            $slide->image = $file_name;
        }

        $slide->save();
        return redirect()->route('admin.slides')->with('status','Slide updated successfully');
    }

    public function slide_delete($id)
    {
        $slide = Slide::find($id);
        if(File::exists(public_path('uploads/slides').'/'.$slide->image)){
            File::delete(public_path('uploads/slides').'/'.$slide->image);
        }
        $slide->delete();
        return redirect()->route('admin.slides')->with('status','Slide deleted successfully');
    }

    public function search(Request $request){
        $query = $request->input('query');
        $results = Product::where('name', 'LIKE', "%{$query}%")->get()->take(8);
        return response()->json($results);
    }

    public function show_users()
    {
        $users = User::where('utype', 'USR')->withCount('orders')->get();
        return view('admin.users-index', compact('users'));
    }
}

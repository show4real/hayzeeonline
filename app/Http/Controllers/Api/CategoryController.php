<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Http\Requests\CategoryRequest;
use App\Http\Traits\productTrait;
use Illuminate\Support\Facades\URL;
use App\Models\Product;
use App\Models\Youtube;

class CategoryController extends Controller
{
    use productTrait;

    public function index(Request $request)
    {
        $categories = Category::searchAll($request->search)
            ->latest()
            ->paginate($request->rows == null ? 100 : $request->rows, ['*'], 'page', $request->page);
        $youtube = Youtube::first()->youtubeid;
        return response()->json(compact('categories', 'youtube'));
    }



    public function createSlug($title)
    {
        $slug = Str::slug($title);
        return $slug . '-' . time();
    }


    public function create(CategoryRequest $request)
    {
        $data = $request->validated();

        $image = $request->image;
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $imageUrl = URL::asset('images/' . $imageName);


        $category = Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'image_url' => $imageUrl,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->singleUpload($image, $imageName, 0);



        return $category;
    }
    
      public function show($slug)
    {
        $category = Category::where('slug',$slug)->first();

        if ($category) {
           
             return response()->json(compact('category'));
        }
        return response()->json(['message' => 'Category not found'], 404);
    }


    public function update(CategoryRequest $request, Category $category)
    {
        $this->deleteImage($category->image_url);

        $data = $request->validated();

        $image = $request->file('image');
        $rotations = $request->rotations;
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $imageUrl = URL::asset('images/' . $imageName);



        $category->update([
            'name' => $data['name'],
            'image_url' => $imageUrl,
            'slug' => Str::slug($data['name']),
        ]);



        $this->singleUpload($image, $imageName, 0);

        return response()->json(compact('category'));
    }



    public function delete($id)
    {
        $category = Category::find($id);

        if ($category) {
            $this->deleteImage($category->image_url);
            $category->delete();
            $cat = Category::first();
            if ($cat) {
                Product::where('category_id', $id)->update(['category_id' => $cat->id]);
            }
            return response()->json(true);
        }
        return response()->json(['message' => 'Category not found'], 404);
    }

    public function userCategories(Request $request)
    {
       $categories = Category::has('products')->with(['products'])->get()->each(function ($query) {
            $query->setRelation('products', $query->products->orderByRaw("availability = 1 DESC")->take(8));
            return $query;
        })->take(5);
        $youtube = Youtube::first()->youtubeid;


        return response()->json(compact('categories', 'youtube'));
    }

    public function allCats(Request $request)
    {
        $categories = Category::select('id', 'name', 'slug')->get();



        return response()->json(compact('categories'));
    }
}

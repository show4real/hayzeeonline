<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Brand;
use App\Http\Requests\BrandRequest;
use App\Http\Traits\productTrait;
use Illuminate\Support\Facades\URL;
use App\Models\Product;

class BrandController extends Controller
{
    use productTrait;

    public function index(Request $request)
    {
        $brands = Brand::search($request->search)
            ->paginate($request->rows, ['*'], 'page', $request->page);
        return response()->json(compact('brands'));
    }



    public function createSlug($title)
    {
        $slug = Str::slug($title);
        return $slug . '-' . time();
    }
    
      public function show($id)
    {
        $brand = Brand::where('slug',$id)->first();

        if ($brand) {
           
             return response()->json(compact('brand'));
        }
        return response()->json(['message' => 'Brand not found'], 404);
    }


    public function create(BrandRequest $request)
    {
        $data = $request->validated();

        $image = $request->image;
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $imageUrl = URL::asset('images/' . $imageName);


        $brand = Brand::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'image_url' => $imageUrl,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->singleUpload($image, $imageName, 0);



        return $brand;
    }

    public function update(BrandRequest $request, Brand $brand)
    {
        $this->deleteImage($brand->image_url);

        $data = $request->validated();

        $image = $request->file('image');
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $imageUrl = URL::asset('images/' . $imageName);



        $brand->update([
            'name' => $data['name'],
            'image_url' => $imageUrl,
            'slug' => Str::slug($data['name']),
        ]);



        $this->singleUpload($image, $imageName, 0);

        return response()->json(compact('brand'));
    }



    public function delete($id, Request $request)
    {
        $brand = Brand::find($id);

        if ($brand) {
            $this->deleteImage($brand->image_url);
            $brand->delete();
            $bra = Brand::first();
            if ($bra) {
                Product::where('brand_id', $id)->update(['brand_id' => $bra->id]);
            }
            return response()->json(true);
        }
        return response()->json(['message' => 'Brand not found'], 404);
    }
}

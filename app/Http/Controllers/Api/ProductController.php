<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\productTrait;
use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\ProductImages;
use App\Models\Youtube;
use App\Models\Notice;

class ProductController extends Controller
{


    use productTrait;

    public function index(Request $request)
    {
        $products = Product::searchAll($request->search)
            ->brand($request->brand)
            ->category($request->category)
            ->sort($request->sort)
            ->storage($request->storages)
            ->processor($request->processors)
            ->ram($request->rams)
            ->orderByRaw("availability = 1 DESC")
            ->orderBy('updated_at', 'desc')
            ->paginate($request->rows, ['*'], 'page', $request->page);

        $youtube = Youtube::first()->youtubeid;
        $notice = Notice::first()->notice;

        return response()->json(compact('products', 'youtube','notice'));
    }






    public function store(Request $request)
    {


        $images = $request->file('images');
        $rotations = $request->rotations;
        $imageName = time() . '.' . $images[0]->getClientOriginalExtension();

        $product = $this->create($request, $imageName);

        if ($product) {
            $this->upload($images, $rotations, $product->id);
            $this->singleUpload($images[0], $imageName, $rotations[0]);
        }


        return response()->json((compact('product')), 200);
    }

    public function update(Request $request, Product $product)
    {
        $this->deletePreviousFile($product->id);

        $images = $request->file('images');
        $rotations = $request->rotations;
        $imageName = time() . '.' . $images[0]->getClientOriginalExtension();

        $create = $this->updateProduct($request, $product, $imageName);

        if ($create) {

            $this->upload($images, $rotations, $product->id);
            $this->singleUpload($images[0], $imageName, $rotations[0]);
        }


        return response()->json((compact('product')), 200);
    }

    public function delete($id)
    {
        $product = Product::find($id);

        if ($product) {
            $this->deletePreviousFile($product->id);
            $product->delete();
            return response()->json(true);
        }
        return response()->json(['message' => 'product not found'], 404);
    }


    public function productDescriptions($id)
    {
        $product_descriptions = ProductDescription::where('product_id', $id)->get();

        return response()->json((compact('product_descriptions')), 200);
    }

    public function productImages($id)
    {
        $product_images = ProductImages::where('product_id', $id)->pluck('url');

        return response()->json((compact('product_images')), 200);
    }

    public function relatedProducts($id)
    {
        $products = Product::where('category_id', $id)->orderByRaw("availability = 1 DESC")->take(4)
            ->get();

        return response()->json((compact('products')), 200);
    }
}

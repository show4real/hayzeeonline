<?php

namespace App\Http\Traits;

use Intervention\Image\Facades\Image;
use App\Models\Product;
use App\Models\ProductDescription;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use App\Models\ProductImages;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;



trait productTrait
{


    public function singleUpload($image, $imageName, $rotation)
    {

        $compressedImage = Image::make($image);
        $compressedImage->rotate(-$rotation)->save(public_path('images') . '/' . $imageName, 80);

        return response()->json(['message' => 'Image uploaded and compressed successfully']);
    }

    public function upload($images, $rotations, $product_id)
    {
        foreach ($images as $index => $image) {

            $rotation =  $rotations[$index];
            $imageName = time() . $image->getClientOriginalName();
            $compressedImage = Image::make($image);

            $compressedImage->rotate(-$rotation)->save(public_path('detailedproducts') . '/' . $imageName, 80);

            ProductImages::create([
                'url' => URL::asset('detailedproducts/' . $imageName),
                'product_id' => $product_id
            ]);
        }
    }

    public function createSlug($title)
    {
        $slug = Str::slug($title);
        return $slug . '-' . time();
    }



    public function create($data, $imageName)
    {
        // $imageUrl = URL::asset('images/' . $imageName);
        // $product = Product::create([
        //     'name' => $data['name'],
        //     'description' => $data['description'],
        //     'product_type' => $data['product_type'],
        //     'price' => $data['price'],
        //     'other_sales' => $data['other_sales'],
        //     'availability' => $data['availability'],
        //     'category_id' => $data['category'],
        //     'brand_id' => $data['brand'],
        //     'ram' => $data['ram'],
        //     'storage' => $data['storage'],
        //     'processor' => $data['processor'],
        //     'slug' => $this->createSlug($data['name']),
        //     'image' => $imageUrl,
            

        // ]);

        $imageUrl = URL::asset('images/' . $imageName);
        $product = new Product();
        $product->name = $data['name'];
        $product->description = $data['description'];
        $product->product_type = $data['product_type'];
        $product->price = $data['price'];
        $product->other_sales = $data['other_sales'];
        $product->availability = $data['availability'];
        $product->category_id = $data['category'];
        $product->brand_id = $data['brand'];
        $product->ram = $data['ram'];
        $product->storage = $data['storage'];
        $product->processor = $data['processor'];
        $product->slug = $this->createSlug($data['name']);
        $product->image = $imageUrl;
        $product->save();

        if (count($data->labels) > 0) {
            for ($i = 0; $i < count($data->labels); $i++) {
                $productInfo = new ProductDescription();
                $productInfo->product_id = $product->id;
                $productInfo->label = $data->labels[$i];
                $productInfo->values = $data->values[$i];
                $productInfo->save();
            }
        }

        return $product;
    }

    public function updateProduct($request, $product, $imageName)
    {

        $imageUrl = URL::asset('images/' . $imageName);
        // $product->update([
        //     'name' => $request['name'],
        //     'description' => $request['description'],
        //     'product_type' => $request['product_type'],
        //     'price' => $request['price'],
        //     'availability' => $request['availability'],
        //     'category_id' => $request['category'],
        //     'brand_id' => $request['brand'],
        //     'ram' => $request['ram'],
        //     'storage' => $request['storage'],
        //     'processor' => $request['processor'],
        //     'slug' => $this->createSlug($request['name']),
        //     'image' => $imageUrl,
        //     'other_sales' => $request['other_sales'],
        //     'created_at' => now(),
        //     'updated_at' => now()
        // ]);
        $product->name = $request['name'];
        $product->description = $request['description'];
        $product->product_type = $request['product_type'];
        $product->price = $request['price'];
        $product->availability = $request['availability'];
        $product->category_id = $request['category'];
        $product->brand_id = $request['brand'];
        $product->ram = $request['ram'];
        $product->storage = $request['storage'];
        $product->processor = $request['processor'];
        $product->slug = $this->createSlug($request['name']);
        $product->image = $imageUrl;
        $product->other_sales = $request['other_sales'];
        $product->created_at = now();
        $product->updated_at = now();
        $product->save();

        if ($request->labels && count($request->labels) > 0) {

            ProductDescription::where('product_id', $product->id)->delete();
            if (count($request->labels) > 0) {
                for ($i = 0; $i < count($request->labels); $i++) {
                    $productInfo = new ProductDescription();
                    $productInfo->product_id = $product->id;
                    $productInfo->label = $request->labels[$i];
                    $productInfo->values = $request->values[$i];
                    $productInfo->save();
                }
            }
        }
        return $product;
    }

    private function deletePreviousFile($product_id)
    {
        $detailed_images = ProductImages::where('product_id', $product_id)->pluck('url');
        $single_image = Product::where('id', $product_id)->first()->image;

        $single_image_name = str_replace('https://hayzeeonline.com/images/', '', $single_image);


        $single_imagepath = public_path('images/' . $single_image_name);
        File::delete($single_imagepath);

        foreach ($detailed_images as $detailed_image) {
            $image_name = str_replace('https://hayzeeonline.com/detailedproducts/', '', $detailed_image);
            $filePath = public_path('detailedproducts/' . $image_name);
            File::delete($filePath);
        }



        ProductImages::where('product_id', $product_id)->delete();
    }


    public function deleteImage($image)
    {

        $baseUrl = 'https://hayzeeonline.com/images/';

        $single_image_name = str_replace($baseUrl, '', $image);

        Storage::disk('public')->delete('images/' . $single_image_name);
    }
}

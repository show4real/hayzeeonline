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
        $imageUrl = URL::asset('images/' . $imageName);

        $product = new Product();
        $this->fillProductAttributes($product, $data, $imageUrl);
        $product->slug = $this->createSlug($data['name']);
        $product->save();

        $this->syncDescriptions($product, $data);

        return $product;
    }

    public function updateProduct($request, $product, $imageName)
    {
        $imageUrl = URL::asset('images/' . $imageName);

        $this->fillProductAttributes($product, $request, $imageUrl);
        $product->created_at = now();
        $product->updated_at = now();
        $product->save();

        if (! empty($request['labels'])) {
            ProductDescription::where('product_id', $product->id)->delete();
            $this->syncDescriptions($product, $request);
        }

        return $product;
    }

    /**
     * Map an incoming request/array payload onto a Product instance.
     */
    private function fillProductAttributes(Product $product, $data, $imageUrl)
    {
        $product->name = $data['name'];
        $product->description = $data['description'];
        $product->product_type = $data['product_type'];
        $product->price = $data['price'];
        $product->other_sales = $data['other_sales'];
        $product->availability = $data['availability'];
        $product->category_id = $data['category'];
        $product->brand_id = $data['brand'];
        $product->ram = $data['ram'];
        $product->storage = $data['storage_capacity'] ?? $data['storage'] ?? null;
        $product->processor = $data['processor'];
        $product->model = $data['model'] ?? null;
        $product->subtype = $data['subtype'] ?? null;
        $product->condition = $data['condition'] ?? null;
        $product->number_of_cores = $data['number_of_cores'] ?? null;
        $product->storage_type = $data['storage_type'] ?? null;
        $product->display_size = $data['display_size'] ?? null;
        $product->graphics_card = $data['graphics_card'] ?? null;
        $product->graphics_card_memory = $data['graphics_card_memory'] ?? null;
        $product->operating_system = $data['operating_system'] ?? null;
        $product->color = $data['color'] ?? null;
        $product->exchange_possible = $data['exchange_possible'] ?? null;
        $product->image = $imageUrl;

        return $product;
    }

    /**
     * Persist the label/value description rows for a product.
     */
    private function syncDescriptions(Product $product, $data)
    {
        $labels = $data['labels'] ?? [];
        $values = $data['values'] ?? [];

        foreach ($labels as $i => $label) {
            $productInfo = new ProductDescription();
            $productInfo->product_id = $product->id;
            $productInfo->label = $label;
            $productInfo->values = $values[$i] ?? null;
            $productInfo->save();
        }
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

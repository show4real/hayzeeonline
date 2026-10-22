<?php

namespace App\Http\Traits;

use Intervention\Image\Laravel\Facades\Image;
use App\Models\Product;
use App\Models\ProductDescription;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use App\Models\ProductImages;
use Illuminate\Support\Facades\File;



trait productTrait
{


    public function singleUpload($image, $imageName, $rotation)
    {
        $directory = public_path('images');
        File::ensureDirectoryExists($directory);

        // Image v3: read() replaces make(), and save() takes the JPEG
        // quality as a named option rather than a positional argument.
        $compressedImage = Image::read($image);
        $compressedImage->rotate(-$rotation)->save($directory . '/' . $imageName, quality: 80);

        return response()->json(['message' => 'Image uploaded and compressed successfully']);
    }

    public function upload($images, $rotations, $product_id)
    {
        $directory = public_path('detailedproducts');
        File::ensureDirectoryExists($directory);

        foreach ($images as $index => $image) {

            $rotation =  $rotations[$index];
            $imageName = time() . $image->getClientOriginalName();
            $compressedImage = Image::read($image);

            $compressedImage->rotate(-$rotation)->save($directory . '/' . $imageName, quality: 80);

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
        $product->new_price = $data['new_price'] ?? null;
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

    /**
     * Resolve the on-disk path of an upload from the absolute URL stored for it.
     *
     * The stored URL comes from URL::asset(), which builds on the host of the
     * request that did the upload. With more than one hostname pointing at this
     * API (api. and apiv2.) that host varies per row, so the filename cannot be
     * recovered by stripping a fixed prefix — which is what this used to do,
     * against a hardcoded 'https://hayzeeonline.com/...' that matched no stored
     * URL at all. Nothing was ever deleted and the files accumulated.
     *
     * Reads the path component only, so the host is irrelevant. Returns null
     * when the URL does not point into the expected directory, which keeps a
     * stored S3/CDN URL — or a traversal attempt — from resolving to a path.
     *
     * @param  string|null  $url
     * @param  string  $directory  e.g. 'images'
     * @return string|null absolute path, or null if it is not ours to delete
     */
    private function uploadPathFromUrl($url, $directory)
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || ! str_starts_with($path, '/' . $directory . '/')) {
            return null;
        }

        // basename() after the prefix check, so "/images/../../.env" cannot
        // escape the directory.
        $name = basename($path);

        if ($name === '' || $name === '.' || $name === '..') {
            return null;
        }

        return public_path($directory . '/' . $name);
    }

    private function deletePreviousFile($product_id)
    {
        $detailed_images = ProductImages::where('product_id', $product_id)->pluck('url');
        $single_image = Product::where('id', $product_id)->first()->image;

        $single_imagepath = $this->uploadPathFromUrl($single_image, 'images');

        if ($single_imagepath !== null && File::exists($single_imagepath)) {
            File::delete($single_imagepath);
        }

        foreach ($detailed_images as $detailed_image) {
            $filePath = $this->uploadPathFromUrl($detailed_image, 'detailedproducts');

            if ($filePath !== null && File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        ProductImages::where('product_id', $product_id)->delete();
    }

    public function deleteImage($image)
    {
        // Deliberately File::delete on public_path, not Storage::disk('public').
        // The public disk is storage/app/public, but singleUpload() writes to
        // public_path('images') — so this deleted a path that never existed and
        // reported success.
        $path = $this->uploadPathFromUrl($image, 'images');

        if ($path !== null && File::exists($path)) {
            File::delete($path);
        }
    }
}

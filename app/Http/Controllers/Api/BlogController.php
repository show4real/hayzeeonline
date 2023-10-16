<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog;
use App\Http\Traits\productTrait;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class BlogController extends Controller
{

    use productTrait;

    public function index(Request $request)
    {
        $blogs = Blog::search($request->search)
            ->latest()
            ->paginate($request->rows, ['*'], 'page', $request->page);
        return response()->json(compact('blogs'));
    }

    public function createSlug($title)
    {
        $slug = Str::slug($title);
        return $slug . '-' . time();
    }


    public function store(Request $request)
    {


        $image = $request->file('image');

        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $imageUrl = URL::asset('images/' . $imageName);


        $blog = Blog::create([
            'name' => $request['name'],
            'description' => $request['description'],
            'slug' => $this->createSlug($request['name']),
            'image' => $imageUrl,
        ]);

        $this->singleUpload($image, $imageName, 0);



        return response()->json((compact('blog')), 200);
    }

    public function update(Request $request, Blog $blog)
    {
        $this->deleteImage($blog->image);
        $image = $request->file('image');
        $rotations = $request->rotations;
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $imageUrl = URL::asset('images/' . $imageName);
        $blog->update([
            'name' => $request['name'],
            'description' => $request['description'],
            'slug' => $this->createSlug($request['name']),
            'image' => $imageUrl,
        ]);

        $this->singleUpload($image, $imageName, 0);

        return response()->json(compact('blog'));
    }


    public function delete($id)
    {
        $blog = Blog::find($id);

        if ($blog) {
            $this->deleteImage($blog->image);
            $blog->delete();
            return response()->json(true);
        }
        return response()->json(['message' => 'Blog not found'], 404);
    }

    public function show($slug)
    {
        $blog = Blog::where('slug', $slug)->first();
        return response()->json(compact('blog'));
    }
}
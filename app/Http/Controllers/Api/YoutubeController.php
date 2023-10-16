<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Youtube;

class YoutubeController extends Controller
{
    public function addYoutube(Request $request)
    {
        $youtube = Youtube::first();

        $youtube->youtubeid = $request->youtubeid;
        $youtube->save();
        return response()->json(compact('youtube'));
    }

    public function index(Request $request)
    {
        $youtubes = Youtube::paginate($request->rows, ['*'], 'page', $request->page);
        return response()->json(compact('youtubes'));
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notice;

class NoticeController extends Controller
{

    public function index(Request $request){

        $notices = Notice::paginate($request->rows, ['*'], 'page', $request->page);

        return response()->json(compact('notices'));

    }
    public function create(Request $request){

        $notice = Notice::first();
        $notice->notice = $request->notice;
        $notice->save();
        return response()->json(compact('notice'));
    }
  
    
}

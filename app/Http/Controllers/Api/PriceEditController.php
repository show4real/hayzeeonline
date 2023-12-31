<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PriceEdit;


class PriceEditController extends Controller
{

    public function index(Request $request){

        $prices = PriceEdit::latest()->paginate(10);
        return response()->json(compact('prices'));

    }


    public function create(Request $request){

        $price_edit = PriceEdit::updateOrCreate(
            ['start_date' => $request->start_date, 'end_date' => $request->end_date],
            ['percentage' => $request->percentage, 'comment' => $request->comment]
        );

        return response()->json(compact('price_edit'));
    }


      public function delete($id)
    {
        $price = PriceEdit::find($id);

        if ($price) {
           
            $price->delete();
            return response()->json(true);
        }
        return response()->json(['message' => 'price not found'], 404);
    }




    
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuickFormRequest;
use App\Models\QuickForm;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\File;

class QuickFormController extends Controller
{
    public function index()
    {
        $rows = (int) request()->input('rows', 15);
        $rows = $rows > 0 ? min($rows, 100) : 15;

        $search = trim((string) request()->input('search', ''));

        $query = QuickForm::query()->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%' . $search . '%';

                $q->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        $quickForms = $query->paginate($rows);

        return response()->json(compact('quickForms'));
    }

    public function show($id)
    {
        $quickForm = QuickForm::find($id);
        if (! $quickForm) {
            return response()->json(['message' => 'Quick form not found.'], 404);
        }

        return response()->json(compact('quickForm'));
    }

    public function store(QuickFormRequest $request)
    {
        $data = $request->validated();

        $dir = public_path('quick_forms');
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $idCardPath = null;
        if ($request->hasFile('idCard')) {
            $file = $request->file('idCard');
            $name = time() . '_' . Str::random(8) . '_idcard.' . $file->getClientOriginalExtension();
            $file->move($dir, $name);
            $idCardPath = URL::asset('quick_forms/' . $name);
        }

        $quickForm = QuickForm::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'id_card_path' => $idCardPath,
        ]);

        return response()->json([
            'message' => 'Quick form submitted.',
            'data' => $quickForm,
        ], 201);
    }
}

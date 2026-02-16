<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuickFormRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'idCard' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

    public function attributes()
    {
        return [
            'idCard' => 'ID Card',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'tagline' => 'required|string',
            'description' => 'required|string|max:500',
            'category_id' => 'required|integer',
            'is_popular' => 'required|boolean',
            'students' => 'nullable|string',
            'details' => 'nullable|string',
            'access_positions' => 'sometimes|array|max:1000',
            'access_positions.*' => 'string|distinct|max:100',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
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
            'submenu_id' => 'required|integer',
            'title' => 'required|string|max:150',
            'no_surat' => 'nullable|string|max:255',
            'type' => 'string|max:100',
            'content' => 'required_if:type,text|nullable|string',
            'dokumen' => 'required_unless:type,text|nullable|file|mimes:word,pdf,xlsx,docx|max:20480', // max 20MB
            'tgl_berlaku' => 'nullable|date',
            'regionals_id' => 'required|array',
            'kd_jabatan' => 'required|array',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
        ];
    }
}

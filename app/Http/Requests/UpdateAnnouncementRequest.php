<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnnouncementRequest extends FormRequest
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
            'dokumen' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:20480', // max 20MB
            'type' => 'string|max:100',
            'content' => 'nullable|string|max:10000',
            'tgl_berlaku' => 'nullable|date',
            'regionals_id' => 'required|array',
            'kd_jabatan' => 'required|array',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
            'document_id' => 'required|integer'
        ];
    }

    /**
     * Prepare validation data before validation logic runs.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'document_id' => (int) $this->route('document_id')
        ]);
    }
}

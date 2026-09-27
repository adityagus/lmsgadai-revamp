<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentViewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Require active authentication session
        return session()->has('auth');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'document_id' => 'required|integer|exists:documents,id',
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'document_id.required' => 'ID Dokumen wajib diisi.',
            'document_id.integer' => 'ID Dokumen harus berupa angka.',
            'document_id.exists' => 'Dokumen tidak ditemukan.',
        ];
    }
}

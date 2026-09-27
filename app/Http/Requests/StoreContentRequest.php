<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentRequest extends FormRequest
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
            'course_id' => 'required|integer|exists:courses,id',
            'quiz_id' => 'nullable|integer|exists:quizzes,id',
            'title' => 'required|string|max:150',
            'type' => 'required|string|in:text,video,pdf,quiz,form,google_form',
            // Jika type pdf, content harus file pdf. Jika bukan, content string (nullable)
            'content' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $type = request('type');
                    if ($type === 'pdf') {
                        if (!request()->hasFile('content')) {
                            $fail('Content harus berupa file PDF jika type adalah pdf.');
                        } else {
                            $file = request()->file('content');
                            if ($file->getClientOriginalExtension() !== 'pdf') {
                                $fail('File harus berformat PDF.');
                            }
                        }
                    } else {
                        // Untuk selain pdf, harus string jika diisi
                        if ($value !== null && !is_string($value)) {
                            $fail('Content harus berupa string.');
                        }
                    }
                }
            ],
            'lampiran' => 'nullable|file|mimes:pdf|max:2048',
            'order' => 'required|integer|min:1', // Order required for create
            // 'created_by' => 'required|integer|exists:users,id',
            // 'updated_by' => 'nullable|integer|exists:users,id',
        ];
    }
    
    public function messages()
    {
        return [
            'course_id.required' => 'Course ID is required.',
            'course_id.integer' => 'Course ID must be a number.',
            'course_id.exists' => 'The selected course does not exist.',
            'title.required' => 'Judul wajib diisi.',
            'title.max' => 'Judul maksimal 150 karakter.',
            'type.required' => 'Content type is required.',
            'type.in' => 'Content type must be one of: text, video, pdf, quiz, form, google_form.',
            'order.required' => 'Order is required.',
            'order.integer' => 'Order must be a number.',
            'order.min' => 'Order must be at least 1.',
        ];
    }
}

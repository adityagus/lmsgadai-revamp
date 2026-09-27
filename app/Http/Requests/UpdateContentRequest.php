<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContentRequest extends FormRequest
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
            'course_id' => 'sometimes|integer|exists:courses,id',
            'quiz_id' => 'nullable|integer|exists:quizzes,id',
            'title' => 'sometimes|required|string|max:150',
            'type' => 'sometimes|string|in:text,video,pdf,quiz,form,google_form',
            'content' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $type = request('type');
                    if ($type === 'pdf') {
                        if (request()->hasFile('content')) {
                            $file = request()->file('content');
                            $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension());
                            if ($ext !== 'pdf') {
                                $fail('File harus berformat PDF.');
                            }
                        }
                    } else {
                        if ($value !== null && !is_string($value) && !request()->hasFile('content')) {
                            $fail('Content harus berupa string.');
                        }
                    }
                }
            ],
            'lampiran' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:10240',
            'order' => 'sometimes|integer|min:1',
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
            'order.integer' => 'Order must be a number.',
            'order.min' => 'Order must be at least 1.',
        ];
    }
}

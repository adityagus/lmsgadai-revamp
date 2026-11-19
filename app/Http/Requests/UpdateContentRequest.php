<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('type') === 'pdf') {
            $this->merge(['type' => 'dokumen']);
        }
    }

    public function rules()
    {
        $allowedExt = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx'];

        return [
            'course_id' => 'sometimes|integer|exists:courses,id',
            'parent_id' => 'nullable|integer',
            'new_parent_title' => 'nullable|string|max:150',
            'quiz_id' => 'nullable|integer|exists:quizzes,id',
            'title' => 'sometimes|required|string|max:150',
            'type' => 'sometimes|string|in:text,video,dokumen,pdf,quiz,form,google_form,parent',
            'content' => [
                'nullable',
                function ($attribute, $value, $fail) use ($allowedExt) {
                    $type = request('type');
                    if (in_array($type, ['dokumen', 'pdf'], true)) {
                        if (request()->hasFile('content')) {
                            $file = request()->file('content');
                            $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension()));
                            if (!in_array($ext, $allowedExt, true)) {
                                $fail('Format yang didukung: PDF, PPT, PPTX, DOCX, Excel (Maks. 5MB).');
                                return;
                            }
                            if ($file->getSize() > 5 * 1024 * 1024) {
                                $fail('Ukuran file maksimal 5MB.');
                            }
                        }
                    } else {
                        if ($value !== null && !is_string($value) && !request()->hasFile('content')) {
                            $fail('Content harus berupa string.');
                        }
                    }
                }
            ],
            'lampiran' => 'nullable|file|mimes:pdf,ppt,pptx,doc,docx,xls,xlsx|max:5120',
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
            'type.in' => 'Content type must be one of: text, video, dokumen, quiz, form, google_form, parent.',
            'lampiran.mimes' => 'Format yang didukung: PDF, PPT, PPTX, DOCX, Excel (Maks. 5MB).',
            'lampiran.max' => 'Ukuran file lampiran maksimal 5MB.',
            'order.integer' => 'Order must be a number.',
            'order.min' => 'Order must be at least 1.',
        ];
    }
}

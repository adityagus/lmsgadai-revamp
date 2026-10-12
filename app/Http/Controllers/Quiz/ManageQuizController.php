<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Services\Quiz\QuizService;
use App\Services\Quiz\EmployeeParticipants;
use App\Services\Quiz\QuizAudience;
use App\Services\Quiz\AudienceDirectory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ManageQuizController extends Controller
{
    private $service;
    public function __construct(QuizService $service)
    {
        $this->service = $service;
    }
    private function user($r)
    {
        $u = $this->service->identity($r);
        $this->service->manage($u);
        return $u;
    }
    private function owned($table, $id, $user)
    {
        $row = DB::table($table)->find($id);
        abort_unless($row, 404);
        abort_unless($user->role === 'admin' || $row->created_by === $user->id, 403);
        return $row;
    }

    public function index(Request $r)
    {
        $u = $this->user($r);
        return DB::table('quizzes')->when($u->role !== 'admin', function ($q) use ($u) {
            $q->where('created_by', $u->id); })->orderByDesc('id')->get()->map(function ($q) { return $this->dates($q); });
    }

    private function dates($row)
    {
        foreach (['available_from', 'available_until'] as $key) {
            $row->$key = $row->$key ? Carbon::parse($row->$key)->toIso8601String() : null;
        }
        return $row;
    }

    public function metadata(Request $r)
    {
        $u = $this->user($r);
        $classes = DB::table('classes')->when($u->role !== 'admin', function ($q) use ($u) {
            $q->where('created_by', $u->id);
        })->get();
        $members = DB::table('class_students')->whereIn('class_id', $classes->pluck('id'))->get()->groupBy('class_id');
        return [
            'user' => $u,
            'students' => app(EmployeeParticipants::class)->all(),
            'classes' => $classes->map(function ($c) use ($members) {
                    $c->student_ids = $members->get($c->id, collect())->pluck('student_id');
                    return $c; }),
            'courses' => DB::table('courses')->get(['id', 'name']),
            'banks' => DB::table('question_banks')->when($u->role !== 'admin', function ($q) use ($u) {
                $q->where('created_by', $u->id); })->get(),
        ];
    }

    public function audienceOptions(Request $r)
    {
        $this->user($r);
        $directory = app(AudienceDirectory::class);
        return ['positions' => $directory->positions(), 'branches' => $directory->branches()];
    }

    public function saveUser(Request $r)
    {
        $u = $this->user($r);
        abort_unless($u->role === 'admin', 403);
        abort(405, 'Data pengguna dikelola melalui sistem karyawan.');
    }

    public function saveClass(Request $r)
    {
        $u = $this->user($r);
        $data = $r->validate(['id' => 'nullable|integer', 'name' => 'required|string|max:255', 'student_ids' => 'present|array', 'student_ids.*' => ['bail', 'integer', 'distinct', app(EmployeeParticipants::class)->validationRule()]]);
        return DB::transaction(function () use ($u, $data) {
            if (!empty($data['id'])) {
                $this->owned('classes', $data['id'], $u);
                $id = $data['id'];
                DB::table('classes')->where('id', $id)->update(['name' => $data['name'], 'updated_at' => now()]);
            } else {
                $id = DB::table('classes')->insertGetId(['name' => $data['name'], 'created_by' => $u->id, 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('class_students')->where('class_id', $id)->delete();
            foreach ($data['student_ids'] as $student) {
                DB::table('class_students')->insert(['class_id' => $id, 'student_id' => $student]);
            }
            return ['id' => $id];
        });
    }

    public function bank(Request $r)
    {
        $u = $this->user($r);
        $data = $r->validate(['name' => 'required|string|max:255']);
        return ['id' => DB::table('question_banks')->insertGetId($data + ['created_by' => $u->id, 'created_at' => now(), 'updated_at' => now()])];
    }

    public function questions(Request $r)
    {
        $u = $this->user($r);
        $questions = DB::table('questions')->select('questions.*')
            ->selectRaw('(EXISTS (SELECT 1 FROM quiz_questions WHERE question_id = questions.id) OR EXISTS (SELECT 1 FROM quiz_attempt_questions WHERE question_id = questions.id)) AS is_used')
            ->when($u->role !== 'admin', function ($q) use ($u) {
            $q->where('created_by', $u->id); })
            ->when($r->bank_id, function ($q) use ($r) {
                $q->where('bank_id', $r->bank_id); })->orderByDesc('id')->get();
        $options = DB::table('question_options')->whereIn('question_id', $questions->pluck('id'))->orderBy('id')->get()->groupBy('question_id');
        return $questions->map(function ($q) use ($options) {
                    $q->is_used = (bool) $q->is_used;
                    $q->options = $options->get($q->id, collect())->values();
                    $q->image_url = \App\Services\Quiz\QuestionImage::url($q->image_path);
                    return $q;
                });
    }

    public function uploadQuestionImage(Request $r)
    {
        $user = $this->user($r);
        $r->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=8000,max_height=8000']);
        $path = $r->file('image')->store('quiz-questions/'.$user->id, 'public');
        abort_unless($path, 500, 'Gambar gagal disimpan. Silakan coba lagi.');
        return ['image_path' => $path, 'image_url' => \App\Services\Quiz\QuestionImage::url($path)];
    }

    public function showQuestionImage(string $path)
    {
        $disk = Storage::disk('public');
        $file = 'quiz-questions/'.$path;
        abort_unless($disk->exists($file), 404, 'Gambar soal tidak ditemukan.');

        return $disk->response($file, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    public function saveQuestion(Request $r, $id = null)
    {
        $u = $this->user($r);
        $data = $r->validate([
            'bank_id' => 'nullable|integer|exists:question_banks,id',
            'version_if_used' => 'sometimes|boolean',
            'course_id' => 'nullable|integer|exists:courses,id',
            'question' => 'required|string|max:20000',
            'image_path' => ['nullable', 'string', 'max:255', 'regex:~^quiz-questions/[0-9]+/[A-Za-z0-9]+\.(jpg|jpeg|png|webp)$~'],
            'type' => 'required|in:multiple_choice,multiple_answer,true_false,essay',
            'weight' => 'required|numeric|min:0.01|max:10000',
            'category' => 'nullable|string|max:255',
            'topic' => 'nullable|string|max:255',
            'difficulty' => 'required|in:easy,medium,hard',
            'explanation' => 'nullable|string|max:20000',
            'options' => 'present|array|max:30',
            'options.*.text' => 'required|string|max:5000',
            'options.*.is_correct' => 'required|boolean',
        ]);
        if (!empty($data['image_path'])) {
            abort_unless($u->role === 'admin' || str_starts_with($data['image_path'], 'quiz-questions/'.$u->id.'/'), 403);
            abort_unless(Storage::disk('public')->exists($data['image_path']), 422, 'Gambar tidak ditemukan. Unggah kembali gambar soal.');
        }
        if (empty($data['bank_id'])) {
            $bank = DB::table('question_banks')->where('created_by', $u->id)->first();
            if (!$bank) {
                $bankId = DB::table('question_banks')->insertGetId([
                    'name' => 'Bank Soal Utama',
                    'created_by' => $u->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $bankId = $bank->id;
            }
            $data['bank_id'] = $bankId;
        }
        $this->owned('question_banks', $data['bank_id'], $u);
        return DB::transaction(function () use ($data, $u, $id) {
            $versionIfUsed = $data['version_if_used'] ?? false;
            unset($data['version_if_used']);
            $createdVersion = false;
            if ($id) {
                $this->owned('questions', $id, $u);
                DB::table('questions')->where('id', $id)->lockForUpdate()->first();
                $used = DB::table('quiz_questions')->where('question_id', $id)->exists() || DB::table('quiz_attempt_questions')->where('question_id', $id)->exists();
                abort_if($used && !$versionIfUsed, 422, 'Soal sudah digunakan. Buat soal baru untuk menjaga riwayat ujian.');
                if ($used) {
                    $id = null;
                    $createdVersion = true;
                }
            }
            $options = $data['options'];
            unset($data['options']);
            $correct = count(array_filter($options, function ($o) {
                return $o['is_correct']; }));
            if ($data['type'] === 'essay') {
                abort_if(count($options), 422, 'Essay tidak memiliki opsi.');
            } else {
                abort_if(count($options) < 2 || !$correct, 422, 'Minimal dua opsi dan satu jawaban benar.');
                abort_if($data['type'] !== 'multiple_answer' && $correct !== 1, 422, 'Pilih tepat satu jawaban benar.');
                abort_if($data['type'] === 'true_false' && count($options) !== 2, 422, 'Benar/salah harus memiliki dua opsi.');
            }
            $data['updated_at'] = now();
            if ($id) {
                DB::table('questions')->where('id', $id)->update($data);
                DB::table('question_options')->where('question_id', $id)->delete();
            } else {
                $id = DB::table('questions')->insertGetId($data + ['created_by' => $u->id, 'created_at' => now()]);
            }
            foreach ($options as $o) {
                DB::table('question_options')->insert(['question_id' => $id, 'text' => $o['text'], 'is_correct' => $o['is_correct']]);
            }
            return ['id' => $id, 'created_version' => $createdVersion];
        });
    }

    public function show(Request $r, $quiz)
    {
        $u = $this->user($r);
        $q = $this->service->quiz($quiz);
        $this->service->manage($u, $q);
        $q->audience = app(QuizAudience::class)->read($quiz);
        $q->class_ids = DB::table('quiz_classes')->where('quiz_id', $quiz)->pluck('class_id');
        $q->question_ids = DB::table('quiz_questions')->where('quiz_id', $quiz)->orderBy('position')->pluck('question_id');
        $q->question_explanations = DB::table('quiz_questions')->where('quiz_id', $quiz)->whereNotNull('explanation')->pluck('explanation', 'question_id');
        $q->overrides = DB::table('quiz_students')->where('quiz_id', $quiz)->get()->map(function ($o) { return $this->dates($o); });
        $q->has_attempts = DB::table('quiz_attempts')->where('quiz_id', $quiz)->exists();
        $q->course_locked = $q->has_attempts || (DB::getSchemaBuilder()->hasTable('course_details') && DB::table('course_details')->where('quiz_id', $quiz)->exists());
        return $this->dates($q);
    }

    public function save(Request $r, $quiz = null)
    {
        $u = $this->user($r);
        if (is_string($r->input('title'))) {
            $r->merge(['title' => trim($r->input('title'))]);
        }
        
        // Auto fallback dates if empty on draft save
        if ($r->boolean('no_schedule')) {
            // Keep legacy non-null date columns compatible; scheduling ignores these values.
            $r->merge(['available_from' => now()->toIso8601String(), 'available_until' => now()->addDays(30)->toIso8601String(), 'release_after_close' => false]);
        }
        if ($r->input('status') === 'DRAFT') {
            if (!$r->input('available_from')) {
                $r->merge(['available_from' => now()->toIso8601String()]);
            }
            if (!$r->input('available_until')) {
                $r->merge(['available_until' => now()->addDays(30)->toIso8601String()]);
            }
        }

        $rules = [
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:20000',
            'no_schedule' => 'sometimes|boolean',
            'course_id' => 'required|integer|exists:courses,id',
            'duration_minutes' => 'required|integer|min:1|max:1440',
            'passing_grade' => 'required|numeric|min:0|max:100',
            'maximum_attempt' => 'required|integer|min:0|max:100',
            'grading_method' => 'required|in:highest,latest,average,first',
            'question_count' => 'required|integer|min:1|max:500',
            'available_from' => 'required|date',
            'available_until' => 'required|date|after:available_from',
            'status' => 'required|in:DRAFT,PUBLISHED,CLOSED',
            'class_ids' => 'sometimes|array|max:0',
            'class_ids.*' => 'integer|distinct|exists:classes,id',
            'question_ids' => 'present|array|max:1000',
            'question_ids.*' => 'integer|distinct|exists:questions,id',
            'overrides' => 'present|array',
            'overrides.*.student_id' => ['bail', 'required', 'integer', 'distinct', app(EmployeeParticipants::class)->validationRule()],
            'overrides.*.additional_minutes' => 'required|integer|min:0|max:1440',
            'overrides.*.maximum_attempt' => 'nullable|integer|min:0|max:100',
            'overrides.*.available_from' => 'nullable|date',
            'overrides.*.available_until' => 'nullable|date',
        ];
        foreach (['randomize_question', 'randomize_answer', 'show_score', 'show_correct_answer', 'show_wrong_answer', 'show_explanation', 'show_total_correct', 'show_passing_status', 'release_after_close'] as $key) {
            $rules[$key] = 'required|boolean';
        }
        $data = $r->validate($rules, [
            'title.required' => 'Judul quiz wajib diisi.',
            'course_id.required' => 'Quiz wajib terikat dengan kelas. Buat quiz melalui materi kelas.',
            'title.max' => 'Judul quiz maksimal 150 karakter.',
            'class_ids.max' => 'Quiz menggunakan satu course. Pilih course, bukan daftar kelas.',
            'course_id.integer' => 'Pilih satu course yang valid.',
        ]);
        $audience = app(QuizAudience::class)->validate($r);
        return DB::transaction(function () use ($data, $quiz, $u, $audience) {
            if ($quiz) {
                $existing = DB::table('quizzes')->where('id', $quiz)->lockForUpdate()->first();
                abort_unless($existing, 404);
                $this->service->manage($u, $existing);
                abort_if(DB::table('quiz_attempts')->where('quiz_id', $quiz)->exists(), 422, 'Quiz sudah dikerjakan. Gunakan pengaturan hasil/override atau duplikasi quiz.');
                if ((int) $existing->course_id !== (int) ($data['course_id'] ?? null) && DB::getSchemaBuilder()->hasTable('course_details')) {
                    abort_if(DB::table('course_details')->where('quiz_id', $quiz)->exists(), 422, 'Quiz sudah dipasang pada course. Lepaskan dari materi course tersebut sebelum mengganti course.');
                }
            }
            $classes = [];
            $questions = $data['question_ids'];
            $overrides = $data['overrides'];
            unset($data['class_ids'], $data['question_ids'], $data['overrides']);
            foreach ($classes as $id) {
                $this->owned('classes', $id, $u);
            }
            foreach ($questions as $id) {
                $this->owned('questions', $id, $u);
                DB::table('questions')->where('id', $id)->lockForUpdate()->first();
            }
            $this->service->validatePassingGrade($data['passing_grade']);
            if ($data['status'] === 'PUBLISHED') {
                $issues = [];
                if (count($questions) < $data['question_count']) {
                    $issues['question_ids'] = 'Quiz membutuhkan '.$data['question_count'].' soal, tetapi baru '.count($questions).' soal dipilih. Lengkapi soal atau kurangi jumlah soal.';
                }
                if (empty($data['course_id'])) {
                    $issues['course_id'] = 'Pilih satu course sebelum menerbitkan quiz.';
                }
                if ($issues) { throw \Illuminate\Validation\ValidationException::withMessages($issues); }
            }
            foreach (['available_from', 'available_until'] as $key) {
                $data[$key] = Carbon::parse($data[$key])->setTimezone(config('app.timezone'))->toDateTimeString();
            }
            $data['updated_at'] = now();
            if ($quiz) {
                DB::table('quizzes')->where('id', $quiz)->update($data);
            } else {
                $quiz = DB::table('quizzes')->insertGetId($data + ['created_by' => $u->id, 'created_at' => now()]);
            }
            app(QuizAudience::class)->save($quiz, $audience);
            $explanations = DB::table('quiz_questions')->where('quiz_id', $quiz)->pluck('explanation', 'question_id');
            foreach (['quiz_classes', 'quiz_questions', 'quiz_students'] as $table) {
                DB::table($table)->where('quiz_id', $quiz)->delete();
            }
            foreach ($classes as $id) {
                DB::table('quiz_classes')->insert(['quiz_id' => $quiz, 'class_id' => $id]);
            }
            foreach ($questions as $i => $id) {
                DB::table('quiz_questions')->insert(['quiz_id' => $quiz, 'question_id' => $id, 'position' => $i, 'explanation' => $explanations[$id] ?? null]);
            }
            foreach ($overrides as $o) {
                $this->storeOverride($quiz, $o);
            }
            return ['id' => $quiz];
        }, 3);
    }

    private function storeOverride($quiz, $o)
    {
        $q = $this->service->quiz($quiz);
        foreach (['available_from', 'available_until'] as $key) {
            $o[$key] = empty($o[$key]) ? null : Carbon::parse($o[$key])->setTimezone(config('app.timezone'))->toDateTimeString();
        }
        $from = $o['available_from'] ?? (!empty($q->no_schedule) ? null : $q->available_from);
        $until = $o['available_until'] ?? (!empty($q->no_schedule) ? null : $q->available_until);
        abort_if($from && $until && !Carbon::parse($from)->lt(Carbon::parse($until)), 422, 'Periode override tidak valid.');
        DB::table('quiz_students')->updateOrInsert(['quiz_id' => $quiz, 'student_id' => $o['student_id']], collect($o)->only(['additional_minutes', 'maximum_attempt', 'available_from', 'available_until'])->all());
    }

    public function override(Request $r, $quiz)
    {
        $u = $this->user($r);
        $this->service->manage($u, $this->service->quiz($quiz));
        $o = $r->validate(['student_id' => ['bail', 'required', 'integer', app(EmployeeParticipants::class)->validationRule()], 'additional_minutes' => 'required|integer|min:0|max:1440', 'maximum_attempt' => 'nullable|integer|min:0|max:100', 'available_from' => 'nullable|date', 'available_until' => 'nullable|date']);
        $this->storeOverride($quiz, $o);
        return ['message' => 'Override berlaku untuk attempt berikutnya.'];
    }

    public function visibility(Request $r, $quiz)
    {
        $u = $this->user($r);
        $this->service->manage($u, $this->service->quiz($quiz));
        $rules = [
            'status' => 'sometimes|in:PUBLISHED,CLOSED',
            'maximum_attempt' => 'sometimes|required|integer|min:0|max:100',
            'overrides' => 'sometimes|array',
            'overrides.*.student_id' => ['bail', 'required', 'integer', 'distinct', app(EmployeeParticipants::class)->validationRule()],
            'overrides.*.additional_minutes' => 'required|integer|min:0|max:1440',
            'overrides.*.maximum_attempt' => 'nullable|integer|min:0|max:100',
            'overrides.*.available_from' => 'nullable|date',
            'overrides.*.available_until' => 'nullable|date',
        ];
        foreach (['show_score', 'show_correct_answer', 'show_wrong_answer', 'show_explanation', 'show_total_correct', 'show_passing_status', 'release_after_close'] as $k) {
            $rules[$k] = 'sometimes|boolean';
        }
        $data = $r->validate($rules);
        $audience = app(QuizAudience::class)->validate($r);
        return DB::transaction(function () use ($data, $quiz, $audience) {
            $existing = DB::table('quizzes')->where('id', $quiz)->lockForUpdate()->first();
            abort_if($existing->status === 'DRAFT', 422, 'Terbitkan quiz melalui editor.');
            if (($data['status'] ?? $existing->status) === 'PUBLISHED') {
                $this->service->validatePassingGrade($existing->passing_grade);
            }
            if (!empty($existing->no_schedule)) { $data['release_after_close'] = false; }
            $overrides = $data['overrides'] ?? [];
            unset($data['overrides']);
            DB::table('quizzes')->where('id', $quiz)->update($data + ['updated_at' => now()]);
            foreach ($overrides as $override) { $this->storeOverride($quiz, $override); }
            app(QuizAudience::class)->save($quiz, $audience);
            return ['message' => 'Pengaturan diperbarui.'];
        }, 3);
    }

    public function explanation(Request $r, $quiz, $question)
    {
        $user = $this->user($r);
        $this->service->manage($user, $this->service->quiz($quiz));
        $data = $r->validate(['explanation' => 'present|nullable|string|max:20000']);
        return DB::transaction(function () use ($quiz, $question, $data) {
            DB::table('quizzes')->where('id', $quiz)->lockForUpdate()->first();
            $link = DB::table('quiz_questions')->where('quiz_id', $quiz)->where('question_id', $question);
            abort_unless((clone $link)->exists(), 404);
            // Only this quiz's explanation changes; shared bank questions and scoring stay intact.
            $link->update(['explanation' => trim($data['explanation'] ?? '')]);
            return ['message' => 'Pembahasan soal disimpan.'];
        });
    }

    public function generate(Request $r)
    {
        $u = $this->user($r);
        $data = $r->validate(['bank_id' => 'nullable|integer|exists:question_banks,id', 'course_id' => 'nullable|integer|exists:courses,id', 'buckets' => 'required|array|min:1|max:50', 'buckets.*.difficulty' => 'nullable|in:easy,medium,hard', 'buckets.*.category' => 'nullable|string|max:255', 'buckets.*.count' => 'required|integer|min:1|max:500']);
        if (!empty($data['bank_id'])) { $this->owned('question_banks', $data['bank_id'], $u); }
        $ids = [];
        foreach ($data['buckets'] as $bucket) {
            $query = DB::table('questions')->when($u->role !== 'admin', function ($query) use ($u) { $query->where('created_by', $u->id); })->whereNotIn('id', $ids);
            if (!empty($data['bank_id'])) { $query->where('bank_id', $data['bank_id']); }
            if (!empty($data['course_id'])) {
                $query->where('course_id', $data['course_id']);
            }
            foreach (['difficulty', 'category'] as $key) {
                if (!empty($bucket[$key])) {
                    $query->where($key, $bucket[$key]);
                }
            }
            $chosen = $query->inRandomOrder()->limit($bucket['count'])->pluck('id')->all();
            abort_if(count($chosen) < $bucket['count'], 422, 'Bank soal tidak mencukupi konfigurasi.');
            $ids = array_merge($ids, $chosen);
        }
        abort_if(count($ids) > 500, 422, 'Maksimal 500 soal.');
        return ['question_ids' => $ids];
    }

    public function duplicate(Request $r, $quiz)
    {
        $u = $this->user($r);
        $q = $this->service->quiz($quiz);
        $this->service->manage($u, $q);
        return DB::transaction(function () use ($q, $u) {
            $data = (array) $q;
            unset($data['id']);
            $data['title'] = mb_substr($q->title, 0, 140) . ' (Salinan)';
            $data['status'] = 'DRAFT';
            $data['created_by'] = $u->id;
            $data['created_at'] = $data['updated_at'] = now();
            $id = DB::table('quizzes')->insertGetId($data);
            app(QuizAudience::class)->save($id, app(QuizAudience::class)->read($q->id));
            foreach (DB::table('quiz_questions')->where('quiz_id', $q->id)->get() as $item) {
                DB::table('quiz_questions')->insert(['quiz_id' => $id, 'question_id' => $item->question_id, 'position' => $item->position, 'explanation' => $item->explanation]);
            }
            return ['id' => $id];
        });
    }

    public function preview(Request $r, $quiz)
    {
        $u = $this->user($r);
        $q = $this->service->quiz($quiz);
        $this->service->manage($u, $q);
        $ids = DB::table('quiz_questions')->where('quiz_id', $quiz)->orderBy('position')->pluck('question_id');
        if ($q->randomize_question) {
            $ids = $ids->shuffle();
        }
        return [
            'title' => $q->title,
            'duration_minutes' => $q->duration_minutes,
            'questions' => $ids->take($q->question_count)->values()->map(function ($id, $i) use ($q) {
                $question = DB::table('questions')->find($id);
                $imageUrl = \App\Services\Quiz\QuestionImage::url($question->image_path);
                $options = DB::table('question_options')->where('question_id', $id)->get(['id', 'text']);
                return ['id' => $id, 'position' => $i + 1, 'question' => $question->question, 'image_url' => $imageUrl, 'type' => $question->type, 'options' => $q->randomize_answer ? $options->shuffle()->values() : $options, 'selected_options' => [], 'essay' => '', 'flagged' => false, 'visited' => false];
            })
        ];
    }

    public function replaceOneQuestion(Request $r)
    {
        $u = $this->user($r);
        $data = $r->validate([
            'exclude_ids' => 'present|array|max:1000', 'exclude_ids.*' => 'integer',
            'bank_id' => 'nullable|integer|exists:question_banks,id',
            'course_id' => 'nullable|integer|exists:courses,id',
            'category' => 'nullable|string|max:255', 'difficulty' => 'nullable|in:easy,medium,hard',
        ]);
        if (!empty($data['bank_id'])) { $this->owned('question_banks', $data['bank_id'], $u); }
        $query = DB::table('questions')->when($u->role !== 'admin', function ($q) use ($u) {
            $q->where('created_by', $u->id);
        })->whereNotIn('id', $data['exclude_ids']);
        foreach (['bank_id', 'course_id', 'category', 'difficulty'] as $filter) {
            if (!empty($data[$filter])) { $query->where($filter, $data[$filter]); }
        }
        $chosen = $query->inRandomOrder()->first();
        abort_if(!$chosen, 422, 'Belum ada soal lain dengan bank, kategori, dan tingkat kesulitan yang sama. Gunakan Pilih dari Bank Soal untuk memilih sendiri.');

        $chosen->options = DB::table('question_options')->where('question_id', $chosen->id)->get();
        $chosen->image_url = \App\Services\Quiz\QuestionImage::url($chosen->image_path);
        return ['question' => $chosen];
    }

    public function saveQuestionsBulk(Request $r)
    {
        $u = $this->user($r);
        $data = $r->validate([
            'bank_id' => 'nullable|integer|exists:question_banks,id',
            'course_id' => 'nullable|integer|exists:courses,id',
            'questions' => 'required|array|min:1|max:200',
            'questions.*.question' => 'required|string|max:20000',
            'questions.*.type' => 'required|in:multiple_choice,multiple_answer,true_false,essay',
            'questions.*.weight' => 'required|numeric|min:0.01|max:10000',
            'questions.*.difficulty' => 'sometimes|in:easy,medium,hard',
            'questions.*.options' => 'present|array|max:30',
            'questions.*.options.*.text' => 'required|string|max:5000',
            'questions.*.options.*.is_correct' => 'required|boolean',
        ]);

        $bankId = $data['bank_id'] ?? null;
        if ($bankId) { $this->owned('question_banks', $bankId, $u); }
        foreach ($data['questions'] as $index => $question) {
            $options = $question['options'];
            $correct = count(array_filter($options, function ($o) { return $o['is_correct']; }));
            $valid = $question['type'] === 'essay' ? !count($options) : count($options) >= 2 && $correct > 0
                && ($question['type'] === 'multiple_answer' || $correct === 1)
                && ($question['type'] !== 'true_false' || count($options) === 2);
            if (!$valid) {
                throw \Illuminate\Validation\ValidationException::withMessages(['questions.'.$index.'.options' => 'Pilihan dan kunci jawaban soal '.($index + 1).' tidak sesuai jenis soal.']);
            }
        }
        if (empty($bankId)) {
            $bank = DB::table('question_banks')->where('created_by', $u->id)->first();
            if (!$bank) {
                $bankId = DB::table('question_banks')->insertGetId([
                    'name' => 'Bank Soal Utama',
                    'created_by' => $u->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $bankId = $bank->id;
            }
        }

        return DB::transaction(function () use ($data, $bankId, $u) {
            $createdIds = [];
            foreach ($data['questions'] as $qItem) {
                $options = $qItem['options'];
                unset($qItem['options']);
                $qItem['bank_id'] = $bankId;
                $qItem['course_id'] = $data['course_id'] ?? null;
                $qItem['difficulty'] = $qItem['difficulty'] ?? 'medium';
                $qItem['created_by'] = $u->id;
                $qItem['created_at'] = now();
                $qItem['updated_at'] = now();

                $id = DB::table('questions')->insertGetId($qItem);
                foreach ($options as $opt) {
                    DB::table('question_options')->insert([
                        'question_id' => $id,
                        'text' => $opt['text'],
                        'is_correct' => $opt['is_correct'],
                    ]);
                }
                $createdIds[] = $id;
            }

            $results = DB::table('questions')->whereIn('id', $createdIds)->get()->map(function ($q) {
                $q->options = DB::table('question_options')->where('question_id', $q->id)->get();
                return $q;
            });

            return ['question_ids' => $createdIds, 'questions' => $results];
        });
    }
}

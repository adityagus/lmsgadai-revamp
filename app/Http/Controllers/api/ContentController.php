<?php

namespace App\Http\Controllers\api;

use App\Models\Course;
use App\Models\Content;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContentRequest;
use App\Http\Requests\UpdateContentRequest;

class ContentController extends Controller
{
    public function index(Request $request, $courseId)
    {
        app(\App\Services\CourseService::class)->assertAccessible((int) $courseId, $request);
        // Fetch contents related to the course ordered by sequence
        $contents = Content::where('course_id', $courseId)
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $contents->transform(function ($content) {
            $content->user_name = $content->user ? $content->user->name : 'Unknown';
            return $content;
        });

        return response()->json($contents);
    }
    
    public function getContentByCourseId($courseId)
    {
        app(\App\Services\CourseService::class)->assertAccessible((int) $courseId, request());
        $content = Content::where('course_id', $courseId)
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($content);
    }
    
    public function show($id)
    {
        $content = Content::find($id);
        if (!$content) {
            return response()->json(['message' => 'Content not Found'], 404);
        }
        app(\App\Services\CourseService::class)->assertAccessible((int) $content->course_id, request());
        if (\App\Services\RoleAccess::role(request()) === 'student') {
            app(\App\Services\CourseSequence::class)->assertUnlocked(
                (int) $content->course_id, (int) $content->id, (string) request()->session()->get('auth.user')
            );
        }
        
        $content->user_name = $content->user ? $content->user->name : 'Unknown';
        
        return response()->json($content);
    }

    
    public function store(StoreContentRequest $request){
      $validated = $request->validated();
      $validated = $this->linkQuiz($request, $validated);
      
      // Jika type == 'pdf' dan ada file, upload file dan simpan path-nya
      if ($request->get('type') === 'pdf' && $request->hasFile('content')) {
          $contentPath = Content::uploadContent($request->file('content'));
          $validated['content'] = $contentPath;
      }else if($request->get('type') === 'text' && $request->hasFile('lampiran')){
        $lampiranPath = Content::uploadLampiran($request->file('lampiran'));
        $validated['lampiran'] = $lampiranPath;
      }else{
        $validated['content'] = $request->get('content', null); 
      }

      if (!empty($validated['quiz_id'])) {
          $validated['content'] = null;
      }
      if (empty($validated['quiz_id']) || !\Illuminate\Support\Facades\Schema::hasColumn('course_details', 'quiz_id')) {
          unset($validated['quiz_id']);
      }
      $contents = Content::create($validated);
      app(\App\Services\Quiz\AttemptHistory::class)->content($contents);

      return response()->json([
        'message' => 'Content created successfully',
        'data' => $contents,
        'status' => 201
      ]);
    }
    
    public function update(UpdateContentRequest $request, $id)
    {
        // Debug: Log received data
        // \Log::info('Update Content Request:', $request->all());
        // \Log::info('Update Content ID:', $id);
        
        $content = Content::find($id);
        if (!$content) {
            return response()->json(['message' => 'Content not Found'], 404);
        }

        $validated = $request->validated();
        $validated = $this->linkQuiz($request, $validated, $content);

        // Jika type == 'pdf' dan ada file baru yang diunggah
        if ($request->get('type') === 'pdf' && $request->hasFile('content')) {
            $contentPath = Content::uploadContent($request->file('content'));
            $validated['content'] = $contentPath;
        } else if (!$request->has('content') || $request->input('content') === 'undefined' || $request->input('content') === 'null') {
            // Jangan hapus content lama jika pengguna tidak mengunggah file pdf baru
            unset($validated['content']);
        }

        // Jika ada lampiran file baru (untuk type == 'text')
        if ($request->hasFile('lampiran')) {
            $lampiranPath = Content::uploadContent($request->file('lampiran'));
            $validated['lampiran'] = $lampiranPath;
        }

        if (!empty($validated['quiz_id'])) {
            $validated['content'] = null;
        }
        if (empty($validated['quiz_id']) || !\Illuminate\Support\Facades\Schema::hasColumn('course_details', 'quiz_id')) {
            unset($validated['quiz_id']);
        }
        app(\App\Services\Quiz\AttemptHistory::class)->content($content);
        $content->update($validated);
        app(\App\Services\Quiz\AttemptHistory::class)->content($content);

        return response()->json([
            'message' => 'Content updated successfully',
            'data' => $content
        ]);
    }
    
    public function destroy($id)
    {
        $content = Content::find($id);
        if (!$content) {
            return response()->json(['message' => 'Content not Found'], 404);
        }
        
        if ($content->quiz_id) {
            $service = app(\App\Services\Quiz\QuizService::class);
            $service->manage($service->identity(request()), $service->quiz($content->quiz_id));
        }
        app(\App\Services\CourseService::class)->assertAccessible((int) $content->course_id, request());
        app(\App\Services\Quiz\AttemptHistory::class)->content($content);
        $content->delete();
        
        return response()->json([
            'message' => 'Content deleted successfully'
        ]);
    }
    private function linkQuiz($request, array $data, $existing = null)
    {
        $service = app(\App\Services\Quiz\QuizService::class);
        if ($existing && $existing->quiz_id) {
            $service->manage($service->identity($request), $service->quiz($existing->quiz_id));
        }
        $type = $data['type'] ?? ($existing->type ?? null);
        $id = array_key_exists('quiz_id', $data) ? $data['quiz_id'] : ($existing->quiz_id ?? null);
        if ($type !== 'quiz') { $id = null; }
        if ($id) {
            $quiz = $service->quiz($id);
            $service->manage($service->identity($request), $quiz);
            abort_unless((int) $quiz->course_id === (int) ($data['course_id'] ?? $existing->course_id), 422, 'Quiz harus berasal dari course yang sama.');
            $data['content'] = null;
        }
        $data['quiz_id'] = $id;
        return $data;
    }
}

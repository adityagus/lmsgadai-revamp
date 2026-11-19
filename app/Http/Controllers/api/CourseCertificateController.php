<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CourseService;
use App\Services\RoleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CourseCertificateController extends Controller
{
    private function completionStats(int $courseId, string $username): array
    {
        $total = (int) DB::table('course_details')
            ->where('course_id', $courseId)
            ->where('type', '!=', 'parent')
            ->count();

        $completedQuery = DB::table('course_content_progress as p')
            ->join('course_details as d', 'd.id', '=', 'p.content_id')
            ->where('p.course_id', $courseId)
            ->where('p.username', $username)
            ->where('d.type', '!=', 'parent')
            ->whereNotNull('p.completed_at');

        $completed = (int) (clone $completedQuery)->count();
        $completedAt = (clone $completedQuery)->max('p.completed_at');

        return [
            'total_contents' => $total,
            'completed_contents' => $completed,
            'is_complete' => $total > 0 && $completed >= $total,
            'completed_at' => $completedAt,
        ];
    }

    private function resolveTemplate(int $courseId): ?object
    {
        if (!Schema::hasTable('course_certificate_templates')) {
            return null;
        }

        $tpl = DB::table('course_certificate_templates')
            ->where('course_id', $courseId)
            ->orderByDesc('id')
            ->first();

        if (!$tpl) {
            $tpl = DB::table('course_certificate_templates')
                ->whereNull('course_id')
                ->orderByDesc('id')
                ->first();
        }

        return $this->formatTemplate($tpl);
    }

    private function formatTemplate(?object $tpl): ?object
    {
        if (!$tpl) {
            return null;
        }
        $tpl->file_url = url('/api/certificate-templates/' . $tpl->id . '/file');
        $tpl->show_number = (bool) $tpl->show_number;
        $tpl->show_date = (bool) $tpl->show_date;
        $tpl->name_x = (float) $tpl->name_x;
        $tpl->name_y = (float) $tpl->name_y;
        $tpl->number_x = (float) $tpl->number_x;
        $tpl->number_y = (float) $tpl->number_y;
        $tpl->date_x = (float) $tpl->date_x;
        $tpl->date_y = (float) $tpl->date_y;
        $tpl->font_size = (int) $tpl->font_size;
        return $tpl;
    }

    public function status(Request $request, int $id)
    {
        $course = Course::findOrFail($id);
        app(CourseService::class)->assertAccessible($id, $request);

        $username = (string) $request->session()->get('auth.user', '');
        $nama = trim((string) $request->session()->get('auth.nama', '')) ?: $username;
        $stats = $this->completionStats($id, $username);

        $certificate = Schema::hasTable('course_certificates')
            ? DB::table('course_certificates')
                ->where('course_id', $id)
                ->where('username', $username)
                ->first()
            : null;

        return response()->json([
            'course' => ['id' => $course->id, 'name' => $course->name],
            ...$stats,
            'default_recipient_name' => $nama,
            'certificate' => $certificate,
            'template' => $this->resolveTemplate($id),
        ]);
    }

    public function issue(Request $request, int $id)
    {
        $course = Course::findOrFail($id);
        app(CourseService::class)->assertAccessible($id, $request);

        $username = (string) $request->session()->get('auth.user', '');
        $nama = trim((string) $request->session()->get('auth.nama', '')) ?: $username;

        $stats = $this->completionStats($id, $username);
        abort_unless($stats['is_complete'], 422, 'Selesaikan seluruh materi kelas terlebih dahulu untuk mengunduh sertifikat.');

        $template = $this->resolveTemplate($id);

        $certificate = DB::transaction(function () use ($id, $username, $nama, $template) {
            $existing = DB::table('course_certificates')
                ->where('course_id', $id)
                ->where('username', $username)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                DB::table('course_certificates')
                    ->where('id', $existing->id)
                    ->update([
                        'download_count' => (int) $existing->download_count + 1,
                        'last_downloaded_at' => now(),
                        'updated_at' => now(),
                    ]);
                return DB::table('course_certificates')->find($existing->id);
            }

            $seq = (int) DB::table('course_certificates')->where('course_id', $id)->count() + 1;
            $number = sprintf('CERT/%d/%s/%04d', $id, now()->format('Y'), $seq);
            while (DB::table('course_certificates')->where('certificate_number', $number)->exists()) {
                $seq++;
                $number = sprintf('CERT/%d/%s/%04d', $id, now()->format('Y'), $seq);
            }

            $certId = DB::table('course_certificates')->insertGetId([
                'course_id' => $id,
                'username' => $username,
                'recipient_name' => $nama,
                'certificate_number' => $number,
                'template_id' => $template?->id,
                'issued_at' => now(),
                'download_count' => 1,
                'last_downloaded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return DB::table('course_certificates')->find($certId);
        });

        return response()->json([
            'course' => ['id' => $course->id, 'name' => $course->name],
            'certificate' => $certificate,
            'template' => $template,
        ]);
    }

    public function getTemplate(Request $request, int $id)
    {
        RoleAccess::assertLearningManager($request);
        $course = Course::findOrFail($id);

        $courseTemplate = Schema::hasTable('course_certificate_templates')
            ? $this->formatTemplate(DB::table('course_certificate_templates')->where('course_id', $id)->orderByDesc('id')->first())
            : null;

        $defaultTemplate = Schema::hasTable('course_certificate_templates')
            ? $this->formatTemplate(DB::table('course_certificate_templates')->whereNull('course_id')->orderByDesc('id')->first())
            : null;

        $issued = Schema::hasTable('course_certificates')
            ? DB::table('course_certificates')->where('course_id', $id)->orderByDesc('id')->get()
            : collect();

        return response()->json([
            'course' => ['id' => $course->id, 'name' => $course->name],
            'course_template' => $courseTemplate,
            'default_template' => $defaultTemplate,
            'issued_certificates' => $issued,
        ]);
    }

    public function saveTemplate(Request $request, int $id)
    {
        RoleAccess::assertLearningManager($request);
        Course::findOrFail($id);

        $data = $request->validate([
            'scope' => ['required', 'in:course,default'],
            'name' => ['nullable', 'string', 'max:150'],
            'file' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:5120'],
            'name_x' => ['required', 'numeric', 'min:0', 'max:100'],
            'name_y' => ['required', 'numeric', 'min:0', 'max:100'],
            'font_size' => ['required', 'integer', 'min:10', 'max:120'],
            'font_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'show_number' => ['required', 'boolean'],
            'number_x' => ['required', 'numeric', 'min:0', 'max:100'],
            'number_y' => ['required', 'numeric', 'min:0', 'max:100'],
            'show_date' => ['required', 'boolean'],
            'date_x' => ['required', 'numeric', 'min:0', 'max:100'],
            'date_y' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'file.mimes' => 'Template harus berformat PDF, PNG, atau JPG (Maks. 5MB).',
            'file.max' => 'Ukuran template maksimal 5MB.',
        ]);

        $targetCourseId = $data['scope'] === 'default' ? null : $id;
        $existing = DB::table('course_certificate_templates')
            ->where('course_id', $targetCourseId)
            ->orderByDesc('id')
            ->first();

        if (!$request->hasFile('file') && !$existing) {
            return response()->json([
                'message' => 'Silakan unggah file template sertifikat (PDF, PNG, atau JPG).',
            ], 422);
        }

        $filePath = $existing?->file_path;
        $fileType = $existing?->file_type;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension()));
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
            $filePath = $file->store('uploads/certificate-templates', 'public');
            $fileType = $ext;
        }

        $payload = [
            'course_id' => $targetCourseId,
            'name' => $data['name'] ?? ($targetCourseId ? 'Template Kelas #' . $id : 'Template Default'),
            'file_path' => $filePath,
            'file_type' => $fileType,
            'name_x' => $data['name_x'],
            'name_y' => $data['name_y'],
            'font_size' => $data['font_size'],
            'font_color' => $data['font_color'],
            'show_number' => (bool) $data['show_number'],
            'number_x' => $data['number_x'],
            'number_y' => $data['number_y'],
            'show_date' => (bool) $data['show_date'],
            'date_x' => $data['date_x'],
            'date_y' => $data['date_y'],
            'created_by' => (string) $request->session()->get('auth.user', ''),
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('course_certificate_templates')->where('id', $existing->id)->update($payload);
            $templateId = $existing->id;
        } else {
            $payload['created_at'] = now();
            $templateId = DB::table('course_certificate_templates')->insertGetId($payload);
        }

        return response()->json([
            'message' => 'Template sertifikat berhasil disimpan.',
            'template' => $this->formatTemplate(DB::table('course_certificate_templates')->find($templateId)),
        ]);
    }

    public function previewIssue(Request $request, int $id)
    {
        RoleAccess::assertLearningManager($request);
        $course = Course::findOrFail($id);

        $data = $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
        ], [
            'recipient_name.required' => 'Nama penerima sertifikat wajib diisi.',
        ]);

        return response()->json([
            'course' => ['id' => $course->id, 'name' => $course->name],
            'certificate' => [
                'recipient_name' => trim($data['recipient_name']),
                'certificate_number' => sprintf('CERT/%d/%s/0001', $id, now()->format('Y')),
                'issued_at' => now()->toIso8601String(),
            ],
            'template' => $this->resolveTemplate($id),
        ]);
    }

    public function streamTemplateFile(int $templateId)
    {
        $tpl = DB::table('course_certificate_templates')->find($templateId);
        abort_unless($tpl, 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($tpl->file_path), 404);

        $mime = match (strtolower((string) $tpl->file_type)) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        return response()->stream(function () use ($disk, $tpl) {
            $stream = $disk->readStream($tpl->file_path);
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

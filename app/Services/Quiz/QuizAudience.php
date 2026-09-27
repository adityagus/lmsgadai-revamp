<?php
namespace App\Services\Quiz;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuizAudience
{
    public function read($quiz): array
    {
        $rows = DB::table('quiz_access_rules')->where('quiz_id', $quiz)->get();
        return collect(['positions' => 'position', 'branches' => 'branch'])
            ->map(fn ($type) => $rows->where('type', $type)->pluck('value')->values()->all())->all();
    }

    public function validate(Request $request): ?array
    {
        if (!$request->exists('audience')) return null; // Older clients preserve existing restrictions.
        $data = $request->validate([
            'audience' => 'required|array:positions,branches',
            'audience.positions' => 'present|array|max:1000',
            'audience.positions.*' => 'string|distinct|max:100',
            'audience.branches' => 'present|array|max:1000',
            'audience.branches.*' => 'string|distinct|max:100',
        ])['audience'];
        $directory = app(AudienceDirectory::class);
        foreach (['positions', 'branches'] as $field) {
            if (!$data[$field]) continue;
            $known = array_map(fn ($item) => (string) $item->value, $directory->$field());
            // Keep saved inactive entries selectable, but reject new unknown codes.
            $quiz = $request->route('quiz');
            if ($quiz) $known = array_merge($known, $this->read($quiz)[$field]);
            validator(['audience' => $data], ["audience.$field.*" => [Rule::in($known)]])->validate();
        }
        return $data;
    }

    public function save($quiz, ?array $audience): void
    {
        if ($audience === null) return;
        DB::table('quiz_access_rules')->where('quiz_id', $quiz)->delete();
        foreach (['positions' => 'position', 'branches' => 'branch'] as $field => $type) {
            foreach ($audience[$field] as $value) {
                DB::table('quiz_access_rules')->insert(['quiz_id' => $quiz, 'type' => $type, 'value' => $value]);
            }
        }
    }

    public function allows($quiz, $user): bool
    {
        $audience = $this->read($quiz);
        foreach (['positions' => $user->position ?? null, 'branches' => $user->branch ?? null] as $field => $value) {
            if ($audience[$field] && ($value === null || !in_array((string) $value, $audience[$field], true))) return false;
        }
        return true;
    }
}

<?php
namespace Tests\Feature;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Services\LmsAccess;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_sign_in_is_a_public_inertia_page(): void
    {
        $this->get('/')->assertRedirect('/sign-in');
        $this->get('/sign-in')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/SignIn', false)->where('fullscreen', true));
        $this->get('/overview')->assertRedirect('/sign-in');
        $this->getJson('/api/courses')->assertUnauthorized();
    }

    public function test_all_migrated_pages_resolve_with_inertia_and_route_parameters(): void
    {
        $this->mock(LmsAccess::class, fn ($mock) => $mock->shouldReceive('role')->andReturn('admin'));
        $this->withSession(['auth' => ['user' => 'test-admin', 'status' => 'login', 'idgrup' => 'JBT-032']]);
        foreach (config('pages') as [$uri, $name, $component, $title, $access, $fullscreen]) {
            if ($component === 'Dashboard') continue;
            $url = '/'.preg_replace('/\{\w+\}/', '42', $uri);
            $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component($component, false)->where('routeContext.name', $name)->where('title', $title));
        }
        $this->get('/does-not-exist')->assertNotFound();
    }

    public function test_student_cannot_open_management_pages_or_mutate_courses(): void
    {
        $this->mock(LmsAccess::class, fn ($mock) => $mock->shouldReceive('role')->andReturn('student'));
        $this->withSession(['auth' => ['user' => 'student', 'status' => 'login']]);
        $this->get('/kelas')->assertForbidden();
        $this->get('/courses/create')->assertForbidden();
        $this->get('/report/documents')->assertForbidden();
        $this->postJson('/api/courses', [])->assertForbidden();
        $this->get('/student/kelas')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Student/courses/index', false));
    }

    public function test_public_course_details_keep_the_existing_preview_contract(): void
    {
        $course = (new \App\Models\Course)->forceFill(['id' => 42, 'name' => 'Kelas publik']);
        $this->mock(\App\Services\CourseService::class, fn ($mock) => $mock->shouldReceive('previewCourse')->once()->with(42)
            ->andReturn(['course' => $course, 'contents' => collect()]));
        $this->getJson('/api/preview-course/42')->assertOk()->assertJsonPath('course.name', 'Kelas publik');
    }

    public function test_dashboard_uses_database_counts_and_course_data(): void
    {
        \Illuminate\Support\Facades\Schema::create('categories', function ($table) { $table->id(); $table->string('name'); });
        \Illuminate\Support\Facades\Schema::create('courses', function ($table) {
            $table->id(); $table->string('name'); $table->unsignedBigInteger('category_id')->nullable(); $table->string('thumbnail')->nullable(); $table->timestamps();
        });
        \Illuminate\Support\Facades\Schema::create('documents', function ($table) {
            $table->id(); $table->string('title'); $table->unsignedBigInteger('submenu_id')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        \Illuminate\Support\Facades\DB::table('courses')->insert(['name' => 'Pelatihan baru', 'created_at' => now()]);
        $this->withSession(['auth' => ['user' => 'admin', 'status' => 'login', 'idgrup' => 'JBT-032']]);
        $this->get('/overview')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Dashboard', false)
            ->where('stats.courses', 1)->where('stats.documents', 0)->where('stats.categories', 0)->where('courses.0.name', 'Pelatihan baru'));
    }

    public function test_inertia_login_validates_password_and_redirects_on_success(): void
    {
        $this->mock(UserRepositoryInterface::class, fn ($mock) => $mock->shouldReceive('cekLogin')->twice()->andReturn(collect([
            (object) ['username' => 'tester', 'password_hash' => Hash::make('correct pass'), 'full_name' => 'Test User', 'position_code' => 'JBT-032'],
        ])));
        $this->from('/sign-in')->withHeader('X-Inertia', 'true')->post('/login', ['user' => 'tester', 'pass' => 'wrong'])
            ->assertRedirect('/sign-in')->assertSessionHasErrors('user')->assertSessionMissing('auth');
        $this->post('/login', ['user' => 'tester', 'pass' => 'correct pass'])
            ->assertRedirect('/overview')->assertSessionHas('auth.user', 'tester');
        $this->post('/logout')->assertRedirect('/sign-in')->assertSessionMissing('auth');
    }
}

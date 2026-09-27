<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MaintenancePageTest extends TestCase
{
    public function test_estimate_is_read_again_when_database_changes()
    {
        DB::shouldReceive('table')->with('maintenance_settings')->twice()->andReturnSelf();
        DB::shouldReceive('where')->with('id', 1)->twice()->andReturnSelf();
        DB::shouldReceive('value')->with('estimated_hours')->twice()->andReturn('2.00', '3.50');

        $this->assertStringContainsString('Sekitar 2 jam', view('errors.503')->render());
        $this->assertStringContainsString('Sekitar 3,5 jam', view('errors/503')->render());
    }

    public function test_error_response_still_renders_when_database_is_unavailable()
    {
        DB::shouldReceive('table')->andThrow(new \RuntimeException('Database unavailable'));
        $request = \Illuminate\Http\Request::create('/');
        $response = app(\Illuminate\Contracts\Debug\ExceptionHandler::class)->render(
            $request, new \Symfony\Component\HttpKernel\Exception\HttpException(503)
        );

        $this->assertSame(503, $response->getStatusCode());
        $this->assertStringContainsString('Belum tersedia', $response->getContent());
    }

    public function test_unknown_or_invalid_estimates_do_not_display_a_duration()
    {
        DB::shouldReceive('table')->times(3)->andReturnSelf();
        DB::shouldReceive('where')->with('id', 1)->times(3)->andReturnSelf();
        DB::shouldReceive('value')->with('estimated_hours')->times(3)->andReturn(null, 0, -2);

        for ($i = 0; $i < 3; $i++) {
            $this->assertStringContainsString('Belum tersedia', view('errors.503')->render());
        }
    }
}

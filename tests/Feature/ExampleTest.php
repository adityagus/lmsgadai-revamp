<?php
namespace Tests\Feature;
use Tests\TestCase;
class ExampleTest extends TestCase
{
    public function test_landing_redirects_to_sign_in(): void
    {
        $this->get('/')->assertRedirect('/sign-in');
    }
}

<?php

namespace Tests\Feature;

use App\Contracts\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_employee_bcrypt_2a_hash_accepts_only_the_correct_password()
    {
        $employeeHash = preg_replace('/^\$2y\$/', '\$2a\$', password_hash('employee password', PASSWORD_BCRYPT));
        $this->mock(UserRepositoryInterface::class, function ($mock) use ($employeeHash) {
            $mock->shouldReceive('cekLogin')->twice()->andReturn(collect([
                (object) ['username' => 'employee', 'password_hash' => $employeeHash, 'full_name' => 'Employee'],
            ]));
        });
        $this->postJson('/login', ['user' => 'employee', 'pass' => 'wrong password'])
            ->assertOk()->assertJsonPath('success', false)->assertSessionMissing('auth');
        $this->postJson('/login', ['user' => 'employee', 'pass' => 'employee password'])
            ->assertOk()->assertJsonPath('success', true)->assertSessionHas('auth.user', 'employee');
    }

    public function test_guest_can_check_session_without_an_error()
    {
        $this->getJson('/session')->assertOk()->assertExactJson(['auth' => null]);
        $this->getJson('/api/v1/session')->assertOk()->assertExactJson(['auth' => null]);
        $this->getJson('/wizard/session')->assertUnauthorized();
    }

    public function test_login_persists_session_and_preserves_password_spaces()
    {
        $this->mock(UserRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('cekLogin')->once()->with('login-test')->andReturn(collect([
                (object) [
                    'username' => 'login-test',
                    'password_hash' => Hash::make(' password with spaces '),
                    'full_name' => 'Login Test',
                    'position_code' => 'JBT-032',
                ],
            ]));
        });

        $this->postJson('/login', ['user' => 'login-test', 'pass' => ' password with spaces '])
            ->assertOk()->assertJsonPath('success', true)
            ->assertSessionHas('auth.user', 'login-test');
        $this->getJson('/session')->assertOk()->assertJsonPath('auth.user', 'login-test');
        $this->postJson('/logout');
        $this->getJson('/session')->assertOk()->assertJsonPath('auth', null);
    }

    public function test_invalid_credentials_do_not_create_a_session()
    {
        $this->mock(UserRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('cekLogin')->once()->andReturn(collect([
                (object) ['username' => 'login-test', 'password_hash' => Hash::make('correct-password')],
            ]));
        });
        $this->postJson('/login', ['user' => 'login-test', 'pass' => 'wrong-password'])
            ->assertOk()->assertJsonPath('success', false)->assertSessionMissing('auth');
    }

    public function test_csrf_token_endpoint_returns_a_token()
    {
        $this->getJson('/csrf-token')->assertOk()->assertJsonStructure(['csrf_token']);
    }
}

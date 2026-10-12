<?php

namespace Tests;

use Illuminate\Support\Facades\DB;

trait EmployeeDatabase
{
    private function createEmployeeDatabase(): void
    {
        config(['database.connections.db2' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('db2');
        $db = DB::connection('db2');
        $db->statement("ATTACH DATABASE ':memory:' AS auth");
        $db->statement("ATTACH DATABASE ':memory:' AS master");
        $db->statement('CREATE TABLE auth.users (username TEXT, full_name TEXT, employee_id INTEGER, is_active INTEGER, password_hash TEXT)');
        $db->statement('CREATE TABLE master.employee (employee_id INTEGER, position_id INTEGER)');
        $db->statement('CREATE TABLE master.position (position_id INTEGER, position_code TEXT)');
        $db->table('master.position')->insert([
            ['position_id' => 1, 'position_code' => 'STAFF'],
            ['position_id' => 2, 'position_code' => 'JBT-TRAINER'],
            ['position_id' => 3, 'position_code' => 'JBT-032'],
        ]);
        foreach (['trainer', 'budi', 'other', 'another-trainer', 'administrator', 'fixture', 'new-employee', 'inactive'] as $index => $username) {
            $db->table('master.employee')->insert(['employee_id' => $index + 1, 'position_id' => $username === 'administrator' ? 3 : (str_contains($username, 'trainer') ? 2 : 1)]);
            $db->table('auth.users')->insert(['username' => $username, 'full_name' => ucfirst($username).' Karyawan', 'employee_id' => $index + 1, 'is_active' => $username !== 'inactive', 'password_hash' => 'never-expose']);
        }
    }
}

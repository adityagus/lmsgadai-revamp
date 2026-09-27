<?php

namespace App\Contracts\Repositories;

interface UserRepositoryInterface
{
    /**
     * Get user details for login verification by username.
     *
     * @param string $username
     * @return \Illuminate\Support\Collection
     */
    public function cekLogin(string $username);
    public function getUserRole(string $username);
    public function getUserProfile(string $username);
}

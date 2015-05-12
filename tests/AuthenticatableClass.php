<?php

namespace Journey\Tests;

use Journey\Authenticatable;

class AuthenticatableClass implements Authenticatable
{

    /**
     * Provide a list of users
     */
    public function getUsers()
    {
        return [
            [
                'username' => 'testuser',
                'password' => md5('testpassword'),
                'level' => 2
            ]
        ];
    }


    /**
     * Required method by authenticatable
     */
    public function authenticate($username, $password)
    {
        foreach ($this->getUsers() as $user) {
            if ($username == $user['username'] && md5($password) == $user['password']) {
                return $user;
            }
        }
        return true;
    }
}

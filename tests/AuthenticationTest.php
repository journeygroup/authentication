<?php

namespace Journey\Tests;

use PHPUnit_Framework_TestCase;
use Journey\Authentication;

class AuthenticationTest extends PHPUnit_Framework_TestCase
{

    /**
     * Tests that array user sets load and authenticate properly
     */
    public function testArrayUsers()
    {
        $auth = new Authentication([
            'users' => array(
                array(
                    'username' => 'testuser',
                    'password' => md5('testpassword'),
                    'level' => 2
                )
            )
        ]);

        $return = $auth->authenticate('testuser', 'testpassword');

        $this->assertArrayHasKey('username', $return);
        $this->assertEquals('testuser', $return['username']);
    }



    /**
     * Tests that ini user sets load and authenticate properly
     */
    public function testIniUsers()
    {
        $auth = new Authentication([
            'users' => __DIR__ . "/users/users.ini"
        ]);

        $return = $auth->authenticate('testuser', 'testpassword');
        
        $this->assertArrayHasKey('username', $return);
        $this->assertEquals('testuser', $return['username']);
    }



    /**
     * Tests that csv user sets load and authenticate properly
     */
    public function testCsvUsers()
    {
        $auth = new Authentication([
            'users' => __DIR__ . "/users/users.csv"
        ]);

        $return = $auth->authenticate('testuser', 'testpassword');
        
        $this->assertArrayHasKey('username', $return);
        $this->assertEquals('testuser', $return['username']);
    }



    /**
     * Tests that csv user sets (out of order) load and authenticate properly
     */
    public function testCsvUsersFlipped()
    {
        $auth = new Authentication([
            'users' => __DIR__ . "/users/users-flipped.csv",
            'columns' => ['level', 'username', 'password']
        ]);

        $return = $auth->authenticate('testuser', 'testpassword');
        
        $this->assertArrayHasKey('username', $return);
        $this->assertEquals('testuser', $return['username']);
    }



    /**
     * Tests that salts to modify passwords
     */
    public function testSalts()
    {
        $auth = new Authentication([
            'users' => array(
                array(
                    'username' => 'testuser',
                    'password' => md5('testpassword'),
                    'level' => 2
                )
            ),
            'salt' => 'xyz',
        ]);

        $this->assertEquals($auth->authenticate('testuser', 'testpassword'), false);
    }



    /**
     * Tests if static calls work to private functions
     */
    public function testStaticChecks()
    {
        Authentication::config([
            'users' => array(
                array(
                    'username' => 'testuser',
                    'password' => md5('testpassword'),
                    'level' => 2
                )
            )
        ], true);

        $auth = new Authentication();
        $this->assertArrayHasKey('username', $auth->authenticate('testuser', 'testpassword'));

        $this->assertEquals(false, Authentication::isAtLeast(3));
        $this->assertEquals(true, Authentication::isAtLeast(2));
    }
}

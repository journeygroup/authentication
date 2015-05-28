<?php

namespace Journey\Tests;

use PHPUnit_Framework_TestCase;
use Journey\Authentication;
use PDO;
use Exception;

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



    /**
     * Tests if the string level mapping for permissions works
     */
    public function testLevelMap()
    {
        Authentication::config([
            'levels' => ['un-authenticated', 'user', 'editor']
        ]);

        $auth = new Authentication();
        $auth->authenticate('testuser', 'testpassword');

        $this->assertEquals(true, Authentication::is('editor'));
    }



    /**
     * Tests if using classes that implement Authenticatable work
     */
    public function testAuthenticatableClass()
    {
        Authentication::config([
            'users' => new AuthenticatableClass()
        ], true);

        $auth = new Authentication();
        $this->assertArrayHasKey('username', $auth->authenticate('testuser', 'testpassword'));
        $this->assertEquals(true, $auth::is(2));
    }


    /**
     * Tests a pdo statement as the user list
     */
    public function testPDOStatement()
    {
        $location = __DIR__ . "/users/users.db";
        $db = new PDO('sqlite:' . $location);
        Authentication::config([
            'users' => $db->query('SELECT * FROM users')
        ], true);

        $auth = new Authentication();
        $this->assertArrayHasKey('username', $auth->authenticate('testuser', 'testpassword'));
        $this->assertEquals(true, $auth::is(2));
    }



    /**
     * Test an authentication failure and restriction
     */
    public function testRestrict()
    {
        $location = __DIR__ . "/users/users.db";
        $db = new PDO('sqlite:' . $location);
        Authentication::config([
            'users' => $db->query('SELECT * FROM users')
        ], true);

        $auth = new Authentication([
            'block' => function ($auth) {
                throw new Exception('Access was restricted', 403);
            }
        ]);

        $auth->authenticate('testuser', 'testpassword');
        try {
            Authentication::restrict(3);
        } catch (Exception $e) {
            $this->assertEquals(403, $e->getCode());
            return true;
        }
        $this->fail('Failed to restrict access');
    }
}

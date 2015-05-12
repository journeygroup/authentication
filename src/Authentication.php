<?php

namespace Journey;

use Exception;
use League\Csv\Reader;
use PDO;
use PDOStatement;

class Authentication implements Authenticatable
{
    private $config;

    private $users;

    private $authenticatable;

    private $level;

    private $user;

    /**
     * Construct the authentication
     */
    public function __construct($config = array(), $static = false)
    {
        if (!$static) {
            static::factory(null, $this);
        }

        if (php_sapi_name() != 'cli' && session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $this->config = static::config($config);

        if (!isset($this->config['users'])) {
            throw new AuthenticationException('User configuration for authentication is required, none set');
        }

        if (!$this->authenticatable instanceof Authenticatable) {
            $this->authenticatable = $this;
        }
    }



    /**
     * Get the user set
     * @return Array  an array of users
     */
    public function getUsers()
    {
        if (!$this->users) {
            $this->loadUsers();
        }
        return $this->users;
    }



    /**
     * Login a particular user with a username and password
     * @return boolean  true or false if login was successful
     */
    public function authenticate($username, $password)
    {
        $config = $this->config;
        $users = $this->getUsers();

        if ($this->authenticatable == $this) {
            foreach ($users as $user) {
                $hash = $config['hash']($password . $config['salt']);
                if ($user['username'] == $username && $user['password'] == $hash) {
                    $this->user = $user;
                    break;
                }
            }
        } else if ($this->authenticatable instanceof Authenticatable) {
            $this->user = $this->authenticatable->authenticate($username, $password);
        }

        if ($this->user && php_sapi_name() != 'cli') {
            $_SESSION['user'] = $this->user;
        }

        return ($this->user) ? $this->user:false;
    }



    /**
     * Get the level of a particular string
     * @param  String $level reverse maps the string to an integer level
     * @return Int           returns an integer level
     */
    public function mapLevel($level)
    {
        if (is_int($level)) {
            return $level;
        } else if (is_numeric($level)) {
            return (int) $level;
        } else if (is_string($level)) {
            $level = array_search($level, $this->config['levels']);
            if ($level !== false) {
                return $level;
            }
        }
        throw new AuthenticationException('Unable to determine requested level: ' . $level);
    }



    /**
     * Checks if the current user is at least a given level
     * @param  Mixed   $level An integer level or string level
     * @return boolean
     */
    private function isAtLeast($level)
    {
        $level = $this->mapLevel($level);
        if (isset($this->user)) {
            if ($this->user['level'] >= $level) {
                return true;
            }
        }

        return false;
    }


    /**
     * Checks if the current user is exactly equal to a certain level 
     * @param  Mixed   $level Either a string level, or integer
     * @return boolean
     */
    private function is($level)
    {
        return $this->mapLevel($level) == $this->user['level'];
    }



    /**
     * Restrict access to code from this point in the runtime forward
     * @param  Mixed  $level String or integer level
     * @return void
     */
    private function restrict($level)
    {
        if ($this->mapLevel($level) >= $this->user['level']) {
            $this->config['block']($this);
        }
    }



    /**
     * Load a list of valid users from the configuration.
     * @return void
     */
    public function loadUsers()
    {
        $users = $this->config['users'];

        # If we are dealing with a list of users in array format
        if (is_array($users)) {
            $this->users = $users;
            return true;

        # If we are dealing with a filename
        } else if (is_string($users) && file_exists($users)) {
            $type = pathinfo($users, PATHINFO_EXTENSION);
            
            switch ($type) {
                
                // parse a php ini file
                case "ini":
                    $users = parse_ini_file($users, true);
                    for ($i = 0; $i < count($users['username']); $i++) {
                        $this->users[] = array(
                            'username' => $users['username'][$i],
                            'password' => $users['password'][$i],
                            'level' => $users['level'][$i]
                        );
                    }
                    return true;

                // parse a csv file
                case "csv":
                    if (! ini_get("auto_detect_line_endings")) {
                        ini_set("auto_detect_line_endings", '1');
                    }

                    $csv = Reader::createFromPath($users, 'r');
                    $users = $csv->fetchAll();

                    $keys = (isset($this->config['columns'])) ? $this->config['columns']:['username', 'password', 'level'];
                    foreach ($users as $user) {
                        $this->users[] = array_combine($keys, $user);
                    }
                    return true;
            }

        # If users is a database result, fetch all the results
        } else if ($users instanceof PDOStatement) {
            $this->users = $users->fetchAll(PDO::FETCH_ASSOC);
            return true;
        # If users is an instance of Authenticatable, use that instance
        } else if ($users instanceof Authenticatable) {
            $this->authenticatable = $users;
            return true;
        }
        
        throw new AuthenticationException('Unable to load the user set, read the documentation for supported user sets');
    }



    /**
     * Configure the authentication system
     * @param  [type] $options [description]
     * @return [type]          [description]
     */
    public static function config($options = array(), $reset = false)
    {
        static $config;

        if (!$config || $reset) {
            $config = [
                'users'   => null,
                'salt'    => null,
                'hash'    => function ($password) {
                    return md5($password);
                },
                'block'   => function () {
                    header('location: /login');
                    die();
                }
            ];
        }

        $config = array_replace_recursive($config, $options);
        return $config;
    }



    /**
     * Instance production factory
     * @return Authentication  returns the proper authentication object
     */
    public static function factory($config = array(), $staticInstance = null)
    {
        static $instance;

        if (isset($staticInstance)) {
            $instance = $staticInstance;
        } else {
            if (!$instance) {
                $instance = new static($config, true);
            }
        }

        return $instance;
    }



    /**
     * Static mapping to methods (maps PDO methods directly)
     * @param String $method    Method name to pass to PDO
     * @param Array  $argyments Array of arguments to pass to the method
     */
    public static function __callStatic($method, $arguments)
    {
        $instance = static::factory();
        return call_user_func_array([$instance, $method], $arguments);
    }
}

Authentication
--------------

[![Build Status](https://travis-ci.org/journeygroup/authentication.svg?branch=master)](https://travis-ci.org/journeygroup/authentication)

# Why

Frequently micro frameworks require a small user base, whether for administration settings or restricting access to content, this Authentication class exists to allow micro framework authors to spend no more than a few seconds setting up an authentication system.

# Usage

### Installation

To add Authentication to your project, just use composer:

    composer require journey/authentication dev-master


### Configuration

The easiest way to configure the authentication module is in your project's bootstrap file:

```php
# bootstrap.php

Journey\Authentication::config([
    'users' => array( ... )         # (required) See details below
]);
```

In the above example, those configuration options would be set for all instances of Authentication called through the runtime. There are several different configuration options that allow a great level of flexibility and ease of use:

Option | Default            | Description
-------|--------------------|--------------------------------------------------------------------------
users  | `null`             | ***Required*** See the user list configuration options below
salt   | `null`             | A random string your passwords are salted with
hash   | `md5()`            | A Callable that returns a hashed password (by default simply uses md5())
block  | redirect           | A Callable responsible for blocking access when called
columns| `null`             | Column keys to apply to un-keyed data types (currently only csv). While there is technically no default, the system implicitly uses the order: `['username', 'password', 'level']`
levels | `null`             | A numeric index of human readable names to assign your permission levels (something like: `['user', 'editor', 'developer'];`)

### User List

The configuration option `users` allows you to provide a list of valid users to authenticate against. All lists require three parameters for each user `username`, `password`, and `level`, where the password is a valid hash. The list can be provided though a number of flexible methods:

#### Array

The simplest method for providing a user list is an explicit array. A sequential array containing arrays of users.

```php
# bootstrap.php
$users = [
    [
        'username' => 'some-username',                      # a username
        'password' => '5f4dcc3b5aa765d61d8327deb882cf99',   # md5 hash of of the password
        'level'    => 1                                     # permission level
    ],
    [
        'username' => 'another-user',
        'password' => '48cccca3bab2ad18832233ee8dff1b0b',
        'level'    => 1
    ]
];

Journey\Authentication::config([
    'users' => $users
]);
```

#### Comma Separated Values

The user list can be provided as a path to a .csv file. 

```php
# bootstrap.php
$users = 'path/to/users.csv';

Journey\Authentication::config([
    'users' => $users
]);
```

```
# users.csv
some-username,5f4dcc3b5aa765d61d8327deb882cf99,1
another-user,48cccca3bab2ad18832233ee8dff1b0b,1
```

*Note: because csv files lack keys, it is expected they will be in the order `username`, `password`, `level`. If they aren't you may provide a secondary configuration option `columns` which expects an array containing the three required keys in the the order they are used in the csv.*

#### Initialization File (.ini)

A user list could also be a simple .ini file.


```php
# bootstrap.php
$users = 'path/to/users.ini';

Journey\Authentication::config([
    'users' => $users
]);
```

```
# users.ini
username[] = some-username
password[] = 5f4dcc3b5aa765d61d8327deb882cf99
level[]    = 1

username[] = another-user
password[] = 48cccca3bab2ad18832233ee8dff1b0b
level[]    = 1
```


#### Database

A PDOStatement may also provide the user list. The statement should represent the entire table of users, and of course, contain the columns `username`, `password`, and `level`.

```php
# MyLogic.php

use Journey\Authentication;
use PDO;

class MyLogic
{
    public function __construct()
    {
        $db = new PDO("sqlite: /path/to/database.db");
                
        Authentication::config([
            'users' => $db->query('SELECT * FROM users')
        ]);
    }
}
```


#### Authenticatable

The most robust option is to provide an object which implements the [Authenticatable interface](src/Authenticatable.php). This delegates control of the user list and user-lookup to your own external class.

```php
# MyAuthenticator.php

use Journey\Authenticatable;

class MyAuthenticator implements Authenticatable
{
    public function authenticate($username, $password)
    {
        $users = $this->getUsersHoweverIWant();
        foreach ($users as $user) {
            if ($user['username'] == $username && $password == $password) {
                return $user;   # returned user must be an array containing username, password, and level
            }
        }
        return false;
    }
    ...
}
```


```php
# bootstrap.php

Journey\Authentication::config([
    'users' => new MyAuthenticatable()
]);
```

*Note: When providing an Authenticatable class rather than a user list, the `salt` and `hash` configuration properties will not be used. It is up to your class to provide the user list, and validate usernames and passwords against it.*


### Authenticating Users

Once your users have been configured, actually authenticating is easy-peasy. There are four frequently used methods `authenticate()`, `restrict()`, `isAtLeast()`, and `is()`. Before a user's permissions can be checked they must be `authenticated` or logged in:

```php
# login.php
...

use Journey\Authentication;

$auth = new Authentication();
if ($auth->authenticate($_POST['username'], $_POST['password'])) {
    echo "You're logged in!";
} else {
    echo "Woops. Bad username or password";
}
```

Once a user has been authenticated, a browser session will be set to keep them logged in. On the command line, they will stay authenticated for the remainder of the runtime. After authentication, restricting access only requires a call to `restrict()`.

```php
# sensitive.php

use Journey\Authentication;

class MySensitiveThings
{
    public function __construct()
    {
        Authenticate::restrict(1);
    }
}
```

If the `restrict()` method fails, they application _will_ die to prevent further execution. The configuration option `block` (a Callable) will be called before the die() command is issued (by default `block` contains a redirect to `GET /login`). To check access without killing the application, use `isAtLeast()` or `is()` which only return boolean values.

*Note: All three access control methods also accept a level map string from the configuration file like: `Authentication::isAtLeast('editor');`*

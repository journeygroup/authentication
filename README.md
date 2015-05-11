Authentication
--------------

# Why

Frequently micro frameworks require a small user base, whether for administration settings or restricting access to content, this Authentication class exists to allow micro framework authors to spend no more than a few seconds setting up an authentication system.

# Usage

### Installation

To add Authentication to your project, just use composer:

    composer require journeygroup/authentication @dev-master


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
users  | `null`             | ***Required*** See the user list configuration options
salt   | `null`             | A random string your passwords are salted with
hash   | `md5()`            | A Callable that returns a hashed password (by default simply uses md5())
block  | redirect to /login | C Callable responsible for blocking access when called
column | `null`             | Column keys to apply to unstructured data types (currently only csv). While there is technically no default, the system implicitly uses the order: `['username', 'password', 'level']`

... more soon

<?php

namespace Journey;

interface Authenticatable
{
    public function authenticate($username, $password);
}

<?php

namespace Javaabu\Stats\Tests\TestSupport\Models;

/**
 * A second user model, for the web_admin guard
 */
class Admin extends User
{
    protected $table = 'users';
}

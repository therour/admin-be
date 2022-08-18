<?php

namespace Tests;


abstract class ApiTestCase extends TestCase
{
    /**
     * Additional headers for the request.
     *
     * @var array
     */
    protected $defaultHeaders = [
        'user-agent' => 'phpunit',
    ];
}
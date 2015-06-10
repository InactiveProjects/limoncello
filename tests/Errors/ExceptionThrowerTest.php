<?php namespace Neomerx\Tests\Limoncello\Errors;

/**
 * Copyright 2015 info@neomerx.com (www.neomerx.com)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use \Neomerx\Tests\Limoncello\BaseTestCase;
use \Neomerx\Limoncello\Errors\ExceptionThrower;
use \Neomerx\JsonApi\Contracts\Integration\ExceptionThrowerInterface;

/**
 * @package Neomerx\Tests\Limoncello
 */
class ExceptionThrowerTest extends BaseTestCase
{
    /**
     * @var ExceptionThrowerInterface
     */
    private $thrower;

    /**
     * Set up test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->thrower = new ExceptionThrower();
    }

    /**
     * Test throw exception.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testThrowBadRequest()
    {
        $this->thrower->throwBadRequest();
    }

    /**
     * Test throw exception.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     */
    public function testNotAcceptableHttp()
    {
        $this->thrower->throwNotAcceptable();
    }

    /**
     * Test throw exception.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException
     */
    public function testUnsupportedMediaTypeHttp()
    {
        $this->thrower->throwUnsupportedMediaType();
    }

    /**
     * Test throw exception.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testAccessDenied()
    {
        $this->thrower->throwForbidden();
    }

    /**
     * Test throw exception.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\ConflictHttpException
     */
    public function testConflict()
    {
        $this->thrower->throwConflict();
    }
}

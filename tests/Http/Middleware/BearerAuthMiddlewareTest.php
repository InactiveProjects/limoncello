<?php namespace Neomerx\Tests\Limoncello\Http\Middleware;

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

use \Closure;
use \Mockery;
use \Neomerx\Tests\Limoncello\BaseTestCase;
use \Symfony\Component\HttpFoundation\Request;
use \Neomerx\Limoncello\Http\FrameworkIntegration;
use \Neomerx\Limoncello\Contracts\IntegrationInterface;
use \Neomerx\Limoncello\Http\Middleware\BearerAuthMiddleware;

/**
 * @package Neomerx\Tests\Limoncello
 */
class BearerAuthMiddlewareTest extends BaseTestCase
{
    /**
     * @var FrameworkIntegration
     */
    private $integration;

    /**
     * Set up test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->integration = Mockery::mock(IntegrationInterface::class);
    }

    /**
     * Test login.
     */
    public function testAuthentication()
    {
        // Preparation
        $isAuthCalled = false;
        $isNextCalled = false;

        $token = 'some-token';

        $middleware = new BearerAuthMiddleware(
            $this->integration,
            $this->getAuthenticatedClosure($token, $isAuthCalled)
        );

        // Test
        $middleware->handle(
            $this->createRequest($token),
            $this->getNextHandlerClosure($isNextCalled)
        );

        // Check
        $this->assertTrue($isAuthCalled);
        $this->assertTrue($isNextCalled);
    }

    /**
     * @param bool &$isCalled
     *
     * @return Closure
     */
    private function getNextHandlerClosure(&$isCalled)
    {
        return function () use (&$isCalled) {
            $isCalled = true;
        };
    }

    /**
     * @param string  $expectedToken
     * @param bool   &$authenticated
     *
     * @return Closure
     */
    private function getAuthenticatedClosure($expectedToken, &$authenticated)
    {
        return function ($actualToken) use ($expectedToken, &$authenticated) {
            $this->assertEquals($expectedToken, $actualToken);

            $authenticated = true;

            return true;
        };
    }

    /**
     * @param string $token
     *
     * @return Request
     */
    private function createRequest($token)
    {
        $headers = [
            'HTTP_' . BearerAuthMiddleware::HEADER_AUTHORIZATION =>
                BearerAuthMiddleware::AUTHENTICATION_SCHEME . ' ' . $token,
        ];

        return new Request([], [], [], [], [], $headers);
    }
}

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
use \Mockery\MockInterface;
use \Neomerx\Tests\Limoncello\BaseTestCase;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Neomerx\Limoncello\Http\FrameworkIntegration;
use \Neomerx\Limoncello\Contracts\IntegrationInterface;
use \Neomerx\Limoncello\Http\Middleware\BasicAuthMiddleware;

/**
 * @package Neomerx\Tests\Limoncello
 */
class BasicAuthMiddlewareTest extends BaseTestCase
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
    public function testAuthorizeDeny()
    {
        // Preparation
        $isNextCalled      = false;
        $isAuthCalled      = false;
        $isAuthorizeCalled = false;

        $login    = 'user';
        $password = 'password';

        $authorizationClosure = function (Request $request) use (&$isAuthorizeCalled) {
            $this->assertNotNull($request);

            $isAuthorizeCalled = true;

            return false;
        };

        $middleware = new BasicAuthMiddleware(
            $this->integration,
            $this->getAuthenticatedClosure($login, $password, $isAuthCalled),
            $authorizationClosure,
            'testRealm'
        );

        /** @var MockInterface $mockIntegration */
        $mockIntegration = $this->integration;
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('createResponse')
            ->once()
            ->withArgs([
                null,
                Response::HTTP_UNAUTHORIZED,
                ['WWW-Authenticate' => 'Basic realm="testRealm"']
            ])->andReturnUndefined();

        // Test
        $middleware->handle(
            $this->createRequest($login, $password),
            $this->getNextHandlerClosure($isNextCalled)
        );

        // Check
        $this->assertTrue($isAuthCalled);
        $this->assertTrue($isAuthorizeCalled);
        $this->assertFalse($isNextCalled);
    }

    /**
     * Test login.
     */
    public function testAuthorized()
    {
        // Preparation
        $isNextCalled      = false;
        $isAuthCalled      = false;
        $isAuthorizeCalled = false;

        $login    = 'user';
        $password = 'password';

        $authorizationClosure = function (Request $request) use (&$isAuthorizeCalled) {
            $this->assertNotNull($request);

            $isAuthorizeCalled = true;

            return true;
        };

        $middleware = new BasicAuthMiddleware(
            $this->integration,
            $this->getAuthenticatedClosure($login, $password, $isAuthCalled),
            $authorizationClosure
        );

        // Test
        $middleware->handle(
            $this->createRequest($login, $password),
            $this->getNextHandlerClosure($isNextCalled)
        );

        // Check
        $this->assertTrue($isAuthCalled);
        $this->assertTrue($isAuthorizeCalled);
        $this->assertTrue($isNextCalled);
    }

    /**
     * Test login.
     */
    public function testAuthentication()
    {
        // Preparation
        $isAuthCalled = false;
        $isNextCalled = false;

        $login    = 'user';
        $password = 'password';

        $middleware = new BasicAuthMiddleware(
            $this->integration,
            $this->getAuthenticatedClosure($login, $password, $isAuthCalled)
        );

        // Test
        $middleware->handle(
            $this->createRequest($login, $password),
            $this->getNextHandlerClosure($isNextCalled)
        );

        // Check
        $this->assertTrue($isAuthCalled);
        $this->assertTrue($isNextCalled);
    }

    /**
     * Test login.
     */
    public function testAuthenticationPasswordWithSeparator()
    {
        // Preparation
        $isAuthCalled = false;
        $isNextCalled = false;

        $login    = 'user';
        $password = 'pass:word';

        $middleware = new BasicAuthMiddleware(
            $this->integration,
            $this->getAuthenticatedClosure($login, $password, $isAuthCalled)
        );

        // Test
        $middleware->handle(
            $this->createRequest($login, $password),
            $this->getNextHandlerClosure($isNextCalled)
        );

        // Check
        $this->assertTrue($isAuthCalled);
        $this->assertTrue($isNextCalled);
    }

    /**
     * Test login.
     */
    public function testAuthenticationEmptyPass()
    {
        // Preparation
        $isAuthCalled = false;
        $isNextCalled = false;

        $login    = 'user';
        $password = '';

        $middleware = new BasicAuthMiddleware(
            $this->integration,
            $this->getAuthenticatedClosure($login, $password, $isAuthCalled)
        );

        // Test
        $middleware->handle(
            $this->createRequest($login, $password),
            $this->getNextHandlerClosure($isNextCalled)
        );

        // Check
        $this->assertTrue($isAuthCalled);
        $this->assertTrue($isNextCalled);
    }

    /**
     * Test login.
     */
    public function testAuthenticationEmptyUserName()
    {
        // Preparation
        $isAuthCalled = false;
        $isNextCalled = false;

        // that's a bit strange to have empty user however we want to handle that if happens
        $login    = '';
        $password = 'password';

        $middleware = new BasicAuthMiddleware(
            $this->integration,
            $this->getAuthenticatedClosure($login, $password, $isAuthCalled)
        );

        // Test
        $middleware->handle(
            $this->createRequest($login, $password),
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
     * @param string  $login
     * @param string  $password
     * @param bool   &$authenticated
     *
     * @return Closure
     */
    private function getAuthenticatedClosure($login, $password, &$authenticated)
    {
        return function ($userId, $pass) use ($login, $password, &$authenticated) {
            $this->assertEquals($login, $userId);
            $this->assertEquals($password, $pass);

            $authenticated = true;

            return true;
        };
    }

    /**
     * @param string $login
     * @param string $password
     *
     * @return Request
     */
    private function createRequest($login, $password)
    {
        $headers = [
            'HTTP_' . BasicAuthMiddleware::HEADER_AUTHORIZATION =>
                BasicAuthMiddleware::AUTHENTICATION_SCHEME . ' ' . base64_encode($login . ':' . $password),
        ];

        return new Request([], [], [], [], [], $headers);
    }
}

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
use \Neomerx\Limoncello\Http\Middleware\CorsMiddleware;

/**
 * @package Neomerx\Tests\Limoncello
 */
class CorsMiddlewareTest extends BaseTestCase
{
    /**
     * @var FrameworkIntegration
     */
    private $integration;

    /**
     * @var object
     */
    private $responseFromNext;

    /**
     * Set up test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->responseFromNext = null;
        $this->integration      = Mockery::mock(IntegrationInterface::class);
    }

    /**
     * Test pre-flight.
     */
    public function testPreFlightResponse()
    {
        // Preparation
        $isNextCalled      = false;
        $preFlightResponse = 'some-response';

        /** @var MockInterface $mockIntegration */
        $mockIntegration = $this->integration;
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('createResponse')->once()
            ->withArgs([null, Response::HTTP_OK, typeOf('array')])->andReturn($preFlightResponse);

        // Test
        $middleware = new CorsMiddleware($this->integration);
        $response   = $middleware->handle($this->createPreFlightRequest(), $this->getNextHandlerClosure($isNextCalled));

        // Check
        $this->assertFalse($isNextCalled);
        $this->assertEquals($preFlightResponse, $response);
    }

    /**
     * Test ordinary request-response flow.
     */
    public function testOrdinaryResponse()
    {
        // Preparation
        $isNextCalled = false;

        $mockHeaders = Mockery::mock();
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockHeaders->shouldReceive('add')->once()->withAnyArgs()->andReturnUndefined();
        $this->responseFromNext = (object)[
            'headers' => $mockHeaders,
        ];

        // Test
        $middleware = new CorsMiddleware($this->integration);
        $response   = $middleware->handle($this->createOrdinaryRequest(), $this->getNextHandlerClosure($isNextCalled));

        // Check
        $this->assertTrue($isNextCalled);
        $this->assertEquals($this->responseFromNext, $response);
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

            return $this->responseFromNext;
        };
    }

    /**
     * @return Request
     */
    private function createOrdinaryRequest()
    {
        $request = new Request();
        $request->setMethod('GET');
        $request->headers->add(['X-Requested-With' => 'XMLHttpRequest']);

        return $request;
    }

    /**
     * @return Request
     */
    private function createPreFlightRequest()
    {
        $request = $this->createOrdinaryRequest();
        $request->setMethod('OpTiOns');

        return $request;
    }
}

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

use \Mockery;
use \Mockery\MockInterface;
use Neomerx\JsonApi\Contracts\Integration\NativeResponsesInterface;
use \Neomerx\Tests\Limoncello\BaseTestCase;
use \Neomerx\Limoncello\Errors\RenderContainer;
use \Symfony\Component\HttpFoundation\Response;
use \Neomerx\JsonApi\Contracts\Exceptions\RenderContainerInterface;
use \Neomerx\JsonApi\Contracts\Parameters\SupportedExtensionsInterface;
use \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * @package Neomerx\Tests\Limoncello
 */
class RenderContainerTest extends BaseTestCase
{
    /** Default error code */
    const DEFAULT_CODE = 567;

    /**
     * @var RenderContainerInterface
     */
    private $container;

    /**
     * @var MockInterface
     */
    private $mockResponses;

    /**
     * Set up test.
     */
    protected function setUp()
    {
        parent::setUp();

        $mockSupExt = Mockery::mock(SupportedExtensionsInterface::class);
        $mockSupExt->shouldReceive('getExtensions')->once()->withNoArgs()->andReturn([]);
        $extensionsClosure = function () use ($mockSupExt) {
            return $mockSupExt;
        };

        $this->mockResponses = Mockery::mock(NativeResponsesInterface::class);

        /** @var NativeResponsesInterface $mockResponses */
        $mockResponses = $this->mockResponses;

        $this->container = new RenderContainer($mockResponses, $extensionsClosure, self::DEFAULT_CODE);
    }

    /**
     * Test register mapping.
     */
    public function testRegisterMapping()
    {
        $this->mockResponses->shouldReceive('createResponse')->once()
            ->withArgs([null, Response::HTTP_TOO_MANY_REQUESTS, Mockery::any()])
            ->andReturn('error: '. Response::HTTP_TOO_MANY_REQUESTS);

        $render = $this->container->getRender(new TooManyRequestsHttpException());
        $this->assertEquals('error: ' . Response::HTTP_TOO_MANY_REQUESTS, $render());
    }
}

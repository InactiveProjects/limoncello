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
use \Neomerx\JsonApi\Document\Error;
use \Neomerx\Tests\Limoncello\BaseTestCase;
use \Symfony\Component\HttpFoundation\Response;
use \Neomerx\Limoncello\Errors\RendererContainer;
use \Neomerx\JsonApi\Parameters\Headers\MediaType;
use \Neomerx\Limoncello\Contracts\IntegrationInterface;
use \Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use \Neomerx\JsonApi\Contracts\Responses\ResponsesInterface;
use \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * @package Neomerx\Tests\Limoncello
 */
class RenderContainerTest extends BaseTestCase
{
    /** Default error code */
    const DEFAULT_CODE = 567;

    /**
     * @var RendererContainer
     */
    private $container;

    /**
     * @var MockInterface
     */
    private $mockResponses;

    /**
     * @var MockInterface
     */
    private $mockCodecMatcher;

    /**
     * Set up test.
     */
    protected function setUp()
    {
        parent::setUp();

        $mockIntegration = Mockery::mock(IntegrationInterface::class);
        $this->mockResponses = Mockery::mock(ResponsesInterface::class);
        $this->mockCodecMatcher = Mockery::mock(CodecMatcherInterface::class);

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('getFromContainer')->zeroOrMoreTimes()
            ->with(ResponsesInterface::class)->andReturn($this->mockResponses);

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('getFromContainer')->zeroOrMoreTimes()
            ->with(CodecMatcherInterface::class)->andReturn($this->mockCodecMatcher);

        /** @var IntegrationInterface $mockIntegration */

        $this->container = new RendererContainer($mockIntegration, self::DEFAULT_CODE);
    }

    /**
     * Test register mapping.
     */
    public function testRegisterMapping()
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $this->mockResponses->shouldReceive('getResponse')->once()
            ->withArgs([Response::HTTP_TOO_MANY_REQUESTS, Mockery::any(), null, [], []])
            ->andReturn('error: '. Response::HTTP_TOO_MANY_REQUESTS);

        $exception = new TooManyRequestsHttpException();
        $render    = $this->container->getRenderer(get_class($exception));
        $render->withMediaType(new MediaType(MediaType::JSON_API_TYPE, MediaType::JSON_API_SUB_TYPE));
        $this->assertEquals('error: ' . Response::HTTP_TOO_MANY_REQUESTS, $render->render($exception));
    }

    /**
     * Test createConvertContentRenderer.
     */
    public function testCreateConvertContentRenderer()
    {
        $renderer = $this->container->createConvertContentRenderer(Response::HTTP_BAD_REQUEST, function () {
            return new Error(123);
        });

        $this->assertNotNull($renderer);
        $renderer->withMediaType(new MediaType(MediaType::JSON_API_TYPE, MediaType::JSON_API_SUB_TYPE));

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $this->mockCodecMatcher->shouldReceive('getEncoder')->once()->withAnyArgs()->andReturnSelf();
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $this->mockCodecMatcher->shouldReceive('encodeError')->once()->withAnyArgs()->andReturn('anything');
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $this->mockResponses->shouldReceive('getResponse')->once()->withAnyArgs()->andReturn('result');

        $this->assertEquals('result', $renderer->render(new \Exception()));
    }
}

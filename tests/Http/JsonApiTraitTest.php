<?php namespace Neomerx\Tests\Limoncello\Http;

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
use \Neomerx\JsonApi\Factories\Factory;
use \Neomerx\JsonApi\Codec\CodecMatcher;
use \Neomerx\JsonApi\Responses\Responses;
use \Neomerx\Limoncello\Http\JsonApiTrait;
use \Neomerx\JsonApi\Decoders\ArrayDecoder;
use \Neomerx\Limoncello\Config\Config as C;
use \Neomerx\Tests\Limoncello\BaseTestCase;
use \Neomerx\Limoncello\Errors\ExceptionThrower;
use \Neomerx\JsonApi\Parameters\Headers\MediaType;
use \Neomerx\Limoncello\Contracts\IntegrationInterface;
use \Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use \Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use \Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use \Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use \Neomerx\JsonApi\Contracts\Responses\ResponsesInterface;
use \Neomerx\JsonApi\Contracts\Integration\ExceptionThrowerInterface;
use \Neomerx\JsonApi\Contracts\Parameters\Headers\MediaTypeInterface;

/**
 * @package Neomerx\Tests\Limoncello
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class JsonApiTraitTest extends BaseTestCase
{
    use JsonApiTrait;

    /**
     * @var MockInterface
     */
    private $mockEncoder;

    /**
     * Set up test.
     */
    protected function setUp()
    {
        parent::setUp();

        $mockIntegration = Mockery::mock(IntegrationInterface::class);
        $this->mockEncoder = Mockery::mock(EncoderInterface::class);

        $codecMatcher = new CodecMatcher();
        $mediaType    = new MediaType(MediaType::JSON_API_TYPE, MediaType::JSON_API_SUB_TYPE);
        $codecMatcher->registerEncoder($mediaType, function () {
            return $this->mockEncoder;
        });
        $codecMatcher->registerDecoder($mediaType, function () {
            return new ArrayDecoder();
        });

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('getHeader')->zeroOrMoreTimes()->with('Content-Type')
            ->andReturn(MediaTypeInterface::JSON_API_MEDIA_TYPE);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('getHeader')->zeroOrMoreTimes()->with('Accept')
            ->andReturn(MediaTypeInterface::JSON_API_MEDIA_TYPE);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('getQueryParameters')->zeroOrMoreTimes()->withNoArgs()->andReturn([]);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('getFromContainer')->once()
            ->with(FactoryInterface::class)->andReturn(new Factory());
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('getFromContainer')->once()
            ->with(ExceptionThrowerInterface::class)->andReturn(new ExceptionThrower());
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        /** @noinspection PhpParamsInspection */
        $mockIntegration->shouldReceive('getFromContainer')->zeroOrMoreTimes()
            ->with(ResponsesInterface::class)->andReturn(new Responses($mockIntegration));
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('getFromContainer')->once()
            ->with(CodecMatcherInterface::class)->andReturn($codecMatcher);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('setInContainer')->zeroOrMoreTimes()->withAnyArgs()->andReturnUndefined();

        /** @var IntegrationInterface $mockIntegration */

        $this->initJsonApiSupport($mockIntegration);
    }

    /**
     * Test get document.
     */
    public function testGetDocument()
    {
        /** @var MockInterface $mockIntegration */
        $mockIntegration = $this->integration;
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('getContent')->once()->withNoArgs()->andReturn('{"key": "value"}');

        $this->assertEquals(['key' => 'value'], $this->getDocument());
    }

    /**
     * Test get code response.
     */
    public function testGetCodeResponse()
    {
        $headers = ['Content-Type' => MediaTypeInterface::JSON_API_MEDIA_TYPE];

        /** @var MockInterface $mockIntegration */
        $mockIntegration = $this->integration;
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('createResponse')->once()
            ->withArgs([null, 123, $headers])->andReturn('response');

        $this->assertEquals('response', $this->getCodeResponse(123));
    }

    /**
     * Test get content response.
     */
    public function testGetContentResponse()
    {
        // trigger parse params and headers
        $this->getParameters();

        /** @var MockInterface $mockIntegration */
        $mockIntegration = $this->integration;
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('createResponse')->once()
            ->withArgs([Mockery::type('string'), 200, ['Content-Type' => MediaTypeInterface::JSON_API_MEDIA_TYPE]])
            ->andReturn('response');

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $this->mockEncoder->shouldReceive('encodeData')->once()
            ->withAnyArgs()->andReturn('result will be overwritten by another mock');

        // any object can be sent to getContentResponse however we need to mock schema for it
        $this->assertEquals('response', $this->getContentResponse($this));
    }

    /**
     * Test checkParametersEmpty.
     */
    public function testCheckParametersEmpty()
    {
        $this->checkParametersEmpty();
    }

    /**
     * Test getSupportedExtensions.
     */
    public function testGetSupportedExtensions()
    {
        $this->getSupportedExtensions();
    }

    /**
     * Test get meta response.
     */
    public function testGetMetaResponse()
    {
        $headers = ['Content-Type' => MediaTypeInterface::JSON_API_MEDIA_TYPE];

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $this->mockEncoder->shouldReceive('encodeMeta')->once()
            ->withAnyArgs()->andReturn('result will be overwritten by another mock');

        /** @var MockInterface $mockIntegration */
        $mockIntegration = $this->integration;
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('createResponse')->once()
            ->withArgs([Mockery::type('string'), 200, $headers])->andReturn('response');

        // any object can be sent to getContentResponse however we need to mock schema for it
        $this->assertEquals('response', $this->getMetaResponse(['meta' => 'info']));
    }

    /**
     * Test get 'created' (HTTP code 201) response.
     */
    public function testGetCreatedResponse()
    {
        $headers = [
            'Location'     => '/fake-items/123',
            'Content-Type' => MediaTypeInterface::JSON_API_MEDIA_TYPE
        ];

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $this->mockEncoder->shouldReceive('encodeData')->once()
            ->withAnyArgs()->andReturn('result will be overwritten by another mock');

        /** @var MockInterface $mockIntegration */
        $mockIntegration = $this->integration;
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('createResponse')->once()
            ->withArgs([Mockery::type('string'), 201, $headers])->andReturn('response');
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('getFromContainer')->once()->with(ContainerInterface::class)->andReturnSelf();
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('getFromContainer')->once()->with(C::class)->andReturn([]);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('getSchema')->once()->withAnyArgs()->andReturnSelf();
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('getSelfSubLink')->once()->withAnyArgs()->andReturnSelf();
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('getSubHref')->once()->withAnyArgs()->andReturn('/fake-items/123');

        // any object can be sent to getContentResponse however we need to mock schema for it
        $this->assertEquals('response', $this->getCreatedResponse($this));
    }
}

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
use \Neomerx\Limoncello\Http\JsonApiTrait;
use \Neomerx\Tests\Limoncello\BaseTestCase;
use \Neomerx\Limoncello\Contracts\IntegrationInterface;
use \Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use \Neomerx\JsonApi\Contracts\Parameters\Headers\MediaTypeInterface;

/**
 * @package Neomerx\Tests\Limoncello
 */
class JsonApiTraitCodecTest extends BaseTestCase
{
    use JsonApiTrait;

    /**
     * Set up test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->integration = Mockery::mock(IntegrationInterface::class);

        /** @var MockInterface $mockIntegration */
        $mockIntegration = $this->integration;
        $mockIntegration->shouldReceive('declareSupportedExtensions')->once()->withAnyArgs()->andReturnUndefined();

        $mockIntegration->shouldReceive('getHeader')->once()->with('Content-Type')
            ->andReturn(MediaTypeInterface::JSON_API_MEDIA_TYPE);
        $mockIntegration->shouldReceive('getHeader') ->once()->with('Accept')
            ->andReturn(MediaTypeInterface::JSON_API_MEDIA_TYPE);
        $mockIntegration->shouldReceive('getQueryParameters')->once()->withNoArgs()->andReturn([]);

        $this->initJsonApiSupport();
    }

    /**
     * Test get document.
     */
    public function testGetDocument()
    {
        /** @var MockInterface $mockIntegration */
        $mockIntegration = $this->integration;
        $mockIntegration->shouldReceive('getContent')->once()->withNoArgs()->andReturn('{"key": "value"}');

        $this->assertEquals(['key' => 'value'], $this->getDocument());

        $this->assertNotNull($this->getCodecMatcher());
    }

    /**
     * Test get code response.
     */
    public function testGetCodeResponse()
    {
        $headers = ['Content-Type' => MediaTypeInterface::JSON_API_MEDIA_TYPE];

        /** @var MockInterface $mockIntegration */
        $mockIntegration = $this->integration;
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

        $matchedMediaType = $this->getCodecMatcher()->getEncoderRegisteredMatchedType();
        $this->assertEquals(MediaTypeInterface::JSON_API_MEDIA_TYPE, $matchedMediaType->getMediaType());

        // set encoder
        $this->getCodecMatcher()->setEncoder(function () {
            $encoder = Mockery::mock(EncoderInterface::class);
            $encoder->shouldReceive('encode')->once()->withAnyArgs()
                ->andReturn('result will be overwritten by another mock');
            return $encoder;
        });

        /** @var MockInterface $mockIntegration */
        $mockIntegration = $this->integration;
        $mockIntegration->shouldReceive('createResponse')->once()
            ->withArgs([Mockery::type('string'), 200, ['Content-Type' => MediaTypeInterface::JSON_API_MEDIA_TYPE]])
            ->andReturn('response');

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
}

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
use \Neomerx\Tests\Limoncello\BaseTestCase;
use \Symfony\Component\HttpFoundation\Request;
use \Neomerx\Limoncello\Http\FrameworkIntegration;

/**
 * @package Neomerx\Tests\Limoncello
 */
class FrameworkIntegrationTest extends BaseTestCase
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
        $this->integration = Mockery::mock(FrameworkIntegration::class)->makePartial();
    }

    /**
     * Test get content.
     */
    public function testGetNotEmptyContent()
    {
        $mockCurRequest = Mockery::mock(Request::class);

        /** @var MockInterface $mockIntegration */
        $mockIntegration = $this->integration;
        $mockIntegration->shouldReceive('getCurrentRequest')->once()->withNoArgs()->andReturn($mockCurRequest);
        $mockCurRequest->shouldReceive('getContent')->once()->withNoArgs()->andReturn('content');

        $this->assertEquals('content', $this->integration->getContent());
    }

    /**
     * Test get content.
     */
    public function testGetNotContent()
    {
        $mockCurRequest = Mockery::mock(Request::class);

        /** @var MockInterface $mockIntegration */
        $mockIntegration = $this->integration;
        $mockIntegration->shouldReceive('getCurrentRequest')->once()->withNoArgs()->andReturn($mockCurRequest);
        $mockCurRequest->shouldReceive('getContent')->once()->withNoArgs()->andReturn('');

        $this->assertNull($this->integration->getContent());
    }

    /**
     * Test get query parameters.
     */
    public function testGetQueryParameters()
    {
        $queryParams = ['key' => 'value'];
        $curRequest  = new Request($queryParams);

        /** @var MockInterface $mockIntegration */
        $mockIntegration = $this->integration;
        $mockIntegration->shouldReceive('getCurrentRequest')->once()->withNoArgs()->andReturn($curRequest);

        $this->assertEquals($queryParams, $this->integration->getQueryParameters());
    }

    /**
     * Test get header.
     */
    public function testGetHeader()
    {
        $headers = ['HTTP_key' => 'value'];
        $curRequest  = new Request([], [], [], [], [], $headers);

        /** @var MockInterface $mockIntegration */
        $mockIntegration = $this->integration;
        $mockIntegration->shouldReceive('getCurrentRequest')->once()->withNoArgs()->andReturn($curRequest);

        $this->assertEquals('value', $this->integration->getHeader('key'));
    }
}

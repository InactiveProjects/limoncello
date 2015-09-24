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
use \Neomerx\Tests\Limoncello\BaseTestCase;
use \Neomerx\JsonApi\Encoder\EncoderOptions;
use \Neomerx\Limoncello\Http\AppServiceProviderTrait;
use \Neomerx\Limoncello\Contracts\IntegrationInterface;

/**
 * @package Neomerx\Tests\Limoncello
 */
class AppServiceProviderTraitTest extends BaseTestCase
{
    use AppServiceProviderTrait;

    /**
     * Test get 'created' (HTTP code 201) response.
     */
    public function testRegister()
    {
        $mockIntegration = Mockery::mock(IntegrationInterface::class);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('setInContainer')->zeroOrMoreTimes()->withAnyArgs()->andReturnUndefined();
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $mockIntegration->shouldReceive('getConfig')->once()->withAnyArgs()->andReturn([]);

        /** @var IntegrationInterface $mockIntegration */

        $this->registerResponses($mockIntegration);
        $this->registerCodecMatcher($mockIntegration);
        $this->registerExceptionThrower($mockIntegration);
    }

    /**
     * Test getEncoderClosure.
     */
    public function testGetEncoderAndDecoderClosures()
    {
        $config          = [];
        $encoderOptions  = new EncoderOptions();
        $factory         = $this->createFactory();
        $schemaContainer = $this->createSchemaContainer($config, $factory);
        $encoderClosure  = $this->getEncoderClosure($factory, $schemaContainer, $encoderOptions, $config);

        $decoderClosure  = $this->getDecoderClosure();

        $encoderClosure();
        $decoderClosure();
    }
}

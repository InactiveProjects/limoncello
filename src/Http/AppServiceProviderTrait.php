<?php namespace Neomerx\Limoncello\Http;

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
use \Neomerx\JsonApi\Factories\Factory;
use \Neomerx\JsonApi\Codec\CodecMatcher;
use \Neomerx\JsonApi\Responses\Responses;
use \Neomerx\JsonApi\Decoders\ArrayDecoder;
use \Neomerx\Limoncello\Config\Config as C;
use \Neomerx\JsonApi\Encoder\EncoderOptions;
use \Neomerx\Limoncello\Errors\ExceptionThrower;
use \Neomerx\Limoncello\Contracts\IntegrationInterface;
use \Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use \Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use \Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use \Neomerx\JsonApi\Contracts\Responses\ResponsesInterface;
use \Neomerx\JsonApi\Contracts\Parameters\Headers\MediaTypeInterface;
use \Neomerx\JsonApi\Contracts\Integration\ExceptionThrowerInterface;

/**
 * @package Neomerx\Limoncello
 */
trait AppServiceProviderTrait
{
    /**
     * @param IntegrationInterface $integration
     */
    public function registerResponses(IntegrationInterface $integration)
    {
        $integration->setInContainer(ResponsesInterface::class, new Responses($integration));
    }

    /**
     * @param IntegrationInterface $integration
     */
    public function registerExceptionThrower(IntegrationInterface $integration)
    {
        $integration->setInContainer(ExceptionThrowerInterface::class, new ExceptionThrower());
    }

    /**
     * @param IntegrationInterface $integration
     */
    public function registerCodecMatcher(IntegrationInterface $integration)
    {
        // register factory
        $factory = $this->createFactory();
        $integration->setInContainer(FactoryInterface::class, $factory);

        // register config
        $config  = $integration->getConfig();
        $integration->setInContainer(C::class, $config);

        // register schemas
        $schemaContainer = $this->createSchemaContainer($config, $factory);
        $integration->setInContainer(ContainerInterface::class, $schemaContainer);

        // register codec matcher
        $codecMatcher = $this->createCodecMatcher($config, $factory, $schemaContainer);
        $integration->setInContainer(CodecMatcherInterface::class, $codecMatcher);
    }

    /**
     * @return FactoryInterface
     */
    protected function createFactory()
    {
        return new Factory();
    }

    /**
     * @param array            $config
     * @param FactoryInterface $factory
     *
     * @return ContainerInterface
     */
    protected function createSchemaContainer(array $config, FactoryInterface $factory)
    {
        $schemas         = isset($config[C::SCHEMAS]) === true ? $config[C::SCHEMAS] : [];
        $schemaContainer = $factory->createContainer($schemas);

        return $schemaContainer;
    }

    /**
     * @param array              $config
     * @param FactoryInterface   $factory
     * @param ContainerInterface $schemaContainer
     *
     * @return CodecMatcherInterface
     */
    protected function createCodecMatcher(array $config, FactoryInterface $factory, ContainerInterface $schemaContainer)
    {
        $options         = $this->getValue($config, C::JSON, C::JSON_OPTIONS, C::JSON_OPTIONS_DEFAULT);
        $depth           = $this->getValue($config, C::JSON, C::JSON_DEPTH, C::JSON_DEPTH_DEFAULT);
        $urlPrefix       = $this->getValue($config, C::JSON, C::JSON_URL_PREFIX, null);
        $encoderOptions  = new EncoderOptions($options, $urlPrefix, $depth);
        $decoderClosure  = $this->getDecoderClosure();
        $encoderClosure  = $this->getEncoderClosure($factory, $schemaContainer, $encoderOptions, $config);
        $codecMatcher    = new CodecMatcher();
        $jsonApiType     = $factory->createMediaType(
            MediaTypeInterface::JSON_API_TYPE,
            MediaTypeInterface::JSON_API_SUB_TYPE
        );
        $jsonApiTypeUtf8 = $factory->createMediaType(
            MediaTypeInterface::JSON_API_TYPE,
            MediaTypeInterface::JSON_API_SUB_TYPE,
            ['charset' => 'UTF-8']
        );
        $codecMatcher->registerEncoder($jsonApiType, $encoderClosure);
        $codecMatcher->registerDecoder($jsonApiType, $decoderClosure);
        $codecMatcher->registerEncoder($jsonApiTypeUtf8, $encoderClosure);
        $codecMatcher->registerDecoder($jsonApiTypeUtf8, $decoderClosure);

        return $codecMatcher;
    }

    /**
     * @return Closure
     */
    protected function getDecoderClosure()
    {
        return function () {
            return new ArrayDecoder();
        };
    }

    /**
     * @param FactoryInterface   $factory
     * @param ContainerInterface $container
     * @param EncoderOptions     $encoderOptions
     * @param array              $config
     *
     * @return Closure
     */
    private function getEncoderClosure(
        FactoryInterface $factory,
        ContainerInterface $container,
        EncoderOptions $encoderOptions,
        array $config
    ) {
        return function () use ($factory, $container, $encoderOptions, $config) {
            $isShowVer   = $this->getValue($config, C::JSON, C::JSON_IS_SHOW_VERSION, C::JSON_IS_SHOW_VERSION_DEFAULT);
            $versionMeta = $this->getValue($config, C::JSON, C::JSON_VERSION_META, null);
            $encoder     = $factory->createEncoder($container, $encoderOptions);

            $isShowVer === false ?: $encoder->withJsonApiVersion($versionMeta);

            return $encoder;
        };
    }

    /**
     * @param array  $array
     * @param string $key1
     * @param string $key2
     * @param mixed  $default
     *
     * @return mixed
     */
    private function getValue(array $array, $key1, $key2, $default)
    {
        return isset($array[$key1][$key2]) === true ? $array[$key1][$key2] : $default;
    }
}

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

use \Neomerx\JsonApi\Encoder\Encoder;
use \Neomerx\JsonApi\Codec\CodecMatcher;
use \Neomerx\JsonApi\Responses\Responses;
use \Neomerx\JsonApi\Schema\SchemaFactory;
use \Neomerx\JsonApi\Decoders\ArrayDecoder;
use \Neomerx\Limoncello\Config\Config as C;
use \Neomerx\JsonApi\Encoder\EncoderOptions;
use \Neomerx\JsonApi\Document\DocumentFactory;
use \Symfony\Component\HttpFoundation\Response;
use \Neomerx\Limoncello\Errors\ExceptionThrower;
use \Neomerx\JsonApi\Parameters\ParametersFactory;
use \Neomerx\JsonApi\Encoder\Factory\EncoderFactory;
use \Neomerx\Limoncello\Contracts\IntegrationInterface;
use \Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use \Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use \Neomerx\JsonApi\Parameters\RestrictiveParameterChecker;
use \Neomerx\JsonApi\Contracts\Responses\ResponsesInterface;
use \Neomerx\JsonApi\Contracts\Parameters\ParametersInterface;
use \Neomerx\JsonApi\Contracts\Parameters\ParametersParserInterface;
use \Neomerx\JsonApi\Contracts\Parameters\ParameterCheckerInterface;
use \Neomerx\JsonApi\Contracts\Integration\ExceptionThrowerInterface;
use \Neomerx\JsonApi\Contracts\Parameters\Headers\MediaTypeInterface;
use \Neomerx\JsonApi\Contracts\Parameters\ParametersFactoryInterface;
use \Neomerx\JsonApi\Contracts\Parameters\SupportedExtensionsInterface;

/**
 * @package Neomerx\Limoncello
 */
trait JsonApiTrait
{
    /**
     * If unrecognized parameters should be allowed in input parameters.
     *
     * @var bool
     */
    protected $allowUnrecognizedParams = false;

    /**
     * A list of allowed include paths in input parameters.
     *
     * Empty array [] means clients are not allowed to specify include paths and 'null' means all paths are allowed.
     *
     * @var string[]|null
     */
    protected $allowedIncludePaths = [];

    /**
     * A list of JSON API types which clients can sent field sets to.
     *
     * Possible values
     *
     * $allowedFieldSetTypes = null; // <-- for all types all fields are allowed
     *
     * $allowedFieldSetTypes = []; // <-- non of the types and fields are allowed
     *
     * $allowedFieldSetTypes = [
     *      'author'   => null,              // <-- all fields for 'author' are allowed
     *      'comments' => [],                // <-- no fields for 'comments' are allowed (all denied)
     *      'posts'    => ['title', 'body'], // <-- only 'title' and 'body' fields are allowed for 'posts'
     * ];
     *
     * @var array|null
     */
    protected $allowedFieldSetTypes = null;

    /**
     * A list of allowed sort field names in input parameters.
     *
     * Empty array [] means clients are not allowed to specify sort fields and 'null' means all fields are allowed.
     *
     * @var string[]|null
     */
    protected $allowedSortFields = [];

    /**
     * A list of allowed pagination input parameters (e.g 'number', 'size', 'offset' and etc).
     *
     * Empty array [] means clients are not allowed to specify paging and 'null' means all parameters are allowed.
     *
     * @var string[]|null
     */
    protected $allowedPagingParameters = [];

    /**
     * A list of allowed filtering input parameters.
     *
     * Empty array [] means clients are not allowed to specify filtering and 'null' means all parameters are allowed.
     *
     * @var string[]|null
     */
    protected $allowedFilteringParameters = [];

    /**
     * JSON API extensions supported by this controller (comma separated).
     *
     * @var string
     */
    protected $extensions = MediaTypeInterface::NO_EXT;

    /**
     * If JSON API extension should be allowed.
     *
     * @var bool
     */
    protected $allowExtensionsSupport = false;

    /**
     * @var IntegrationInterface
     */
    protected $integration;

    /**
     * @var CodecMatcherInterface
     */
    private $codecMatcher;

    /**
     * @var ParametersParserInterface
     */
    private $parametersParser;

    /**
     * @var ParameterCheckerInterface
     */
    private $parametersChecker;

    /**
     * @var ExceptionThrowerInterface
     */
    private $exceptionThrower;

    /**
     * @var ParametersInterface
     */
    private $parameters = null;

    /**
     * @var bool
     */
    private $parametersChecked = false;

    /**
     * @var SupportedExtensionsInterface
     */
    private $supportedExtensions;

    /**
     * @var ResponsesInterface
     */
    private $responses;

    /**
     * @var ContainerInterface
     */
    private $schemaContainer;

    /**
     * @var ParametersFactoryInterface
     */
    private $parametersFactory;

    /**
     * Init integrations with JSON API implementation.
     *
     * @return void
     */
    protected function initJsonApiSupport()
    {
        $this->parametersFactory = new ParametersFactory();

        // integrations with framework
        $this->responses        = new Responses($this->getIntegration());
        $this->exceptionThrower = new ExceptionThrower();

        // init support from json-api implementation
        $this->initCodecMatcher();
        $this->parametersParser    = $this->parametersFactory->createParametersParser();
        $this->supportedExtensions = $this->parametersFactory->createSupportedExtensions($this->extensions);
        $this->parametersChecker   = new RestrictiveParameterChecker(
            $this->exceptionThrower,
            $this->codecMatcher,
            $this->allowUnrecognizedParams,
            $this->allowedIncludePaths,
            $this->allowedFieldSetTypes,
            $this->allowedSortFields,
            $this->allowedPagingParameters,
            $this->allowedFilteringParameters,
            $this->allowExtensionsSupport
        );

        // information about extensions supported by the current controller might be used in exception handler
        $this->getIntegration()->declareSupportedExtensions($this->getSupportedExtensions());
    }

    /**
     * Init codec container.
     *
     * @return void
     */
    protected function initCodecMatcher()
    {
        $jsonApiEncoder = function () {
            $config  = $this->getConfig();

            $schemas = isset($config[C::SCHEMAS]) === true ? $config[C::SCHEMAS] : [];

            $container = $this->schemaContainer = (new SchemaFactory())->createContainer($schemas);

            $options = isset($config[C::JSON][C::JSON_OPTIONS]) === true ?
                $config[C::JSON][C::JSON_OPTIONS] : C::JSON_OPTIONS_DEFAULT;

            $depth = isset($config[C::JSON][C::JSON_DEPTH]) === true ?
                $config[C::JSON][C::JSON_DEPTH] : C::JSON_DEPTH_DEFAULT;

            $isShowVersion = isset($config[C::JSON][C::JSON_IS_SHOW_VERSION]) === true ?
                $config[C::JSON][C::JSON_IS_SHOW_VERSION] : C::JSON_IS_SHOW_VERSION_DEFAULT;

            $versionMeta = isset($config[C::JSON][C::JSON_VERSION_META]) === true ?
                $config[C::JSON][C::JSON_VERSION_META] : null;

            $urlPrefix = isset($config[C::JSON][C::JSON_URL_PREFIX]) === true ?
                $config[C::JSON][C::JSON_URL_PREFIX] : null;

            $encoderFactory    = new EncoderFactory();
            $parametersFactory = new ParametersFactory();
            return new Encoder(
                new DocumentFactory(),
                $encoderFactory,
                $encoderFactory,
                $parametersFactory,
                $container,
                new EncoderOptions($options, $urlPrefix, $isShowVersion, $versionMeta, $depth)
            );
        };

        $this->codecMatcher = new CodecMatcher();
        $jsonApiType        = $this->parametersFactory->createMediaType(
            MediaTypeInterface::JSON_API_TYPE,
            MediaTypeInterface::JSON_API_SUB_TYPE
        );

        $this->codecMatcher->registerEncoder($jsonApiType, $jsonApiEncoder);
        $this->codecMatcher->registerDecoder($jsonApiType, function () {
            return new ArrayDecoder();
        });
    }

    /**
     * Get Limoncello config.
     *
     * @return array
     */
    protected function getConfig()
    {
        return $this->getIntegration()->getConfig();
    }

    /**
     * @return IntegrationInterface
     */
    protected function getIntegration()
    {
        assert('$this->integration !== null', 'Haven\'t you forgotten to init integration with framework?');
        return $this->integration;
    }

    /**
     * @return mixed
     */
    protected function getDocument()
    {
        if ($this->codecMatcher->getDecoder() === null) {
            $this->codecMatcher->findDecoder($this->getParameters()->getContentTypeHeader());
        }

        $decoder = $this->codecMatcher->getDecoder();
        return $decoder->decode($this->getIntegration()->getContent());
    }

    /**
     * @return ParametersInterface
     */
    protected function getUncheckedParameters()
    {
        if ($this->parameters === null) {
            $this->parameters = $this->parametersParser->parse($this->getIntegration(), $this->exceptionThrower);
        }

        return $this->parameters;
    }

    /**
     * @return void
     */
    protected function checkParameters()
    {
        $this->parametersChecker->check($this->getUncheckedParameters());
        $this->parametersChecked = true;
    }

    /**
     * @return void
     */
    protected function checkParametersEmpty()
    {
        $this->getParameters()->isEmpty() === true ?: $this->exceptionThrower->throwBadRequest();
    }

    /**
     * @return ParametersInterface
     */
    protected function getParameters()
    {
        if ($this->parametersChecked === false) {
            $this->checkParameters();
        }

        return $this->getUncheckedParameters();
    }

    /**
     * Get response with HTTP code only.
     *
     * @param $statusCode
     *
     * @return Response
     */
    protected function getCodeResponse($statusCode)
    {
        $this->checkParameters();

        $outputMediaType = $this->codecMatcher->getEncoderRegisteredMatchedType();
        return $this->responses->getResponse($statusCode, $outputMediaType, null, $this->supportedExtensions);
    }

    /**
     * Get response with meta information only.
     *
     * @param array|object $meta       Meta information.
     * @param int          $statusCode
     *
     * @return Response
     */
    protected function getMetaResponse($meta, $statusCode = Response::HTTP_OK)
    {
        $this->checkParameters();

        $encoder         = $this->codecMatcher->getEncoder();
        $outputMediaType = $this->codecMatcher->getEncoderRegisteredMatchedType();
        $content         = $encoder->meta($meta);

        return $this->responses->getResponse($statusCode, $outputMediaType, $content, $this->supportedExtensions);
    }

    /**
     * @return SupportedExtensionsInterface
     */
    protected function getSupportedExtensions()
    {
        return $this->supportedExtensions;
    }

    /**
     * Get response with regular JSON API Document in body.
     *
     * @param object|array                                                       $data
     * @param int                                                                $statusCode
     * @param array<string,\Neomerx\JsonApi\Contracts\Schema\LinkInterface>|null $links
     * @param mixed                                                              $meta
     *
     * @return Response
     */
    protected function getContentResponse(
        $data,
        $statusCode = Response::HTTP_OK,
        $links = null,
        $meta = null
    ) {
        $parameters      = $this->getParameters();
        $encoder         = $this->codecMatcher->getEncoder();
        $outputMediaType = $this->codecMatcher->getEncoderRegisteredMatchedType();
        $content         = $encoder->encode($data, $links, $meta, $parameters);

        return $this->responses->getResponse($statusCode, $outputMediaType, $content, $this->supportedExtensions);
    }

    /**
     * @param object                                                             $resource
     * @param array<string,\Neomerx\JsonApi\Contracts\Schema\LinkInterface>|null $links
     * @param mixed                                                              $meta
     *
     * @return Response
     */
    protected function getCreatedResponse(
        $resource,
        $links = null,
        $meta = null
    ) {
        $parameters      = $this->getParameters();
        $encoder         = $this->codecMatcher->getEncoder();
        $outputMediaType = $this->codecMatcher->getEncoderRegisteredMatchedType();
        $content         = $encoder->encode($resource, $links, $meta, $parameters);

        $urlPrefix = $encoder->getEncoderOptions() === null ? null : $encoder->getEncoderOptions()->getUrlPrefix();
        $location  = $urlPrefix. $this->schemaContainer->getSchema($resource)->getSelfSubLink($resource)->getSubHref();

        return $this->responses->getCreatedResponse($location, $outputMediaType, $content, $this->supportedExtensions);
    }

    /**
     * Get codec matcher.
     *
     * @return CodecMatcherInterface
     */
    protected function getCodecMatcher()
    {
        return $this->codecMatcher;
    }

    /**
     * @return ExceptionThrowerInterface
     */
    protected function getExceptionThrower()
    {
        return $this->exceptionThrower;
    }
}

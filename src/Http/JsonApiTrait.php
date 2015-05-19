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
use \Neomerx\JsonApi\Responses\Responses;
use \Neomerx\JsonApi\Codec\CodecContainer;
use \Neomerx\JsonApi\Parameters\MediaType;
use \Neomerx\JsonApi\Schema\SchemaFactory;
use \Neomerx\JsonApi\Decoders\ArrayDecoder;
use \Neomerx\Limoncello\Config\Config as C;
use \Neomerx\JsonApi\Document\DocumentFactory;
use \Neomerx\JsonApi\Encoder\JsonEncodeOptions;
use \Symfony\Component\HttpFoundation\Response;
use \Neomerx\Limoncello\Errors\ExceptionThrower;
use \Neomerx\JsonApi\Parameters\ParametersFactory;
use \Neomerx\JsonApi\Encoder\Factory\EncoderFactory;
use \Neomerx\Limoncello\Contracts\IntegrationInterface;
use \Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use \Neomerx\JsonApi\Parameters\RestrictiveParameterChecker;
use \Neomerx\JsonApi\Contracts\Responses\ResponsesInterface;
use \Neomerx\JsonApi\Contracts\Codec\CodecContainerInterface;
use \Neomerx\JsonApi\Contracts\Parameters\MediaTypeInterface;
use \Neomerx\JsonApi\Contracts\Parameters\ParametersInterface;
use \Neomerx\JsonApi\Contracts\Document\DocumentLinksInterface;
use \Neomerx\JsonApi\Contracts\Parameters\ParametersParserInterface;
use \Neomerx\JsonApi\Contracts\Parameters\ParameterCheckerInterface;
use \Neomerx\JsonApi\Contracts\Integration\ExceptionThrowerInterface;
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
     * Empty array [] means clients are not allowed to specify field sets for all types and
     * 'null' means any field sets are allowed.
     *
     * @var string[]|null
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
     * @var IntegrationInterface
     */
    protected $integration;

    /**
     * @var CodecContainerInterface
     */
    private $codecContainer;

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
     * Init integrations with JSON API implementation.
     *
     * @return void
     */
    protected function initJsonApiSupport()
    {
        $parametersFactory = new ParametersFactory();

        // integrations with framework
        $this->responses        = new Responses($this->getIntegration());
        $this->exceptionThrower = new ExceptionThrower();

        // init support from json-api implementation
        $this->initCodecContainer();
        $this->parametersParser    = $parametersFactory->createParametersParser();
        $this->supportedExtensions = $parametersFactory->createSupportedExtensions($this->extensions);
        $this->parametersChecker   = new RestrictiveParameterChecker(
            $this->exceptionThrower,
            $this->codecContainer,
            $this->allowUnrecognizedParams,
            $this->allowedIncludePaths,
            $this->allowedFieldSetTypes,
            $this->allowedSortFields,
            $this->allowedPagingParameters,
            $this->allowedFilteringParameters
        );

        // information about extensions supported by the current controller might be used in exception handler
        $this->getIntegration()->declareSupportedExtensions($this->getSupportedExtensions());
    }

    /**
     * Init codec container.
     *
     * @return void
     */
    protected function initCodecContainer()
    {
        $config  = $this->getIntegration()->getConfig();
        $schemas = isset($config[C::SCHEMAS]) === true ? $config[C::SCHEMAS] : [];

        $container = $this->schemaContainer = (new SchemaFactory())->createContainer($schemas);

        $jsonApiEncoder = function () use ($container, $config) {
            $options = isset($config[C::JSON][C::JSON_OPTIONS]) === true ?
                $config[C::JSON][C::JSON_OPTIONS] : C::JSON_OPTIONS_DEFAULT;

            $depth = isset($config[C::JSON][C::JSON_DEPTH]) === true ?
                $config[C::JSON][C::JSON_DEPTH] : C::JSON_DEPTH_DEFAULT;

            $encoderFactory    = new EncoderFactory();
            $parametersFactory = new ParametersFactory();
            return new Encoder(
                new DocumentFactory(),
                $encoderFactory,
                $encoderFactory,
                $parametersFactory,
                $container,
                new JsonEncodeOptions($options, $depth)
            );
        };

        $this->codecContainer = new CodecContainer();
        $jsonApiType          = new MediaType(CodecContainerInterface::JSON_API_TYPE);
        $this->codecContainer->registerEncoder($jsonApiType, $jsonApiEncoder);
        $this->codecContainer->registerDecoder($jsonApiType, function () {
            return new ArrayDecoder();
        });
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
        $decoder = $this->codecContainer->getDecoder($this->getParameters()->getInputMediaType());
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
     * @param $statusCode
     *
     * @return Response
     */
    protected function getCodeResponse($statusCode)
    {
        $mediaType = $this->getParameters()->getOutputMediaType();
        return $this->responses->getResponse($statusCode, $mediaType, null, $this->supportedExtensions);
    }

    /**
     * @return SupportedExtensionsInterface
     */
    protected function getSupportedExtensions()
    {
        return $this->supportedExtensions;
    }

    /**
     * @param object|array                $data
     * @param int                         $statusCode
     * @param DocumentLinksInterface|null $links
     * @param mixed                       $meta
     *
     * @return Response
     */
    protected function getContentResponse(
        $data,
        $statusCode = Response::HTTP_OK,
        DocumentLinksInterface $links = null,
        $meta = null
    ) {
        $parameters      = $this->getParameters();
        $outputMediaType = $parameters->getOutputMediaType();
        $encoder         = $this->codecContainer->getEncoder($outputMediaType);
        $content         = $encoder->encode($data, $links, $meta, $parameters);

        return $this->responses->getResponse($statusCode, $outputMediaType, $content, $this->supportedExtensions);
    }

    /**
     * @param object                      $resource
     * @param DocumentLinksInterface|null $links
     * @param mixed                       $meta
     *
     * @return Response
     */
    protected function getCreatedResponse(
        $resource,
        DocumentLinksInterface $links = null,
        $meta = null
    ) {
        $parameters      = $this->getParameters();
        $outputMediaType = $parameters->getOutputMediaType();
        $encoder         = $this->codecContainer->getEncoder($outputMediaType);
        $location        = $this->schemaContainer->getSchema($resource)->getSelfUrl($resource);
        $content         = $encoder->encode($resource, $links, $meta, $parameters);

        return $this->responses->getCreatedResponse($location, $outputMediaType, $content, $this->supportedExtensions);
    }
}

<?php namespace Neomerx\Limoncello\Http;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\SupportedExtensionsInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use Neomerx\JsonApi\Http\Responses as JsonApiResponses;
use Neomerx\Limoncello\Contracts\Http\ResponsesInterface;
use Neomerx\Limoncello\Contracts\JsonApi\PagedDataInterface;

/**
 * @package Neomerx\Limoncello
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Responses extends JsonApiResponses implements ResponsesInterface
{
    /**
     * @var EncodingParametersInterface
     */
    private $parameters;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var MediaTypeInterface
     */
    private $outputMediaType;

    /**
     * @var SupportedExtensionsInterface
     */
    private $extensions;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var null|string
     */
    private $urlPrefix;

    /**
     * Responses constructor.
     *
     * @param EncodingParametersInterface  $parameters
     * @param MediaTypeInterface           $outputMediaType
     * @param SupportedExtensionsInterface $extensions
     * @param EncoderInterface             $encoder
     * @param ContainerInterface           $container
     * @param string|null                  $urlPrefix
     */
    public function __construct(
        EncodingParametersInterface $parameters,
        MediaTypeInterface $outputMediaType,
        SupportedExtensionsInterface $extensions,
        EncoderInterface $encoder,
        ContainerInterface $container,
        $urlPrefix = null
    ) {
        $this->parameters      = $parameters;
        $this->extensions      = $extensions;
        $this->encoder         = $encoder;
        $this->outputMediaType = $outputMediaType;
        $this->container       = $container;
        $this->urlPrefix       = $urlPrefix;
    }

    /**
     * @inheritdoc
     */
    public function getContentResponse(
        $data,
        $statusCode = JsonApiResponses::HTTP_OK,
        $links = null,
        $meta = null,
        array $headers = []
    ) {
        if ($data instanceof Collection) {
            $data = $data->all();
        }

        return parent::getContentResponse($data, $statusCode, $links, $meta);
    }

    /**
     * @inheritdoc
     */
    public function getPagedDataResponse(PagedDataInterface $data, $statusCode = self::HTTP_OK)
    {
        return $this->getContentResponse($data->getData(), $statusCode, $data->getLinks(), $data->getMeta());
    }

    /**
     * @inheritdoc
     */
    protected function createResponse($content, $statusCode, array $headers)
    {
        return new Response($content, $statusCode, $headers);
    }

    /**
     * @inheritdoc
     */
    protected function getEncoder()
    {
        return $this->encoder;
    }

    /**
     * @inheritdoc
     */
    protected function getUrlPrefix()
    {
        return $this->urlPrefix;
    }

    /**
     * @inheritdoc
     */
    protected function getEncodingParameters()
    {
        return $this->parameters;
    }

    /**
     * @inheritdoc
     */
    protected function getSchemaContainer()
    {
        return $this->container;
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedExtensions()
    {
        return $this->extensions;
    }

    /**
     * @inheritdoc
     */
    protected function getMediaType()
    {
        return $this->outputMediaType;
    }
}

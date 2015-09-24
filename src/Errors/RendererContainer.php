<?php namespace Neomerx\Limoncello\Errors;

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
use \Symfony\Component\HttpFoundation\Response;
use \Neomerx\Limoncello\Contracts\IntegrationInterface;
use \Symfony\Component\HttpKernel\Exception\HttpException;
use \Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use \Neomerx\JsonApi\Contracts\Exceptions\RendererInterface;
use \Neomerx\JsonApi\Contracts\Responses\ResponsesInterface;
use \Symfony\Component\HttpKernel\Exception\GoneHttpException;
use \Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use \Symfony\Component\HttpKernel\Exception\LengthRequiredHttpException;
use \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use \Neomerx\JsonApi\Exceptions\RendererContainer as BaseRendererContainer;
use \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use \Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use \Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;
use \Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * @package Neomerx\Limoncello
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RendererContainer extends BaseRendererContainer
{
    /**
     * @var IntegrationInterface
     */
    private $integration;

    /**
     * @var ResponsesInterface
     */
    private $responses;

    /**
     * @var CodecMatcherInterface
     */
    private $codecMatcher;

    /**
     * @param IntegrationInterface $integration
     * @param int                  $defaultStatusCode
     */
    public function __construct(
        IntegrationInterface $integration,
        $defaultStatusCode = Response::HTTP_INTERNAL_SERVER_ERROR
    ) {
        $this->integration = $integration;

        parent::__construct($this->createNoContentRenderer($defaultStatusCode));

        $this->registerHttpCodeMapping([
            HttpException::class                        => Response::HTTP_INTERNAL_SERVER_ERROR,
            GoneHttpException::class                    => Response::HTTP_GONE,
            ConflictHttpException::class                => Response::HTTP_CONFLICT,
            NotFoundHttpException::class                => Response::HTTP_NOT_FOUND,
            BadRequestHttpException::class              => Response::HTTP_BAD_REQUEST,
            AccessDeniedHttpException::class            => Response::HTTP_FORBIDDEN,
            UnauthorizedHttpException::class            => Response::HTTP_UNAUTHORIZED,
            NotAcceptableHttpException::class           => Response::HTTP_NOT_ACCEPTABLE,
            LengthRequiredHttpException::class          => Response::HTTP_LENGTH_REQUIRED,
            TooManyRequestsHttpException::class         => Response::HTTP_TOO_MANY_REQUESTS,
            MethodNotAllowedHttpException::class        => Response::HTTP_METHOD_NOT_ALLOWED,
            PreconditionFailedHttpException::class      => Response::HTTP_PRECONDITION_FAILED,
            ServiceUnavailableHttpException::class      => Response::HTTP_SERVICE_UNAVAILABLE,
            PreconditionRequiredHttpException::class    => Response::HTTP_PRECONDITION_REQUIRED,
            UnsupportedMediaTypeHttpException::class    => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
        ]);
    }

    /**
     * @return ResponsesInterface
     */
    protected function getResponses()
    {
        if ($this->responses === null) {
            $this->responses = $this->integration->getFromContainer(ResponsesInterface::class);
        }

        return $this->responses;
    }

    /**
     * @return CodecMatcherInterface
     */
    protected function getCodecMatcher()
    {
        if ($this->codecMatcher === null) {
            $this->codecMatcher = $this->integration->getFromContainer(CodecMatcherInterface::class);
        }

        return $this->codecMatcher;
    }

    /**
     * @param int $statusCode
     *
     * @return RendererInterface
     */
    public function createNoContentRenderer($statusCode)
    {
        return new NoContentRenderer($this->getResponses(), $statusCode);
    }

    /**
     * @param int     $statusCode
     * @param Closure $converter
     *
     * @return RendererInterface
     */
    public function createConvertContentRenderer($statusCode, Closure $converter)
    {
        return new ConvertContentRenderer($this->getCodecMatcher(), $this->getResponses(), $statusCode, $converter);
    }

    /**
     * @param array $exToCodeMapping
     */
    public function registerHttpCodeMapping(array $exToCodeMapping)
    {
        foreach ($exToCodeMapping as $exClass => $statusCode) {
            $renderer = $this->createNoContentRenderer($statusCode);
            $this->registerRenderer($exClass, $renderer);
        }
    }
}

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
use \Symfony\Component\HttpKernel\Exception\HttpException;
use \Symfony\Component\HttpKernel\Exception\GoneHttpException;
use \Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use \Neomerx\JsonApi\Contracts\Integration\NativeResponsesInterface;
use \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use \Neomerx\JsonApi\Exceptions\RenderContainer as BaseRenderContainer;
use \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use \Symfony\Component\HttpKernel\Exception\LengthRequiredHttpException;
use \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use \Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use \Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;
use \Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * @package Neomerx\Limoncello
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RenderContainer extends BaseRenderContainer
{
    /**
     * @param NativeResponsesInterface $responses
     * @param Closure                  $extensionsClosure
     * @param int                      $defaultStatusCode
     */
    public function __construct(
        NativeResponsesInterface $responses,
        Closure $extensionsClosure,
        $defaultStatusCode = Response::HTTP_INTERNAL_SERVER_ERROR
    ) {
        parent::__construct($responses, $extensionsClosure, $defaultStatusCode);

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

        $this->registerJsonApiErrorMapping([
            JsonApiException::class                     => Response::HTTP_BAD_REQUEST,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getErrorsRender($statusCode)
    {
        return parent::getErrorsRender($statusCode);
    }
}

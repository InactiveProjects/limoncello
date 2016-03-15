<?php namespace Neomerx\Limoncello\Errors;

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

use Exception;
use Illuminate\Contracts\Container\Container as ContainerInterface;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerInterface;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Neomerx\Limoncello\Contracts\Http\ResponsesInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Neomerx\Limoncello
 */
class JsonApiExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var ExceptionHandlerInterface|null
     */
    private $previous;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface        $container
     * @param ExceptionHandlerInterface $previous
     */
    public function __construct(ContainerInterface $container, ExceptionHandlerInterface $previous = null)
    {
        $this->previous  = $previous;
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function report(Exception $exception)
    {
        $this->previous === null ?: $this->previous->report($exception);
    }

    /**
     * @inheritdoc
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof JsonApiException) {
            $response = $this->createJsonApiResponse($exception);
        } else {
            $response = $this->previous === null ? null : $this->previous->render($request, $exception);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function renderForConsole($output, Exception $exception)
    {
        /** @var OutputInterface $output */

        $this->previous === null ?: $this->previous->renderForConsole($output, $exception);
    }

    /**
     * @param JsonApiException $exception
     *
     * @return Response
     */
    protected function createJsonApiResponse(JsonApiException $exception)
    {
        /** @var ResponsesInterface $responses */
        $responses = $this->container->make(ResponsesInterface::class);

        $errors = $exception->getErrors();
        $code   = $exception->getHttpCode();

        switch (count($errors)) {
            case 0:
                $response = $responses->getCodeResponse($code);
                break;

            case 1:
                $response = $responses->getErrorResponse($errors[0], $code);
                break;

            default:
                $response = $responses->getErrorResponse($errors, $code);
                break;
        }

        return $response;
    }
}

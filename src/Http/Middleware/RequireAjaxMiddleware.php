<?php namespace Neomerx\Limoncello\Http\Middleware;

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
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Neomerx\Limoncello\Contracts\IntegrationInterface;

/**
 * @package Neomerx\Limoncello
 */
class RequireAjaxMiddleware
{
    /**
     * @var IntegrationInterface
     */
    private $integration;

    /**
     * Constructor.
     *
     * @param IntegrationInterface $integration
     */
    public function __construct(IntegrationInterface $integration)
    {
        $this->integration = $integration;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var Response $response */
        $response = $request->isXmlHttpRequest() === true ? $next($request) : $this->createForbiddenResponse();

        return $response;
    }

    /**
     * @return Response
     */
    protected function createForbiddenResponse()
    {
        $response = $this->integration->createResponse(null, Response::HTTP_FORBIDDEN, []);

        return $response;
    }
}

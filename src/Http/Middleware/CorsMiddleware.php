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
class CorsMiddleware
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
        // if it's CORS pre-flight stop processing request and return CORS headers
        $isCorsPreFlight = (strcasecmp('OPTIONS', $request->getMethod()) === 0);
        if ($isCorsPreFlight === true) {
            $response = $this->createResponse(null);
        } else {
            /** @var Response $response */
            $response = $next($request);
            $response->headers->add($this->getCorsHeaders());
        }

        return $response;
    }

    /**
     * @param string|null $content
     * @param int         $statusCode
     *
     * @return Response
     */
    protected function createResponse($content, $statusCode = Response::HTTP_OK)
    {
        $response = $this->integration->createResponse($content, $statusCode, $this->getCorsHeaders());

        return $response;
    }

    /**
     * @return array
     */
    protected function getCorsHeaders()
    {
        return [
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Headers'     => 'Content-Type',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods'     => 'PATCH, GET, POST, DELETE, OPTIONS',
            'Access-Control-Max-Age'           => 30, // how long the pre-flight response can be cached in seconds
        ];
    }
}

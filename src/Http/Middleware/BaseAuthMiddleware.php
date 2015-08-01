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
abstract class BaseAuthMiddleware
{
    /**
     * Authentication scheme. Child classes should override this constant.
     */
    const AUTHENTICATION_SCHEME = null;

    /**
     * Authorization header.
     */
    const HEADER_AUTHORIZATION = 'Authorization';

    /**
     * WWW authenticate header.
     */
    const HEADER_WWW_AUTHENTICATE = 'WWW-Authenticate';

    /**
     * @var IntegrationInterface
     */
    private $integration;

    /**
     * @var Closure
     */
    private $authenticationClosure;

    /**
     * @var Closure|null
     */
    private $authorizationClosure;

    /**
     * @var string|null
     */
    private $realm;

    /**
     * Constructor.
     *
     * @param IntegrationInterface $integration
     * @param Closure              $authenticateClosure
     * @param Closure|null         $authorizeClosure
     * @param string|null          $realm
     */
    public function __construct(
        IntegrationInterface $integration,
        Closure $authenticateClosure,
        Closure $authorizeClosure = null,
        $realm = null
    ) {
        $this->realm                 = $realm;
        $this->integration           = $integration;
        $this->authenticationClosure = $authenticateClosure;
        $this->authorizationClosure  = $authorizeClosure;
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    abstract protected function authenticate(Request $request);

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $isAuthenticated = $this->authenticate($request);
        $isAuthorized    = $isAuthenticated === true ? $this->authorize($request) : $isAuthenticated;

        return $isAuthorized === true ? $next($request) : $this->getUnauthorizedResponse();
    }

    /**
     * Get response for invalid authentication credentials.
     *
     * @return Response
     */
    protected function getUnauthorizedResponse()
    {
        $authHeaderValue = $this->realm === null ? static::AUTHENTICATION_SCHEME :
            static::AUTHENTICATION_SCHEME . ' realm="' . $this->realm . '"';

        return $this->integration->createResponse(
            null,
            Response::HTTP_UNAUTHORIZED,
            [self::HEADER_WWW_AUTHENTICATE => $authHeaderValue]
        );
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    protected function authorize(Request $request)
    {
        $isAuthorized = true;

        // if authorization is required check it as well
        if (($authorizationClosure = $this->getAuthorizationClosure()) !== null) {
            $isAuthorized = $authorizationClosure($request);
        }

        return $isAuthorized;
    }

    /**
     * @return Closure|null
     */
    protected function getAuthorizationClosure()
    {
        return $this->authorizationClosure;
    }

    /**
     * @return Closure
     */
    protected function getAuthenticationClosure()
    {
        return $this->authenticationClosure;
    }
}

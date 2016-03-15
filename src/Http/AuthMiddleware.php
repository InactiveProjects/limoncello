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

use Closure;
use Illuminate\Contracts\Auth\Factory as AuthManagerInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @package App
 */
class AuthMiddleware
{
    /**
     * @var AuthManagerInterface
     */
    protected $authManager;

    /**
     * @param AuthManagerInterface $authManager
     */
    public function __construct(AuthManagerInterface $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request     $request
     * @param  Closure     $next
     * @param  string|null $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $authOk = $this->authManager->guard($guard)->check();

        return $authOk === true ? $next($request) : new Response(null, Response::HTTP_UNAUTHORIZED);
    }
}

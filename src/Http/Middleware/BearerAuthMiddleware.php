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

use \Symfony\Component\HttpFoundation\Request;

/**
 * @package Neomerx\Limoncello
 */
class BearerAuthMiddleware extends BaseAuthMiddleware
{
    /**
     * @inheritdoc
     */
    const AUTHENTICATION_SCHEME = 'Bearer';

    /**
     * @inheritdoc
     */
    protected function authenticate(Request $request)
    {
        $isAuthenticated = false;

        // 7 is a length of 'Bearer ' in Bearer Auth header
        $bearerToken = substr($request->headers->get(self::HEADER_AUTHORIZATION), 7);

        // check user authentication
        if ($bearerToken !== false) {
            $authenticateClosure = $this->getAuthenticationClosure();
            $isAuthenticated     = $authenticateClosure($bearerToken);
        }

        return $isAuthenticated;
    }
}

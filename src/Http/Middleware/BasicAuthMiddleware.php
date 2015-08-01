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
class BasicAuthMiddleware extends BaseAuthMiddleware
{
    /**
     * @inheritdoc
     */
    const AUTHENTICATION_SCHEME = 'Basic';

    /**
     * @inheritdoc
     */
    protected function authenticate(Request $request)
    {
        $isAuthenticated = false;

        $authHeader = $request->headers->get(self::HEADER_AUTHORIZATION);
        // 6 is a length of 'Basic ' in Basic Auth header
        $idAndPassInBase64 = substr($authHeader, 6);
        if (empty($idAndPassInBase64) === false) {
            $idAndPassDecoded = base64_decode($idAndPassInBase64);
            if (is_string($idAndPassDecoded) === true) {
                // RFC2617 #2 says user name can't contain ':' thus we have to split by the first ':'
                $splitPos = strpos($idAndPassDecoded, ':');
                if ($splitPos !== false) {
                    $userId = substr($idAndPassDecoded, 0, $splitPos);
                    // if $splitPos is the last character (substr returns false) it will be empty string
                    $password = (string)substr($idAndPassDecoded, $splitPos + 1);

                    // check user authentication (login and password)
                    $authenticateClosure = $this->getAuthenticationClosure();
                    $isAuthenticated = $authenticateClosure($userId, $password);
                }
            }
        }

        return $isAuthenticated;
    }
}

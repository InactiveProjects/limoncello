<?php namespace Neomerx\Limoncello\Contracts;

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
use \Neomerx\JsonApi\Contracts\Integration\CurrentRequestInterface;
use \Neomerx\JsonApi\Contracts\Integration\NativeResponsesInterface;
use \Neomerx\JsonApi\Contracts\Parameters\SupportedExtensionsInterface;

/**
 * @package Neomerx\Limoncello
 */
interface IntegrationInterface extends NativeResponsesInterface, CurrentRequestInterface
{
    /**
     * Get Limoncello config.
     *
     * @return array
     */
    public function getConfig();

    /**
     * Get current request.
     *
     * @return Request
     */
    public function getCurrentRequest();

    /**
     * Declare JSON API extensions supported in current controller/request/response.
     *
     * @param SupportedExtensionsInterface $extensions
     *
     * @return void
     */
    public function declareSupportedExtensions(SupportedExtensionsInterface $extensions);
}

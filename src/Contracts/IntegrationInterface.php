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
     * Get value from container by key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getFromContainer($key);

    /**
     * Set value in container.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function setInContainer($key, $value);

    /**
     * Check if container has value by key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasInContainer($key);
}

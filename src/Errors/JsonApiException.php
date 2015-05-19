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

use \Exception;
use \RuntimeException;
use \Neomerx\JsonApi\Document\Error;

/**
 * @package Neomerx\Limoncello
 */
class JsonApiException extends RuntimeException
{
    /**
     * @var Error
     */
    private $error;

    /**
     * @param int|string|null $idx
     * @param string|null     $href
     * @param string|null     $status
     * @param string|null     $code
     * @param string|null     $title
     * @param string|null     $detail
     * @param string[]|null   $links
     * @param string[]|null   $paths
     * @param array|null      $members Array of additional members in [memberName => memberValue, ...] format
     * @param Exception|null  $previous
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $idx = null,
        $href = null,
        $status = null,
        $code = null,
        $title = null,
        $detail = null,
        array $links = null,
        array $paths = null,
        array $members = null,
        Exception $previous = null
    ) {
        parent::__construct($title, 0, $previous);
        $this->error = new Error($idx, $href, $status, $code, $title, $detail, $links, $paths, $members);
    }

    /**
     * @return Error
     */
    public function getError()
    {
        return $this->error;
    }
}

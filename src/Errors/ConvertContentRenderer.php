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

use \Closure;
use \Exception;
use \Neomerx\JsonApi\Exceptions\BaseRenderer;
use \Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use \Neomerx\JsonApi\Contracts\Responses\ResponsesInterface;

/**
 * @package Neomerx\Limoncello
 */
class ConvertContentRenderer extends BaseRenderer
{
    /**
     * @var CodecMatcherInterface
     */
    private $codecMatcher;

    /**
     * @var Closure
     */
    private $converter;

    /**
     * @param CodecMatcherInterface $codecMatcher
     * @param ResponsesInterface    $responses
     * @param int                   $statusCode
     * @param Closure               $converter Exception to JSON API Error converter
     */
    public function __construct(
        CodecMatcherInterface $codecMatcher,
        ResponsesInterface $responses,
        $statusCode,
        Closure $converter
    ) {
        parent::__construct($responses);

        $this->converter    = $converter;
        $this->codecMatcher = $codecMatcher;

        $this->withStatusCode($statusCode);
    }

    /**
     * @inheritdoc
     */
    public function getContent(Exception $exception)
    {
        $converter = $this->converter;
        $errors    = $converter($exception);
        $encoder   = $this->codecMatcher->getEncoder();
        $content   = is_array($errors) === true ? $encoder->encodeErrors($errors) : $encoder->encodeError($errors);

        return $content;
    }
}

<?php namespace Neomerx\Limoncello\JsonApi;

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

use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\Limoncello\Contracts\JsonApi\PagedDataInterface;

/**
 * @package Neomerx\Limoncello
 */
class PagedData implements PagedDataInterface
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var LinkInterface[]
     */
    private $links;

    /**
     * @var mixed
     */
    private $meta;

    /**
     * PagedData constructor.
     *
     * @param array           $data
     * @param LinkInterface[] $links
     * @param mixed           $meta
     */
    public function __construct(array $data, array $links = [], $meta = null)
    {
        $this->data  = $data;
        $this->links = $links;
        $this->meta  = $meta;
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        return $this->meta;
    }
}

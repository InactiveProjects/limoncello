<?php namespace Neomerx\Limoncello\Contracts\JsonApi;

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

/**
 * @package Neomerx\Limoncello
 */
interface FactoryInterface extends \Neomerx\JsonApi\Contracts\Factories\FactoryInterface
{
    /**
     * @param array $data
     * @param array $links
     * @param mixed $meta
     *
     * @return PagedDataInterface
     */
    public function createPagedData(array $data, array $links = [], $meta = null);

    /**
     * @param bool $isAddFirst
     * @param bool $isAddPrev
     * @param bool $isAddNext
     * @param bool $isAddLast
     *
     * @return PagingStrategyInterface
     */
    public function createPagingStrategy($isAddFirst = true, $isAddPrev = true, $isAddNext = true, $isAddLast = true);
}

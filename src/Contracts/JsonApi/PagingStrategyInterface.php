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

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @package Neomerx\Limoncello
 */
interface PagingStrategyInterface
{
    /**
     * @param LengthAwarePaginator $paginator
     * @param string               $currentUrl
     * @param bool                 $treatAsHref
     * @param array                $urlParameters
     *
     * @return PagedDataInterface
     */
    public function createPagedData(
        LengthAwarePaginator $paginator,
        $currentUrl,
        $treatAsHref,
        array $urlParameters = []
    );

    /**
     * @param bool $isAddLink
     *
     * @return $this
     */
    public function setAddLinkToFirst($isAddLink);

    /**
     * @param bool $isAddLink
     *
     * @return $this
     */
    public function setAddLinkToPrev($isAddLink);

    /**
     * @param bool $isAddLink
     *
     * @return $this
     */
    public function setAddLinkToNext($isAddLink);

    /**
     * @param bool $isAddLink
     *
     * @return $this
     */
    public function setAddLinkToLast($isAddLink);
}

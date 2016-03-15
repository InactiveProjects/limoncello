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

use Neomerx\JsonApi\Factories\Factory as BaseFactory;
use Neomerx\Limoncello\Contracts\JsonApi\FactoryInterface;

/**
 * @package Neomerx\Limoncello
 */
class Factory extends BaseFactory implements FactoryInterface
{
    /** @noinspection PhpMissingParentCallCommonInspection
     *
     * @inheritdoc
     */
    public function createContainer(array $providers = [])
    {
        return new SchemaContainer($this, $providers);
    }

    /**
     * @inheritdoc
     */
    public function createPagingStrategy($isAddFirst = true, $isAddPrev = true, $isAddNext = true, $isAddLast = true)
    {
        return new NumberAndSizePagingStrategy($this, $isAddFirst, $isAddPrev, $isAddNext, $isAddLast);
    }

    /**
     * @inheritdoc
     */
    public function createPagedData(array $data, array $links = [], $meta = null)
    {
        return new PagedData($data, $links, $meta);
    }
}

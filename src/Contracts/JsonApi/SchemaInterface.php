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

use Neomerx\JsonApi\Contracts\Schema\SchemaProviderInterface;

/**
 * @package Neomerx\Limoncello
 */
interface SchemaInterface extends SchemaProviderInterface
{
    /** Type */
    const TYPE = null;

    /** Model class name */
    const MODEL = null;

    /** Mapping key */
    const IDX_TYPE = 0;

    /** Mapping key */
    const IDX_ATTRIBUTES = 1;

    /** Mapping key */
    const IDX_BELONGS_TO = 2;

    /** Mapping key */
    const IDX_HAS_MANY = 3;

    /** Mapping key */
    const IDX_BELONGS_TO_MANY = 4;

    /** Mapping key */
    const IDX_INCLUDE = 5;

    /**
     * @return array
     */
    public function getAttributesMap();

    /**
     * @return array
     */
    public function getModelAttributesToResourceMap();

    /**
     * @param string $modelAttribute
     *
     * @return string
     */
    public function toResourceAttribute($modelAttribute);

    /**
     * @return array
     */
    public function getBelongsToRelationshipsMap();

    /**
     * @return array
     */
    public function getModelBelongsToRelationshipsToResourceMap();

    /**
     * @param string $modelBelongsTo
     *
     * @return string
     */
    public function toResourceBelongsTo($modelBelongsTo);

    /**
     * @return array
     */
    public function getHasManyRelationshipsMap();

    /**
     * @return array
     */
    public function getModelHasManyRelationshipsToResourceMap();

    /**
     * @param string $modelHasMany
     *
     * @return string
     */
    public function toResourceHasMany($modelHasMany);

    /**
     * @return array
     */
    public function getBelongsToManyRelationshipsMap();

    /**
     * @return array
     */
    public function getModelBelongsToManyRelationshipsToResourceMap();

    /**
     * @return array
     */
    public function getWriteOnly();

    /**
     * @return array
     */
    public function getReadOnly();

    /**
     * @param string $attribute
     *
     * @return bool
     */
    public function isReadOnly($attribute);

    /**
     * @param string $attribute
     *
     * @return bool
     */
    public function isWriteOnly($attribute);
}

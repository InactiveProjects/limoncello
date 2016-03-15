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

use Neomerx\JsonApi\Contracts\Schema\ContainerInterface as BaseContainerInterface;

/**
 * @package Neomerx\Limoncello
 */
interface SchemaContainerInterface extends BaseContainerInterface
{
    /**
     * Get schema provider for resource object.
     *
     * @param object $resourceObject
     *
     * @return SchemaInterface
     */
    public function getSchema($resourceObject);

    /**
     * Get schema provider by model class.
     *
     * @param string $modelClass
     *
     * @return SchemaInterface
     */
    public function getSchemaByType($modelClass);

    /**
     * Get schema provider by JSON API type.
     *
     * @param string $resourceType
     *
     * @return SchemaInterface
     */
    public function getSchemaByResourceType($resourceType);

    /**
     * Get schema provider by its type.
     *
     * @param string $schemaClass
     *
     * @return SchemaInterface
     */
    public function getSchemaBySchemaClass($schemaClass);

    /**
     * Get model type by JSON API type.
     *
     * @param string $resourceType
     *
     * @return string
     */
    public function getType($resourceType);

    /**
     * If container has a Schema for resource type.
     *
     * @param string $resourceType
     *
     * @return bool
     */
    public function hasSchemaForResourceType($resourceType);

    /**
     * If container has a Schema for model class.
     *
     * @param string $modelClass
     *
     * @return bool
     */
    public function hasSchemaForModelClass($modelClass);
}

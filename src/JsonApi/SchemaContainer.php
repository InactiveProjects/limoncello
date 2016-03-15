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

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaProviderInterface;
use Neomerx\Limoncello\Contracts\JsonApi\SchemaContainerInterface;
use Neomerx\Limoncello\I18n\Translate as T;

/**
 * @package Neomerx\Limoncello
 */
class SchemaContainer implements SchemaContainerInterface
{
    /**
     * @var SchemaFactoryInterface
     */
    private $factory;

    /**
     * @var array
     */
    private $type2modelMap = [];

    /**
     * @var array
     */
    private $model2typeMap = [];

    /**
     * @var array
     */
    private $model2schemaMap = [];

    /**
     * @var array
     */
    private $schema2ModelMap = [];

    /**
     * @var array
     */
    private $schemaInstances = [];

    /**
     * Constructor.
     *
     * @param SchemaFactoryInterface $factory
     * @param array                  $schemas
     */
    public function __construct(SchemaFactoryInterface $factory, array $schemas)
    {
        $this->factory = $factory;

        foreach ($schemas as $schemaClass) {
            if (empty($schemaClass) === true) {
                $message = T::trans(T::KEY_ERR_EMPTY_SCHEMA);
                throw new InvalidArgumentException($message);
            }

            $typeName = $schemaClass::TYPE;
            if (empty($typeName) === true) {
                $message = T::trans(T::KEY_ERR_EMPTY_TYPE_IN_SCHEMA, [$schemaClass]);
                throw new InvalidArgumentException($message);
            }
            if (array_key_exists($typeName, $this->type2modelMap) === true) {
                $message = T::trans(T::KEY_ERR_SCHEMA_REGISTERED_FOR_RESOURCE, [$typeName]);
                throw new InvalidArgumentException($message);
            }

            $modelClass = $schemaClass::MODEL;
            if (empty($modelClass) === true) {
                $message = T::trans(T::KEY_ERR_EMPTY_MODEL_IN_SCHEMA, [$schemaClass]);
                throw new InvalidArgumentException($message);
            }
            if (array_key_exists($modelClass, $this->type2modelMap) === true) {
                $message = T::trans(T::KEY_ERR_SCHEMA_REGISTERED_FOR_MODEL, [$modelClass]);
                throw new InvalidArgumentException($message);
            }

            $this->model2typeMap[$modelClass]    = $typeName;
            $this->type2modelMap[$typeName]      = $modelClass;
            $this->model2schemaMap[$modelClass]  = $schemaClass;
            $this->schema2ModelMap[$schemaClass] = $modelClass;
        }
    }

    /**
     * @inheritdoc
     */
    public function getSchema($model)
    {
        /** @var Model $model */
        $modelClass = $this->getModelClass($model);
        $result     = $this->getSchemaByType($modelClass);

        return $result;
    }

    /**
     * Get schema provider by resource type.
     *
     * @param string $modelClass
     *
     * @return SchemaProviderInterface
     */
    public function getSchemaByType($modelClass)
    {
        if (array_key_exists($modelClass, $this->schemaInstances) === true) {
            return $this->schemaInstances[$modelClass];
        }

        if ($this->hasSchemaForModelClass($modelClass) === false) {
            $message = T::trans(T::KEY_ERR_NO_SCHEMA_FOR_MODEL, [$modelClass]);
            throw new InvalidArgumentException($message);
        }

        $schemaClass = $this->model2schemaMap[$modelClass];
        $schema      = new $schemaClass($this->factory, $this);

        $this->schemaInstances[$modelClass] = $schema;

        return $schema;
    }

    /**
     * @inheritdoc
     */
    public function getSchemaByResourceType($resourceType)
    {
        $modelClass = $this->getType($resourceType);
        $result     = $this->getSchemaByType($modelClass);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getSchemaBySchemaClass($schemaClass)
    {
        $modelClass = $this->getTypeBySchema($schemaClass);
        $result     = $this->getSchemaByType($modelClass);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getType($resourceType)
    {
        if ($this->hasSchemaForResourceType($resourceType) === false) {
            $message = T::trans(T::KEY_ERR_NO_SCHEMA_FOR_RESOURCE, [$resourceType]);
            throw new InvalidArgumentException($message);
        }

        $result = $this->type2modelMap[$resourceType];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function hasSchemaForResourceType($resourceType)
    {
        $result = array_key_exists($resourceType, $this->type2modelMap);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function hasSchemaForModelClass($modelClass)
    {
        $result = array_key_exists($modelClass, $this->model2schemaMap);

        return $result;
    }

    /**
     * @inheritdoc
     */
    private function getTypeBySchema($schemaClass)
    {
        if (array_key_exists($schemaClass, $this->schema2ModelMap) === false) {
            $message = T::trans(T::KEY_ERR_NO_SCHEMA, [$schemaClass]);
            throw new InvalidArgumentException($message);
        }

        $result = $this->schema2ModelMap[$schemaClass];

        return $result;
    }

    /**
     * @param Model $model
     *
     * @return string
     */
    private function getModelClass(Model $model)
    {
        return get_class($model);
    }
}

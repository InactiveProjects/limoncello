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

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use Neomerx\JsonApi\Schema\SchemaProvider;
use Neomerx\Limoncello\Contracts\JsonApi\FactoryInterface;
use Neomerx\Limoncello\Contracts\JsonApi\PagingStrategyInterface;
use Neomerx\Limoncello\Contracts\JsonApi\SchemaInterface;

/**
 * @package Neomerx\Limoncello
 */
abstract class Schema extends SchemaProvider implements SchemaInterface
{
    /** Schema keyword */
    const KEYWORD_ID = DocumentInterface::KEYWORD_ID;

    /** Schema keyword */
    const KEYWORD_TYPE = DocumentInterface::KEYWORD_TYPE;

    /** Relationship data type */
    const TYPE_ALL_RESOURCES = 0;

    /** Relationship data type */
    const TYPE_LINK_ONLY = 1;

    /** Relationship data type */
    const TYPE_PAGINATED = 2;

    /**
     * Fields will be hidden in output.
     *
     * @var array
     */
    private $writeOnly = [];

    /**
     * Fields will be ignored from input.
     *
     * @var array
     */
    private $readOnly = [];

    /**
     * @var array
     */
    private $writeOnlyMap = null;

    /**
     * @var array
     */
    private $readOnlyMap = null;

    /**
     * @var array
     */
    private $resToModelAttrMap;

    /**
     * @var null|array
     */
    private $modelToResAttrMap;

    /**
     * @var array
     */
    private $belongsToMap;

    /**
     * @var null|array
     */
    private $modBelongsToToResMap;

    /**
     * @var array
     */
    private $hasManyMap;

    /**
     * @var null|array
     */
    private $modHasManyToResMap;

    /**
     * @var array
     */
    private $belongsToManyMap;

    /**
     * @var null|array
     */
    private $modBelongsToManyToResMap;

    /**
     * @var array
     */
    private $includes;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var PagingStrategyInterface
     */
    private $pagingStrategy = false;

    /**
     * @var string
     */
    protected $carbonFormat = Carbon::ISO8601;

    /**
     * @return array
     */
    abstract protected function getSchemaMappings();

    /**
     * EloquentSchema constructor.
     *
     * @param FactoryInterface   $factory
     * @param ContainerInterface $container
     */
    public function __construct(FactoryInterface $factory, ContainerInterface $container)
    {
        $this->factory = $factory;

        $this->init();

        parent::__construct($factory, $container);
    }

    /**
     * @inheritdoc
     */
    final public function getId($model)
    {
        /** @var Model $model */
        return $model->getKey();
    }

    /**
     * @inheritdoc
     */
    final public function getAttributes($model)
    {
        /** @var Model $model */

        $result = $this->resToModelAttrMap;
        foreach ($this->resToModelAttrMap as $jsonAttr => $modelAttr) {
            if ($this->isWriteOnly($jsonAttr) === false) {
                $value             = $model->getAttributeValue($modelAttr);
                $result[$jsonAttr] = ($value instanceof Carbon) === true ? $value->format($this->carbonFormat) : $value;
            } else {
                unset($result[$jsonAttr]);
            }
        }

        return $result;
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     *
     * @inheritdoc
     */
    final public function getRelationships($model, $isPrimary, array $includeRelationships)
    {
        /** @var Model $model */

        $result = [];

        $this->getBelongsToRelationships($model, $isPrimary, $includeRelationships, $result);

        $this->getHasManyRelationships($model, $isPrimary, $includeRelationships, $result);

        $this->getBelongsToManyRelationships($model, $isPrimary, $includeRelationships, $result);

        return $result;
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     *
     * @inheritdoc
     */
    final public function getIncludePaths()
    {
        return $this->includes;
    }

    /**
     * @inheritdoc
     */
    public function getAttributesMap()
    {
        return $this->resToModelAttrMap;
    }

    /**
     * @inheritdoc
     */
    public function getModelAttributesToResourceMap()
    {
        if ($this->modelToResAttrMap === null) {
            $this->modelToResAttrMap = array_flip($this->getAttributesMap());
        }

        return $this->modelToResAttrMap;
    }

    /**
     * @inheritdoc
     */
    public function toResourceAttribute($modelAttribute)
    {
        $map    = $this->getModelAttributesToResourceMap();
        $result = $map[$modelAttribute];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getBelongsToRelationshipsMap()
    {
        return $this->belongsToMap;
    }

    /**
     * @inheritdoc
     */
    public function getModelBelongsToRelationshipsToResourceMap()
    {
        if ($this->modBelongsToToResMap === null) {
            $this->modBelongsToToResMap = [];
            foreach ($this->getBelongsToRelationshipsMap() as $resName => list(, $modelName)) {
                $this->modBelongsToToResMap[$modelName] = $resName;
            }
        }

        return $this->modBelongsToToResMap;
    }

    /**
     * @inheritdoc
     */
    public function toResourceBelongsTo($modelBelongsTo)
    {
        $map    = $this->getModelBelongsToRelationshipsToResourceMap();
        $result = $map[$modelBelongsTo];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getHasManyRelationshipsMap()
    {
        return $this->hasManyMap;
    }

    /**
     * @inheritdoc
     */
    public function getModelHasManyRelationshipsToResourceMap()
    {
        if ($this->modHasManyToResMap === null) {
            $this->modHasManyToResMap = [];
            foreach ($this->getHasManyRelationshipsMap() as $resName => list($modelName)) {
                $this->modHasManyToResMap[$modelName] = $resName;
            }
        }

        return $this->modHasManyToResMap;
    }

    /**
     * @inheritdoc
     */
    public function toResourceHasMany($modelHasMany)
    {
        $map    = $this->getModelHasManyRelationshipsToResourceMap();
        $result = $map[$modelHasMany];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getBelongsToManyRelationshipsMap()
    {
        return $this->belongsToManyMap;
    }

    /**
     * @inheritdoc
     */
    public function getModelBelongsToManyRelationshipsToResourceMap()
    {
        if ($this->modBelongsToManyToResMap === null) {
            $this->modBelongsToManyToResMap = [];
            foreach ($this->getBelongsToManyRelationshipsMap() as $resName => list(, $modelName)) {
                $this->modBelongsToManyToResMap[$modelName] = $resName;
            }
        }

        return $this->modBelongsToManyToResMap;
    }

    /**
     * @param string $modelBelongsToMany
     *
     * @return string
     */
    public function toResourceBelongsToMany($modelBelongsToMany)
    {
        $map    = $this->getModelBelongsToManyRelationshipsToResourceMap();
        $result = $map[$modelBelongsToMany];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getWriteOnly()
    {
        return $this->writeOnly;
    }

    /**
     * @inheritdoc
     */
    public function getReadOnly()
    {
        return $this->readOnly;
    }

    /**
     * @inheritdoc
     */
    public function isWriteOnly($attribute)
    {
        if ($this->writeOnlyMap === null) {
            $this->writeOnlyMap = array_flip($this->getWriteOnly());
        }

        $result = array_key_exists($attribute, $this->writeOnlyMap);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function isReadOnly($attribute)
    {
        if ($this->readOnlyMap === null) {
            $this->readOnlyMap = array_flip($this->getReadOnly());
        }

        $result = array_key_exists($attribute, $this->readOnlyMap);

        return $result;
    }

    /**
     * @param array $fields
     */
    public function addWriteOnly(array $fields)
    {
        $this->writeOnly    = array_merge($this->writeOnly, $fields);
        $this->writeOnlyMap = null;
    }

    /**
     * @param array $fields
     */
    public function addReadOnly(array $fields)
    {
        $this->readOnly    = array_merge($this->readOnly, $fields);
        $this->readOnlyMap = null;
    }

    /**
     * @param Model  $model
     * @param bool   $isPrimary
     * @param array  $includeRelationships
     * @param array &$result
     *
     * @return void
     */
    protected function getBelongsToRelationships(Model $model, $isPrimary, array $includeRelationships, array &$result)
    {
        $isPrimary ?: null;

        foreach ($this->belongsToMap as $jsonRel => list(, $modelRel)) {
            if ($this->isWriteOnly($jsonRel) === true) {
                continue;
            }

            if ($model->relationLoaded($modelRel) === true) {
                // relationship is already loaded
                $value = $model->getRelation($modelRel);
            } else {
                // relationship is not loaded

                // will this relationship be included as full resource (or only id will be needed)?
                if (isset($includeRelationships[$jsonRel]) === true) {
                    // as it will be included as full resource we have to give full resource
                    $value = $model->getRelationValue($modelRel);
                } else {
                    // as it will be included as just id and type so it's not necessary to load it from database
                    /** @var BelongsTo $relation */
                    $relation  = $model->{$modelRel}();
                    $fkName    = $relation->getForeignKey();
                    $relatedId = $model->{$fkName};
                    if ($relatedId === null) {
                        $value = null;
                    } else {
                        $value = $relation->getRelated();
                        $value->setRawAttributes([$value->getKeyName() => $relatedId], true);
                    }
                }
            }

            $result[$jsonRel] = [self::DATA => $value];
        }
    }

    /**
     * @param Model  $model
     * @param bool   $isPrimary
     * @param array  $includeRelationships
     * @param array &$result
     *
     * @return void
     */
    protected function getHasManyRelationships(Model $model, $isPrimary, array $includeRelationships, array &$result)
    {
        foreach ($this->hasManyMap as $jsonRel => list($modelRel, $fetchType, $pageSize)) {
            if ($this->isWriteOnly($jsonRel) === false) {
                $isWillBeIncluded = isset($includeRelationships[$jsonRel]);
                // If resource is not primary and data from relationships would be included we'll
                // take paged data. Developer enabled inclusion for this relationship so it
                // it looks reasonable. Otherwise no data will be included to relationship on user
                // request. If you find it's not logical then override this method and do it your way.
                if ($isPrimary === false) {
                    $fetchType = $isWillBeIncluded === true ? self::TYPE_PAGINATED : self::TYPE_LINK_ONLY;
                }
                $this->getReadDataFromRelationship($model, $jsonRel, $fetchType, $modelRel, $pageSize, $result);
            }
        }
    }

    /**
     * @param Model  $model
     * @param bool   $isPrimary
     * @param array  $includeRelationships
     * @param array &$result
     *
     * @return void
     */
    protected function getBelongsToManyRelationships(
        Model $model,
        $isPrimary,
        array $includeRelationships,
        array &$result
    ) {
        foreach ($this->belongsToManyMap as $jsonRel => list(, $modelRel, $fetchType, $pageSize)) {
            if ($this->isWriteOnly($jsonRel) === false) {
                $isWillBeIncluded = isset($includeRelationships[$jsonRel]);
                // If resource is not primary and data from relationships would be included we'll
                // take paged data. Developer enabled inclusion for this relationship so it
                // it looks reasonable. Otherwise no data will be included to relationship on user
                // request. If you find it's not logical then override this method and do it your way.
                if ($isPrimary === false) {
                    $fetchType = $isWillBeIncluded === true ? self::TYPE_PAGINATED : self::TYPE_LINK_ONLY;
                }
                $this->getReadDataFromRelationship($model, $jsonRel, $fetchType, $modelRel, $pageSize, $result);
            }
        }
    }

    /**
     * @return PagingStrategyInterface
     */
    protected function getPagingStrategy()
    {
        if ($this->pagingStrategy === false) {
            $this->pagingStrategy = $this->getFactory()->createPagingStrategy();
        }

        return $this->pagingStrategy;
    }

    /**
     * @param Model   $model
     * @param string  $jsonRel
     * @param int     $fetchType
     * @param string  $modelRel
     * @param int     $pageSize
     * @param array  &$result
     */
    protected function getReadDataFromRelationship(
        Model $model,
        $jsonRel,
        $fetchType,
        $modelRel,
        $pageSize,
        array &$result
    ) {
        switch ($fetchType) {
            case self::TYPE_ALL_RESOURCES:
                $result[$jsonRel] = [self::DATA => $model->getRelationValue($modelRel)->all()];
                break;
            case self::TYPE_LINK_ONLY:
                $relSelfLink = $this->getRelationshipSelfLink($model, $modelRel);
                $result[$jsonRel] = [
                    self::LINKS => [LinkInterface::SELF => $relSelfLink],
                    self::SHOW_DATA => false,
                ];
                break;
            default:
                /** @var Relation $relation */
                $relation  = $model->{$modelRel}();
                $paginator = $relation->getQuery()->paginate($pageSize);
                $url       = $this->getRelationshipSelfUrl($model, $modelRel);
                $pagedData = $this->getPagingStrategy()->createPagedData($paginator, $url, false);
                $result[$jsonRel] = [
                    self::DATA  => $pagedData->getData(),
                    self::META  => $pagedData->getMeta(),
                    self::LINKS => $pagedData->getLinks(),
                ];
                break;
        }
    }

    /**
     * @return FactoryInterface
     */
    private function getFactory()
    {
        return $this->factory;
    }

    /**
     * @return void
     */
    private function init()
    {
        $mappings = static::getSchemaMappings();

        $getArrayValue = function (array $array, $key, $default) {
            return array_key_exists($key, $array) === true ? $array[$key] : $default;
        };

        $this->resourceType      = $mappings[self::IDX_TYPE];
        $this->resToModelAttrMap = $getArrayValue($mappings, self::IDX_ATTRIBUTES, []);
        $this->belongsToMap      = $getArrayValue($mappings, self::IDX_BELONGS_TO, []);
        $this->hasManyMap        = $getArrayValue($mappings, self::IDX_HAS_MANY, []);
        $this->belongsToManyMap  = $getArrayValue($mappings, self::IDX_BELONGS_TO_MANY, []);
        $this->includes          = $getArrayValue($mappings, self::IDX_INCLUDE, []);
    }
}

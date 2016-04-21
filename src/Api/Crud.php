<?php namespace Neomerx\Limoncello\Api;

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

use Closure;
use Illuminate\Contracts\Container\Container as ContainerInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Neomerx\Limoncello\Contracts\Api\CrudAuthorizationsInterface;
use Neomerx\Limoncello\Contracts\Api\CrudInterface;
use Neomerx\Limoncello\Contracts\JsonApi\FactoryInterface;
use Neomerx\Limoncello\Contracts\JsonApi\PagedDataInterface;
use Neomerx\Limoncello\Contracts\JsonApi\SchemaContainerInterface as SchemaContainerInterface;
use Neomerx\Limoncello\Errors\ErrorCollection;
use Neomerx\Limoncello\Http\JsonApiRequest;
use Neomerx\Limoncello\I18n\Translate as T;
use Neomerx\Limoncello\JsonApi\Decoder\RelationshipsObject;
use Neomerx\Limoncello\JsonApi\Decoder\ResourceIdentifierObject;
use Neomerx\Limoncello\JsonApi\Schema;

/**
 * @package Neomerx\Limoncello
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Crud implements CrudInterface
{
    /**
     * @var Model
     */
    private $model;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var CrudAuthorizationsInterface
     */
    private $authorizations;

    /**
     * @var int
     */
    private $policyErrorHttpCode;

    /**
     * @param ContainerInterface          $container
     * @param FactoryInterface            $factory
     * @param Model                       $model
     * @param CrudAuthorizationsInterface $authorizations
     * @param int                         $policyErrorHttpCode
     */
    public function __construct(
        ContainerInterface $container,
        FactoryInterface $factory,
        Model $model,
        CrudAuthorizationsInterface $authorizations,
        $policyErrorHttpCode = Response::HTTP_FORBIDDEN
    ) {
        $this->model               = $model;
        $this->container           = $container;
        $this->authorizations      = $authorizations;
        $this->policyErrorHttpCode = $policyErrorHttpCode;
        $this->factory             = $factory;
    }

    /**
     * @return string
     */
    public function getModelClass()
    {
        return get_class($this->getModel());
    }

    /**
     * @inheritdoc
     */
    public function index(EncodingParametersInterface $parameters = null, array $relations = [])
    {
        $builder = $this->createBuilderOnIndex($relations, $parameters);

        $result = $this->readOnIndex($builder, $parameters);

        $this->applyIndexPolicy($result->getData());

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function read($index, EncodingParametersInterface $parameters = null, array $relations = [])
    {
        $builder = $this->createBuilderOnRead($relations, $parameters);

        $model = $builder->findOrFail($index);

        $this->applyReadPolicy($model);

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function delete($index)
    {
        $model = $this->read($index);

        $errors = $this->createErrorCollection();
        $this->getAuthorizations()->canDelete($errors, $model);
        if ($errors->count() > 0) {
            throw new JsonApiException($errors);
        }

        $this->onDelete($model);

        $this->executeInTransaction(function () use ($model) {
            $this->deleteModel($model);
            $this->onDeleting($model);
        });
    }

    /**
     * @inheritdoc
     */
    public function create(JsonApiRequest $request)
    {
        /** @var SchemaContainerInterface $container */
        $container = $this->getContainer()->make(SchemaContainerInterface::class);

        /** @var Schema $schema */
        $schema = $container->getSchemaByType($this->getModelClass());

        $errors = $this->createErrorCollection();
        if ($schema::TYPE !== $request->getType()) {
            $errors->addDataTypeError(T::trans(T::KEY_ERR_INVALID_ELEMENT));
        }

        $this->validateInputOnCreate($request, $schema, $errors);

        $newInstance = $this->createInstance($request, $errors);
        if ($errors->count() > 0) {
            throw new JsonApiException($errors);
        }

        $this->getAuthorizations()->canCreateNewInstance($errors, $newInstance);
        if ($errors->count() > 0) {
            throw new JsonApiException($errors);
        }

        $this->setAttributesOnCreate($newInstance, $request->getAttributes(), $schema, $errors);
        $this->setBelongsToOnCreate($newInstance, $request->getBelongsTo(), $schema, $errors);
        if ($errors->count() > 0) {
            throw new JsonApiException($errors);
        }

        $this->validateModelOnCreate($newInstance, $schema, $errors);
        if ($errors->count() > 0) {
            throw new JsonApiException($errors);
        }

        $belongsToMany = $request->getBelongsToMany();
        $this->executeInTransaction(function () use ($newInstance, $schema, $belongsToMany, $errors) {
            $this->getAuthorizations()->canSaveNewInstance($errors, $newInstance, $schema);
            if ($errors->count() > 0) {
                throw new JsonApiException($errors);
            }
            $this->saveModelOnCreate($newInstance);

            $this->setBelongsToManyOnCreate($newInstance, $belongsToMany, $schema, $errors);
            if ($errors->count() > 0) {
                throw new JsonApiException($errors);
            }
            $this->onCreating($newInstance);
        });

        return $newInstance;
    }

    /**
     * @inheritdoc
     */
    public function update(JsonApiRequest $request)
    {
        $model = $this->read($request->getId());

        $errors = $this->createErrorCollection();
        $this->getAuthorizations()->canUpdateExistingInstance($errors, $model);
        if ($errors->count() > 0) {
            throw new JsonApiException($errors);
        }

        $this->onUpdate($request, $model);

        /** @var SchemaContainerInterface $container */
        $container = $this->getContainer()->make(SchemaContainerInterface::class);
        /** @var Schema $schema */
        $schema = $container->getSchemaByType($this->getModelClass());

        if ($schema::TYPE !== $request->getType()) {
            $errors->addDataTypeError(T::trans(T::KEY_ERR_INVALID_ELEMENT));
        }

        $this->validateInputOnUpdate($request, $schema, $errors);

        $this->setAttributesOnUpdate($model, $request->getAttributes(), $schema, $errors);
        $this->setBelongsToOnUpdate($model, $request->getBelongsTo(), $schema, $errors);

        $this->validateModelOnUpdate($model, $schema, $errors);

        if ($errors->count() > 0) {
            throw new JsonApiException($errors);
        }

        $belongsToMany = $request->getBelongsToMany();
        $this->executeInTransaction(function () use ($model, $schema, $belongsToMany, $errors) {
            $this->onUpdating($model);

            $this->getAuthorizations()->canSaveExistingInstance($errors, $model, $schema);
            if ($errors->count() > 0) {
                throw new JsonApiException($errors);
            }
            $this->saveModelOnUpdate($model);

            $this->setBelongsToManyOnUpdate($model, $belongsToMany, $schema, $errors);
            if ($errors->count() > 0) {
                throw new JsonApiException($errors);
            }
        });

        return $model;
    }

    /**
     * @param JsonApiRequest  $request
     * @param ErrorCollection $errors
     *
     * @return null|Model
     */
    protected function createInstance(JsonApiRequest $request, ErrorCollection $errors)
    {
        $instance = null;

        // we do not support input 'id' on creation
        if (null !== $request->getId()) {
            $errors->addDataIdError(T::trans(T::KEY_ERR_INVALID_ELEMENT));
        } else {
            $instance = $this->model->newInstance();
        }

        return $instance;
    }

    /**
     * @param array                            $relations
     * @param EncodingParametersInterface|null $parameters
     *
     * @return Builder
     */
    protected function createBuilderOnRead(array $relations = [], EncodingParametersInterface $parameters = null)
    {
        $parameters ?: null;

        $builder = $this->getModel()->newQuery();
        if (empty($relations) === false) {
            $builder = $builder->with($relations);
        }

        return $builder;
    }

    /**
     * @param array                            $relations
     * @param EncodingParametersInterface|null $parameters
     *
     * @return Builder
     */
    protected function createBuilderOnIndex(array $relations = [], EncodingParametersInterface $parameters = null)
    {
        $parameters ?: null;

        $builder = $this->getModel()->newQuery();
        if (empty($relations) === false) {
            $builder = $builder->with($relations);
        }

        return $builder;
    }

    /**
     * @param Builder                     $builder
     * @param EncodingParametersInterface $parameters
     *
     * @return PagedDataInterface
     */
    protected function readOnIndex(Builder $builder, EncodingParametersInterface $parameters = null)
    {
        $parameters ?: null;

        $result = $this->getFactory()->createPagedData($builder->get());

        return $result;
    }

    /**
     * Validate model on read.
     *
     * @param Model $model
     *
     * @return void
     */
    protected function applyReadPolicy(Model $model)
    {
        $errors = $this->createErrorCollection();
        $this->getAuthorizations()->canRead($errors, $model);
        if ($errors->count() > 0) {
            throw new JsonApiException($errors, $this->policyErrorHttpCode);
        }
    }

    /**
     * Validate models on index.
     *
     * @param array $models
     *
     * @return void
     */
    protected function applyIndexPolicy($models)
    {
        $errors = $this->createErrorCollection();
        foreach ($models as $model) {
            $this->getAuthorizations()->canRead($errors, $model);
        }
        if ($errors->count() > 0) {
            throw new JsonApiException($errors, $this->policyErrorHttpCode);
        }
    }

    /**
     * @param Model $newInstance
     */
    protected function saveModelOnCreate(Model $newInstance)
    {
        $newInstance->saveOrFail();
    }

    /**
     * @param Model $model
     */
    protected function saveModelOnUpdate(Model $model)
    {
        $model->saveOrFail();
    }

    /**
     * @param Model $model
     */
    protected function deleteModel(Model $model)
    {
        $model->delete();
    }

    /**
     * @param Model           $model
     * @param array           $attributes
     * @param Schema          $schema
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function setAttributesOnCreate(
        Model $model,
        array $attributes,
        Schema $schema,
        ErrorCollection $errors
    ) {
        if ($this->getAuthorizations()->canSetAttributesOnCreate($errors, $model, $schema, $attributes) === true) {
            $policy = function (
                Model $model,
                $fieldName,
                $resourceRelName,
                $value,
                ErrorCollection $errors
            ) use ($schema) {
                $resourceRelName ?: null;

                return $this->getAuthorizations()
                    ->canSetAttributeOnCreate($errors, $model, $schema, $fieldName, $value);
            };
            $this->setAttributes($model, $attributes, $schema, $policy, $errors);
        }
    }

    /**
     * @param Model           $model
     * @param array           $attributes
     * @param Schema          $schema
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function setAttributesOnUpdate(
        Model $model,
        array $attributes,
        Schema $schema,
        ErrorCollection $errors
    ) {
        if ($this->getAuthorizations()->canSetAttributesOnUpdate($errors, $model, $schema, $attributes) === true) {
            $policy = function (
                Model $model,
                $fieldName,
                $resourceRelName,
                $value,
                ErrorCollection $errors
            ) use ($schema) {
                $resourceRelName ?: null;

                return $this->getAuthorizations()
                    ->canSetAttributeOnUpdate($errors, $model, $schema, $fieldName, $value);
            };
            $this->setAttributes($model, $attributes, $schema, $policy, $errors);
        }
    }

    /**
     * @param Model           $model
     * @param array           $attributes
     * @param Schema          $schema
     * @param Closure         $policy
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function setAttributes(
        Model $model,
        array $attributes,
        Schema $schema,
        Closure $policy,
        ErrorCollection $errors
    ) {
        $attributeMap = $schema->getAttributesMap();
        foreach ($attributes as $resourceRelName => $value) {
            if (array_key_exists($resourceRelName, $attributeMap) === true &&
                $schema->isReadOnly($resourceRelName) === false
            ) {
                $fieldName = $attributeMap[$resourceRelName];
                if ($policy($model, $fieldName, $resourceRelName, $value, $errors) === true) {
                    $model->setAttribute($fieldName, $value);
                }
            }
        }
    }

    /**
     * @param Model           $model
     * @param array           $belongsTo
     * @param Schema          $schema
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function setBelongsToOnCreate(
        Model $model,
        array $belongsTo,
        Schema $schema,
        ErrorCollection $errors
    ) {
        if ($this->getAuthorizations()->canSetBelongsToOnCreate($errors, $model, $schema, $belongsTo) === true) {
            $policy = function (
                Model $parent,
                $modelRelName,
                $resourceRelName,
                RelationshipsObject $relationship,
                ErrorCollection $errors
            ) use ($schema) {
                $data = $relationship->getData();
                $idx  = $data !== null ? $data->getIdentifier() : null;

                return $this->getAuthorizations()
                    ->canSetBelongToOnCreate($errors, $parent, $schema, $resourceRelName, $modelRelName, $idx);
            };

            $this->setBelongsTo($model, $belongsTo, $schema, $policy, $errors);
        }
    }

    /**
     * @param Model           $model
     * @param array           $belongsTo
     * @param Schema          $schema
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function setBelongsToOnUpdate(
        Model $model,
        array $belongsTo,
        Schema $schema,
        ErrorCollection $errors
    ) {
        if ($this->getAuthorizations()->canSetBelongsToOnUpdate($errors, $model, $schema, $belongsTo) === true) {
            $policy = function (
                Model $parent,
                $modelRelName,
                $resourceRelName,
                RelationshipsObject $relationship,
                ErrorCollection $errors
            ) use ($schema) {
                $data = $relationship->getData();
                $idx  = $data !== null ? $data->getIdentifier() : null;

                return $this->getAuthorizations()
                    ->canSetBelongToOnUpdate($errors, $parent, $schema, $resourceRelName, $modelRelName, $idx);
            };

            $this->setBelongsTo($model, $belongsTo, $schema, $policy, $errors);
        }
    }

    /**
     * @param Model           $model
     * @param array           $belongsToMany
     * @param Schema          $schema
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function setBelongsToManyOnCreate(
        Model $model,
        array $belongsToMany,
        Schema $schema,
        ErrorCollection $errors
    ) {
        if ($this->getAuthorizations()
                ->canSetBelongsToManyOnCreate($errors, $model, $schema, $belongsToMany) === true) {
            $relPolicy = function (
                Model $parent,
                $modelRelName,
                $resourceRelName,
                RelationshipsObject $relationship,
                ErrorCollection $errors
            ) use ($schema) {
                return $this->getAuthorizations()->canSetBelongToManyRelationshipOnCreate(
                    $errors,
                    $parent,
                    $schema,
                    $resourceRelName,
                    $modelRelName,
                    $relationship
                );
            };

            $itemPolicy = function (
                Model $model,
                $modelRelName,
                $resourceRelName,
                ResourceIdentifierObject $identifier,
                ErrorCollection $errors
            ) use ($schema) {
                $resourceRelName ?: null;
                $idx = $identifier->getIdentifier();

                return $this->getAuthorizations()
                    ->canSetBelongToManyOnCreate($errors, $model, $schema, $resourceRelName, $modelRelName, $idx);
            };

            $this->setBelongsToMany($model, $belongsToMany, $schema, $relPolicy, $itemPolicy, $errors);
        }
    }

    /**
     * @param Model           $model
     * @param array           $belongsToMany
     * @param Schema          $schema
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function setBelongsToManyOnUpdate(
        Model $model,
        array $belongsToMany,
        Schema $schema,
        ErrorCollection $errors
    ) {
        if ($this->getAuthorizations()
                ->canSetBelongsToManyOnUpdate($errors, $model, $schema, $belongsToMany) === true) {
            $relPolicy = function (
                Model $parent,
                $modelRelName,
                $resourceRelName,
                RelationshipsObject $relationship,
                ErrorCollection $errors
            ) use ($schema) {
                return $this->getAuthorizations()->canSetBelongToManyRelationshipOnUpdate(
                    $errors,
                    $parent,
                    $schema,
                    $resourceRelName,
                    $modelRelName,
                    $relationship
                );
            };

            $itemPolicy = function (
                Model $model,
                $modelRelName,
                $resourceRelName,
                ResourceIdentifierObject $identifier,
                ErrorCollection $errors
            ) use ($schema) {
                $resourceRelName ?: null;
                $idx = $identifier->getIdentifier();

                return $this->getAuthorizations()
                    ->canSetBelongToManyOnUpdate($errors, $model, $schema, $resourceRelName, $modelRelName, $idx);
            };

            $this->setBelongsToMany($model, $belongsToMany, $schema, $relPolicy, $itemPolicy, $errors, true);
        }
    }

    /**
     * @param Model           $model
     * @param array           $belongsTo
     * @param Schema          $schema
     * @param Closure         $policy
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function setBelongsTo(
        Model $model,
        array $belongsTo,
        Schema $schema,
        Closure $policy,
        ErrorCollection $errors
    ) {
        $belongsToMap = $schema->getBelongsToRelationshipsMap();
        foreach ($belongsTo as $resourceRelName => $relationship) {
            if (array_key_exists($resourceRelName, $belongsToMap) === false ||
                $schema->isReadOnly($resourceRelName) === true
            ) {
                continue;
            }

            /** @var RelationshipsObject $relationship */
            list($expResourceType, $modelRelName) = $belongsToMap[$resourceRelName];
            if ($policy($model, $modelRelName, $resourceRelName, $relationship, $errors) === true) {
                $data = $relationship->getData();
                $this->associate($model, $modelRelName, $resourceRelName, $expResourceType, $errors, $data);
            }
        }
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param Model           $model
     * @param array           $belongsToMany
     * @param Schema          $schema
     * @param Closure         $relPolicy
     * @param Closure         $itemPolicy
     * @param ErrorCollection $errors
     * @param bool            $cleanRelation
     *
     * @return void
     */
    protected function setBelongsToMany(
        Model $model,
        array $belongsToMany,
        Schema $schema,
        Closure $relPolicy,
        Closure $itemPolicy,
        ErrorCollection $errors,
        $cleanRelation = false
    ) {
        $belongsToManyMap = $schema->getBelongsToManyRelationshipsMap();
        foreach ($belongsToMany as $resourceRelName => $relationship) {
            if (array_key_exists($resourceRelName, $belongsToManyMap) === false ||
                $schema->isReadOnly($resourceRelName) === true
            ) {
                continue;
            }
            /** @var RelationshipsObject $relationship */
            list($expResourceType, $modelRelName) = $belongsToManyMap[$resourceRelName];

            if ($relPolicy($model, $modelRelName, $resourceRelName, $relationship, $errors) === false) {
                continue;
            }

            if ($cleanRelation === true) {
                /** @var BelongsToMany $relation */
                $relation = $model->{$modelRelName}();
                $relation->detach();
            }

            if (is_array($relationship->getData()) === false) {
                $errors->addRelationshipError($resourceRelName, T::trans(T::KEY_ERR_INVALID_ELEMENT));
            } else {
                foreach ($relationship->getData() as $identifier) {
                    /** @var ResourceIdentifierObject $identifier */
                    if ($itemPolicy($model, $modelRelName, $resourceRelName, $identifier, $errors) === true) {
                        $this->attach($model, $modelRelName, $resourceRelName, $expResourceType, $identifier, $errors);
                    }
                }
            }
        }
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * @return Model
     */
    protected function getModel()
    {
        return $this->model;
    }

    /**
     * @return CrudAuthorizationsInterface
     */
    protected function getAuthorizations()
    {
        return $this->authorizations;
    }

    /**
     * @return ErrorCollection
     */
    protected function createErrorCollection()
    {
        return new ErrorCollection();
    }

    /**
     * Called after Create method completes and before result is committed (after save so model has ID assigned).
     *
     * @param Model $model
     *
     * @return void
     */
    protected function onCreating(Model $model)
    {
        $model ?: null;
    }

    /**
     * Called before Delete does any changes.
     *
     * @param Model $model
     *
     * @return void
     */
    protected function onDelete(Model $model)
    {
        $model ?: null;
    }

    /**
     * Called after Delete method completes and before result is committed.
     *
     * @param Model $model
     *
     * @return void
     */
    protected function onDeleting(Model $model)
    {
        $model ?: null;
    }

    /**
     * Called before Update method committed (before save so the model has old and new values).
     *
     * @param JsonApiRequest $request
     * @param Model          $model
     *
     * @return void
     */
    protected function onUpdate(JsonApiRequest $request, Model $model)
    {
        $request && $model ?: null;
    }

    /**
     * Called after Update method completes and before result is committed.
     *
     * @param Model $model
     *
     * @return void
     */
    protected function onUpdating(Model $model)
    {
        $model ?: null;
    }

    /**
     * Validate input data on model creation.
     *
     * @param JsonApiRequest  $request
     * @param Schema          $schema
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function validateInputOnCreate(JsonApiRequest $request, Schema $schema, ErrorCollection $errors)
    {
        $request && $schema && $errors ?: null;
    }

    /**
     * Validate model on create.
     *
     * @param Model           $model
     * @param Schema          $schema
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function validateModelOnCreate(Model $model, Schema $schema, ErrorCollection $errors)
    {
        $model && $schema && $errors ?: null;
    }

    /**
     * Validate input data on model update.
     *
     * @param JsonApiRequest  $request
     * @param Schema          $schema
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function validateInputOnUpdate(JsonApiRequest $request, Schema $schema, ErrorCollection $errors)
    {
        $request && $schema && $errors ?: null;
    }

    /**
     * Validate model on update.
     *
     * @param Model           $model
     * @param Schema          $schema
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function validateModelOnUpdate(Model $model, Schema $schema, ErrorCollection $errors)
    {
        $model && $schema && $errors ?: null;
    }

    /**
     * @return FactoryInterface
     */
    protected function getFactory()
    {
        return $this->factory;
    }

    /**
     * Apply input JSON API parameters to builder on resource reading.
     *
     * @param EncodingParametersInterface $parameters
     * @param Builder                     $builder
     *
     * @return void
     */
    protected function applyParametersToBuilder(EncodingParametersInterface $parameters, Builder $builder)
    {
        $parameters && $builder ?: null;
    }

    /**
     * @param Closure $closure
     */
    protected function executeInTransaction(Closure $closure)
    {
        /** @var DatabaseManager $manager */
        $manager    = $this->getContainer()->make(DatabaseManager::class);
        $connection = $manager->connection();
        $connection->beginTransaction();

        try {
            $closure();
            $executedOk = true;
        } finally {
            isset($executedOk) === true ? $connection->commit() : $connection->rollBack();
        }
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param Model                         $model
     * @param string                        $modelRelName
     * @param string                        $resourceRelName
     * @param string                        $expResourceType
     * @param ErrorCollection               $errors
     * @param ResourceIdentifierObject|null $identifier
     *
     * @return void
     */
    private function associate(
        Model $model,
        $modelRelName,
        $resourceRelName,
        $expResourceType,
        ErrorCollection $errors,
        ResourceIdentifierObject $identifier = null
    ) {
        $relIdx = null;

        if ($identifier !== null) {
            $relType = $identifier->getType();
            $relIdx  = $identifier->getIdentifier();
            if ($relType !== $expResourceType) {
                $errors->addRelationshipTypeError($resourceRelName, T::trans(T::KEY_ERR_INVALID_ELEMENT));
            }
        }

        /** @var BelongsTo $relation */
        $relation = $model->{$modelRelName}();
        $relation->associate($relIdx);
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param Model                    $model
     * @param string                   $modelRelName
     * @param string                   $resourceRelName
     * @param string                   $expResourceType
     * @param ResourceIdentifierObject $identifier
     * @param ErrorCollection          $errors
     *
     * @return void
     */
    private function attach(
        Model $model,
        $modelRelName,
        $resourceRelName,
        $expResourceType,
        ResourceIdentifierObject $identifier,
        ErrorCollection $errors
    ) {
        $relType = $identifier->getType();
        $relIdx  = $identifier->getIdentifier();
        if ($relType !== $expResourceType) {
            $errors->addRelationshipTypeError($resourceRelName, T::trans(T::KEY_ERR_INVALID_ELEMENT));
        }

        /** @var BelongsToMany $relation */
        $relation = $model->{$modelRelName}();
        $relation->attach($relIdx);
    }
}

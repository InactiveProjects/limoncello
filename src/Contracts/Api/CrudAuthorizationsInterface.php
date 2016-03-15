<?php namespace Neomerx\Limoncello\Contracts\Api;

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
use Neomerx\Limoncello\Errors\ErrorCollection;
use Neomerx\Limoncello\JsonApi\Decoder\RelationshipsObject;
use Neomerx\Limoncello\JsonApi\Schema;

/**
 * @package Neomerx\Limoncello
 */
interface CrudAuthorizationsInterface
{
    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     *
     * @return bool
     */
    public function canCreateNewInstance(
        ErrorCollection $errors,
        Model $model
    );

    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     * @param Schema          $schema
     * @param array           $attributes
     *
     * @return bool
     */
    public function canSetAttributesOnCreate(
        ErrorCollection $errors,
        Model $model,
        Schema $schema,
        array $attributes
    );

    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     * @param Schema          $schema
     * @param string          $key
     * @param string          $value
     *
     * @return bool
     */
    public function canSetAttributeOnCreate(
        ErrorCollection $errors,
        Model $model,
        Schema $schema,
        $key,
        $value
    );

    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     * @param Schema          $schema
     * @param array           $belongsTo
     *
     * @return bool
     */
    public function canSetBelongsToOnCreate(
        ErrorCollection $errors,
        Model $model,
        Schema $schema,
        array $belongsTo
    );

    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     * @param Schema          $schema
     * @param string          $resRelationshipName
     * @param string          $modRelationshipName
     * @param null            $idx
     *
     * @return bool
     */
    public function canSetBelongToOnCreate(
        ErrorCollection $errors,
        Model $model,
        Schema $schema,
        $resRelationshipName,
        $modRelationshipName,
        $idx = null
    );

    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     * @param Schema          $schema
     *
     * @return bool
     */
    public function canSaveNewInstance(
        ErrorCollection $errors,
        Model $model,
        Schema $schema
    );

    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     * @param Schema          $schema
     * @param array           $belongsToMany
     *
     * @return bool
     */
    public function canSetBelongsToManyOnCreate(
        ErrorCollection $errors,
        Model $model,
        Schema $schema,
        array $belongsToMany
    );

    /**
     * @param ErrorCollection     $errors
     * @param Model               $model
     * @param Schema              $schema
     * @param string              $resRelationshipName
     * @param string              $modRelationshipName
     * @param RelationshipsObject $relationship
     *
     * @return bool
     */
    public function canSetBelongToManyRelationshipOnCreate(
        ErrorCollection $errors,
        Model $model,
        Schema $schema,
        $resRelationshipName,
        $modRelationshipName,
        RelationshipsObject $relationship
    );

    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     * @param Schema          $schema
     * @param string          $resRelationshipName
     * @param string          $modRelationshipName
     * @param string          $idx
     *
     * @return bool
     */
    public function canSetBelongToManyOnCreate(
        ErrorCollection $errors,
        Model $model,
        Schema $schema,
        $resRelationshipName,
        $modRelationshipName,
        $idx = null
    );

    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     *
     * @return bool
     */
    public function canRead(
        ErrorCollection $errors,
        Model $model
    );


    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     *
     * @return bool
     */
    public function canUpdateExistingInstance(
        ErrorCollection $errors,
        Model $model
    );

    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     * @param Schema          $schema
     * @param array           $attributes
     *
     * @return bool
     */
    public function canSetAttributesOnUpdate(
        ErrorCollection $errors,
        Model $model,
        Schema $schema,
        array $attributes
    );

    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     * @param Schema          $schema
     * @param string          $key
     * @param string          $value
     *
     * @return bool
     */
    public function canSetAttributeOnUpdate(
        ErrorCollection $errors,
        Model $model,
        Schema $schema,
        $key,
        $value
    );

    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     * @param Schema          $schema
     * @param array           $belongsTo
     *
     * @return bool
     */
    public function canSetBelongsToOnUpdate(
        ErrorCollection $errors,
        Model $model,
        Schema $schema,
        array $belongsTo
    );

    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     * @param Schema          $schema
     * @param string          $resRelationshipName
     * @param string          $modRelationshipName
     * @param null            $idx
     *
     * @return bool
     */
    public function canSetBelongToOnUpdate(
        ErrorCollection $errors,
        Model $model,
        Schema $schema,
        $resRelationshipName,
        $modRelationshipName,
        $idx = null
    );

    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     * @param Schema          $schema
     *
     * @return bool
     */
    public function canSaveExistingInstance(
        ErrorCollection $errors,
        Model $model,
        Schema $schema
    );

    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     * @param Schema          $schema
     * @param array           $belongsToMany
     *
     * @return bool
     */
    public function canSetBelongsToManyOnUpdate(
        ErrorCollection $errors,
        Model $model,
        Schema $schema,
        array $belongsToMany
    );

    /**
     * @param ErrorCollection     $errors
     * @param Model               $model
     * @param Schema              $schema
     * @param string              $resRelationshipName
     * @param string              $modRelationshipName
     * @param RelationshipsObject $relationship
     *
     * @return bool
     */
    public function canSetBelongToManyRelationshipOnUpdate(
        ErrorCollection $errors,
        Model $model,
        Schema $schema,
        $resRelationshipName,
        $modRelationshipName,
        RelationshipsObject $relationship
    );

    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     * @param Schema          $schema
     * @param string          $resRelationshipName
     * @param string          $modRelationshipName
     * @param null            $idx
     *
     * @return bool
     */
    public function canSetBelongToManyOnUpdate(
        ErrorCollection $errors,
        Model $model,
        Schema $schema,
        $resRelationshipName,
        $modRelationshipName,
        $idx = null
    );

    /**
     * @param ErrorCollection $errors
     * @param Model           $model
     *
     * @return bool
     */
    public function canDelete(
        ErrorCollection $errors,
        Model $model
    );
}

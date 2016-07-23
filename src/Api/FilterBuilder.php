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
use Illuminate\Database\Eloquent\Builder;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Neomerx\Limoncello\I18n\Translate as T;

/**
 * @package Neomerx\Limoncello
 */
class FilterBuilder
{
    /**
     * @var string
     */
    private $defaultOperation;

    /**
     * @var Closure[]
     */
    private $operationHandlers = [];

    /**
     * @var ErrorCollection
     */
    private $errors;

    /**
     * @param string $defaultOperation
     * @param array  $operationHandlers
     */
    public function __construct($defaultOperation, array $operationHandlers)
    {
        $this->defaultOperation = $defaultOperation;
        foreach ($operationHandlers as $operation => $handler) {
            $this->registerOperation($operation, $handler);
        }

        $this->errors = new ErrorCollection();
    }

    /**
     * @param Builder                     $builder
     * @param EncodingParametersInterface $parameters
     *
     * @return void
     */
    public function build(Builder $builder, EncodingParametersInterface $parameters)
    {
        $filterParams = $parameters->getFilteringParameters();
        if (empty($filterParams) === true || is_array($filterParams) === false) {
            return;
        }

        $joinWithAnd = true;

        // check of top level element is `AND` or `OR`
        reset($filterParams);
        $firstKey = strtolower(key($filterParams));
        if ($firstKey === 'or' || $firstKey === 'and') {
            if (count($filterParams) > 1) {
                next($filterParams);
                $field = key($filterParams);
                $this->errors->addQueryParameterError($field, 'Invalid element');
                return;
            } else {
                $filterParams = $filterParams[$firstKey];
                if ($firstKey === 'or') {
                    $joinWithAnd = false;
                }
            }
        }

        foreach ($filterParams as $fieldName => $value) {
            if (is_string($value) === true) {
                $operation = $this->getDefaultOperation();
                $this->applyOperationToBuilder($builder, $fieldName, $operation, [$value], $joinWithAnd);
            } elseif (is_array($value) === true) {
                foreach ($value as $operation => $params) {
                    $normalizedParams = is_array($params) === true ? $params : [$params];
                    $this->applyOperationToBuilder($builder, $fieldName, $operation, $normalizedParams, $joinWithAnd);
                }
            }
        }

        if ($this->errors->count() > 0) {
            throw new JsonApiException($this->errors);
        }
    }

    /**
     * @return string
     */
    public function getDefaultOperation()
    {
        return $this->defaultOperation;
    }

    /**
     * @param string  $name
     * @param Closure $handler
     */
    public function registerOperation($name, Closure $handler)
    {
        $this->operationHandlers[strtolower($name)] = $handler;
    }

    /**
     * @param Builder $builder
     * @param string  $fieldName
     * @param string  $operation
     * @param array   $parameters
     * @param bool    $joinWithAnd
     *
     * @return void
     */
    protected function applyOperationToBuilder(
        Builder $builder,
        $fieldName,
        $operation,
        array $parameters,
        $joinWithAnd
    ) {
        $operation = strtolower($operation);
        if (array_key_exists($operation, $this->operationHandlers) === true) {
            $opHandler = $this->operationHandlers[$operation];
            $opHandler($builder, $fieldName, $parameters, $joinWithAnd);
        } else {
            $this->errors->addQueryParameterError($operation, T::trans(T::KEY_ERR_PARAMETERS_NOT_SUPPORTED));
        }
    }
}

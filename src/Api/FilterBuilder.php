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
     * @param string $defaultOperation
     * @param array  $operationHandlers
     */
    public function __construct($defaultOperation, array $operationHandlers)
    {
        $this->defaultOperation = $defaultOperation;
        foreach ($operationHandlers as $operation => $handler) {
            $this->registerOperation($operation, $handler);
        }
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
        if (empty($filterParams) === false && is_array($filterParams)) {
            foreach ($filterParams as $fieldName => $value) {
                if (is_string($value) === true) {
                    $this->applyOperationToBuilder($builder, $fieldName, $this->getDefaultOperation(), [$value]);
                } elseif (is_array($value) === true) {
                    foreach ($value as $operation => $params) {
                        $normalizedParams = is_array($params) === true ? $params : [$params];
                        $this->applyOperationToBuilder($builder, $fieldName, $operation, $normalizedParams);
                    }
                }
            }
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
     *
     * @return void
     */
    protected function applyOperationToBuilder(Builder $builder, $fieldName, $operation, array $parameters)
    {
        $operation = strtolower($operation);
        if (array_key_exists($operation, $this->operationHandlers) === true) {
            $opHandler = $this->operationHandlers[$operation];
            $opHandler($builder, $fieldName, $parameters);
        }
    }
}

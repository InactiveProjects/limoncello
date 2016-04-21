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
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\Limoncello\Contracts\JsonApi\PagedDataInterface;
use Neomerx\Limoncello\Http\JsonApiRequest;

/**
 * @package Neomerx\Limoncello
 */
interface CrudInterface
{
    /**
     * @return string
     */
    public function getModelClass();

    /**
     * @param EncodingParametersInterface $parameters
     * @param array                       $relations
     *
     * @return PagedDataInterface
     */
    public function index(EncodingParametersInterface $parameters = null, array $relations = []);

    /**
     * @param int                         $index
     * @param EncodingParametersInterface $parameters
     * @param array                       $relations
     *
     * @return Model
     */
    public function read($index, EncodingParametersInterface $parameters = null, array $relations = []);

    /**
     * @param int $index
     *
     * @return void
     */
    public function delete($index);

    /**
     * @param JsonApiRequest $request
     *
     * @return Model
     */
    public function create(JsonApiRequest $request);

    /**
     *
     * @param JsonApiRequest $request
     *
     * @return Model
     */
    public function update(JsonApiRequest $request);
}

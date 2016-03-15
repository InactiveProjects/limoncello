<?php namespace Neomerx\Limoncello\Http;

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
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Neomerx\JsonApi\Contracts\Http\Parameters\ParametersInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Neomerx\Limoncello\Contracts\Api\CrudInterface;
use Neomerx\Limoncello\Contracts\Http\ResponsesInterface;
use Neomerx\Limoncello\Contracts\JsonApi\PagedDataInterface;
use Neomerx\Limoncello\Errors\ErrorCollection;
use Neomerx\Limoncello\I18n\Translate as T;

/**
 * @package Neomerx\Limoncello
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CrudController extends BaseController
{
    /**
     * @var CrudInterface
     */
    private $crudApi;

    /**
     * @var JsonApiRequest
     */
    private $request;

    /**
     * @var ResponsesInterface
     */
    private $responses;

    /**
     * CrudController constructor.
     *
     * @param JsonApiRequest     $request
     * @param ResponsesInterface $responses
     * @param CrudInterface      $crudApi
     */
    public function __construct(
        JsonApiRequest $request,
        ResponsesInterface $responses,
        CrudInterface $crudApi
    ) {
        $this->crudApi   = $crudApi;
        $this->request   = $request;
        $this->responses = $responses;
    }

    /**
     * Display a listing of the resources.
     *
     * @return Response
     */
    public function index()
    {
        $parameters = $this->getRequest()->getParameters();

        $pagedData = $this->callApiIndex($parameters);

        return $this->getResponses()->getPagedDataResponse($pagedData);
    }

    /**
     * Display specified resource.
     *
     * @param string $idx
     *
     * @return Response
     */
    public function show($idx)
    {
        $parameters = $this->getRequest()->getParameters();

        $model = $this->callApiRead($idx, $parameters);

        return $this->getResponses()->getContentResponse($model);
    }

    /**
     * Remove the specified resource.
     *
     * @param string $idx
     *
     * @return Response
     */
    public function destroy($idx)
    {
        $this->getApi()->delete($idx);

        return $this->getResponses()->getCodeResponse(Response::HTTP_NO_CONTENT);
    }

    /**
     * Store a newly created resource.
     *
     * @return Response
     */
    public function store()
    {
        $model = $this->callApiCreate($this->getRequest());

        return $this->getResponses()->getCreatedResponse($model);
    }

    /**
     * Update the specified resource.
     *
     * @param string $idx
     *
     * @return Response
     */
    public function update($idx)
    {
        $request = $this->getRequest();
        if ($idx !== $request->getId()) {
            $errors = new ErrorCollection();
            $errors->addDataIdError(T::trans(T::KEY_ERR_IDS_IN_URL_AND_DOC_DO_NOT_MATCH));
            throw new JsonApiException($errors);
        }

        $model = $this->callApiUpdate($request);

        return $this->getResponses()->getContentResponse($model);
    }

    /**
     * @param ParametersInterface $parameters
     *
     * @return PagedDataInterface
     */
    protected function callApiIndex(ParametersInterface $parameters)
    {
        $resources = $this->getApi()->index($parameters);

        return $resources;
    }

    /**
     * @param int                 $idx
     * @param ParametersInterface $parameters
     *
     * @return Model
     */
    protected function callApiRead($idx, ParametersInterface $parameters)
    {
        $model = $this->getApi()->read($idx, $parameters);

        return $model;
    }

    /**
     * @param JsonApiRequest $request
     *
     * @return Model
     */
    protected function callApiCreate(JsonApiRequest $request)
    {
        $model = $this->getApi()->create($request);

        return $model;
    }

    /**
     * @param JsonApiRequest $request
     *
     * @return Model
     */
    protected function callApiUpdate(JsonApiRequest $request)
    {
        $model = $this->getApi()->update($request);

        return $model;
    }

    /**
     * @return CrudInterface
     */
    protected function getApi()
    {
        return $this->crudApi;
    }

    /**
     * @return JsonApiRequest
     */
    protected function getRequest()
    {
        return $this->request;
    }

    /**
     * @return ResponsesInterface
     */
    protected function getResponses()
    {
        return $this->responses;
    }
}

<?php namespace Neomerx\Limoncello\Providers;

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
use Illuminate\Http\Request;
use Neomerx\Limoncello\Http\JsonApiRequest;

/**
 * @package Neomerx\Limoncello
 */
class LumenServiceProvider extends LaravelServiceProvider
{
    /** @noinspection PhpMissingParentCallCommonInspection
     *
     * @inheritdoc
     */
    protected function registerPublishedResources()
    {
        // do nothing
    }

    /**
     * @inheritdoc
     */
    protected function configureLimoncello()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->app->configure(self::CONFIG_FILE_NAME_WO_EXT);

        parent::configureLimoncello();
    }

    /**
     * @return void
     */
    protected function configureJsonApiRequests()
    {
        $this->app->resolving(function (JsonApiRequest $request) {
            // do not replace with $this->getRequest()
            // in tests when more than 1 request it will be executed more than once.
            // if replaced tests will fail
            $currentRequest = $this->app->make(Request::class);
            $files          = $currentRequest->files->all();
            $files          = is_array($files) === true ? array_filter($files) : $files;

            $request->initialize(
                $currentRequest->query->all(),
                $currentRequest->request->all(),
                $currentRequest->attributes->all(),
                $currentRequest->cookies->all(),
                $files,
                $currentRequest->server->all(),
                $currentRequest->getContent()
            );

            $request->setUserResolver($currentRequest->getUserResolver());
            $request->setRouteResolver($currentRequest->getRouteResolver());
            $currentRequest->getSession() === null ?: $request->setSession($currentRequest->getSession());
            $request->setJsonApiFactory($this->getFactory());
            $request->setQueryParameters($this->getQueryParameters());
            $request->setSchemaContainer($this->getSchemaContainer());

            // lumen do not call `validate` from `ValidatesWhenResolved` so have to do it manually
            $request->validate();
        });
    }
}

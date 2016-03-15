<?php namespace Neomerx\Limoncello\Auth;

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

use Illuminate\Auth\GenericUser;
use Neomerx\Limoncello\Contracts\Auth\AccountInterface;

/**
 * @package Neomerx\Limoncello
 */
class Anonymous extends GenericUser implements AccountInterface
{
    /**
     * @inheritdoc
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * @inheritdoc
     */
    public function user()
    {
        return null;
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @return null
     */
    public function getAuthIdentifier()
    {
        return null;
    }
}

<?php namespace Neomerx\Limoncello\Config;

/**
 * Copyright 2015 info@neomerx.com (www.neomerx.com)
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

/**
 * @package Neomerx\Limoncello
 */
class Config
{
    /** Config file name w/o extension */
    const NAME = 'limoncello';

    /** Config key for schema list */
    const SCHEMAS = 'schemas';

    /** Config key for json options section */
    const JSON = 'json';

    /** Config key for json_encode options */
    const JSON_OPTIONS = 'options';

    /** Default value for json_encode options */
    const JSON_OPTIONS_DEFAULT = 0;

    /** Config key for json_encode max depth */
    const JSON_DEPTH = 'depth';

    /** Default value for json_encode max depth */
    const JSON_DEPTH_DEFAULT = 512;
}

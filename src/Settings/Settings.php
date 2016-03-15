<?php namespace Neomerx\Limoncello\Settings;

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

/**
 * @package Neomerx\Limoncello
 */
class Settings
{
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

    /** If JSON API version should be shown in top-level 'jsonapi' section */
    const JSON_IS_SHOW_VERSION = 'showVer';

    /** Default value for 'show JSON API version' */
    const JSON_IS_SHOW_VERSION_DEFAULT = false;

    /** Config key for JSON API version meta information */
    const JSON_VERSION_META = 'verMeta';

    /** Config key for URL prefix that will be added to all document links which have $treatAsHref flag set to false */
    const JSON_URL_PREFIX = 'urlPrefix';

    /** Config section for authentication and authorization */
    const AUTH = 'auth';

    /** User model class name (e.g. App\Authentication\TokenCodec::class) */
    const AUTH_CODEC = 'codec';
}

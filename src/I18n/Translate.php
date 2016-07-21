<?php namespace Neomerx\Limoncello\I18n;

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

use Illuminate\Container\Container;
use Illuminate\Translation\Translator;
use Neomerx\Limoncello\Providers\LaravelServiceProvider;

/**
 * @package Neomerx\Limoncello
 */
class Translate
{
    /** Translation file name without .php extension */
    const FILE_NAME_MESSAGES_WO_EXT = 'messages';

    /** Message id */
    const KEY_ERR_EMPTY_SCHEMA = 'err_empty_schema';

    /** Message id */
    const KEY_ERR_EMPTY_TYPE_IN_SCHEMA = 'err_empty_type_in_schema';

    /** Message id */
    const KEY_ERR_EMPTY_MODEL_IN_SCHEMA = 'err_empty_model_in_schema';

    /** Message id */
    const KEY_ERR_NO_SCHEMA = 'err_no_schema';

    /** Message id */
    const KEY_ERR_NO_SCHEMA_FOR_RESOURCE = 'err_no_schema_for_resource';

    /** Message id */
    const KEY_ERR_NO_SCHEMA_FOR_MODEL = 'err_no_schema_for_model';

    /** Message id */
    const KEY_ERR_SCHEMA_REGISTERED_FOR_RESOURCE = 'err_schema_registered_for_resource';

    /** Message id */
    const KEY_ERR_SCHEMA_REGISTERED_FOR_MODEL = 'err_schema_registered_for_model';

    /** Message id */
    const KEY_ERR_INVALID_DOCUMENT = 'err_inv_document';

    /** Message id */
    const KEY_ERR_INVALID_ELEMENT = 'err_inv_element';

    /** Message id */
    const KEY_ERR_INVALID_ELEMENT_SHOULD_BE_NON_EMPTY_STRING = 'err_inv_element_should_be_non_empty_str';

    /** Message id */
    const KEY_ERR_INVALID_ELEMENT_OPT_SHOULD_BE_NON_EMPTY_STRING = 'err_inv_element_opt_should_be_non_empty_str';

    /** Message id */
    const KEY_ERR_IDS_IN_URL_AND_DOC_DO_NOT_MATCH = 'err_ids_in_url_and_doc_do_not_match_title';

    /** Message id */
    const KEY_ERR_PARAMETERS_NOT_SUPPORTED = 'err_parameters_not_supported';

    /** Message id */
    const KEY_ERR_UNAUTHORIZED = 'err_unauthorized';

    /** Message id */
    const KEY_ERR_FORBIDDEN = 'err_forbidden';

    /** Message id */
    const KEY_ERR_RESOURCE_IDENTITY = 'err_resource_identity';

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @param string $messageKey
     * @param array  $parameters
     *
     * @return string
     */
    public function get($messageKey, array $parameters = [])
    {
        if ($this->translator === null) {
            $this->translator = Container::getInstance()->make('translator');
        }

        $key = LaravelServiceProvider::PACKAGE_NAMESPACE.'::'.self::FILE_NAME_MESSAGES_WO_EXT.'.'.$messageKey;

        return $this->translator->trans($key, $parameters);
    }

    /**
     * @param string $messageKey
     * @param array  $parameters
     *
     * @return string
     */
    public static function trans($messageKey, array $parameters = [])
    {
        return (new static)->get($messageKey, $parameters);
    }
}

<?php

use Neomerx\Limoncello\I18n\Translate as T;

return [

    /*
     * Errors
     */

    T::KEY_ERR_EMPTY_SCHEMA                   => 'Schema class is not specified.',
    T::KEY_ERR_EMPTY_TYPE_IN_SCHEMA           => 'Type is not set in Schema \':0\'.',
    T::KEY_ERR_EMPTY_MODEL_IN_SCHEMA          => 'Model is not set in Schema \':0\'.',
    T::KEY_ERR_NO_SCHEMA                      => 'Schema \':0\' was not registered.',
    T::KEY_ERR_NO_SCHEMA_FOR_RESOURCE         => 'Schema was not registered for resource \':0\'.',
    T::KEY_ERR_NO_SCHEMA_FOR_MODEL            => 'Schema was not registered for model \':0\'.',
    T::KEY_ERR_SCHEMA_REGISTERED_FOR_RESOURCE => 'Schema has been already registered for resource \':0\'.',
    T::KEY_ERR_SCHEMA_REGISTERED_FOR_MODEL    => 'Schema has been already registered for model \':0\'.',

    T::KEY_ERR_INVALID_DOCUMENT                                  => 'Invalid document.',
    T::KEY_ERR_INVALID_ELEMENT                                   => 'Invalid element.',
    T::KEY_ERR_INVALID_ELEMENT_SHOULD_BE_NON_EMPTY_STRING        => 'Element should be non empty string.',
    T::KEY_ERR_INVALID_ELEMENT_OPT_SHOULD_BE_NON_EMPTY_STRING    => 'Optional element should be non empty string.',

    T::KEY_ERR_IDS_IN_URL_AND_DOC_DO_NOT_MATCH   => 'Id in URL and JSON API document do not match.',
    T::KEY_ERR_PARAMETERS_NOT_SUPPORTED          => 'Parameters are not supported.',

    T::KEY_ERR_UNAUTHORIZED                      => 'Unauthorized.',
    T::KEY_ERR_FORBIDDEN                         => 'Forbidden.',
    T::KEY_ERR_RESOURCE_IDENTITY                 => 'Resource identity \':0\'.',

];

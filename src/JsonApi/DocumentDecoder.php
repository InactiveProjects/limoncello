<?php namespace Neomerx\Limoncello\JsonApi;

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

use Neomerx\JsonApi\Contracts\Decoder\DecoderInterface;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\Limoncello\Errors\ErrorCollection;
use Neomerx\Limoncello\I18n\Translate as T;
use Neomerx\Limoncello\JsonApi\Decoder\DocumentObject;
use Neomerx\Limoncello\JsonApi\Decoder\RelationshipsObject;
use Neomerx\Limoncello\JsonApi\Decoder\ResourceIdentifierObject;
use Neomerx\Limoncello\JsonApi\Decoder\ResourceObject;

/**
 * @package Neomerx\Limoncello
 */
class DocumentDecoder implements DecoderInterface
{
    /**
     * @var ErrorCollection
     */
    private $errors;

    /**
     * @param string $content
     *
     * @return DocumentObject|null
     */
    public function decode($content)
    {
        $result       = null;
        $this->errors = new ErrorCollection();

        $documentAsArray = json_decode($content, true);
        if ($documentAsArray !== null) {
            $result = $this->parseDocument($documentAsArray);

            return $this->errors->count() <= 0 ? $result : null;
        } else {
            return null;
        }
    }

    /**
     * @return ErrorCollection
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param array $data
     *
     * @return DocumentObject|null
     */
    private function parseDocument(array $data)
    {
        $result = null;

        $dataSegment = $this->getArrayValue($data, DocumentInterface::KEYWORD_DATA, null);
        if (empty($dataSegment) === false && is_array($dataSegment) === true) {
            $isProbablySingle = $this->isProbablySingleIdentity($dataSegment);
            $parsed           = $isProbablySingle === true ?
                $this->parseSinglePrimaryData($dataSegment) :
                $this->parseArrayOfPrimaryData($dataSegment);

            if ($parsed !== null) {
                $result = new DocumentObject($parsed);
            }
        } elseif ($dataSegment !== null) {
            $this->errors->addDataError(T::trans(T::KEY_ERR_INVALID_ELEMENT));
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return ResourceObject|null
     */
    protected function parseSinglePrimaryData(array $data)
    {
        $result = null;

        list ($type, $idx) = $this->parseTypeAndId($data);
        if (empty($type) === true || is_string($type) === false) {
            $this->errors->addDataTypeError(T::trans(T::KEY_ERR_INVALID_ELEMENT_SHOULD_BE_NON_EMPTY_STRING));
        }

        if ($idx !== null && is_string($idx) === false) {
            $this->errors->addDataIdError(T::trans(T::KEY_ERR_INVALID_ELEMENT_OPT_SHOULD_BE_NON_EMPTY_STRING));
        }

        $attributes = $this->getArrayValue($data, DocumentInterface::KEYWORD_ATTRIBUTES, []);

        $relationshipsData = $this->getArrayValue($data, DocumentInterface::KEYWORD_RELATIONSHIPS, []);
        $relationships     = $this->parseRelationships($relationshipsData);

        if ($this->errors->count() <= 0) {
            $result = new ResourceObject($type, $idx, $attributes, $relationships);
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return ResourceObject[]|null
     */
    protected function parseArrayOfPrimaryData(array $data)
    {
        $isValid = true;
        $result  = null;
        foreach ($data as $primaryData) {
            $parsed = $this->parseSinglePrimaryData($primaryData);
            if ($parsed === null) {
                $isValid = false;
            } else {
                $result[] = $parsed;
            }
        }

        return $isValid === true ? $result : null;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function parseTypeAndId(array $data)
    {
        $idx  = $this->getArrayValue($data, DocumentInterface::KEYWORD_ID, null);
        $type = $this->getArrayValue($data, DocumentInterface::KEYWORD_TYPE, null);

        return [$type, $idx];
    }

    /**
     * @param array $data
     *
     * @return RelationshipsObject[]|null
     */
    protected function parseRelationships(array $data)
    {
        $result = [];
        foreach ($data as $name => $relationshipData) {
            $dataSegment  = $relationshipData !== null ?
                $this->getArrayValue($relationshipData, DocumentInterface::KEYWORD_DATA, null) : null;

            if ($dataSegment === null || (empty($dataSegment) === true && is_array($dataSegment) === true)) {
                $result[$name] = new RelationshipsObject($dataSegment);
                continue;
            }

            if (is_array($dataSegment) === false) {
                $this->errors->addRelationshipError($name, T::trans(T::KEY_ERR_INVALID_ELEMENT));
                continue;
            }

            if ($this->isProbablySingleIdentity($dataSegment) === true) {
                $parsed = $this->parseSingleIdentityInRelationship($name, $dataSegment);
            } else {
                $parsed = $this->parseArrayOfIdentitiesInRelationship($name, $dataSegment);
            }

            if ($parsed !== null) {
                $result[$name] = new RelationshipsObject($parsed);
            }
        }

        return $result;
    }

    /**
     * @param string $name
     * @param array  $data
     *
     * @return ResourceIdentifierObject|null
     */
    protected function parseSingleIdentityInRelationship($name, array $data)
    {
        list ($type, $idx) = $this->parseTypeAndId($data);

        $isValid = true;
        if (empty($type) === true || is_string($type) === false) {
            $isValid  = false;
            $message  = T::trans(T::KEY_ERR_INVALID_ELEMENT_SHOULD_BE_NON_EMPTY_STRING);
            $this->errors->addRelationshipTypeError($name, $message);
        }
        if ($idx === null || is_string($idx) === false) {
            $isValid = false;
            $message = T::trans(T::KEY_ERR_INVALID_ELEMENT_SHOULD_BE_NON_EMPTY_STRING);
            $this->errors->addRelationshipIdError($name, $message);
        }

        $result = $isValid === true ? new ResourceIdentifierObject($type, $idx) : null;

        return $result;
    }

    /**
     * @param string $currentPath
     * @param array  $data
     *
     * @return ResourceIdentifierObject[]|null
     */
    protected function parseArrayOfIdentitiesInRelationship($currentPath, array $data)
    {
        $result  = [];
        $isValid = true;

        foreach ($data as $typeAndIdPair) {
            $parsed = $this->parseSingleIdentityInRelationship($currentPath, $typeAndIdPair);
            if ($parsed === null) {
                $isValid = false;
            } else {
                $result[] = $parsed;
            }
        }

        return $isValid === true ? $result : null;
    }

    /**
     * @param array      $array
     * @param string|int $key
     * @param mixed      $default
     *
     * @return mixed
     */
    private function getArrayValue(array $array, $key, $default)
    {
        return array_key_exists($key, $array) === true ? $array[$key] : $default;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    private function isProbablySingleIdentity(array $data)
    {
        $isProbablySingle = array_key_exists(DocumentInterface::KEYWORD_TYPE, $data) ||
            array_key_exists(DocumentInterface::KEYWORD_ID, $data);

        return $isProbablySingle;
    }
}

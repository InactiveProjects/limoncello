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

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerInterface;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\Validator;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Http\Parameters\ParametersCheckerInterface;
use Neomerx\JsonApi\Contracts\Http\Parameters\ParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Parameters\ParametersParserInterface as PPI;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Neomerx\Limoncello\Contracts\JsonApi\SchemaContainerInterface as SchemaContainerInterface;
use Neomerx\Limoncello\Contracts\JsonApi\SchemaInterface;
use Neomerx\Limoncello\Errors\ErrorCollection;
use Neomerx\Limoncello\I18n\Translate as T;
use Neomerx\Limoncello\JsonApi\Decoder\DocumentObject;
use Neomerx\Limoncello\JsonApi\Decoder\RelationshipsObject;
use Neomerx\Limoncello\JsonApi\Decoder\ResourceObject;
use Neomerx\Limoncello\JsonApi\DocumentDecoder;

/**
 * @package Neomerx\Limoncello
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class JsonApiRequest extends Request implements ValidatesWhenResolved
{
    /** Related schema class */
    const SCHEMA = null;
    /** Rules index for parameters checker */
    const RULE_ALLOW_UNRECOGNIZED = 0;

    /** Rules index for parameters checker */
    const RULE_ALLOWED_INCLUDE_PATHS = 1;

    /** Rules index for parameters checker */
    const RULE_ALLOWED_FIELD_SET_TYPES = 2;

    /** Rules index for parameters checker */
    const RULE_ALLOWED_SORT_FIELDS = 3;

    /** Rules index for parameters checker */
    const RULE_ALLOWED_PAGING_PARAMS = 4;

    /** Rules index for parameters checker */
    const RULE_ALLOWED_FILTERING_PARAMS = 5;

    /**
     * @var ParametersInterface
     */
    private $requestParameters;

    /**
     * @var SchemaContainerInterface
     */
    private $schemaContainer;

    /**
     * @var CodecMatcherInterface
     */
    private $codecMatcher;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var DocumentObject
     */
    private $jsonApiDocument;

    /**
     * @var bool
     */
    private $isParsed = false;

    /**
     * @var string
     */
    private $type = null;

    /**
     * @var string
     */
    private $idx = null;

    /**
     * @var array
     */
    private $resourceAttr = [];

    /**
     * @var RelationshipsObject[]
     */
    private $belongsTo = [];

    /**
     * @var RelationshipsObject[]
     */
    private $belongsToMany = [];

    /**
     * @param ParametersInterface $requestParameters
     */
    public function setRequestParameters($requestParameters)
    {
        $this->requestParameters = $requestParameters;
    }

    /**
     * @param SchemaContainerInterface $schemaContainer
     */
    public function setSchemaContainer($schemaContainer)
    {
        $this->schemaContainer = $schemaContainer;
    }

    /**
     * @param CodecMatcherInterface $codecMatcher
     */
    public function setCodecMatcher($codecMatcher)
    {
        $this->codecMatcher = $codecMatcher;
    }

    /**
     * @param FactoryInterface $factory
     */
    public function setJsonApiFactory($factory)
    {
        $this->factory = $factory;
    }

    /**
     */
    protected function validateParsed()
    {
    }

    /**
     * @return null|array
     */
    protected function getParameterRules()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getType()
    {
        $this->ensureDocumentIsParsed();

        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function validate()
    {
        $errors = new ErrorCollection();

        $this->validateParameters($errors);
        $this->validateRelationships($errors);
        $this->validateOnActions($errors);

        if ($errors->count() > 0) {
            throw new JsonApiException($errors);
        }
    }

    /**
     * @return ParametersInterface
     */
    public function getParameters()
    {
        return $this->requestParameters;
    }

    /**
     * @return string
     */
    public function getId()
    {
        $this->ensureDocumentIsParsed();

        return $this->idx;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        $this->ensureDocumentIsParsed();

        return $this->resourceAttr;
    }

    /**
     * @return RelationshipsObject[]
     */
    public function getBelongsTo()
    {
        $this->ensureDocumentIsParsed();

        return $this->belongsTo;
    }

    /**
     * @return RelationshipsObject[]
     */
    public function getBelongsToMany()
    {
        $this->ensureDocumentIsParsed();

        return $this->belongsToMany;
    }

    /**
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function validateParameters(ErrorCollection $errors)
    {
        $rules  = $this->getParameterRules();
        $params = $this->getParameters();
        if ($rules === null && $params->isEmpty() === false) {
            $message = T::trans(T::KEY_ERR_PARAMETERS_NOT_SUPPORTED);
            empty($params->getFieldSets()) ?: $errors->addQueryParameterError(PPI::PARAM_FIELDS, $message);
            empty($params->getIncludePaths()) ?: $errors->addQueryParameterError(PPI::PARAM_INCLUDE, $message);
            empty($params->getSortParameters()) ?: $errors->addQueryParameterError(PPI::PARAM_SORT, $message);
            empty($params->getPaginationParameters()) ?: $errors->addQueryParameterError(PPI::PARAM_PAGE, $message);
            empty($params->getFilteringParameters()) ?: $errors->addQueryParameterError(PPI::PARAM_FILTER, $message);
        } elseif (is_array($rules) === true && empty($rules) === false) {
            $get = function (array $array, $key, $default) {
                return array_key_exists($key, $array) === true ? $array[$key] : $default;
            };

            $parametersChecker = $this->createParametersChecker(
                $get($rules, self::RULE_ALLOW_UNRECOGNIZED, false),
                $get($rules, self::RULE_ALLOWED_INCLUDE_PATHS, []),
                $get($rules, self::RULE_ALLOWED_FIELD_SET_TYPES, null),
                $get($rules, self::RULE_ALLOWED_SORT_FIELDS, []),
                $get($rules, self::RULE_ALLOWED_PAGING_PARAMS, []),
                $get($rules, self::RULE_ALLOWED_FILTERING_PARAMS, [])
            );

            $parametersChecker->check($params);
        }
    }

    /**
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function validateRelationships(ErrorCollection $errors)
    {
        // get errors in 'belongTo' relationships
        foreach ($this->getBelongsTo() as $name => $relationship) {
            $data = $relationship->getData();
            if ($data === null) {
                continue;
            }

            // check if given resource type exists
            $typeInRelationship = $relationship->getData()->getType();
            if ($this->schemaContainer->hasSchemaForResourceType($typeInRelationship) === false) {
                $errors->addRelationshipTypeError($name, T::trans(T::KEY_ERR_INVALID_ELEMENT));
                continue;
            }
        }

        // get errors in 'belongToMany' relationships
        foreach ($this->getBelongsToMany() as $name => $relationship) {
            foreach ($relationship->getData() as $resourceIdentifier) {
                $typeInRelationship = $resourceIdentifier->getType();
                if ($this->schemaContainer->hasSchemaForResourceType($typeInRelationship) === false) {
                    $errors->addRelationshipTypeError($name, T::trans(T::KEY_ERR_INVALID_ELEMENT));
                    continue;
                }
            }
        }
    }

    /**
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function validateOnActions(ErrorCollection $errors)
    {
        $methodName = $this->method();
        if ($methodName !== null) {
            $methodName = 'validateOn' . ucfirst(strtolower($methodName));
            if (method_exists($this, $methodName) === true) {
                $this->{$methodName}($errors);
            }
        }
    }

    /**
     * Validate attributes.
     *
     * @param array           $rules
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function validateAttributes(array $rules, ErrorCollection $errors)
    {
        $this->validateData($this->getAttributes(), $rules, $errors);
    }

    /**
     * Validate data.
     *
     * @param array           $data
     * @param array           $rules
     * @param ErrorCollection $errors
     *
     * @return void
     */
    protected function validateData(array $data, array $rules, ErrorCollection $errors)
    {
        $messages = $this->createValidator($data, $rules)->messages();
        if ($messages->count() > 0) {
            $errors->addAttributeErrorsFromMessageBag($messages);
        }
    }

    /** @noinspection PhpTooManyParametersInspection
     *
     * @param bool $allowUnrecognized
     * @param array|null $includePaths
     * @param array|null $fieldSets
     * @param array|null $sortFields
     * @param array|null $pagingParameters
     * @param array|null $filteringParameters
     *
     * @return ParametersCheckerInterface
     */
    protected function createParametersChecker(
        $allowUnrecognized = false,
        array $includePaths = null,
        array $fieldSets = null,
        array $sortFields = null,
        array $pagingParameters = null,
        array $filteringParameters = null
    ) {
        $parametersChecker = $this->factory->createParametersChecker(
            $this->codecMatcher,
            $allowUnrecognized,
            $includePaths,
            $fieldSets,
            $sortFields,
            $pagingParameters,
            $filteringParameters
        );

        return $parametersChecker;
    }

    /**
     * @return SchemaInterface
     */
    private function getSchema()
    {
        $result = $this->schemaContainer->getSchemaBySchemaClass(static::SCHEMA);

        return $result;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return Container::getInstance();
    }

    /**
     * @return array
     */
    protected function getDefaultRelationships()
    {
        return [];
    }

    /**
     * @param array $data
     * @param array $rules
     *
     * @return Validator
     */
    protected function createValidator(array $data, array $rules)
    {
        /** @var ValidationFactory $factory */
        $factory   = $this->getContainer()->make('validator');
        $validator = $factory->make($data, $rules);

        return $validator;
    }

    /**
     * Ensure document is parsed.
     */
    private function ensureDocumentIsParsed()
    {
        $this->isParsed === true ?: $this->parseDocument();
    }

    /**
     * Parse JSON API document
     */
    private function parseDocument()
    {
        $doc = $this->getDocument();
        if ($doc === null) {
            $this->isParsed = true;
            return;
        }

        $errors = new ErrorCollection();

        if ((($data = $doc->getData()) instanceof ResourceObject) === false) {
            // only single resource is supported in input
            $errors->addDataError(T::trans(T::KEY_ERR_INVALID_ELEMENT));
            throw new JsonApiException($errors);
        }

        $schema        = $this->getSchema();
        $type          = $data->getType();
        $idx           = $data->getIdentifier();
        $relationships = $data->getRelationships() + $this->getDefaultRelationships();
        $attributes    =
            array_intersect_key($data->getAttributes(), $schema->getAttributesMap());

        $belongsTo = array_intersect_key($relationships, $schema->getBelongsToRelationshipsMap());
        foreach ($belongsTo as $name => $relationship) {
            /** @var RelationshipsObject $relationship */
            if (is_array($relationship->getData()) === true) {
                $errors->addRelationshipError($name, T::trans(T::KEY_ERR_INVALID_ELEMENT));
            }
        }

        $belongsToMany = array_intersect_key($relationships, $schema->getBelongsToManyRelationshipsMap());
        foreach ($belongsToMany as $name => $relationship) {
            /** @var RelationshipsObject $relationship */
            if (is_array($relationship->getData()) === false) {
                $errors->addRelationshipError($name, T::trans(T::KEY_ERR_INVALID_ELEMENT));
            }
        }

        if ($errors->count() > 0) {
            throw new JsonApiException($errors);
        }

        $this->type          = $type;
        $this->idx           = $idx;
        $this->resourceAttr  = $attributes;
        $this->belongsTo     = $belongsTo;
        $this->belongsToMany = $belongsToMany;

        $this->validateParsed();

        $this->isParsed = true;
    }

    /**
     * @return DocumentObject
     */
    private function getDocument()
    {
        if ($this->jsonApiDocument === null) {
            $decoder               = new DocumentDecoder();
            $this->jsonApiDocument = $decoder->decode($this->getContent());
            if ($decoder->getErrors()->count() > 0) {
                throw new JsonApiException($decoder->getErrors());
            }
        }

        return $this->jsonApiDocument;
    }
}

<?php namespace Neomerx\Limoncello\Errors;

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

use Illuminate\Support\MessageBag;
use Neomerx\JsonApi\Exceptions\ErrorCollection as BaseErrorCollection;

/**
 * @package Neomerx\Limoncello
 */
class ErrorCollection extends BaseErrorCollection
{
    /**
     * @param MessageBag $messages
     * @param array|null $attributeMap
     *
     * @return $this
     */
    public function addAttributeErrorsFromMessageBag(MessageBag $messages, array $attributeMap = null)
    {
        foreach ($messages->getMessages() as $attribute => $attrMessages) {
            $name = $attributeMap === null ? $attribute : $attributeMap[$attribute];
            foreach ($attrMessages as $message) {
                $this->addDataAttributeError($name, $message);
            }
        }

        return $this;
    }

    /**
     * @param MessageBag $messages
     * @param array|null $relationshipMap
     *
     * @return $this
     */
    public function addRelationshipErrorsFromMessageBag(MessageBag $messages, array $relationshipMap = null)
    {
        foreach ($messages->getMessages() as $relationship => $relMessages) {
            $name = $relationshipMap === null ? $relationship : $relationshipMap[$relationship];
            foreach ($relMessages as $message) {
                $this->addRelationshipError($name, $message);
            }
        }

        return $this;
    }
}

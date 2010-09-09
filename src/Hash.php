<?php
/**
 * Copyright (c) 2010 Stefan Priebsch <stefan@priebsch.de>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *   * Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright notice,
 *     this list of conditions and the following disclaimer in the documentation
 *     and/or other materials provided with the distribution.
 *
 *   * Neither the name of Stefan Priebsch nor the names of contributors
 *     may be used to endorse or promote products derived from this software
 *     without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER ORCONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Hash
 * @author     Stefan Priebsch <stefan@priebsch.de>
 * @copyright  Stefan Priebsch <stefan@priebsch.de>. All rights reserved.
 * @license    BSD License
 */

namespace spriebsch\hash;

/**
 * Hashes everything (scalar values, arrays, objects, nested data structures).
 * Modifications to related objects do not affect the hash (whereas modified
 * references do).
 *
 * @author Stefan Priebsch <stefan@priebsch.de>
 * @copyright Stefan Priebsch <stefan@priebsch.de>. All rights reserved.
 */
class Hash
{
    /**
     * Checks whether a (potentially nested) data structure contains an object reference.
     *
     * @param mixed $value The data
     * @return bool
     */
    protected function containsObjects($value)
    {
        if (is_object($value)) {
            return true;
        }
    
        if (!is_array($value)) {
            return false;
        }
    
        foreach ($value as $item) {
            if (is_object($item)) {
                return true;
            }
            
            if ($this->containsObjects($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculates a hash for any value.
     *
     * For scalar values, their string representation is used
     * For arrays, their serialized representation is used, unless they contain objects
     * For object, the SPL object hash is used.
     *
     * @param mixed $value Data to hash
     * @return string
     */
    protected function toString($value)
    {
        if (is_resource($value)) {
            throw new Exception('Cannot hash a resource');
        }

        if (is_object($value)) {
            return spl_object_hash($value);
        }
      
        if (is_array($value)) {
            if ($this->containsObjects($value)) {
                $result = '';
                foreach ($value as $item) {
                    $result .= $this->toString($item);
                }
                return $result;
            } else {
                return serialize($value);
            }
        }
    
        return (string) $value;
    }
    
    /**
     * Hashes an object. Changes to related objects do not affect this hash.
     *
     * @param object $object The object to hash
     * @return string
     */
    protected function hashObject($object)
    {
        $result = '';

        $reflection = new \ReflectionObject($object);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $result .= $property->getName() . $this->toString($property->getValue($object));
        }

        return sha1($result);
    }
    
    /**
     * Calculates a hash.
     *
     * @param mixed $subject Data to hash
     * @return string
     */
    public function getHash($subject)
    {
        if (is_object($subject)) {
            return $this->hashObject($subject);
        } else {
            return sha1($this->toString($subject));
        }
    }
}
?>

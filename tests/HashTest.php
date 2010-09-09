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
 * @covers spriebsch\hash\Hash
 */
class HashTest extends \PHPUnit_Framework_TestCase
{    
    protected function setUp()
    {
        $this->hash = new Hash();
        $this->dummy = new Dummy();
    }
    
    protected function tearDown()
    {
        unset($this->hash);
        unset($this->dummy);
    }

    /**
     * Calculates a hash of an object, then modifies the object through a closure.
     *
     * @param object $object The object to hash
     * @param callable $modifier Closure that modifies the object to hash
     * @param object $modifyObject Object that the closure modifies
     *                             (can differ from object to hash for nested objects)
     */
    protected function hashAndModify($object, $modifier, $modifyObject = null)
    {
        $hash = $this->hash->getHash($object);
        
        if ($modifyObject === null) {
            $modifyObject = $this->dummy;
        }

        $modifier($modifyObject);
        
        return $hash;
    }
    
    protected function assertHashRemains($object, $modifier, $modifyObject = null)
    {
        $hash = $this->hashAndModify($object, $modifier, $modifyObject);
        $this->assertEquals($hash, $this->hash->getHash($object));
    }

    protected function assertHashDiffers($object, $modifier, $modifyObject = null)
    {
        $hash = $this->hashAndModify($object, $modifier, $modifyObject);
        $this->assertNotEquals($hash, $this->hash->getHash($object));
    }


    /**
     * @expectedException spriebsch\hash\Exception
     */
    public function testHashThrowsExceptionOnResource()
    {
        $this->hash->getHash(tmpfile());
    }

    public function testHashHas40Characters()
    {
        $this->assertEquals(40, strlen($this->hash->getHash($this->dummy)));
    }

    public function testHashingObjectWithSelfReferenceWorks()
    {
        // Create test object with a self reference
        $this->dummy->a = $this->dummy;

        // Basically, we make sure hashing does not end up in an endless loop
        $this->assertEquals(40, strlen($this->hash->getHash($this->dummy)));
    }


    public function testHashDetectsModifiesScalarValue()
    {
        $a = $this->hash->getHash(42);
        $b = $this->hash->getHash(43);
        
        $this->assertNotEquals($a, $b);
    }

    public function testHashDetectsModifiedArray()
    {
        $a = $this->hash->getHash(array(1, 2, 3));
        $b = $this->hash->getHash(array(1, 2, 4));
        
        $this->assertNotEquals($a, $b);
    }

    public function testHashDetectsModifiedKeyInAssociativeArray()
    {
        $a = $this->hash->getHash(array('a' => 'A', 'b' => 'B', 'c' => 'C'));
        $b = $this->hash->getHash(array('a' => 'A', 'b' => 'B', 'd' => 'C'));
        
        $this->assertNotEquals($a, $b);
    }

    public function testHashDetectsModifiedValueInAssociativeArray()
    {
        $a = $this->hash->getHash(array('a' => 'A', 'b' => 'B', 'c' => 'C'));
        $b = $this->hash->getHash(array('a' => 'A', 'b' => 'B', 'c' => 'D'));
        
        $this->assertNotEquals($a, $b);
    }


    public function testHashDetectsModifiedScalarObjectAttribute()
    {
        $this->assertHashDiffers($this->dummy, function($object) { $object->a = 42; } );
    }

    public function testHashDetectsModifiedArrayObjectAttribute()
    {
        $this->dummy->a = array(1, 2);
        $this->assertHashDiffers($this->dummy, function($object) { $object->a = array(1, 42); } );
    }

    public function testHashDetectsModifiedNestedArrayObjectAttribute()
    {
        $this->dummy->a = array(1, array(1, 2));
        $this->assertHashDiffers($this->dummy, function($object) { $object->a = array(1, array(1, 42)); } );
    }


    public function testModifyingRelatedObjectDoesNotChangeHash()
    {
        $object = new Dummy();
        $this->dummy->a = $object;
        $this->assertHashRemains($this->dummy, function($object) { $object->a = 42; }, $object);
    }

    public function testModifyingRelatedObjectInArrayDoesNotChangeHash()
    {
        $object = new Dummy();
        $this->dummy->a = array(1, $object);
        $this->assertHashRemains($this->dummy, function($object) { $object->a = 42; }, $object);
    }

    public function testModifyingRelatedObjectInNestedArrayChangesHash()
    {
        $object = new Dummy();
        $this->dummy->a = array(1, array(1, $object));
        $this->assertHashRemains($this->dummy, function($object) { $object->a = 42; }, $object);
    }


    public function testUnsettingRelatedObjectChangesHash()
    {
        $this->dummy->a = new Dummy();
        $this->assertHashDiffers($this->dummy, function($object) { $object->a = null; } );
    }

    public function testUnsettingRelatedObjectInArrayChangesHash()
    {
        $this->dummy->a = array(1, new Dummy());
        $this->assertHashDiffers($this->dummy, function($object) { $object->a = array(1, null); } );
    }

    public function testUnsettingRelatedObjectInNestedArrayChangesHash()
    {
        $this->dummy->a = array(1, array(1, new Dummy()));
        $this->assertHashDiffers($this->dummy, function($object) { $object->a = array(1, array(1, null)); } );
    }


    public function testReplacingRelatedObjectChangesHash()
    {
        $this->dummy->a = new Dummy();
        $this->assertHashDiffers($this->dummy, function($object) { $object->a = new Dummy(); } );
    }

    public function testReplacingRelatedObjectInArrayChangesHash()
    {
        $this->dummy->a = array(1, new Dummy());
        $this->assertHashDiffers($this->dummy, function($object) { $object->a = array(1, new Dummy()); } );
    }

    public function testReplacingRelatedObjectInNestedArrayChangesHash()
    {
        $this->dummy->a = array(1, array(1, new Dummy()));
        $this->assertHashDiffers($this->dummy, function($object) { $object->a = array(1, array(1, new Dummy())); } );
    }
}
?>

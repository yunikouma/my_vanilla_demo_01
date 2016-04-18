<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Core;

/**
 * Test some of the global functions that operate (or mostly operate) on arrays.
 */
class ArrayFunctionsTest extends \PHPUnit_Framework_TestCase {

    /**
     *
     */
    public function testDbEncodeArray() {
        $data = ['Forum' => 'Vanilla'];
        $encoded = dbencode($data);
        $this->assertSame($data, dbdecode($encoded));
    }

    /**
     * Test the basic encoding/decoding of data.
     *
     * @param mixed $data The data to test.
     * @dataProvider provideDbEncodeValues
     */
    public function testDbEncodeDecode($data) {
        $this->assertNotNull($data);

        $encoded = dbencode($data);
        $this->assertNotFalse($encoded);
        $this->assertTrue(is_string($encoded));

        $decoded = dbdecode($encoded);

        $this->assertSame($data, $decoded);
    }

    /**
     * Provide some values for {@link testDbEncodeDecode()}.
     *
     * @return array Returns a data provider.
     */
    public function provideDbEncodeValues() {
        $r = [
            'string' => ['Hello world!'],
            'int' => [123],
            'true' => [true],
            'false' => [false],
            'array' => [['Forum' => 'Vanilla']],
            'array-nested' => [['userID' => 123, 'prefs' => ['foo' => true, 'bar' => [1, 2, 3]]]]
        ];

        return $r;
    }

    /**
     * Encoding a value of null should be null.
     */
    public function testDbEncodeNull() {
        $this->assertNull(dbencode(null));
    }

    /**
     * Decoding a value of null should be null.
     */
    public function testDbDecodeNull() {
        $this->assertNull(dbdecode(null));
    }

    /**
     * Make sure we have a bad string for {@link dbdecode()}.
     *
     * @param string $str The bad string to decode.
     * @expectedException \PHPUnit_Framework_Error
     * @dataProvider provideBadDbDecodeStrings
     */
    public function testBadDbDecodeString($str) {
        $decoded = unserialize($str);
    }

    /**
     * Test {@link dbdecode()} with a bogus string.
     *
     * The trick here is that {@link dbdecode()} should not raise an exception or throw an error.
     *
     * @see testBadDbDecodeString()
     */
    public function testDbDecodeError() {
        $str = 'a:3:{i:0;i:1;i:';
        $decoded = dbdecode($str);
        $this->assertFalse($decoded);
    }

    /**
     * Provide some strings that would normally cause a deserialization error.
     *
     * @return array Returns a data provider string.
     */
    public function provideBadDbDecodeStrings() {
        $r = [
            ['a:3:{i:0;i:1;i:'],
            ['{"foo": "bar"'],
            [[1, 2, 3]]
        ];

        return $r;
    }
}
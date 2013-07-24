<?php
/**
 * Package Test
 *
 * @author warmans
 */
namespace PHPianola\Ipc;

class PackageTest extends \PHPUnit_Framework_TestCase {

    /**
     *
     * @var \PHPianola\Ipc\Package;
     */
    private $object;

    public function setUp()
    {
        $this->object = new Package(Package::TYPE_REGISTERED, array('foo'=>'bar'), 123, 456);
    }

    /**
     * @group unit-test
     */
    public function testGetType()
    {
        $this->assertEquals(Package::TYPE_REGISTERED, $this->object->getType());
    }

    /**
     * @group unit-test
     */
    public function testGetPayload()
    {
        $this->assertEquals(array('foo'=>'bar'), $this->object->getPayload());
    }

    /**
     * @group unit-test
     */
    public function testGetTo()
    {
        $this->assertEquals(123, $this->object->getTo());
    }

    /**
     * @group unit-test
     */
    public function testGetFrom()
    {
        $this->assertEquals(456, $this->object->getFrom());
    }

    /**
     * @group unit-test
     */
    public function testGetDefaultFrom()
    {
        $object = new Package(Package::TYPE_REGISTERED, 'foo');
        $this->assertTrue(($object->getFrom() > 0));
    }

    /**
     * @group unit-test
     */
    public function testSerialiseUnserialiseArray()
    {
        $serialised = $this->object->serialise();
        $unserialised = Package::unserialise($serialised);

        $this->assertEquals(Package::TYPE_REGISTERED, $unserialised->getType());
        $this->assertEquals(array('foo'=>'bar'), $unserialised->getPayload());
    }

    /**
     * @group unit-test
     */
    public function testSerialiseUnserialiseString()
    {
        $object = new Package(Package::TYPE_REGISTERED, 'foo');
        $serialised = $object->serialise();
        $unserialised = Package::unserialise($serialised);

        $this->assertEquals(Package::TYPE_REGISTERED, $unserialised->getType());
        $this->assertEquals('foo', $unserialised->getPayload());
    }

    /**
     * @group unit-test
     */
    public function testSerialiseUnserialiseObj()
    {
        $pl = new \stdClass();
        $pl->foo = 'bar';

        $object = new Package(Package::TYPE_REGISTERED, $pl);
        $serialised = $object->serialise();
        $unserialised = Package::unserialise($serialised);

        $this->assertEquals(Package::TYPE_REGISTERED, $unserialised->getType());
        $this->assertEquals('bar', $unserialised->getPayload()->foo);
    }
}

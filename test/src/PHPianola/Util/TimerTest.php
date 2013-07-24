<?php
/**
 * @author warmans
 */
namespace PHPianola\Util;

class TimerTest extends \PHPUnit_Framework_TestCase {


    /**
     * @group unit-test
     */
    public function testOneMs()
    {
        $object = new Timer((double)0, (double)(0.001));
        $this->assertEquals('0:00:00.001', $object->elapsed());
    }

    /**
     * @group unit-test
     */
    public function testTenMs()
    {
        $object = new Timer((double)0, (double)(0.010));
        $this->assertEquals('0:00:00.010', $object->elapsed());
    }

    /**
     * @group unit-test
     */
    public function testOneHundredMs()
    {
        $object = new Timer((double)0, (double)(0.100));
        $this->assertEquals('0:00:00.100', $object->elapsed());
    }


    /**
     * @group unit-test
     */
    public function testOneSecond()
    {
        $object = new Timer((double)0, (double)(1.000));
        $this->assertEquals('0:00:01.000', $object->elapsed());
    }

    /**
     * @group unit-test
     */
    public function testOneAndAHalfSeconds()
    {
        $object = new Timer((double)0, (double)(1.500));
        $this->assertEquals('0:00:01.500', $object->elapsed());
    }

    /**
     * @group unit-test
     */
    public function testThirtySecond()
    {
        $object = new Timer((double)0, (double)(30.000));
        $this->assertEquals('0:00:30.000', $object->elapsed());
    }

    /**
     * @group unit-test
     */
    public function testOneMinute()
    {
        $object = new Timer((double)0, (double)(60.000));
        $this->assertEquals('0:01:00.000', $object->elapsed());
    }

    /**
     * @group unit-test
     */
    public function testOneAndAHalfMinutes()
    {
        $object = new Timer((double)0, (double)(90.000));
        $this->assertEquals('0:01:30.000', $object->elapsed());
    }

    /**
     * @group unit-test
     */
    public function testThirtyMinutes()
    {
        $object = new Timer((double)0, (double)(60.000*30));
        $this->assertEquals('0:30:00.000', $object->elapsed());
    }

    /**
     * @group unit-test
     */
    public function testOneHour()
    {
        $object = new Timer((double)0, (double)(60*60));
        $this->assertEquals('1:00:00.000', $object->elapsed());
    }

    /**
     * @group unit-test
     */
    public function testOneAndAHalfHours()
    {
        $object = new Timer((double)0, (double)(90*60));
        $this->assertEquals('1:30:00.000', $object->elapsed());
    }

    /**
     * @group unit-test
     */
    public function testTenHours()
    {
        $object = new Timer((double)0, (double)(10*60*60));
        $this->assertEquals('10:00:00.000', $object->elapsed());
    }

    /**
     * @group unit-test
     */
    public function testStart()
    {
        $object = Timer::start();

        $this->assertTrue($object instanceof Timer);
        sleep(1);
        $elapsed = $object->elapsed();
        $this->assertRegexp('#0\:00:0[0-9]{1}.[0-9]{3}#', $elapsed);
    }
}

<?php

use Kodus\Cache\MockCache;
use Kodus\Cache\InvalidArgumentException;

class MockCacheCest
{
    /**
     * @var int
     */
    const DEFAULT_EXPIRATION = 86400;

    /**
     * @var MockCache
     */
    protected $cache;

    public function _before()
    {
        $this->cache = new MockCache(self::DEFAULT_EXPIRATION);
    }

    public function setGetAndDelete(UnitTester $I)
    {
        $I->assertTrue($this->cache->set("key1", "value1"));
        $I->assertTrue($this->cache->set("key2", "value2"));

        $I->assertSame("value1", $this->cache->get("key1"));
        $I->assertSame("value2", $this->cache->get("key2"));

        $I->assertTrue($this->cache->delete("key1"));
        $I->assertFalse($this->cache->delete("key1"));

        $I->assertSame(null, $this->cache->get("key1"));
        $I->assertSame("value2", $this->cache->get("key2"));

        $I->expectException(InvalidArgumentException::class, function () {
            $this->cache->set("key@", "value1");
        });

        $I->expectException(InvalidArgumentException::class, function () {
            $this->cache->get("key@");
        });

        $I->expectException(InvalidArgumentException::class, function () {
            $this->cache->delete("key@");
        });
    }

    public function getNonExisting(UnitTester $I)
    {
        $I->assertSame(null, $this->cache->get("key"));
        $I->assertSame("default", $this->cache->get("key", "default"));
    }

    public function expirationInSeconds(UnitTester $I)
    {
        $this->cache->set("key", "value", 10);

        $this->cache->skipTime(5);

        $I->assertSame("value", $this->cache->get("key"));

        $this->cache->skipTime(5);

        $I->assertSame(null, $this->cache->get("key"));
        $I->assertSame("default", $this->cache->get("key", "default"));
    }

    public function expirationByInterval(UnitTester $I)
    {
        $interval = new DateInterval("PT10S");

        $this->cache->set("key", "value", $interval);

        $this->cache->skipTime(5);

        $I->assertSame("value", $this->cache->get("key"));

        $this->cache->skipTime(5);

        $I->assertSame(null, $this->cache->get("key"));
        $I->assertSame("default", $this->cache->get("key", "default"));
    }

    public function expirationByDefault(UnitTester $I)
    {
        $this->cache->set("key", "value");

        $this->cache->skipTime(self::DEFAULT_EXPIRATION - 5);

        $I->assertSame("value", $this->cache->get("key"));

        $this->cache->skipTime(10);

        $I->assertSame(null, $this->cache->get("key"));
        $I->assertSame("default", $this->cache->get("key", "default"));
    }

    public function expirationInThePast(UnitTester $I)
    {
        $this->cache->set("key1", "value1", 0);
        $this->cache->set("key2", "value2", -10);

        $I->assertSame("default", $this->cache->get("key1", "default"));
        $I->assertSame("default", $this->cache->get("key2", "default"));
    }

    public function clear(UnitTester $I)
    {
        // add some values that should be gone when we clear cache:

        $this->cache->set("key1", "value1");
        $this->cache->set("key2", "value2");

        $this->cache->clear();

        // check to confirm everything"s been wiped out:

        $I->assertSame(null, $this->cache->get("key1"));
        $I->assertSame("default", $this->cache->get("key1", "default"));

        $I->assertSame(null, $this->cache->get("key2"));
        $I->assertSame("default", $this->cache->get("key2", "default"));
    }

    public function testGetAndSetMultiple(UnitTester $I)
    {
        $this->cache->setMultiple(["key1" => "value1", "key2" => "value2"]);

        $results = $this->cache->getMultiple(["key1", "key2", "key3"], false);

        $I->assertSame(["key1" => "value1", "key2" => "value2", "key3" => false], $results);

        $I->expectException(InvalidArgumentException::class, function () {
            $this->cache->getMultiple("Invalid type");
        });

        $I->expectException(InvalidArgumentException::class, function () {
            $this->cache->setMultiple("Invalid type");
        });

        $I->expectException(InvalidArgumentException::class, function () {
            $this->cache->setMultiple(["Invalid key@" => "value1"]);
        });

        $I->expectException(InvalidArgumentException::class, function () {
            $this->cache->getMultiple(["Invalid key@"]);
        });
    }

    public function testDeleteMultiple(UnitTester $I)
    {
        $this->cache->setMultiple(["key1" => "value1", "key2" => "value2", "key3" => "value3"]);

        $this->cache->deleteMultiple(["key1", "key2"]);

        $I->assertSame(["key1" => null, "key2" => null], $this->cache->getMultiple(["key1", "key2"]));

        $I->assertSame("value3", $this->cache->get("key3"));

        $I->expectException(InvalidArgumentException::class, function () {
            $this->cache->deleteMultiple("Invalid type");
        });

        $I->expectException(InvalidArgumentException::class, function () {
            $this->cache->deleteMultiple(["Invalid key@"]);
        });
    }

    public function testHas(UnitTester $I)
    {
        $this->cache->set("key", "value");

        $I->assertSame(true, $this->cache->has("key"));
        $I->assertSame(false, $this->cache->has("fudge"));
    }
}

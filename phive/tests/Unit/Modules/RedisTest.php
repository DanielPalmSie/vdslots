<?php

namespace Tests\Unit\Modules;

require_once __DIR__ . '/../../../../phive/phive.php';

use PHPUnit\Framework\TestCase;

class RedisTest extends TestCase
{
    public function testAll()
    {
        $this->testMsetMgetShard();
        $this->testMsetMget();
        $this->testExpire();
        $this->testLpush();
        $this->testSetGetJson();
        $this->testMCluster();
        $this->testLocalizerCluster();
        $this->testUaccessCluster();
        $this->testPexec();
        $this->testClusterScan();
        $this->testMuid();
        $this->testUuid();
        $this->testSessionTimeout();
    }

        private function testCluster($cluster_key, $key, $value)
        {
            $cluster = mCluster($cluster_key);
            $cluster->set($key, $value);
            $res = $cluster->get($key);
            $this->assertNotEmpty($res, "testCluster value for the key $key is empty on the $cluster_key");
        }

        private function testMsetMgetShard()
        {
            $user_id = uid('devtestmt');
            phMsetShard('is_working', "test", $user_id, 60);
            $is_working = phMgetShard('is_working', $user_id);

            $this->assertEquals("test", $is_working, "phMsetShard/phMgetShard not working");


            phMdelShard('is_working', $user_id);

            $is_working = phMgetShard('is_working', $user_id);

            $this->assertNotEquals("test", $is_working, "phMdelShard not working");
        }

        private function testMsetMget()
        {
            phMset('is_working', "test");
            $is_working = phMget('is_working');
            $this->assertEquals("test", $is_working, "phMset/phMget not working");

            phMdel('is_working');
            $is_working = phMget('is_working');
            $this->assertNotEquals("test", $is_working, "phMdel not working");
        }

        private function testExpire()
        {
            phMset('is_working', "test", 2);
            $is_working = phMget('is_working');
            $this->assertEquals("test", $is_working, "phMset/phMget not working with expire test 1");

            sleep(3);
            $is_working = phMget('is_working');
            $this->assertNotEquals("test", $is_working, "phMset/phMget not working with expire test 2");

            phMset('is_working', "test", 2);
            phMdel('is_working');
            $is_working = phMget('is_working');
            $this->assertNotEquals("test", $is_working, "phMdel not working");
        }

        private function testLpush()
        {
            $insert = ['type' => 'bet', 'created_at' => phive()->hisNow()];
            $insert = json_encode($insert);
            phM('lpush', "lpush-test", $insert, 36000);
            $arr = phive('Redis')->getRange("lpush-test");
            $this->assertNotEmpty($arr, "testLpush value for the key lpush-test is empty");
        }

        private function testSetGetJson()
        {
            $insert = ['type' => 'bet', 'created_at' => phive()->hisNow()];
            phMsetArr("setjson-test", $insert);
            $arr = phmgetArr("setjson-test");
            $this->assertNotEmpty($arr, "testSetGetJson value for the key setjson-test is empty");
        }

        private function testMCluster()
        {
            $mc = mCluster('mp');
            $arr = ['foo' => 'bar'];
            $mc->setJson('tid-1', $arr, 36000);
            $this->assertNotEmpty($mc->getJson('tid-1'), "testMCluster value for the key tid-1 is empty");
            $ttl = $mc->ttl('tid-1');
            $this->assertSame(36000, $ttl, "testMCluster ttl for the key tid-1 is not equal to 36000");
        }

        private function testLocalizerCluster()
        {
            $lang = 'en';
            $translation = t('your.savings', $lang);
            $this->assertSame("Any other cash savings or wealth", $translation, "testLocalizerCluster translation for the key your.savings is not equal to Any other cash savings or wealth");
        }

        private function testUaccessCluster()
        {
            $uuid = uuidSet([1, 2, 3]);
            $res = uuidGet($uuid);
            $this->assertNotEmpty($res, "testUaccessCluster value for the key $uuid is empty");
            $this->assertSame([1, 2, 3], $res, "testUaccessCluster value for the key $uuid is not equal to 1,2,3");
            $this->testCluster('uaccess', "tic-1", 'abc');
        }

        private function testPexec()
        {
            phive()->pexec('phive', 'dumpTbl', ['gameplaytest', '127.0.0.1'], 500, true);
            sleep(1);
            $result = phive('SQL')->loadAssoc("SELECT * FROM trans_log ORDER BY id DESC LIMIT 1");

            $this->assertSame("'127.0.0.1'", $result['dump_txt'], "testPexec dump_txt for the key gameplaytest is not equal to gameplaytest");
            $this->assertSame("gameplaytest", $result['tag'], "testPexec tag for the key localhost is not equal to localhost");
        }

        private function testClusterScan()
        {
            $cluster = mCluster('uaccess');
            $cluster->delAll();
            $this->testCluster('uaccess', "tic-1", 'abc');
            $this->testCluster('uaccess', "tic-2", 'abc');
            $result = $cluster->keys('*');
            $expected = [
                'tic-2', 'tic-1'
            ];
            sort($result);
            sort($expected);
            $this->assertSame($expected, $result, "testClusterScan keys for the key * is not equal to tic-1, tic-2");

            $this->testCluster('uaccess', "uaccess:{54321}:tic-1", 'abc');
            $this->testCluster('uaccess', "uaccess:{12345}:tic-2", 'abc');
            $expected[] = 'uaccess:{12345}:tic-2';
            $expected[] = 'uaccess:{54321}:tic-1';
            $result = $cluster->scan('*');
            sort($expected);
            sort($result);
            $this->assertSame($expected, $result, "testClusterScan keys for the key * is not equal to tic-1, tic-2, uaccess:{54321}:tic-1, uaccess:{12345}:tic-2");

            $result = $cluster->scan('uaccess*');
            $expected = [
                'uaccess:{54321}:tic-1', 'uaccess:{12345}:tic-2'
            ];
            sort($result);
            sort($expected);
            $this->assertSame($expected, $result, "testClusterScan keys for the key * is not equal to tic-1, tic-2, uaccess:{54321}:tic-1, uaccess:{12345}:tic-2");
        }

        private function testMuid()
        {
            $mkey = mKey('12345', 'tic-2', 'uaccess');
            $id = getMuid($mkey);
            $this->assertSame(12345, $id, "testMuid id for the key uaccess:[12345]:tic-2 is not equal to 12345");
        }

        private function testUuid()
        {
            $uuid = uuidSet([1, 2, 3]);
            $res = uuidGet($uuid);
            $this->assertNotEmpty($res, "testUuid value for the key $uuid is empty");
            $this->assertSame([1, 2, 3], $res, "testUuid value for the key $uuid is not equal to 1,2,3");
        }

        private function testSessionTimeout()
        {
            $user = cu('5541343');
            setSid(984646343, uid($user), $user);
            $mkey = mKey('5541343', 'uaccess');
            $memory_cluster = mCluster('uaccess');
            $result = $memory_cluster->get($mkey);
            $this->assertNotEmpty($result, "testSessionTimeout value for the key $result is empty");

            $result = explode(':', $result);
            $to_redis = $result[0] . ':' . 1000;
            mCluster('uaccess')->set(mKey(5541343, 'uaccess'), $to_redis, 999);
            phive('DBUserHandler')->timeoutSessions();
            $key = mCluster('uaccess')->get($mkey);
            $this->assertEmpty($key, "testSessionTimeout value for the key $key is not empty");
        }
}

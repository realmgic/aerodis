<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'common.php';

class ProxyCounter
{

    public function __construct($target)
    {
        $this->_counter = 0;
        $this->_calls = array();
        $this->_target = $target;
    }

    public function __call($name, $args)
    {
        $this->_counter++;
        if ( !isset($this->_calls[$name])) {
            $this->_calls[$name] = 0;
        }
        $this->_calls[$name]++;
        $res = call_user_func_array(array($this->_target, $name), $args);
        return $res === $this->_target ? $this : $res;
    }

}

$r = new \Redis();
//$r->connect('127.0.0.1', 6480);
//var_dump($r->exists('iiii'));exit;

/** @var \Redis $r */
$r = new ProxyCounter($r);

$start = microtime(true);


$json = file_get_contents('big_json.json');
$bin = gzcompress($json);

echo("Connect\n");
try {
    compare(1, 1);
} catch (Exception $e) {

}

compare($r->connect('127.0.0.1', 6379, 1), true);

//test start
//compare($r->connect('127.0.0.1', 6480), true);
//$r->del('aaa');
//$r->lPush('aaa', $bin);
//echo gzuncompress($r->lRange('aaa', 0, 200)[0]);
//exit;
//test end

echo("Get Set\n");

$r->delete('myKey');
$r->delete('myKey2');

compare($r->get('myKey'), false);
compare($r->set('myKey', 12), true);
compare($r->get('myKey'), '12');
compare($r->set('myKey2', 13), true);
compare($r->get('myKey'), '12');
compare($r->get('myKey2'), '13');
compare($r->del('myKey'), 1);
compare($r->del('myKey'), 0);
compare($r->del('myKey2'), 1);
compare($r->get('myKey'), false);
compare($r->get('myKey2'), false);

echo("Exists\n");

$r->delete('myKey');
compare($r->exists('myKey'), 0);
compare($r->set('myKey', 12), true);
compare($r->exists('myKey'), 1);
$r->delete('myKey');
compare($r->exists('myKey'), 0);

function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

$s = '';
for ($i = 1; $i < 1032; $i++) {
    $s = generateRandomString($i);
    // echo strlen($s).'\n';
    compare($r->set('myKey', $s), true);
    compare($r->get('myKey'), $s);
}

echo("Flush\n");
compare($r->set('myKey1', 'a'), true);
compare($r->set('myKey2', 'b'), true);
compare($r->flushDB(), true);
compare($r->get('myKey1'), false);
compare($r->get('myKey2'), false);

echo("Get Set binary\n");

$r->del('myKey');

compare($r->get('myKey'), false);
compare($r->set('myKey', 'toto\r\ntiti'), true);
compare($r->get('myKey'), 'toto\r\ntiti');

compare($r->set('myKey', 'toto\x00\x01\x02tata'), true);
compare($r->get('myKey'), 'toto\x00\x01\x02tata');

echo("Get Set big data ' . strlen($json) . '\n");

$r->del('myKey');
compare($r->get('myKey'), false);
compare($r->set('myKey', $json), true);
compare($r->get('myKey'), $json);
compare($r->del('myKey'), 1);
compare($r->rPush('myKey', $json), 1);
compare($r->rPop('myKey'), $json);

echo('Get Set big data binary ' . strlen($bin) . "\n");

$r->del('myKey');
compare($r->get('myKey'), false);
compare($r->set('myKey', $bin), true);
compare(gzuncompress($r->get('myKey')), $json);
compare($r->del('myKey'), 1);
compare($r->rPush('myKey', $bin), 1);
compare(gzuncompress($r->lRange('myKey', 0, 200)[0]), $json);
compare(gzuncompress($r->rPop('myKey')), $json);

echo("Incr Decr\n");

$r->delete('myKey');
compare($r->get('myKey'), false);
compare($r->incr('myKey'), 1);
compare($r->get('myKey'), '1');
compare($r->incrBy('myKey', 2), 3);
compare($r->get('myKey'), '3');
compare($r->decr('myKey'), 2);
compare($r->get('myKey'), '2');
compare($r->decrBy('myKey', 5), -3);
compare($r->get('myKey'), '-3');
compare($r->incrBy('myKey', -2), -5);
compare($r->get('myKey'), '-5');
compare($r->decrBy('myKey', -2), -3);
compare($r->get('myKey'), '-3');

$r->delete('myKey');
compare($r->set('myKey', 'a'), true);
compare($r->incr('myKey'), false);

$r->delete('myKey');
compare($r->set('myKey', 'a'), true);
compare($r->set('myKey', 2), true);
compare($r->incr('myKey'), 3);
compare($r->get('myKey'), '3');

echo("Array\n");

$r->del('myKey');
compare($r->lLen('myKey'), 0);
compare($r->rPop('myKey'), false);
compare($r->lPop('myKey'), false);
compare($r->lLen('myKey'), 0);
compare($r->rPush('myKey', 'a'), 1);
compare($r->rPush('myKey', 'b'), 2);
compare($r->rPush('myKey', 'c'), 3);
compare($r->rPush('myKey', 12), 4);
compare($r->lLen('myKey'), 4);
compare($r->rPop('myKey'), '12');
compare($r->lLen('myKey'), 3);
compare($r->rPush('myKey', 'e'), 4);
compare($r->lLen('myKey'), 4);
compare($r->rPop('myKey'), 'e');
compare($r->lLen('myKey'), 3);
compare($r->lPop('myKey'), 'a');
compare($r->lLen('myKey'), 2);
compare($r->rPop('myKey'), 'c');
compare($r->lLen('myKey'), 1);
compare($r->lPush('myKey', 'z'), 2);
compare($r->lLen('myKey'), 2);
compare($r->rPop('myKey'), 'b');
compare($r->lLen('myKey'), 1);
compare($r->rPop('myKey'), 'z');
compare($r->lLen('myKey'), 0);
compare($r->rPop('myKey'), false);
compare($r->lPop('myKey'), false);

compare($r->lPush('myKey', $bin), 1);
compare($r->rPop('myKey'), $bin);
compare($r->rPush('myKey', $bin), 1);
compare($r->rPop('myKey'), $bin);

compare($r->lPush('myKey', 12), 1);
compare($r->rPop('myKey'), '12');

echo("Array Ltrim lRange\n");

$r->del('myKey');
compare($r->lRange('myKey', 0, 0), array());
compare($r->rPush('myKey', 'a'), 1);
compare($r->lLen('myKey'), 1);
compare($r->lRange('myKey', 0, 0), array(0 => 'a'));
compare($r->lTrim('myKey', 0, 0), true);
compare($r->lRange('myKey', 0, 0), array(0 => 'a'));
compare($r->lLen('myKey'), 1);
compare($r->lTrim('myKey', 0, -1), true);
compare($r->lRange('myKey', 0, -1), array(0 => 'a'));
compare($r->lLen('myKey'), 1);
compare($r->lTrim('myKey', -1, -1), true);
compare($r->lRange('myKey', -1, -1), array(0 => 'a'));
compare($r->lLen('myKey'), 1);
compare($r->lTrim('myKey', -1, 0), true);
compare($r->lRange('myKey', -1, 0), array(0 => 'a'));
compare($r->lLen('myKey'), 1);
compare($r->rPush('myKey', 'b'), 2);
compare($r->rPush('myKey', 'c'), 3);
compare($r->lRange('myKey', 0, 12), array(0 => 'a', 1 => 'b', 2 => 'c'));
compare($r->lTrim('myKey', 0, 12), true);
compare($r->lLen('myKey'), 3);
compare($r->lRange('myKey', 2, 2), array(0 => 'c'));
compare($r->lTrim('myKey', 2, 2), true);
compare($r->rPop('myKey'), 'c');
compare($r->lLen('myKey'), 0);
compare($r->lRange('myKey', 2, 2), array());

compare($r->rPush('myKey', 'a'), 1);
compare($r->rPush('myKey', 'b'), 2);
compare($r->rPush('myKey', 'c'), 3);
compare($r->lRange('myKey', 0, -2), array(0 => 'a', 1 => 'b'));
compare($r->lTrim('myKey', 0, -2), true);
compare($r->lLen('myKey'), 2);
compare($r->rPop('myKey'), 'b');
compare($r->rPop('myKey'), 'a');

compare($r->rPush('myKey', 'a'), 1);
compare($r->rPush('myKey', 'b'), 2);
compare($r->rPush('myKey', 'c'), 3);
compare($r->rPush('myKey', 'd'), 4);
compare($r->rPush('myKey', 'e'), 5);
compare($r->rPush('myKey', 'f'), 6);
compare($r->lRange('myKey', -2, 8), array(0 => 'e', 1 => 'f'));
compare($r->lRange('myKey', 0, 18), array(0 => 'a', 1 => 'b', 2 => 'c', 3 => 'd', 4 => 'e', 5 => 'f'));
compare($r->lRange('myKey', 2, 4), array(0 => 'c', 1 => 'd', 2 => 'e'));
compare($r->lTrim('myKey', 2, 4), true);
compare($r->lLen('myKey'), 3);
compare($r->lPop('myKey'), 'c');
compare($r->lPop('myKey'), 'd');
compare($r->lPop('myKey'), 'e');

compare($r->rPush('myKey', 'a'), 1);
compare($r->rPush('myKey', 'b'), 2);
compare($r->rPush('myKey', 'c'), 3);
compare($r->lRange('myKey', -3, 0), array(0 => 'a'));
compare($r->lTrim('myKey', -3, 0), true);
compare($r->lLen('myKey'), 1);
compare($r->lPop('myKey'), 'a');

compare($r->rPush('myKey', 'a'), 1);
compare($r->rPush('myKey', 'b'), 2);
compare($r->rPush('myKey', 'c'), 3);
compare($r->lRange('myKey', -3, -2), array(0 => 'a', 1 => 'b'));
compare($r->lTrim('myKey', -3, -2), true);
compare($r->lLen('myKey'), 2);
compare($r->lPop('myKey'), 'a');
compare($r->lPop('myKey'), 'b');

compare($r->rPush('myKey', 'a'), 1);
compare($r->rPush('myKey', 'b'), 2);
compare($r->rPush('myKey', 'c'), 3);
compare($r->lRange('myKey', -2, -3), array());
compare($r->lTrim('myKey', -2, -3), true);
compare($r->lLen('myKey'), 0);
compare($r->lRange('myKey', 0, 200), array());
compare($r->rPop('myKey'), false);

$r->del('myKey');
compare($r->lRange('myKey', 0, 0), array());
compare($r->rPush('myKey', 'a'), 1);
compare($r->lTrim('myKey', 2, 4), true);
compare($r->lLen('myKey'), 0);
compare($r->lRange('myKey', 0, 0), array());

$r->del('myKey');
$r->set('myKey', 'a');
compare($r->lRange('myKey', 0, 0), false);
compare($r->lLen('myKey'), false);
compare($r->rPush('myKey', 'a'), false);
compare($r->lPush('myKey', 'a'), false);
compare($r->lTrim('myKey', 2, 4), false);

$r->del('myKey');
compare($r->lRange('myKey', 0, 0), array());
compare($r->lTrim('myKey', 2, 4), true);
compare($r->lLen('myKey'), 0);

echo "array int\n";
$r->del('myKey');
compare($r->lPush('myKey', '1'), 1);
compare($r->lRange('myKey', 0, 100), ['1']);
$r->del('myKey');
compare($r->rPush('myKey', 2), 1);
compare($r->lRange('myKey', 0, 100), ['2']);

echo("mGet mSet\n");
$r->del('myKey1');
$r->del('myKey2');
$r->del('myKey3');
compare($r->mget([]), false);
compare($r->mget(['myKey1', 'myKey2']), [false, false]);
compare($r->mset(array('myKey1' => 'a1', 'myKey3' => 2)), true);
compare($r->mget(['myKey1', 'myKey2']), ['a1', false]);
compare($r->mget(['myKey2', 'myKey3']), [false, '2']);
compare($r->mget(['myKey1', 'myKey2', 'myKey3']), ['a1', false, '2']);

echo("hSet hGet hDel\n");
$r->del('myKey');
compare($r->hGet('myKey', 'a'), false);
compare($r->hSet('myKey', 'a', 2), 1);
compare($r->hGet('myKey', 'z'), false);
compare($r->hSet('myKey', 'z', 3), 1);
compare($r->hGet('myKey', 'a'), '2');
compare($r->hSet('myKey', 'a', 1), 0);
compare($r->hSet('myKey', 'b', 'a'), 1);
compare($r->hGet('myKey', 'a'), '1');
compare($r->hGet('myKey', 'b'), 'a');
compare($r->hSet('myKey', 'a', 0), 0);
compare($r->hGet('myKey', 'a'), '0');
compare($r->hDel('myKey', 'a'), 1);
compare($r->hDel('myKey', 'a'), 0);
compare($r->hGet('myKey', 'a'), false);
compare($r->hGet('myKey', 'c'), false);
compare($r->hSet('myKey', 'a', 1), 1);
compare($r->del('myKey'), 1);
compare($r->del('myKey'), 0);
compare($r->hGet('myKey', 'a'), false);
$r->del('myKey');
compare($r->del('myKey'), 0);
compare($r->hSet('myKey', 'b', $bin), 1);
compare($r->hGet('myKey', 'b'), $bin);

compare($r->hSet('myKey', 'veryverylongke', 'toto'), 1);
compare($r->hGet('myKey', 'veryverylongke'), 'toto');

echo("hMSet hMGet\n");
$r->del('myKey');
compare($r->hMGet('myKey', array('a', 'b', 'c')), array('a' => false, 'b' => false, 'c' => false));
compare($r->hSet('myKey', 'b', 2), 1);
compare($r->hMGet('myKey', array('a', 'b', 'c')), array('a' => false, 'b' => '2', 'c' => false));
compare($r->hMSet('myKey', array('a' => 1, 'b' => 2, 'c' => 'a')), true);
compare($r->hMGet('myKey', array('a', 'b', 'c')), array('a' => '1', 'b' => '2', 'c' => 'a'));
compare($r->hDel('myKey', 'a'), 1);
compare($r->hMGet('myKey', array('a', 'b', 'c')), array('a' => false, 'b' => '2', 'c' => 'a'));
compare($r->hMGet('myKey', array('b', 'c')), array('b' => '2', 'c' => 'a'));

compare($r->multi(), $r);
compare($r->hMGet('myKey', array('a', 'b', 'c')), $r);
compare($r->hMGet('myKey', array('b', 'c')), $r);
compare($r->exec(), array(array('a' => false, 'b' => '2', 'c' => 'a'), array('b' => '2', 'c' => 'a')));

echo("hGetAll\n");
$r->del('myKey');
compare($r->hGetAll('myKey'), array());
compare($r->hMSet('myKey', array('b' => 3, 'a' => 1, 1 => 4, 'toto' => 2)), true);
compare_map($r->hGetAll('myKey'), array('b' => '3', 'a' => '1', 1 => '4', 'toto' => '2'));
compare($r->hDel('myKey', 'a'), 1);
compare_map($r->hGetAll('myKey'), array('b' => '3', 1 => '4', 'toto' => '2'));
compare($r->hDel('myKey', 1), 1);
compare_map($r->hGetAll('myKey'), array('b' => '3', 'toto' => '2'));
compare($r->hMSet('myKey', array('b' => $bin)), true);
compare($r->hGet('myKey', 'b'), $bin);
compare_map($r->hGetAll('myKey'), array('b' => $bin, 'toto' => '2'));

if (isset($_ENV['EXPANDED_MAP'])) {
    $r->del('myKey');
    compare($r->hSet('myKey', 'veryveryveryveryveryverylongke', 'toto'), 1);
    compare($r->hGet('myKey', 'veryveryveryveryveryverylongke'), 'toto');
    compare_map($r->hGetAll('myKey'), array('veryveryveryveryveryverylongke' => 'toto'));
}

echo("hIncrBy\n");
$r->del('myKey');
compare($r->hIncrBy('myKey', 'a', 1), 1);
compare($r->hGet('myKey', 'a'), '1');
compare($r->hIncrBy('myKey', 'a', 10), 11);
compare($r->hGet('myKey', 'a'), '11');
compare($r->hIncrBy('myKey', 'a', -15), -4);
compare($r->hGet('myKey', 'a'), '-4');
compare($r->hIncrBy('myKey', 'a', 0), -4);
compare($r->hIncrBy('myKey', 'b', 2), 2);
compare_map($r->hGetAll('myKey'), array('a' => '-4', 'b' => '2'));
compare($r->hMGet('myKey', array('c', 'a', 'b')), array('c' => false, 'a' => '-4', 'b' => '2'));
compare($r->del('myKey'), 1);
compare($r->hIncrBy('myKey', 'a', 0), 0);
compare($r->hIncrBy('myKey', 'b', 2), 2);

$r->del('myKey');
compare($r->hSet('myKey', 'a', 'b'), 1);
compare($r->hIncrBy('myKey', 'a', 1), false);
compare($r->hGet('myKey', 'a'), 'b');

if ( !isset($_ENV['USE_REAL_REDIS']) && 0) {
    echo("hIncrByEx\n");
    $r->del('myKey');
    compare($r->hIncrByEx('myKey', 'a', 2, 500), 2);
    compare($r->hIncrByEx('myKey', 'a', 1, 500), 3);
    upper($r->ttl('myKey'), 100);
    lower($r->ttl('myKey'), 1000);

    echo("hSetEx\n");
    $r->del('myKey');
    compare($r->hSetEx('myKey', 500, 'a', 2), 1);
    compare($r->hSetEx('myKey', 500, 'a', 4), 0);
    compare($r->hGet('myKey', 'a'), '4');
    upper($r->ttl('myKey'), 100);
    lower($r->ttl('myKey'), 1000);

    echo("Batch\n");

    if (isset($_ENV['EXPANDED_MAP'])) {
        $r->del('myKey2');
        $r->del('myKey3');
        compare($r->hmincrByex('myKey2', array(), 200), true);
        upper($r->ttl('myKey2'), 150);
        lower($r->ttl('myKey2'), 250);

        compare($r->hmincrByex('myKey3', array(), -1), true);
        upper($r->ttl('myKey3'), 2000);
    }

    $r->del('myKey');
    compare($r->hmincrByex('myKey', array('key' => 1, 'key2' => 5), 10), true);
    compare_map($r->hGetAll('myKey'), array('key' => '1', 'key2' => '5'));
    compare($r->hmincrByex('myKey', array('key2' => 6), 200), true);
    upper($r->ttl('myKey'), 100);
    lower($r->ttl('myKey'), 1000);
    compare_map($r->hGetAll('myKey'), array('key' => '1', 'key2' => '11'));
    compare($r->hmincrByex('myKey', array('key3' => 12), 2), true);
    compare_map($r->hGetAll('myKey'), array('key' => '1', 'key2' => '11', 'key3' => '12'));
    sleep(5);
    compare_map($r->hGetAll('myKey'), array());
    $r->del('myKey');
    compare($r->hmincrByex('myKey', array('key' => 12), -1), true);
    compare_map($r->hGetAll('myKey'), array('key' => '12'));
}

echo("set expire\n");
$r->del('myKey');
compare($r->set('myKey', 'a'), true);
compare($r->expire('myKey', 200), true);
upper($r->ttl('myKey'), 100);
lower($r->ttl('myKey'), 1000);
compare($r->set('myKey', 'b'), true);
compare($r->ttl('myKey'), -1);

echo("hSet expire\n");
$r->del('myKey');
compare($r->hSet('myKey', 'b', 2), 1);
compare($r->expire('myKey', 200), true);
upper($r->ttl('myKey'), 100);
lower($r->ttl('myKey'), 1000);
compare($r->hSet('myKey', 'b', 3), 0);
upper($r->ttl('myKey'), 100);
lower($r->ttl('myKey'), 1000);
compare($r->hSet('myKey', 'a', 3), 1);
upper($r->ttl('myKey'), 100);
lower($r->ttl('myKey'), 1000);

echo("rPush expire\n");
$r->del('myKey');
compare($r->rPush('myKey', 'a'), 1);
compare($r->expire('myKey', 200), true);
upper($r->ttl('myKey'), 100);
lower($r->ttl('myKey'), 1000);
compare($r->rPush('myKey', 'b'), 2);
upper($r->ttl('myKey'), 100);
lower($r->ttl('myKey'), 1000);

//echo("Exec Multi\n");
//
//$r->del('myKey');
//$r->del('myKey2');
//compare($r->multi(), $r);
//compare($r->exec(), array());
//compare($r->multi(), $r);
//compare($r->get('myKey'), $r);
//compare($r->set('myKey', 'toto2'), $r);
//compare($r->get('myKey'), $r);
//compare($r->del('myKey'), $r);
//compare($r->rPush('myKey', 'a'), $r);
//compare($r->rPush('myKey', 'b'), $r);
//compare($r->rPop('myKey'), $r);
//compare($r->expire('myKey', 12), $r);
//compare($r->hSet('myKey2', 'a', 12), $r);
//compare($r->hGet('myKey2', 'a'), $r);
//compare($r->hSet('myKey2', 'b', '4'), $r);
//compare($r->hGetAll('myKey2'), $r);
//compare($r->exec(), array(false, true, 'toto2', 1, 1, 2, 'b', true, 1, '12', 1, array('a' => '12', 'b' => '4')));

//echo("Discard\n");
//
//$r->del('myKey');
//compare($r->set('myKey', 1), true);
//compare($r->incr('myKey'), 2);
//compare($r->get('myKey'), '2');
//compare($r->multi(), $r);
//compare($r->incr('myKey'), $r);
//compare($r->discard(), true);
//compare($r->discard(), false);
//compare($r->exec(), NULL);
//compare($r->get('myKey'), isset($_ENV['USE_REAL_REDIS']) ? '2' : '3');
//
//echo("Pipeline\n");
//
//$r->del('myKey');
//compare($r->pipeline(), $r);
//compare($r->exec(), array());
//compare($r->multi(), $r);
//compare($r->get('myKey'), $r);
//compare($r->set('myKey', 'toto2'), $r);
//compare($r->get('myKey'), $r);
//compare($r->del('myKey'), $r);
//compare($r->rPush('myKey', 'a'), $r);
//compare($r->rPop('myKey'), $r);
//compare($r->exec(), array(false, true, 'toto2', 1, 1, 'a'));

echo("Map timeout\n");
$r->del('myKey');
compare($r->expire('myKey', 2), false);
compare($r->hSet('myKey', 'a', 2), 1);
compare($r->expire('myKey', 2), true);
compare($r->hGet('myKey', 'a'), '2');
sleep(3);
compare($r->hGet('myKey', 'a'), false);

echo("SetNx\n");
$r->del('myKey');
compare($r->setnx('myKey', 'a'), true);
compare($r->setnx('myKey', 'b'), false);
compare($r->get('myKey'), 'a');

if ( !isset($_ENV['USE_REAL_REDIS']) && 0) {
    echo("SetNxEx\n");
    $r->del('myKey');
    compare($r->setnxex('myKey', 3, 'a'), true);
    compare($r->setnxex('myKey', 3, 'b'), false);
    compare($r->get('myKey'), 'a');
    sleep(4);
    compare($r->get('myKey'), false);

    echo("ArrayEx\n");

    $r->del('myKey');
    compare($r->lLen('myKey'), 0);
    compare($r->lPushEx('myKey', 'toto', 2), 1);
    compare($r->lLen('myKey'), 1);
    sleep(3);
    compare($r->lLen('myKey'), 0);

    $r->del('myKey');
    compare($r->lLen('myKey'), 0);
    compare($r->rPushEx('myKey', 'toto', 2), 1);
    compare($r->lLen('myKey'), 1);
    sleep(3);
    compare($r->lLen('myKey'), 0);

    echo("incrByEx / DecrByEx\n");

    $r->del('myKey');
    compare($r->incrByEx('myKey', 4, 2), 2);
    compare($r->get('myKey'), '2');
    upper($r->ttl('myKey'), 1);
    sleep(1);
    compare($r->get('myKey'), '2');
    upper($r->ttl('myKey'), 1);
    sleep(5);
    compare($r->get('myKey'), false);

    compare($r->incrByEx('myKey', 4, 2), 2);
    compare($r->get('myKey'), '2');
    upper($r->ttl('myKey'), 1);
    compare($r->incrBy('myKey', 4), 6);
    compare($r->get('myKey'), '6');
    sleep(1);
    compare($r->get('myKey'), '6');
    upper($r->ttl('myKey'), 1);
    sleep(5);
    compare($r->get('myKey'), false);

    compare($r->decrByEx('myKey', 4, 2), -2);
    compare($r->get('myKey'), '-2');
    upper($r->ttl('myKey'), 1);
    compare($r->incrBy('myKey', 4), 2);
    compare($r->get('myKey'), '2');
    sleep(1);
    compare($r->get('myKey'), '2');
    upper($r->ttl('myKey'), 1);
    sleep(5);
    compare($r->get('myKey'), false);
}

echo("SetEx\n");

$r->del('not_existing key');
compare($r->expire('not_existing key', 10), false);
compare($r->ttl('not_existing key'), -2);

$r->del('myKey');
compare($r->setex('myKey', 4, 'a'), true);
compare($r->get('myKey'), 'a');
upper($r->ttl('myKey'), 1);
sleep(1);
compare($r->get('myKey'), 'a');
upper($r->ttl('myKey'), 1);
sleep(5);
compare($r->get('myKey'), false);

compare($r->set('myKey', 'a'), true);
sleep(3);
compare($r->get('myKey'), 'a');
compare($r->expire('myKey', 4), true);
sleep(1);
upper($r->ttl('myKey'), 1);
compare($r->get('myKey'), 'a');
sleep(5);
compare($r->get('myKey'), false);

echo("Lot of keys\n");
for ($i = 0; $i < 500; $i++) {
    compare($r->set('myKey' . $i, $i), true);
}

for ($i = 0; $i < 500; $i++) {
    compare($r->get('myKey' . $i), '' . $i);
}

echo("OK\n");

$delay = microtime(true) - $start;

echo("Number of calls ' . $r->_counter . ', delay ' . $delay . '\n");

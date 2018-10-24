<?php
/**
 * 简易轮询类
 * Class Round
 */
class Round
{
    static $lastIndex = 0; //指针计数
    /**
     * @param $list  需要轮询的数组
     * @return mixed
     */
    public function select($list)
    {
        $currentIndex = self::$lastIndex; //当前的index
        $value = $list[$currentIndex];      //获取对应数据
        if ($currentIndex + 1 > count($list) - 1) {
            //记录,如果指针+1 的数量大于 总数 - 1 则 指针重置
            self::$lastIndex = 0;
        } else {
            //否则++
            self::$lastIndex++;
        }
        return $value;
    }
}
//所有哨兵ip和端口 这里由于是通过本机测试,所以该ip能访问
$sentinelConf = [
    ['ip' => '127.0.0.1', 'port' => 26381],
    ['ip' => '127.0.0.1', 'port' => 26382],
    ['ip' => '127.0.0.1', 'port' => 26383]
];
//随机访问 获取一个哨兵的地址
$sentinelInfo = $sentinelConf[array_rand($sentinelConf)];
$redis = new Redis();
$redis->connect($sentinelInfo['ip'], $sentinelInfo['port']);
//获取哨兵返回的所有在线的从服务器信息
$slavesInfo = $redis->rawCommand('SENTINEL', 'slaves', 'mymaster');
$slaves = [];
//进行组装
foreach ($slavesInfo as $val) {
    $slaves[] = ['ip' => $val[3], 'port' => $val[5]];
}
//加载到缓存当中，可以记录这次访问的时间跟上次的访问时间
//模拟客户端访问 1
while (true) {
    //轮训
    $slave = (new Round())->select($slaves);
    try {
        $redis = new Redis();
        $redis->connect($slave['ip'], $slave['port']);
        var_dump($slave, $redis->get('dream'));
    } catch (\RedisException $e) {
        var_dump("出错了!", $e->getMessage());
    }
    sleep(3);
}
<?php
/**
 * Created by PhpStorm.
 * User: suntoo-ssd-02
 * Date: 2018/10/30
 * Time: 15:02
 */

$parameters = ['45.77.5.50:6391', '45.77.5.50:6392', '45.77.5.50:6393'];
$options    = ['cluster' => 'redis'];

try {
    $obj_cluster = new RedisCluster(NULL, $parameters);
    var_dump("<pre>",$obj_cluster->mget(['dream','aaa']));
    $obj_cluster->del(['dream','aaa']);
} catch (Exception $exception) {
    echo $exception->getMessage();
}







#### redis集群搭建
目录介绍:
``` 
config                  配置文件目录
   |____nodes-6391.conf     主节点1
   |____nodes-6392.conf     主节点2
   |____nodes-6393.conf     主节点3
   |____nodes-6394.conf     从节点1
   |____nodes-6395.conf     从节点2
   |____nodes-6396.conf     从节点3
   |____nodes-6397.conf     备用测试主节点4
   |____nodes-6398.conf     备用测试从节点4
   |____redis.sh            容器启动后运行的脚本
   |____redis-trib.rb       ruby 实现的 redis 集群管理工具
Dockerfile              构建Redis镜像文件
docker-compose.yaml     编排所有需要运行的容器文件
```
- Redis Cluster简介
```
Redis Cluster 是Redis的分布式解决方案.当遇到单机内存|并发|流量等瓶颈的时候,可以采用 Cluster 架构达到负载均衡的目的.
```

- 好处及作用:
``` 
1.将数据自动切分到多个节点能力
2.集群中部分节点失效或无法通讯,拥有自动故障转移的能力
```

- Redis Cluster 和 replication + sentinel 使用取舍
``` 
当数据量较少,主要承载高并发高性能的场景,比如缓存一般几个G,使用单机足够了.
replication: 一个主(master)节点,多个从(slave)节点,需要多少个从节点根据读的吞吐量有关系,结合哨兵(sentinel)集群,保证Redis主从架构的高可用性.
Redis Cluster: 主要是针对海量数据 + 高并发 + 高可用的场景.
```

- Redis Cluster数据分布方式
``` 
1.顺序分区:将一整块数据集分散到各个节点.缺点->会导致数据倾斜.
2.哈希分区:将一整块数据集 通过hash的函数,取余产生的数,均匀分配到各个节点.或者使用一致性哈希进行分配.缺点->扩容的时候会导致数据迁移,节点伸缩.
3.推荐方案.虚拟槽分区:用哈希空间,使用分散度良好的哈希函数把所有数据映射到一定范围的整数集合中,定义为槽(slot).槽的范围是0~16383.根据节点的数量进行均匀分配槽中的数据.
```

#### 搭建集群(手动操作和利用Ruby管理工具自动化操作)
**集群最少需要6个节点才能保证完整的高可用**
- 准备节点
``` 
节点配置如下:
# 节点端口
port 6391
# 启用守护进程运行
daemonize yes
# 开启集群模式
cluster-enabled yes
# 集群内部配置文件 名称可以自定义,第一次启动会自动创建和更新,也就是说有两份配置文件.这里 用默认的
cluster-config-file nodes.conf
# 节点超时时间 毫秒
cluster-node-timeout 15000
```

- 规划节点

|   容器名称  |  容器IP地址  |  映射端口(宿主机:容器)  |  服务运行模式  |
|:----------:|:-----------:|:---------:|:------------:|
|redis-master1| 172.50.0.2 |6391:6391| master 主节点|
|redis-master2| 172.50.0.3 |6392:6392| master 主节点|
|redis-master3| 172.50.0.4 |6393:6393| master 主节点|
|redis-slave1| 172.30.0.2 |6394:6394| slave 从节点|
|redis-slave2| 172.30.0.3 |6395:6395| slave 从节点|
|redis-slave3| 172.30.0.4 |6396:6396| slave 从节点|
|redis-master4| 172.50.0.5 |6397:6397| master 主节点|
|redis-slave4| 172.30.0.5 |6398:6398| slave 从节点|

- 节点握手
``` 
cluster meet {ip} {port}
```

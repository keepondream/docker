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

#### 搭建集群(手动)
**集群最少需要6个节点才能保证完整的高可用**
- 准备节点
``` 
节点配置如下:
# 节点端口
port 6391
# 停止启用守护进程运行 , 这里是个坑跟docker机制有关,否则容器退出,具体原因好像是docker默认把第一个启动的进程认为是监听的进程
daemonize no
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
在此之前 需要使用 docker-compose up -d 将所有容器服务启动
# 在redis客户端发起命令进行通信
cluster meet {ip} {port}
参数:
ip  你需要通信的IP地址"注意这里必须要使用公网IP,否则就算连接上了,过段时间也会丢失,这是个大坑"
prot 端口
疑问:
怎么使用此命令进行通信呢,可能第一次看有点懵.
具体使用方法:
1.直接使用 redis-cli -h <公网IP> -p <任意一个节点端口,如:6391> 进入客户端
CLUSTER meet <公网IP> 6391
CLUSTER meet <公网IP> 6392
CLUSTER meet <公网IP> 6393
CLUSTER meet <公网IP> 6394
CLUSTER meet <公网IP> 6395
CLUSTER meet <公网IP> 6396
2.输入 CLUSTER nodes  查看所有通信中的节点 如下示例:
1f1dc752152f586eaedd4ab076c53afa2d216b68 172.50.0.1:6393 master - 0 1540521033264 2 connected
ca2a83085d814059e097c5f24c3bced1e7bc1cb9 172.50.0.1:6395 master - 0 1540521031249 0 connected
138ab9119030ea73f53e7ff85e472a47bada9540 172.50.0.1:6392 master - 0 1540521030244 1 connected
3ccfcd443135d8835fdafdfda41d6b72a7481c25 127.0.0.1:6391 myself,master - 0 0 4 connected
300218b09e6a1017a9200f3372303b0716264ef4 172.50.0.1:6394 master - 0 1540521032262 3 connected
a2fb0edb121332121c63f735926274ee4bbbedce 172.50.0.1:6396 master - 0 1540521034271 5 connected
这样就通信了.最前面一串是 节点固定ID,就是唯一标识
```

- 设置从节点
``` 
因为是集群,难免出现意想不到的事情发生,所有 需要接上面六个节点变成 一主一从 组成三对 这样一个主节点挂了,会自动故障转移将从节点变为主节点,再去尝试拉起故障主节点变从节点
# 分别在从节点客户端运行 命令 指定 主节点的
--------------------------------------
redis-cli -h 127.0.0.1 -p 6394
CLUSTER REPLICATE 3ccfcd443135d8835fdafdfda41d6b72a7481c25
exit
--------------------------------------
redis-cli -h 45.77.5.50 -p 6395
CLUSTER REPLICATE 138ab9119030ea73f53e7ff85e472a47bada9540
exit
--------------------------------------
redis-cli -h 45.77.5.50 -p 6396
CLUSTER REPLICATE 1f1dc752152f586eaedd4ab076c53afa2d216b68
exit
--------------------------------------
# 查看所有几点信息 CLUSTER NODES 信息如下(三主三从,分别对应谁的唯一标识):
3ccfcd443135d8835fdafdfda41d6b72a7481c25 172.30.0.1:6391 master - 0 1540521965842 4 connected
1f1dc752152f586eaedd4ab076c53afa2d216b68 172.30.0.1:6393 master - 0 1540521963834 2 connected
138ab9119030ea73f53e7ff85e472a47bada9540 172.30.0.1:6392 master - 0 1540521967862 1 connected
300218b09e6a1017a9200f3372303b0716264ef4 172.30.0.1:6394 slave 3ccfcd443135d8835fdafdfda41d6b72a7481c25 0 1540521968864 4 connected
a2fb0edb121332121c63f735926274ee4bbbedce 172.30.0.4:6396 myself,slave 1f1dc752152f586eaedd4ab076c53afa2d216b68 0 0 5 connected
ca2a83085d814059e097c5f24c3bced1e7bc1cb9 172.30.0.1:6395 slave 138ab9119030ea73f53e7ff85e472a47bada9540 0 1540521966857 1 connected
```
- 分配槽
``` 
redis集群将所有数据都映射在16384个槽中,每个key会对应一个固定槽,只有节点分配了槽后才能与映射的16384个槽关联
# 为主节点分配槽,目前3个主节点,平均分配 为  16384 / 3 = 5461 
redis-cli -h <ip> -p <port(主节点端口)> cluster addslots {start..end}
redis-cli -h 127.0.0.1 -p 6391 cluster addslots {0..5461}
redis-cli -h 127.0.0.1 -p 6392 cluster addslots {5462..10922}
redis-cli -h 127.0.0.1 -p 6393 cluster addslots {10923..16383}
# 分配后的示例为:
1f1dc752152f586eaedd4ab076c53afa2d216b68 172.50.0.1:6393 master - 0 1540524083903 2 connected 10923-16383
ca2a83085d814059e097c5f24c3bced1e7bc1cb9 172.50.0.1:6395 slave 138ab9119030ea73f53e7ff85e472a47bada9540 0 1540524086927 1 connected
138ab9119030ea73f53e7ff85e472a47bada9540 172.50.0.1:6392 master - 0 1540524081894 1 connected 5462-10922
3ccfcd443135d8835fdafdfda41d6b72a7481c25 127.0.0.1:6391 myself,master - 0 0 4 connected 0-5461
300218b09e6a1017a9200f3372303b0716264ef4 172.50.0.1:6394 slave 3ccfcd443135d8835fdafdfda41d6b72a7481c25 0 1540524085918 4 connected
a2fb0edb121332121c63f735926274ee4bbbedce 172.50.0.1:6396 slave 1f1dc752152f586eaedd4ab076c53afa2d216b68 0 1540524084915 5 connected
```
- 操作集群
``` 
-c 集群模式 指定集群模式 否则 报错 (error moved .....)
redis-cli -c -h <公网IP> -p <端口> get name
redis-cli -c -h <公网IP> -p <端口> set name val
就可以操作了
# 示例:
redis-cli -c -h 127.0.0.1 -p 6391 set aaa haha
OK
redis-cli -c -h 127.0.0.1 -p 6394 get aaa
"haha"
```

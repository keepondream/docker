#### docker笔记
目录
``` 
centos-redis        基于centos 构建Redis镜像
docker-machine      跨平台构建docker环境工具
docker-swarm        集群化管理一个个的docker宿主机或虚拟机
redis-cluster    redis Cluster集群搭建 应对场景海量行数据
redis-sentinel   哨兵集群 + Redis主从  应对场景单机解决高并发,数据量不是很大

```

#### Redis Cluster 相关命令
``` 
# 进入Redis客户端
redis-cli -h <IP地址> -p <端口> 

# 打印集群信息
cluster info

# 列出集群中当前已知的所有节点
cluster nodes 

# 将指定IP + prot 的Redis节点添加到集群中
cluster meet <ip> <port>

# 将一个或者多个槽分配给 当前节点 Redis固定槽位 0~16383
cluster addslots 0 1 2   # 指定单个槽分配
cluster addslots {0..6666} # 指定范围 0~6666 槽分配

# 移除当前节点 的一个或多个节点
cluster delslots 0 1 2 3
cluster delslots {0~..666}

# 列出槽位和节点信息
cluster slots

# 列出主节点下的所有从节点信息
cluster slaves <node_id 主节点唯一标识 如: 1f1dc752152f586eaedd4ab076c53afa2d216b68>

# 将当前节点设置为指定节点的从节点
cluster replicate <node_id 节点唯一标识>

# 手动执行命令保存集群的配置文件,集群默认在配置修改的时候会自动保存配置文件
cluster saveconfig

# 列出 key 被放置在哪个槽上
cluster keyslot <key>

# 移除当前节点被指派的所有槽,让节点变成一个没有任何槽的节点,需要当前节点没有key,使用后所有数据丢失
cluster flushslots

# 返回槽目前包含的键值对数量
cluster cluntkeysinslot <slot>

# 返回count 个槽中的建
cluster getkeysinslot <slot> <count>

# 将槽指派给指定的节点,如果槽已经指派给另一个节点,那么先让另一个节点删除该槽,然后在进行指派
cluster setslot <slot> node <node_id>

# 将本节点的迁移到指定的节点中
cluster setslot <slot> migrating <node_id>

# 从node_id 指定的节点中导入 槽 slot 到 本节点
cluster setslot <slot> importing <node_id>

# 取消对槽 slot 的导入(import) 或者迁移 (migrate)
cluster <slot> stable

# 手动进行故障转移
cluster failover

# 从集群中移除指定的节点,这样就无法完成握手,过期时间为 60s, 60后两个节点会继续完成握手
cluster forget <node_id>

# 重置集群信息,soft 清空其他节点信息,但是不修改自己的ID,hard会修改自己的ID,不传该参数默认使用soft方式
cluster reset [HARD|SOFT]

# 列出某个几点的故障报告的长度
cluster count-failure-reports <node_id>

# 设置节点 epoch, 只有在节点加入集群前才能设置

```
#### redis-trib.rb 相关命令
``` 
# 在启动好6个节点后,该命令进行自动节点握手和槽分配
redis-trib.rb create -replicas 1 127.0.0.1:6391 127.0.0.1:6392 127.0.0.1:6393 127.0.0.1:6394 127.0.0.1:6395 127.0.0.1:6396
-replicas  # 指定集群中每个主节点配备几个从节点,这里设置为1,redis-trib.rb 会自动为分配每个主节点一个从节点,并生成报告

# 检查集群
check  host:port

# 查看集群信息
info   host:port

# 修复集群
fix    host:port

# 在线迁移 slot
redis-trib.rb reshard host:port --from <arg> --to <arg> --slots <arg> --yes --timeout <arg> --pipeline <arg> 
    hostport                    # 必传参数，集群内任意节点地址，用来获取整个集群信息,相当于获取集群信息的入口
    --from <arg>                # 需要从哪些源节点上迁移slot,可从多个源节点完成迁移,以逗号隔开,传递的是节点的node_id,还可以直接传递 --from all,这样源节点就是集群的所有节点,不传递参数则会在迁移过程中提示用户输入
    --to <arg>                  # slot需要迁移的目的节点的node_id,目的节点只能填写一个,不传递参数则会在迁移过程中提示用户输入
    --slots                     # 需要迁移槽的总数量，在迁移过程中提示用户输入。
    --yes                       # 设置该参数,可以在打印执行 reshard计划的时候,提示用户输入yes确认后再执行reshard
    --timeout <arg>             # 设置migrate命令的超时时间。
    --pipeline  <arg>           # 定义cluster getkeysinslot命令一次取出的key数量，不传的话使用默认值为10
    
# 平衡集群节点slot数量  
rebalance       host:port
    --weight <arg>
    --auto-weights
    --use-empty-masters
    --timeout <arg> # 控制每次 migrate 操作的超时时间，默认为60000毫秒。
    --simulate 不会真正迁移，测试用的
    --pipeline <arg> 一次迁移多少分数据
    --threshold <arg>
      
# 将新节点加入集群 
add-node new_host:new_port existing_host:existing_port
    --slave
    --master-id <arg>
      
# 从集群中删除节点
del-node host:port node_id

#设置集群节点间心跳连接的超时时间
set-timeout host:port milliseconds

#在集群全部节点上执行命令
call host:port command arg arg .. arg

#将外部redis数据导入集群
import host:port
    --from <arg>
    --copy
    --replace
```

#### Redis-Cluster 利用redis-trib.rb (自动)快速搭建集群

**实现步骤**
``` 
1.利用git 克隆所有项目需要的包 可以放入在任何目录 这里用/目录为例
git clone https://github.com/keepondream/docker.git
2.进入到当前项目中的redis-cluster目录
cd /docker/redis-cluster
3.启动所有节点
docker-compose up -d
4.进入任意一个容器节点
docker exec -it redis-slave1 bash
5.利用ruby 的 redis-trib.rb 集群管理工具 一键管理进行握手和分配槽
redis-trib.rb create --replicas 1 <你的公网IP>:6391 <你的公网IP>:6392 <你的公网IP>:6393 <你的公网IP>:6394 <你的公网IP>:6395 <你的公网IP>:6396
6.利用Redis客户端 进入任意一个节点
redis-cli -h <公网IP> -p <端口>
7.查看cluster所有几点信息
cluster nodes
-----------------------------
# 成功后的信息示例:
5c7cfb9d9225f6769437c1187eb285b3cc728a9f 172.50.0.1:6393 master - 0 1540641820856 3 connected 10923-16383
50b2ff44afebae3c20e91a2f976e419749884b74 172.50.0.1:6392 master - 0 1540641819852 2 connected 5461-10922
4f9ecfb91ec13e493ae867b5443b596ad18bd032 172.50.0.1:6395 slave 50b2ff44afebae3c20e91a2f976e419749884b74 0 1540641818849 5 connected
2831f01547c9192aee8c0a854dbadcacbf1fa9d0 172.50.0.1:6394 slave 738396cac8fc58cf8fa65db18715004dfb37f848 0 1540641821858 4 connected
738396cac8fc58cf8fa65db18715004dfb37f848 172.50.0.2:6391 myself,master - 0 0 1 connected 0-5460
34aebc326386b91ca5390bfcd07fe9588740aa3f 172.50.0.1:6396 slave 5c7cfb9d9225f6769437c1187eb285b3cc728a9f 0 1540641817848 6 connected
以上信息说明,连接成功 3主3从 槽的分配都OK
-----------------------------
```


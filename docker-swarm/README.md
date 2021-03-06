#### docker swarm

Docker swarm介绍
``` 
Swarm是Docker公司推出的用来管理docker集群，它将一群Docker宿主机变成一个单一的，虚拟的主机。Swarm使用标准的Docker API接口作为其前端访问入口，换言之，各种形式的Docker Client(docker client in Go, docker_py, docker等)均可以直接与Swarm通信。Swarm几乎全部用go语言来完成开发，Swarm0.2发布，相比0.1版本，0.2版本增加了一个新的策略来调度集群中的容器，使得在可用的节点上传播它们，以及支持更多的Docker命令以及集群驱动。
Swarm deamon只是一个调度器（Scheduler）加路由器(router)，Swarm自己不运行容器，它只是接受docker客户端发送过来的请求，调度适合的节点来运行容器，这意味着，即使Swarm由于某些原因挂掉了，集群中的节点也会照常运行，当Swarm重新恢复运行之后，它会收集重建集群信息．
```
作用:
``` 
1、应用想要扩容到两台以上的服务器上，多台服务器总是比单台服务器复杂，可以使用docker-swarm进行集群化的管理跟伸缩
2、应用是否有高可用的要求，在docker swarm集群中有两种不同类型的节点，Master节点和Worker节点,其中的一个Master节点是Leader,如果当前Leader宕机不可用，其他健康的Master中的一台会自动成为Leader 。如果Worker节点宕机不可用，宕机节点上的容器实例会被重新调度到其他健康的Worker节点上。
```
概念:
``` 
# Swarm
集群的管理和编排是使用嵌入到docker引擎的SwarmKit，可以在docker初始化时启动swarm模式或者加入已存在的swarm
# Node
运行 Docker 的主机可以主动初始化一个 Swarm 集群或者加入一个已存在的 Swarm 集群，这样这个运行 Docker 的主机就成为一个 Swarm 集群的节点 ( node )
节点分为管理 ( manager ) 节点和工作 ( worker ) 节点。
管理节点用于 Swarm 集群的管理， docker swarm 命令基本只能在管理节点执行（节点退出集群命令 docker swarm leave 可以在工作节点执行）。
一个 Swarm 集群可以有多个管理节点，但只有一个管理节点可以成为 leader ， leader 通过 raft 协议实现
工作节点是任务执行节点，管理节点将服务 ( service ) 下发至工作节点执行。管理节点默认也作为工作节点。你也可以通过配置让服务只运行在管理节点。
# 服务和任务
任务 （ Task ）是 Swarm 中的最小的调度单位，目前来说就是一个单一的容器。
服务 （ Services ） 是指一组任务的集合，服务定义了任务的属性。
```
docker swarm init 命令参考
``` 
–cert-expiry
设置节点证书有效期
–dispatcher-heartbeat
设置节点报告它们的健康状态间隔的时间。
–external-ca value
设置集群使用一个外部CA来签发节点证书。value的格式为protocol=X,url=Y。protocol指定的是发送签名请求到外部CA所使用的协议。目前只支持cfssl。URL指定的是签名请求应该提交到哪个endpoint。
–force-new-cluster
强制一个失去仲裁能力的集群的其中一个节点重启成为一单节点集群，而不丢失数据。
–listen-addr value
在这个地址监听集群管理相关流量。默认是监听0.0.0.0:2377。也可以指定一个网络接口来监听这个接口的地址。例如–listen-addr eth0:2377。
端口是可选的。如果仅指定IP地址或接口名称，端口就使用默认的2377。
–advertise-addr value
指定通告给集群的节点的地址，这个地址用来给其它节点访问API和overlay网络通信。如果没有指定地址，docker将检查系统是否只有一个IP地址，如果是将使用这个地址并使用监听的端口(查看–listen-addr)。如果系统有多个IP地址，–advertise-addr就必须指定一个以便内部管理节点能够正常通信和overlay网络通信。
也可以指定一个网络接口来通告接口的地址，例如–advertise-addr eth0:2377。
端口是可选的。如果仅指定一个IP地址或接口名称，就使用端口2377。
–task-history-limit
设置任务历史记录保留限制。
```
加入集群
``` 
# 初始化集群领导
docker swarm init #### 不加任何参数则默认为leader 端口为 2377
# 进入阿里云控制台 将所有集群虚拟主机端口开放
2377 端口 集群管理端口
7946 tcp/udp端口 集群节点通信
4789 用于overlay网络流量
# 查看集群中的相关信息
docker node ls 
docker info
# 在manager 节点 即 leader的节点 查看加入swarm集群的命令token
docker swarm join-token worker # 加入worker命令
docker swarm join-token manager #加入manager 命令
-------------------
[root@hk ~]# docker swarm join-token manager
To add a manager to this swarm, run the following command:

    docker swarm join --token SWMTKN-1-3vfapniawgffxly6s68ix2yq1toghwlwvmcvjnt4kb0yr5t4p2-4hlmmz991s5jnksbnw646fe1y 172.31.25.141:2377

[root@hk ~]# docker swarm join-token worker
To add a worker to this swarm, run the following command:

    docker swarm join --token SWMTKN-1-3vfapniawgffxly6s68ix2yq1toghwlwvmcvjnt4kb0yr5t4p2-53892gcuc2h6lpqfbp97xxser 172.31.25.141:2377
-------------------
#### 然后在对应的虚拟主机上运行要加入的命令token
#### 一定要注意 必须开放相关端口 否则连接失败 另外注意 如果你的宿主机里面有安装防火墙 端口也要开发,被这个折磨了一段时间
```
节点升降级
``` 
使用 docker node ls 查看集群所有节点 使用升降功能 如果是leader节点会自动选举
将节点升级为manager:  docker node  promote <节点名ID> 
将节点降级为worker：  docker node  demote <节点名ID>
```
部署服务
``` 
# 创建服务 在manager节点部署nginx服务，服务数量为10个，公开指定端口是8080映射容器80,使用nginx镜像
docker service create --replicas 10  -p 8080:80 --name nginx  nginx
# --replicas 服务数量
容器当中的软件安装:
apt-get update（更新源信息）
apt-get install vim
apt-get install net-tools 
apt-get install iputils-ping
apt-get install psmisc

# 更新服务 <nginx服务名称别名可自定义>
docker service update --image nginx:apline nginx

# 删除服务 
docker service rm nginx

# 减少服务实例
docker service scale nginx=0

# 增加服务实例
我们可以使用 docker service scale 对一个服务运行的容器数量进行伸缩。
当业务处于高峰期时，我们需要扩展服务运行的容器数量，加机器进入到集群当中
docker service scale nginx=5

# 查看所有服务
docker service ls

# 查看服务的容器状态
docker service ps nginx

# 查看服务的详细信息
docker service inspect nginx
```
docker-compose 方式部署服务
``` 
使用 docker service create 一次只能部署一个服务，如果想要部署多个服务，就需要docker Stack结合 docker-compose.yml 我们可以一次启动多个关联的服务,当然swarm会把这些服务分散到各个工作节点

利用docker-compose配置redis集群，实现redis集群部署以及集群伸缩

需要解决的问题：
1、文件共享的问题
Machine主机跟docker虚拟主机之间通讯
挂载目录，只能被挂载一次，不能两台远程主机共享一个挂载目录


2、网络共享问题


3、配置文件问题
 需要一个配置文件的模板，不同的容器当中都能够去拥有这个模板
 
docker config  create  redis.conf  redis.conf
 配置文件名称 文件地址
 
优点：
提供一个所有服务的模板，免去一些公共配置，需要临时修改的时候，修改的复杂度

 docker stack deploy -c docker-compose.yaml  redis-cluster

```

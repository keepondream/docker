#### docker Machine 介绍及安装

1.Docker Machine 是什么
``` 
Docker Machine 是官方编排项目之一,负责在多中平台上快速安装 Docker 环境.
Dokcer Machine 是一个工具,允许在虚拟宿主机上安装Docker Engine,并使用docker-machine
命令管理这些宿主机.可以再本地的 Mac 或者 Windows box . 数据中心, 或者阿里云 或 华为这样的云
提供商创建 Docker 宿主机.
使用docker-machine 命令,可以 启动.审查.停止和重启托管的宿主机.升级Docker客户端和
守护程序,并配置Docker客户端与你的宿主机通信.
```
2.安装
``` 
# macOS
curl -L https://github.com/docker/machine/releases/download/v0.14.0/docker-machine-$(uname -s)-$(uname -m) >/usr/local/bin/docker-machine &&
  chmod +x /usr/local/bin/docker-machine
# Linux
curl -L https://github.com/docker/machine/releases/download/v0.14.0/docker-machine-$(uname -s)-$(uname -m) >/tmp/docker-machine &&
  sudo install /tmp/docker-machine /usr/local/bin/docker-machine
  
# 安装完成后 使用 docker-machine -v 查看版本 则说明安装成功
```
3.使用
``` 
用Docker Machine可以批量安装和配置docker host，其支持在不同的环境下安装配置docker host，包括：
常规 Linux 操作系统
虚拟化平台 - VirtualBox、VMWare、Hyper-V
公有云 - Amazon Web Services、Microsoft Azure、Google Compute Engine、阿里、华为等

# 第三方驱动支持列表
https://github.com/docker/docker.github.io/blob/master/machine/AVAILABLE_DRIVER_PLUGINS.md

# 使用第三方驱动安装
1.下载驱动 这里默认使用阿里云的
Mac OSX 64 bit: 
https://docker-machine-drivers.oss-cn-beijing.aliyuncs.com/docker-machine-driver-aliyunecs_darwin-amd64.tgz

Linux 64 bit: 
https://docker-machine-drivers.oss-cn-beijing.aliyuncs.com/docker-machine-driver-aliyunecs_linux-amd64.tgz

Windows 64 bit:
https://docker-machine-drivers.oss-cn-beijing.aliyuncs.com/docker-machine-driver-aliyunecs_windows-amd64.tgz

2.解压安装
# 下载驱动 并解压 之后删除压缩包
curl -L https://docker-machine-drivers.oss-cn-beijing.aliyuncs.com/docker-machine-driver-aliyunecs_linux-amd64.tgz > driver-aliyunecs.tgz && tar zxvf driver-aliyunecs.tgz && rm driver-aliyunecs.tgz
# 移动驱动文件到bin目录下 给上可执行权限
mv ./bin/docker-machine-driver-aliyunecs.linux-amd64 /usr/local/bin/docker-machine-driver-aliyunecs && chmod +x /usr/local/bin/docker-machine-driver-aliyunecs

3.想要创建一个阿里云虚拟化实例，需要满足几个条件
账户余额大于100，因为创建的实例为按量付费
设置accesskey，要具备操作账户的权限

4.创建云虚拟主机实例
docker-machine create -d aliyunecs --aliyunecs-io-optimized=optimized --aliyunecs-description=aliyunecs-machine-driver --aliyunecs-instance-type=ecs.n4.small --aliyunecs-access-key-id=XXX --aliyunecs-access-key-secret=XXX --aliyunecs-region=cn-hangzhou --aliyunecs-ssh-password=XXX --aliyunecs-image-id=centos_7_04_64_20G_alibase_201701015.vhd <machine-name>

# 说明,详细介绍可是用 docker-machine create -d aliyunecs --help 网址:https://github.com/AliyunContainerService/docker-machine-driver-aliyunecs
--aliyunecs-io-optimized=optimized                  //磁盘io优化
--aliyunecs-description=aliyunecs-machine-driver    //描述
--aliyunecs-instance-type=ecs.mn4.small             //实例规格
--aliyunecs-access-key-id=XXX                       // key
--aliyunecs-access-key-secret=XXX                   //秘钥
--aliyunecs-region=cn-hangzhou                      //地区 参照:  https://help.aliyun.com/document_detail/40654.html
--aliyunecs-ssh-password=XXX                        //ssh登录密码
–-aliyunecs-image-id=centos_7_04_64_20G_alibase_201701015.vhd  //镜像实例  ecs镜像id，我找这个找了很久，因为找的是公共镜像，公共镜像id在控制台，云服务器ECS，镜像，公共镜像里有
```
4.docker-machine 常用命令
``` 
都在help里面有，下面都是docker-machine后加的命令就是docker-machine command
active 查看活跃的 Docker 主机
config 输出连接的配置信息
create 创建一个 Docker 主机
env 显示连接到某个主机需要的环境变量
inspect 输出主机更多信息
ip 获取主机地址
kill 停止某个主机
ls 列出所有管理的主机
provision 重新设置一个已存在的主机
regenerate-certs 为某个主机重新生成 TLS 认证信息
restart 重启主机
rm 删除某台主机
ssh SSH 到主机上执行命令
scp 在主机之间复制文件
mount 挂载主机目录到本地
start 启动一个主机
status 查看主机状态
stop 停止一个主机
upgrade 更新主机 Docker 版本为最新
url 获取主机的 URL
version 输出 docker-machine 版本信息
help 输出帮助信息
每个参数又都是有help的，可以通过
docker-machine COMMAND --help
来查看
```

5.利用docker swarm 将所有虚拟主机都连接上集群后 进行挂载目录
``` 
# 注意 一个目录只能被挂载一次
# sshfs 这个工具可以把远程主机的文件系统映射到本地主机上，透过 SSH 把远程文件系统挂载到本机上，这样我们可以不必使用 scp 工具就可以做到直接复制及删除远程主机的文件了，就像操作本地磁盘一样方便
#### 宿主机必须安装 sshfs 后才能进行挂载
yum  install fuse-sshfs
# 在宿主机创建 manager1 的挂载目录 路径可以自定义
mkdir /root/manager1
# 在虚拟主机 manager 创建目录
docker-machine ssh manager1 mkdir /root/manager1
# 进行挂载 docker-machine mount <虚拟主机的名称:虚拟主机文件路劲目录> <当前宿主机目录路径> 这样宿主机的数据和文件都是实时同步的
docker-machine mount manager1:/root/manager1 /root/manager1
注意 如果挂载文件不为空,则会提示设置参数进行清空挂载,但是docker-machine mount 不支持有文件挂载
# 使用一下命令 进行清空挂载,这个慎用,会清除所有文件和数据
sshfs root@121.196.194.187:/root/worker  worker   -o nonempty
# 取消挂载
docker-machine mount -u manager1:/root/manager1 /root/manager1
# 虚拟主机重启后 需要重新挂载,且宿主机的挂载目录不能有文件,清空后可以挂载成功,之后虚拟主机的文件会同步过来.
```

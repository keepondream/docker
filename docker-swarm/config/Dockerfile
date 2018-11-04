# 基于最新的centos镜像
FROM centos:latest
# 描述
MAINTAINER keepondream
# 添加Redis 用户 和用户组
RUN groupadd -r redis && useradd -r -g redis redis
# 更新yum 安装 redis ruby 等相应工具
RUN yum -y update \
&& yum -y install epel-release \
&& yum -y install redis \
&& yum -y install curl \
&& yum -y install net-tools \
&& yum -y install vim \
&& yum -y install wget \
&& yum -y install ruby \
&& yum -y install rubygems \
&& yum -y install git \
&& yum -y install telnet \
&& yum -y install fuse-sshfs
# 下载ruby Redis管理工具 安装并删除安装包 版本最好对应
RUN wget https://rubygems.global.ssl.fastly.net/gems/redis-3.2.1.gem \
&&  gem install -l ./redis-3.2.1.gem \
&&  rm -f redis-3.2.1.gem
# 定义工作目录 类似cd 且目录不存在自动创建
WORKDIR /config
# 复制Redis节点配置文件 到 容器 的 /config 目录
COPY ./redis.conf /config
# 复制 ruby 管理文件脚本
COPY ./redis-trib.rb /usr/bin
# 给脚本文件 执行权限
RUN chmod 777 /usr/bin/redis-trib.rb



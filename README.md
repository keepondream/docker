# docker
目录:
    centos-redis: 基于centos构建的redis
    redis-cluster: 利用哨兵 + 上面的redis构建集群
    
    
集群使用方法:
    1.克隆到服务器
    2.在redis-cluster目录下 运行 docker-compose up -d --scale slave=3
    3.运行 php test.php
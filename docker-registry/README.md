#### docker 私有仓库搭建
docker registry 简介
``` 
镜像的仓库,比如官方的是Docker Hub,它是开源的.registry可以自己部署一个私有仓库.
作用:
    因为我们自己的业务的特殊性,往往需要自定义好自己的镜像,并且要基于这个镜像在不同的服务器上创建容器,这样一来,镜像文件的拷贝,构建是一个繁琐且没有意义的事情.
使用私有仓库有许多优点：
    节省网络带宽,针对于每个镜像不用每个人都去中央仓库上面去下载,只需要从私有仓库中下载即可；
    提供镜像资源利用,针对于公司内部使用的镜像,推送到本地的私有仓库中,以供公司内部相关人员使用.
```
安装使用
``` 
# 运行私有仓库的容器
docker run -d -v /home/registry:/var/lib/registry -p 5000:5000 --restart=always --privileged=true --name registry registry:latest
    -v  /home/registry:/var/lib/registry 默认情况下，会将仓库存放于容器内的/var/lib/registry目录下，指定本地目录挂载到容器。
    -p 5000:5000 端口映射
    --restart=always 在容器退出时总是重启容器,主要应用在生产环境
    --privileged=true 在CentOS7中的安全模块selinux把权限禁掉了，参数给容器加特权，不加上传镜像会报权限错误OSError: [Errno 13] Permission denied: ‘/tmp/registry/repositories/liibrary’)或者（Received unexpected HTTP status: 500 Internal Server Error）错误
    --name registry 指定容器的名称
    
# 自己构建镜像或者下拉一个镜像
docker pull ansible/centos7-ansible

# 使用tag命令修改标签
docker tag  ansible/centos7-ansible  118.24.109.254:5000/centos         # 推送到仓库
              镜像名称                 修改名称【IP地址:端口号/镜像名】
# 推送镜像到仓库
docker push 118.24.109.254:5000/centos

#### https 报错
私有仓库默认是https上传方式，因为本地没有搭建https的方式所以可以简单修改下
下面的代码是给我们的私有仓库添加一个不安全的注册地址
echo '{ "insecure-registries":["118.24.109.254:5000"] }' > /etc/docker/daemon.json 

# 拉取私有镜像
docker pull 118.24.109.254:5000/centos

# 查看镜像当中镜像
curl 127.0.0.1:5000/v2/_catalog

# 删除镜像
    Api的方式
    
    DELETE /v2/<name>/manifests/<reference>
    name:镜像名称
    reference: 镜像对应sha256值
    相对比较麻烦，需要先得到sha256值，修改配置文件，然后再回收
    
    
    删除镜像其实有个讨巧的方法，总共两步即可
    第一步删除repo
    docker exec 《容器名》 rm -rf /var/lib/registry/docker/registry/v2/repositories/<镜像名>
    
    第二步，使用垃圾回收命令，回收镜像名称
    docker exec 《容器名》  bin/registry garbage-collect /etc/docker/registry/config.yml
```

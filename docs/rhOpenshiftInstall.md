# Описание установки REDHAT openshift в режиме одного узла (SingleNode)

## Начальные действия

### Подготовка live ISO-образа 

Перед установкой необходимо 
- Скачать бинарный код клиента Openshift `oc`:
 ```
 curl -k https://mirror.openshift.com/pub/openshift-v4/clients/ocp/latest/openshift-client-linux.tar.gz > oc.tar.gz
 tar zxf oc.tar.gz
 chmod +x oc
 ```
 
 - Определиться с версией `OpenShift`
   ```
   OCP_VERSION=<ocp_version> 
   ```
    Список версий можно посмотреть на сайте https://mirror.openshift.com/pub/openshift-v4/x86_64/clients/ocp/.
    
- Скачать устанощик `openshift-install`
  ```
  curl -k https://mirror.openshift.com/pub/openshift-v4/clients/ocp/$OCP_VERSION/openshift-install-linux.tar.gz > openshift-install-linux.tar.gz
  tar zxvf openshift-install-linux.tar.gz
  chmod +x openshift-install
  ```
- Определить URL ISO-файла и скачать его
  ```
  ISO_URL=$(./openshift-install coreos print-stream-json | grep location | grep x86_64 | grep iso | cut -d\" -f4)
  curl $ISO_URL > rhcos-live.x86_64.iso
  ```  
- Подготовить `install-config.yaml`:
  Пример файла:
  ```
  apiVersion: v1
  baseDomain: openshift.altlinux.io
  compute:
  - name: worker
    replicas: 0
  controlPlane:
    name: master
    replicas: 1
  metadata:
    name: openshift
  networking:
    networkType: OVNKubernetes
    clusterNetwork:
    - cidr: 10.244.0.0/16
      hostPrefix: 24
    serviceNetwork:
    - 10.96.0.0/16
  platform:
    none: {}
  BootstrapInPlace:
    InstallationDisk: /dev/vda
  pullSecret: ...
  sshKey: |
    ssh-rsa ...
  ```
    * `pullSecret` нужно скачать под Вашим эккаунтом со страницы `https://console.redhat.com/openshift/install/pull-secret`
    * `sshKey` копируется из файла `~/.ssh/id_rsa.pub`.
- Скопировать файл `install-config.yaml` в каталог `ocp` и создать ignition-файл `ocp/bootstrap-in-place-for-live-iso.ign`:
  ```
  mkdir ocp
  cp install-config.yaml ocp
  ./openshift-install --dir=ocp create single-node-ignition-config
  ```
- Скачать образ `quay.io/coreos/coreos-installer:release`:
  ```
  sudo podman pull quay.io/coreos/coreos-installer:release
  ```
- Включить полученный ignition-файл в ISO-образ:
  ```
  cp ocp/bootstrap-in-place-for-live-iso.ign iso.ign
  sudo podman run --privileged --rm -v /dev:/dev -v /run/udev:/run/udev -v $PWD:/data -w /data \
    quay.io/coreos/coreos-installer:release \
    iso ignition embed -fi iso.ign rhcos-live.x86_64.iso
  ```
### Настройка DNS

Выберите корневое доменное имя (например `altlinux.io`) и сформируйте для него DNS-записи типа A:
```
openshift.openshift IN A 192.168.122.135
api.openshift.openshift IN CNAME openshift.openshift
*.apps.openshift.openshift IN CNAME openshift.openshift
api-int.openshift.openshift IN CNAME openshift.openshift
```
и типа PTR
```
135.122.168.192.in-addr.arpa. IN PTR openshift.openshift.altlinux.io.
```
### Создание виртуальной машины в libvirt


## Ссылки

- [Installing OpenShift on a single node ](https://docs.openshift.com/container-platform/4.9/installing/installing_sno/install-sno-installing-sno.html)
- [Openshift Single Node for Distributed Clouds](https://medium.com/codex/openshift-single-node-for-distributed-clouds-582f84022bd0)

# Описание установки REDHAT openshift в режиме одного узла (SingleNode)

## Начальные действия

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
- Подготовить `install-config.yaml` в каталоге `ocp`:
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
    
    
## Ссылки

- [Installing OpenShift on a single node ](https://docs.openshift.com/container-platform/4.9/installing/installing_sno/install-sno-installing-sno.html)

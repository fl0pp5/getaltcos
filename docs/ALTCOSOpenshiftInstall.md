# Установка REDHAT openshift в ALTCOS

## Реализация

- Установка в среду PVE или LIBVIRT QEMU
- При установке развернуть для два диска - / (ext4)  и /var (btrfs). 
  Описание см [Creating a separate /var partition](https://docs.openshift.com/container-platform/4.9/installing/installing_platform_agnostic/installing-platform-agnostic.html#installation-user-infra-machines-advanced_vardisk_installing-platform-agnostic)


## Настройка DNS

## Настройка балансировщика нагрузки

## Генерация SSH-ключей

## Установка дополнительного ПО 

## Установка openshift

### Создание ignition-файлов

#### Создание файла конфигурации install-config.yaml

#### Генерация файлов манифестов

```
# mkdir ocp
# cp install-config.yaml ocp
INFO Consuming Install Config from target directory 
WARNING Making control-plane schedulable by setting MastersSchedulable to true for Scheduler cluster settings 
INFO Manifests created in: ocp/manifests and ocp/openshift
```
![Создание манифестов](./Images/openshift_altcos_manifests.png)

##### Добавление манифестов (создание BTRFS томов)

#### Создание ignition-файлов

```
# ./openshift-install create ignition-configs --dir ocp
INFO Consuming Worker Machines from target directory 
INFO Consuming Common Manifests from target directory 
INFO Consuming OpenShift Install (Manifests) from target directory 
INFO Consuming Openshift Manifests from target directory 
INFO Consuming Master Machines from target directory 
INFO Ignition-Configs created in: ocp and ocp/auth 
```
![Создание манифестов](./Images/openshift_altcos_ignition.png)





## Ссылки

- [Running Openshift at Home - Part 4/4 Deploying Openshift 4 on Proxmox VE ](https://blog.rossbrigoli.com/2020/11/running-openshift-at-home-part-44.html)
- [Install OpenShift on any x86_64 platform with user-provisioned infrastructure](https://console.redhat.com/openshift/install/platform-agnostic)
- [Installing a cluster on any platform](https://docs.openshift.com/container-platform/4.9/installing/installing_platform_agnostic/installing-platform-agnostic.html)
- 

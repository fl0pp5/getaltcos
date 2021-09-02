# Поддержка репозиториев и образов

## Создание tar-файла корневого репозитория:

```
$ cd /var/www/vhosts/getacos/ostree/bin
$ mkimage-profiles.sh sisyphus 
```

## Генерация образа и bare-репозитория

```
$ rootfs_to_repo.sh acos/x86_64/sisyphus
```

# coreos-installer и сборка ISO

Исходники тут: https://github.com/coreos/coreos-installer 

## Компиляция
Для компиляции надо ввести команду make

Бинарник будет тут ./target/debug/coreos-installer

## Скачивание образов
Для скачивания образов существует команда download. Чтобы выполнялось скачивание образов ALTCOS без параметра -u, потребуется поменять ссылку в коде (src/source.rs:33: const DEFAULT_STREAM_BASE_URL: &str = "https://builds.coreos.fedoraproject.org/streams/";)
```
[keremet@mycomp coreos-installer]$ ./target/debug/coreos-installer download --help
coreos-installer-download 0.10.1-alpha.0
Download a CoreOS image

USAGE:
    coreos-installer download [OPTIONS]

OPTIONS:
    -s, --stream <name>            Fedora CoreOS stream [default: stable]
    -a, --architecture <name>      Target CPU architecture [default: x86_64]
    -p, --platform <name>          Fedora CoreOS platform name [default: metal]
    -f, --format <name>            Image format [default: raw.xz]
    -u, --image-url <URL>          Manually specify the image URL
    -C, --directory <path>         Destination directory [default: .]
    -d, --decompress               Decompress image and don't save signature
        --insecure                 Skip signature verification
        --stream-base-url <URL>    Base URL for Fedora CoreOS stream metadata
        --fetch-retries <N>        Fetch retries, or "infinite" [default: 0]
    -h, --help                     Prints help information
[keremet@mycomp coreos-installer]$ 
```

При вызове с такими параметрами скачивание работает без внесения изменений
```
./target/debug/coreos-installer download -u https://altcos.altlinux.org/ALTCOS/streams/altcos/x86_64/sisyphus/images/qcow2/sisyphus.20211108.1.10.qcow2.xz --insecure
```


## Установка

Для установки через coreos-installer требуется создать файлы *.osmet (https://coreos.github.io/coreos-installer/osmet/) и разместить их в каталоге /run/coreos-installer/osmet (src/source.rs:36: const OSMET_FILES_DIR: &str = "/run/coreos-installer/osmet";)

При сборке ISO FCOS файлы *.osmet формируются в COSA (https://github.com/coreos/coreos-assembler) на основе образа виртуальной машины запуском coreos-installer. В COSA в качестве входных данных используется образ *.raw, но формат *.qcow2 тоже подойдет.

Для тестирования процесса создания *.osmet и установки использовались два образа
- sisyphus.20211108.1.10.qcow2 - образ ALTCOS для запуска в QEMU
- sl.img - образ с установленным Simply Linux и скомпилированным coreos-installer. При компиляции вносились следующие изменения, так как в ALTCOS, в отличие от FCOS, не выделяется специальный загрузочный раздел
diff --git a/src/osmet/mod.rs b/src/osmet/mod.rs
```
index c1ba965..a508ae2 100644
--- a/src/osmet/mod.rs
+++ b/src/osmet/mod.rs
@@ -95,18 +95,18 @@ pub fn osmet_pack(config: &OsmetPackConfig) -> Result<()> {
     // MS_RDONLY; this also ensures that the partition isn't already mounted rw elsewhere.
     let disk = Disk::new(&config.device)?;
     let boot = disk.mount_partition_by_label("boot", mount::MsFlags::MS_RDONLY)?;
-    let root = disk.mount_partition_by_label("root", mount::MsFlags::MS_RDONLY)?;
+//    let root = disk.mount_partition_by_label("root", mount::MsFlags::MS_RDONLY)?;
 
     // now, we do a first scan of the boot partition and pick up files over a certain size
     let boot_files = prescan_boot_partition(&boot)?;
 
     // generate the primary OSTree object <--> disk block mappings, and also try to match up boot
     // files with OSTree objects
-    let (root_partition, mapped_boot_files) = scan_root_partition(&root, boot_files)?;
+    let (root_partition, mapped_boot_files) = scan_root_partition(&boot, boot_files)?;
 
     let boot_partition = scan_boot_partition(&boot, mapped_boot_files)?;
 
-    let partitions = vec![boot_partition, root_partition];
+    let partitions = vec![boot_partition /*, root_partition*/];
 
     // create a first tempfile to store the packed image
     eprintln!("Packing image");
@@ -116,7 +116,7 @@ pub fn osmet_pack(config: &OsmetPackConfig) -> Result<()> {
     // verify that re-packing will yield the expected checksum
     eprintln!("Verifying that repacked image matches digest");
     let (checksum, unpacked_size) =
-        get_unpacked_image_digest(&mut xzpacked_image, &partitions, &root)?;
+        get_unpacked_image_digest(&mut xzpacked_image, &partitions, &boot)?;
     xzpacked_image
         .seek(SeekFrom::Start(0))
         .context("seeking back to start of xzpacked image")?;
```


```
qemu-img create -f qcow2 out.qcow2 10G
qemu-system-x86_64 -m 10000 -machine accel=kvm -cpu host -smp 8 -hda sl.img -hdb sisyphus.20211108.1.10.qcow2 -hdc out.img
```

Внутри виртуальной машины:
- Создать каталог для файлов *.osmet. В этом каталоге они будут искаться при установке.
```
sudo mkdir -p /run/coreos-installer/osmet
```
- Создать файл /run/coreos-installer/osmet/1.osmet на основе sisyphus.20211108.1.10.qcow2
```
sudo /home/keremet/src/coreos-installer/target/debug/coreos-installer osmet pack /dev/sdb --description 123 --output /run/coreos-installer/osmet/1.osmet --checksum 8105f3d988dabf59781fc55a682d03dd781ff42916a8d171793a7cd556d6fa69
```

- Примонтировать раздел с ostree-репозиторием
```
sudo mkdir /sysroot
sudo mount /dev/sdb1 /sysroot
```

- Выполнить установку на чистый диск (out.qcow2)
```
sudo /home/keremet/src/coreos-installer/target/debug/coreos-installer install --ignition-file /home/keremet/config_example.ign --offline /dev/sdc
```

Проверка:
```
qemu-system-x86_64 -m 10000 -machine accel=kvm -cpu host -smp 8 -hda out.qcow2
```


## Сборка ISO
Минимальный набор файлов для создания установочного ISO:

```
[keremet@mycomp ~]$ tree /tmp/iso
/tmp/iso
|--syslinux (весь каталог из ISO ALTCOS)
|  |--isolinux.cfg
|  |--pxelinux.0
|  |--menu.c32
|  |--isolinux.bin
|  |--gfxboot.c32
|  `--boot.cat
`--images
   `--pxeboot (из ISO FCOS)
      |--vmlinuz
      |--rootfs.img
      `--initrd.img
[keremet@mycomp ~]$ 
```

Команда сборки: 
```
sudo /usr/bin/genisoimage -verbose -V fedora-coreos-35.20211017.1.0 -volset fedora-coreos-35.20211017.1.0  -rational-rock -J -joliet-long -eltorito-boot syslinux/isolinux.bin -eltorito-catalog syslinux/boot.cat -no-emul-boot -boot-load-size 4 -boot-info-table -eltorito-alt-boot -o /tmp/fcos.iso /tmp/iso/
```

fedora-coreos-35.20211017.1.0 - эта строка должна соответствовать тому, что прописано в initrd.img для монтирования ISO. В данном случае должна выполняться команда
```
mount -t iso9660 -o ro /dev/disk/by-label/fedora-coreos-35.20211017.1.0 /run/media/iso
```

rootfs.img - это архив cpio, в котором находятся *.osmet и root.squashfs - файловая система с ostree-репозиторием

Для создания root.squashfs потребуется образ виртуальной машины и guestfish. 
В файл /usr/lib64/guestfs/initramfs.x86_64.img (пакет guestfs-data) надо добавить mksquashfs.
```
sudo -s
zcat /usr/lib64/guestfs/initramfs.x86_64.img | cpio -idmv
cp /usr/bin/mksquashfs usr/bin/
find . | cpio -o -c -R root:root | gzip -9 > /usr/lib64/guestfs/initramfs.x86_64.img
```

Команды для получения root.squashfs
```
eval "$(guestfish --listen -a /tmp/sisyphus.20211108.1.6.qcow2 --ro)"
guestfish --remote -- run
guestfish --remote -- findfs-label boot
guestfish --remote -- mount-ro /dev/sda1 /
guestfish --remote -- mksquashfs / /tmp/root.squashfs compress:zstd
```

Для ISO надо собрать initrd.img, в котором не будет ignition, так как при запуске с ISO доступ будет только на чтение, смонтировать на запись не получится. 

Кроме того, в initrd должно выполняться монтирование rootfs.img. Пример команд такого монтирования:
```
mkdir -p /run/media/iso
mount -t iso9660 -o ro /dev/disk/by-label/ALTCOS /run/media/iso
mount -t squashfs -o loop,offset=124 /run/media/iso/images/pxeboot/rootfs.img /sysroot
```

Ядру должен передаваться параметр ostree для ostree-prepare-root.service.


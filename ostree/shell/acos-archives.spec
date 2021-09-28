Name: acos-archives
Version: 0.1
Release: alt1

Summary: Archives to install ACOS
License: GPL-3.0-or-later
Group: System/Base

%description
Archives to install ALT Container OS

%install
mkdir -p %buildroot%_datadir/acos/
install -m444 ../SOURCES/acos_root.tar.xz %buildroot%_datadir/acos/
install -m444 ../SOURCES/var.tar.xz %buildroot%_datadir/acos/

%files
%_datadir/acos/acos_root.tar.xz
%_datadir/acos/var.tar.xz

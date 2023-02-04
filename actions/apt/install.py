import string

from actions import Action


class AptInstallAction(Action):
    _TMPL_CMD = string.Template("$bin_dir/apt-get_install.sh $ref $pkgs")


import string

from actions import Action


class AptDistUpgradeAction(Action):
    _TMPL_CMD = string.Template("$bin_dir/apt-get_dist-upgrade.sh $ref 'rpm_list_file'")


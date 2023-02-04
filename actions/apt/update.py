import string

from actions import Action


class AptUpdateAction(Action):
    _TMPL_CMD = string.Template("$bin_dir/apt-get_update.sh $ref")

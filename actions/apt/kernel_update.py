import string

from actions import Action


class KernelUpdate(Action):
    _TMPL_CMD = string.Template("$bin_dir/kernel_update.sh $ref")


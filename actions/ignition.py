import string

from actions import Action


class IgnitionAction(Action):
    _TMPL_CMD = string.Template("echo \"$yml\" | $bin_dir/ignition.sh 'ref_dir' 'root_dir'")

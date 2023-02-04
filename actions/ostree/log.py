import string

from actions import Action


class OSTreeLogAction(Action):
    _TMPL_CMD = string.Template("$bin_dir/ostree_log.sh '$ref'")

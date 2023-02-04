import string

from actions import Action


class OSTreePullLocalAction(Action):
    _TMPL_CMD = string.Template("$bin_dir/ostree_pull-local.sh 'ref'")

import string

from actions import Action


class ClearRootsAction(Action):
    _TMPL_CMD = string.Template("$bin_dir/clear_roots.sh '$ref'")

import string

from actions import Action


class OSTreeCommitAction(Action):
    _TMPL_CMD = string.Template("$bin_dir/ostree_commit.sh '$ref' '$commit_id' '$next_version'")

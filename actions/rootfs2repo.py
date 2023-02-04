import string

from actions import Action


class RootfsToRepoAction(Action):
    _TMPL_CMD = string.Template("sh -x $bin_dir/rootfs_to_repo.sh $ref")

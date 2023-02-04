import string

from actions import Action


class SkopeoCopyAction(Action):
    _TMPL_CMD = string.Template("$bin_dir/skopeo_copy.sh '$merge_dir' '$podman_images'")
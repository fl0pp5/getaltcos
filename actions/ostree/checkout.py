import string

from actions import Action


class OSTreeCheckoutAction(Action):
    _TMPL_CMD = string.Template("$bin_dir/ostree_checkout.sh '$ref' '$last_commit_id' '$to_ref' '$clear'")

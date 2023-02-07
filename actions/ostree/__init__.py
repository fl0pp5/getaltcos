import string

from actions import Action


class OSTreeCreateRefAction(Action):
    _TMPL_CMD = string.Template("sudo ostree refs --create=$ref $commit_id --repo=$repo_dir")


class OSTreeDeleteAction(Action):
    _TMPL_CMD = string.Template("sudo ostree refs --delete $ref --repo=$repo_dir")


class OSTreeInitAction(Action):
    _TMPL_CMD = string.Template(
        "sudo mkdir -p $repo_dir && sudo ostree init --mode=$repo_type --repo=$repo_dir")


class OSTreeGetMetadataAction(Action):
    _TMPL_CMD = string.Template("sudo ostree --repo=$repo_dir show $commit_id --print-metadata-key=$name")


class OSTreeGetRefsAction(Action):
    _TMPL_CMD = string.Template("ostree refs --repo=$repo_dir")

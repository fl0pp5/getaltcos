import string

from actions import Action


class SyncUpdatesAction(Action):
    _TMPL_CMD = string.Template("$bin_dir/syncUpdates.sh 'ref' 'commit_id' 'version'")

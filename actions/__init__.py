import string
import subprocess


class Action:
    _TMPL_CMD = string.Template("$cmd")

    def start(self, **kwargs):
        return subprocess.run(
            self._TMPL_CMD.substitute(kwargs),
            shell=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE
        )

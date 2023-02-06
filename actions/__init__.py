import string
import subprocess


class Action:
    _TMPL_CMD = string.Template("$cmd")

    @classmethod
    def do(cls, **kwargs):
        return subprocess.run(
            cls._TMPL_CMD.substitute(kwargs),
            shell=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE
        )

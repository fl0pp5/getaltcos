import os
import time

from shared import errors

DOCUMENT_ROOT = '.'


def get_arch_list(os_name: str = "altcos") -> list[str]:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L16
    """
    path = os.path.join(DOCUMENT_ROOT, f"ALTCOS/streams/{os_name}")
    return os.listdir(path)


def get_stream_list(os_name: str = "altcos", arch: str = "x86_64", mirror_streams: list[str] = None) -> list[str]:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L26
    """
    mirror_streams = mirror_streams or []
    arch_list = [arch] if arch else get_arch_list(os_name)
    stream_list = []

    for arch in arch_list:
        path = os.path.join(DOCUMENT_ROOT, f"ALTCOS/streams/{os_name}/{arch}")

        stream_list.extend(os.listdir(path))

    for ms in mirror_streams:
        path = ms.split('/')

        if path[0] == os_name and path[1] == arch:
            stream = '/'.join(path[2:])
            stream_list.append(stream)

    return stream_list


def get_repo_types(mirror_mode: bool = False) -> tuple[str, str, str]:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L57
    """
    if mirror_mode:
        return 'archive', 'bare', 'barearchive'
    return 'bare', 'archive', 'barearchive'


def is_base_ref(ref: str) -> bool:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L69
    """
    return len(ref.split('/')) == 3


def is_commit_id(commit_id: str) -> bool:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L74
    """
    return len(commit_id) == 64


def make_sub_ref(ref: str, sub_name: str) -> str:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L85
    """
    path = ref.split('/')
    path[-1] = path[-1].capitalize()
    path.append(sub_name)

    return '/'.join(path)


def make_parent_ref(ref: str) -> str:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L98
    """
    path = ref.split('/')
    path = path[:-1]

    return '/'.join(path).lower()


def ref_to_dir(ref: str) -> str:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L112
    """

    return ref.lower()


def ref_to_abs_dir(ref: str) -> str:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L120
    """
    return os.path.join(DOCUMENT_ROOT, "ALTCOS/streams", ref_to_dir(ref))


def dir_to_ref(ref: str) -> str:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L130
    """
    path = ref.split('/')
    ret = '/'.join(path[:2])

    for part in path[2:-1]:
        ret = os.path.join(ret, part.capitalize())

    return os.path.join(ret, path[-1])


def get_ref_repo_dir(ref: str) -> str:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L146
    """
    path = ref.split('/')[:3]
    path[2] = path[2].lower()
    return '/'.join(path)


def next_minor_version(version: str) -> str:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L156
    """
    stream, date, major, minor = version.split('.')
    minor = int(minor) + 1

    return f"{stream}.{date}.{major}.{minor}"


def version_var_sub_dir(version: str) -> str:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L168
    """
    path = version.lower().split('.')
    date, major, minor = path[1:]

    return f"{date}/{major}/{minor}"


def get_full_commit_id(ref_dir: str, short_commit_id: str) -> str:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L177
    """
    vars_path = os.path.join(DOCUMENT_ROOT, f"ALTCOS/streams/{ref_dir}/vars")

    vars_items = os.listdir(vars_path)

    commit_list = []

    for item in vars_items:
        file = os.path.join(vars_path, item)

        if not os.path.islink(file) or len(item) != 64 \
                or item[:len(short_commit_id)] != short_commit_id:
            continue

        commit_list.append(item)

    if len(commit_list) == 0:
        raise errors.CommitNotFound(f"{short_commit_id} не найден")

    if len(commit_list) > 1:
        raise errors.AmbiguousCommit(f"Коммит {short_commit_id} неоднозначен")

    return commit_list[0]


def get_last_commit_id(ref_dir: str) -> str:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L199
    """
    vars_path = os.path.join(DOCUMENT_ROOT, f"ALTCOS/streams/altcos/{ref_dir}/vars")

    vars_items = os.listdir(vars_path)

    commit_list = {}

    for item in vars_items:
        file = os.path.join(vars_path, item)

        if not os.path.islink(file) or len(item) != 64:
            continue

        mtime = os.stat(file).st_mtime
        commit_list[mtime] = item

    return sorted(commit_list.items())[-1][-1]


def ref_version(ref: str, commit_id: str = None) -> str:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L221
    """
    if not commit_id:
        date, major, minor = time.strftime("%Y%m%d"), 0, 0
    else:
        ref_dir = ref_to_dir(ref)
        full_commit_id = get_full_commit_id(ref, commit_id)
        vars_path = os.path.join(DOCUMENT_ROOT, f"ALTCOS/streams/{ref_dir}/vars")
        commit_link = os.path.join(vars_path, full_commit_id)
        link_target = os.readlink(commit_link)

        path = link_target.split('/')
        date, major, minor = path[:3]

    path = ref.lower().split('/')
    stream = '_'.join(path[2:])

    return f"{stream}.{date}.{major}.{minor}"


def full_rpm_name_to_short(name: str) -> str:
    """
    https://github.com/alt-cloud/getaltcos/blob/6fe4a273b8258189c0e75bcd3cbd306ee2eace2b/ALTCOS/class/repos.php#L245
    """
    path = name.split('-')
    return '-'.join(path[:len(path) - 2])


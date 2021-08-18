#!/bin/sh
set -x
exec 2>&1
ref=$1
repoBarePath="$DOCUMENT_ROOT/ACOS/streams/$ref/bare/repo";
ostree --repo=$repoBarePath log $ref



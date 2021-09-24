#!/bin/sh
set -x
exec 2>&1
export DOCUMENT_ROOT=$(realpath `dirname $0`'/../../')
ref=$1
repoBarePath="$DOCUMENT_ROOT/ACOS/streams/$ref/bare/repo";
ostree --repo=$repoBarePath log $ref



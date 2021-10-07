#!/bin/sh

cd butane
for btnFile in *.btn
do
  basename=`basename $btnFile .btn`
  ignFile="../ignition/$basename.ign"
  butane -p < $btnFile >$ignFile
done

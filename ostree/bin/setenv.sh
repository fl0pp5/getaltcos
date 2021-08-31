
export DOCUMENT_ROOT=/var/www/vhosts/getacos
set -- `ip r | grep default`
while [ $1 != 'src' ]; do shift; done
export UPDATEIP=$2


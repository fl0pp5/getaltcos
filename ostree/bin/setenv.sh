
export DOCUMENT_ROOT=/var/www/vhosts/getaltcos
set -- `ip r | grep default`
while [ $1 != 'src' ]; do shift; done
export UPDATEIP=$2


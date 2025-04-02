#!/bin/bash

set -eo pipefail

if [ -z "${MONGO_INITDB_ROOT_USERNAME}" ] && [[ "$*" != "--auth" ]] ; then
    mongoArgs="--replSet rs0"
else
    mongoArgs="--replSet rs0 --keyFile /mongo.keyfile"
fi

if [ "${1:0:1}" = '-' ]; then
	exec /usr/local/bin/docker-entrypoint.sh $@ ${mongoArgs}
elif [ "$1" == "mongod" ] ; then
    exec /usr/local/bin/docker-entrypoint.sh ${@:2} ${mongoArgs}
else
    exec /usr/local/bin/docker-entrypoint.sh ${@}
fi

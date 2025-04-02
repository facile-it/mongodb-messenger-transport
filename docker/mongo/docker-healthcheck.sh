#!/bin/bash

set -eo pipefail

if [ -z "${MONGO_INITDB_ROOT_USERNAME}" ] || [ -z "${MONGO_INITDB_ROOT_PASSWORD}" ]
then
initalize_mongo_rs=$(echo "rs.initiate({_id: \"rs0\", version: 1, members: [{ _id: 0, host : \"127.0.0.1:27017\" }]}).ok || rs.status().ok" | mongo --quiet)
else
initalize_mongo_rs=$(echo "rs.initiate({_id: \"rs0\", version: 1, members: [{ _id: 0, host : \"127.0.0.1:27017\" }]}).ok || rs.status().ok" | mongo --port 27017 -u "${MONGO_INITDB_ROOT_USERNAME}" -p "${MONGO_INITDB_ROOT_PASSWORD}" --quiet)
fi

test $initalize_mongo_rs -eq 1

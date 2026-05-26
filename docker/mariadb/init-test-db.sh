#!/bin/bash
# Creates the test database and grants the app user full access.
# Runs once on fresh volume creation (docker-entrypoint-initdb.d convention).
MYSQL_PWD="${MARIADB_ROOT_PASSWORD}" mariadb -u root <<-EOSQL
    CREATE DATABASE IF NOT EXISTS \`${MARIADB_DATABASE}_test\`;
    GRANT ALL PRIVILEGES ON \`${MARIADB_DATABASE}_test\`.* TO '${MARIADB_USER}'@'%';
    FLUSH PRIVILEGES;
EOSQL

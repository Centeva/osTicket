#!/bin/sh

printenv | grep -E 'ADMIN_EMAIL|DBHOST|DBNAME|DBPASS|DBTYPE|DBUSER|SALT' > /etc/environment
service cron start
apache2-foreground

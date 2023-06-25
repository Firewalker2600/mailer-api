#!/bin/bash

if [ `ps -auxe | grep "bin/console mailer:dispatch" | wc -l` -eq 1 ]
then
	`cd ../../ && /usr/local/bin/php bin/console mailer:dispatch > /dev/null 2>&1`
fi

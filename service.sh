#!/bin/bash
while true
do
	php run_calllog.php
	echo "Call log/recording download service stopped."
	sleep 3
	echo "Restarting service..."
done

#!/bin/bash
echo $Green"STOPPING SERVER..."$End
cd laradock/
docker-compose down -v
echo $Green
echo "*******************************"
echo "************* COMPLETED *************"
echo "*******************************"$End

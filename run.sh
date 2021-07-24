#!/bin/bash
echo
echo "*******************************"
echo "********* NEPSE TRADING ANALYSIS *********"
echo "*******************************"$End
echo
echo $Green"Building System..."$End
cd laradock/
docker-compose down -v
docker-compose up -d
docker-compose exec workspace npm run watch

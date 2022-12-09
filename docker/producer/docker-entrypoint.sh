#!/usr/bin/env bash

echo "-->> #1 Update composer"
composer update

echo "-->> #2 Start service"
php producer.php
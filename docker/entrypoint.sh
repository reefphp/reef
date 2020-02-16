#!/bin/sh

set -e

echo 'Run composer install'
composer install

echo 'Start apache'
exec apache2-foreground

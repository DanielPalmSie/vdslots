#!/bin/sh

composer update

./console mig:migrate

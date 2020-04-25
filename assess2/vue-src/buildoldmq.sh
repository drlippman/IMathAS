#!/bin/bash

rm ../../javascript/MQbundle_min.js

declare -a filename
filename[0]=javascript/mathquill
filename[1]=javascript/mathquilled
filename[2]=mathquill/AMtoMQ

for name in ${filename[@]}; do
  echo Minifying ${name}
  ./node_modules/.bin/uglifyjs --mangle --compress hoist_vars=true \
    ../../${name}.js >> ../../javascript/MQbundle_min.js
done

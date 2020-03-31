#!/bin/bash

rm ../../javascript/assessment_min.js

declare -a filename
filename[0]=javascript/general
filename[1]=javascript/mathjs
filename[2]=javascript/AMhelpers
filename[3]=javascript/confirmsubmit
filename[4]=javascript/drawing
filename[5]=javascript/eqntips

for name in ${filename[@]}; do
  echo Minifying ${name}
  ./node_modules/.bin/uglifyjs --mangle --compress hoist_vars=true \
    ../../${name}.js >> ../../javascript/assessment_min.js
done

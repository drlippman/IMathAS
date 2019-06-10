#!/bin/bash

declare -a filename
filename[0]=javascript/drawing
filename[1]=javascript/AMhelpers2
filename[2]=javascript/eqntips
filename[3]=javascript/mathjs
filename[4]=javascript/ASCIIMathML
filename[5]=javascript/ASCIIsvg
filename[6]=javascript/ASCIIMathTeXImg
filename[7]=javascript/rubric
filename[8]=mathquill/AMtoMQ
filename[9]=mathquill/mqeditor
filename[10]=mathquill/mqedlayout

for name in ${filename[@]}; do
  echo Minifying ${name}
  ./node_modules/.bin/uglifyjs --mangle --compress hoist_vars=true \
    ../../${name}.js > ../../${name}_min.js
done

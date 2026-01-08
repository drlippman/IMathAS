#!/bin/bash

declare -a filename
filename[0]=javascript/drawing
filename[1]=javascript/AMhelpers2
filename[2]=javascript/eqntips
filename[3]=mathquill/AMtoMQ
filename[4]=mathquill/mqeditor
filename[5]=mathquill/mqedlayout
filename[6]=javascript/mathparser
filename[7]=javascript/ASCIIMathML
filename[8]=javascript/ASCIIsvg
filename[9]=javascript/ASCIIMathTeXImg
filename[10]=javascript/rubric

for name in ${filename[@]}; do
  echo Minifying ${name}
  ./node_modules/.bin/terser ../../${name}.js --mangle --compress --output ../../${name}_min.js
done

rm ../../javascript/assess2_min.js
for i in {0..5}; do
  echo "adding ${filename[$i]} to assess2_min.js";
  cat ../../${filename[$i]}_min.js >> ../../javascript/assess2_min.js
done

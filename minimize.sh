uglifyjs ../../../../javascript/drawing.js -o ../../../../javascript/drawing_min.js -m -c hoist_vars=true & uglifyjs ../../../../javascript/AMhelpers2.js -o ../../../../javascript/AMhelpers2_min.js -m -c hoist_vars=true &
uglifyjs ../../../../javascript/eqntips.js -o ../../../../javascript/eqntips_min.js -m -c hoist_vars=true & uglifyjs ../../../../javascript/mathjs.js -o ../../../../javascript/mathjs_min.js -m -c hoist_vars=true &
uglifyjs ../../../../mathquill/AMtoMQ.js -o ../../../../mathquill/AMtoMQ_min.js -m -c hoist_vars=true & uglifyjs ../../../../mathquill/mqeditor.js -o ../../../../mathquill/mqeditor_min.js -m -c hoist_vars=true &
uglifyjs ../../../../mathquill/mqedlayout.js -o ../../../../mathquill/mqedlayout_min.js -m -c hoist_vars=true & uglifyjs ../../../../javascript/ASCIIMathML.js -o ../../../../javascript/ASCIIMathML_min.js -m -c hoist_vars=true & 
uglifyjs ../../../../javascript/ASCIIsvg.js -o ../../../../javascript/ASCIIsvg_min.js -m -c hoist_vars=true & uglifyjs ../../../../javascript/ASCIIMathTeXImg.js -o ../../../../javascript/ASCIIMathTeXImg_min.js -m -c hoist_vars=true & 
uglifyjs ../../../../javascript/rubric.js -o ../../../../javascript/rubric_min.js -m -c hoist_vars=true

del /f C:\Users\toy\Sync\IMathAS\javascript\assess2_min.js &
copy C:\Users\toy\Sync\IMathAS\javascript\drawing_min.js+C:\Users\toy\Sync\IMathAS\javascript\AMhelpers2_min.js+C:\Users\toy\Sync\IMathAS\javascript\eqntips_min.js+C:\Users\toy\Sync\IMathAS\javascript\mathjs_min.js+C:\Users\toy\Sync\IMathAS\mathquill\AMtoMQ_min.js+C:\Users\toy\Sync\IMathAS\mathquill\mqeditor_min.js+C:\Users\toy\Sync\IMathAS\mathquill\mqedlayout_min.js C:\Users\toy\Sync\IMathAS\javascript\assess2_min.js

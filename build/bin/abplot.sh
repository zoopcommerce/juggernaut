#!/bin/bash

plot_file="abplot.p"

title=${1};
data_file=${2};
output_file=${3}

cat > ${plot_file} <<EOF
# output as png image
set size 1, 1
set terminal png size 1280,720
set output "${output_file}"

# graph title
set title "${title}"

set key left top
# Draw gridlines oriented on the y axis
set grid y
# Specify that the x-series data is time data
set xdata time
# Specify the *input* format of the time data
set timefmt "%s"
# Specify the *output* format for the x-axis tick labels
set format x "%S"
# Label the x-axis
set xlabel 'seconds'
# Label the y-axis
set ylabel "response time (ms)"
# Tell gnuplot to use tabs as the delimiter instead of spaces (default)
set datafile separator '\t'

plot "${data_file}" every ::2 using 2:5 title "${title}" with points
EOF

gnuplot ${plot_file}
rm -rf ${plot_file}

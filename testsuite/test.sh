#!/bin/bash
echo "cleaning up"
rm -rf reference/*
rm -rf target/*
rm reference.zip
rm target.zip
rm -rf preparsed_reference/*
rm -rf preparsed_target/*
rm -rf result_snelslim/*
rm -rf result_mclm/*
echo "building corpus generator"
go build gen_corpus.go
echo "generating corpora"
./gen_corpus
zip -r reference.zip reference
zip -r target.zip target

echo "running snelSLiM preparser"
mkdir extract
../bin/preparser target.zip extract/ plain - - preparsed_target/
mkdir extract
../bin/preparser reference.zip extract/ plain - - preparsed_reference/

echo "running snelSLiM analyser"
../bin/analyser preparsed_target/ preparsed_reference/ 5000 3.841459 result_snelslim/ 120

echo "running mclm R script"
R --vanilla < slma.R

echo "building result compare tool"
go build compare_results.go
echo "comparing results"
./compare_results
echo "test done"
echo "results are available in the results folders"

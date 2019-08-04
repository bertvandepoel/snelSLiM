#!/bin/bash
find formats -type f -name "parser" -delete
rm -f formats/autodetect
rm -f slm/preparser
rm -f slm/analyser
find formats -type d ! -path formats -exec go build -o {}/parser {}/parser.go \;
go build -o formats/autodetect formats/autodetect.go
go build -o slm/preparser slm/preparser.go
go build -o slm/analyser slm/analyser.go

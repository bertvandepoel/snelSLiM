#!/bin/bash
rm -rf bin/*
cd src
find formats -type d ! -path formats -exec go build -o ../bin/{}/parser {}/parser.go \;
cd ..
cp src/formats/FoLiA/foliafolie bin/formats/FoLiA/foliafolie
go build -o bin/formats/autodetect src/formats/autodetect.go
go build -o bin/preparser src/preparser.go
go build -o bin/analyser src/analyser.go

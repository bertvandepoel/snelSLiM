package main

import (
	"bufio"
	"fmt"
	"io"
	"math/rand"
	"os"
	"path/filepath"
	"strings"
	"time"
)

func main() {
	outdir := os.Args[1]

	files := []string{}
	filepath.Walk(outdir, func(path string, info os.FileInfo, err error) error {
		if !info.IsDir() {
			abs, err := filepath.Abs(path)
			if err != nil {
				fmt.Println("Could not convert relative to absolute path")
				panic(err)
			}
			files = append(files, abs)
		}
		return nil
	})

	rand.Seed(time.Now().UnixNano())
	format := "unset"
	for i := 1; i <= 5; i++ {
		file := files[rand.Intn(len(files)-1)]
		handle, err := os.Open(file)
		if err != nil {
			fmt.Println("Could not open file handle")
			panic(err)
		}
		reader := bufio.NewReader(handle)
		var firstlines string
		for j := 1; j <= 5; j++ {
			line, err := reader.ReadString('\n')
			if err == io.EOF {
				break
			}
			firstlines += line
		}

		detected := "unknown"
		if strings.Contains(firstlines, "<graph xmlns=\"http://www.xces.org/ns/GrAF/") {
			detected = "oanc"
		} else if strings.Contains(firstlines, "<cesAna") {
			detected = "masc"
		} else if strings.Contains(firstlines, "<alpino_ds") {
			detected = "alpino"
		} else if strings.Contains(firstlines, "<teiHeader>") {
			detected = "bnc"
		} else if strings.Contains(firstlines, "<DCOI") {
			detected = "dcoi"
		} else if strings.Contains(firstlines, "<FoLiA") {
			detected = "folia"
		} else if strings.Contains(firstlines, "<bron_afk>") {
			detected = "gysseling"
		} else if strings.HasPrefix(firstlines, "[ <") {
			detected = "eindhoven"
		} else if strings.HasPrefix(firstlines, "<?xml") {
			detected = "xml"
		} else if strings.Count(firstlines, "\t") > 5 {
			detected = "tabs"
		}

		if detected == "unknown" && format == "unset" {
			format = "unknown"
		} else if detected == "unknown" && format == "unknown" {
			format = "unknown"
		} else if detected == "unknown" && format != "unknown" { // file was not recognised, this is not the first test, and the previous test(s) were succesfull
			format = "partknown"
			break
		} else if format == "unset" {
			format = detected
		} else if format == "unknown" {
			format = "partknown"
			break
		} else if format != detected {
			format = "mixed"
		}
	}

	fmt.Print(format)
}

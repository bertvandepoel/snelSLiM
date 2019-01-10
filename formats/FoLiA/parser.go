package main

import (
	"bytes"
	"fmt"
	"html"
	"io/ioutil"
	"os"
	"os/exec"
	"path/filepath"
	"strconv"
	"strings"
)

var lemma bool
var folie bool
var output []byte
var err error

func main() {
	filename := os.Args[1]
	filtertype := os.Args[2]
	if filtertype == "lemma" {
		lemma = true
	} else if filtertype == "text" {
		lemma = false
	} else {
		fmt.Println("Please define whether to filter lemmas or text elements as the second argument")
		panic("Please define whether to filter lemmas or text elements as the second argument")
	}
	method := os.Args[3]
	if method == "fast" {
		folie = true
	} else if method == "xpath" {
		folie = false
	} else {
		fmt.Println("Please define whether to use fast or xpath method as the third argument")
		panic("Please define whether to use fast or xpath method as the third argument")
	}
	outfilename := os.Args[4]

	count := make(map[string]int)

	if folie {
		currentexec, err := os.Executable()
		if err != nil {
			fmt.Println("Could not get current location")
			panic(err)
		}
		currentlocation := filepath.Dir(currentexec)
		cmd := exec.Command("./foliafolie", filename)
		cmd.Dir = currentlocation + "/"
		output, err = cmd.Output()
		if err != nil {
			fmt.Println("There was a problem executing foliafolie")
			panic(err)
		}
		datastring := string(output)
		rows := strings.Split(datastring, "\n")
		for _, row := range rows {
			// select every w class WORD* (including WORD-WITHSUFFIX, WORD-COMPOUNT, WORD-TOKEN, WORD-PARPREFIX and WORD-PARSUFFIX
			if strings.HasPrefix(row, "WORD") {
				fields := strings.Split(row, " ")
				for key, field := range fields {
					if key == 0 {
						continue
					}
					if lemma {
						if !strings.HasPrefix(field, "-t:") {
							count[strings.ToLower(html.UnescapeString(field))]++
							break
						}
					} else {
						if strings.HasPrefix(field, "-t:") {
							value := strings.Split(field, ":")
							count[strings.ToLower(html.UnescapeString(value[1]))]++
						}
					}
				}
			}
		}
	} else {
		var cmd string
		if lemma {
			cmd = "/bin/sed -e \"s/xmlns=/ignore=/\" \"" + filename + "\" | /usr/bin/xmllint --xpath '//s/w[@class=\"WORD\"]/lemma/@class' - "
		} else {
			cmd = "/bin/sed -e \"s/xmlns=/ignore=/\" \"" + filename + "\" | /usr/bin/xmllint --xpath '//s/w[@class=\"WORD\"]/t' - "
		}
		output, err = exec.Command("/bin/bash", "-c", cmd).Output()
		if err != nil {
			fmt.Println("There was a problem executing xmllint")
			panic(err)
		}
		datastring := string(output)
		if lemma {
			rows := strings.Split(datastring, "class=\"")
			for _, row := range rows {
				if row != "" && row != " " {
					value := strings.Split(row, "\"")
					count[strings.ToLower(html.UnescapeString(value[0]))]++
				}
			}
		} else {
			// split on close because open might have offset
			rows := strings.Split(datastring, "</t>")
			for _, row := range rows {
				if row != "" && row != " " {
					value := strings.Split(row, ">")
					count[strings.ToLower(html.UnescapeString(value[1]))]++
				}
			}
		}
	}

	var result bytes.Buffer
	filetotal := 0

	for key, value := range count {
		result.WriteString(key)
		result.WriteString("\t")
		valuestring := strconv.Itoa(value)
		result.WriteString(valuestring)
		result.WriteString("\n")
		filetotal += value
	}
	result.WriteString("total.snelslim")
	result.WriteString("\t")
	valuestring := strconv.Itoa(filetotal)
	result.WriteString(valuestring)
	result.WriteString("\n")

	err = ioutil.WriteFile(outfilename, result.Bytes(), 0644)
	if err != nil {
		fmt.Println("Could not write result")
		panic(err)
	}
	fmt.Println("OK")
}

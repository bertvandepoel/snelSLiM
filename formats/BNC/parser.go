package main

import (
	"bytes"
	"fmt"
	"html"
	"io/ioutil"
	"os"
	"os/exec"
	"strconv"
	"strings"
)

var lemma bool

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
	outfilename := os.Args[3]

	count := make(map[string]int)

	var cmd string
	if lemma {
		cmd = "/bin/sed -e \"s/xmlns=/ignore=/\" \"" + filename + "\" | /usr/bin/xmllint --xpath '//s/w/@hw' - "
	} else {
		cmd = "/bin/sed -e \"s/xmlns=/ignore=/\" \"" + filename + "\" | /usr/bin/xmllint --xpath '//s/w' - "
	}
	output, err := exec.Command("/bin/bash", "-c", cmd).Output()
	if err != nil {
		fmt.Println("There was a problem executing xmllint")
		panic(err)
	}
	datastring := string(output)
	if lemma {
		rows := strings.Split(datastring, "hw=\"")
		for _, row := range rows {
			if row != "" && row != " " {
				value := strings.Split(row, "\"")
				count[strings.ToLower(value[0])]++
			}
		}
	} else {
		// split on close because open might have attributes
		rows := strings.Split(datastring, "</w>")
		for _, row := range rows {
			if row != "" && row != " " {
				value := strings.Split(row, ">")
				count[strings.ToLower(value[1])]++
			}
		}
	}

	var result bytes.Buffer

	for key, value := range count {
		result.WriteString(html.UnescapeString(key))
		result.WriteString("\t")
		valuestring := strconv.Itoa(value)
		result.WriteString(valuestring)
		result.WriteString("\n")
	}

	err = ioutil.WriteFile(outfilename, result.Bytes(), 0644)
	if err != nil {
		fmt.Println("Could not write result")
		panic(err)
	}
	fmt.Println("OK")
}

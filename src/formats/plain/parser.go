package main

import (
	"bytes"
	"fmt"
	"io/ioutil"
	"os"
	"strconv"
	"strings"
)

func main() {
	filename := os.Args[1]
	striptagsarg := os.Args[2]
	striptags := false
	if striptagsarg != "-" && striptagsarg != "0" {
		striptags = true
	}
	outfilename := os.Args[3]
	outplainwords := os.Args[4]
	plainwords := false
	if outplainwords != "-" {
		plainwords = true
	}

	data, err := ioutil.ReadFile(filename)
	if err != nil {
		fmt.Println("Could not read file")
		panic(err)
	}

	datastring := string(data)
	if striptags && strings.Contains(datastring, "<") {
		lefttagsplit := strings.Split(datastring, "<")
		for index, leftfield := range lefttagsplit {
			if index == 0 {
				datastring = leftfield
				continue
			}
			if strings.Contains(leftfield, ">") {
				righttagsplit := strings.SplitN(leftfield, ">", 2)
				datastring += righttagsplit[1]
			} else {
				datastring += "<" + leftfield
			}
		}
	}
	datastring = strings.Replace(datastring, "\n", " ", -1)
	datastring = strings.Replace(datastring, "\r", " ", -1)
	datastring = strings.Replace(datastring, "\t", " ", -1)
	datastring = strings.Replace(datastring, ".", " ", -1)
	datastring = strings.Replace(datastring, ",", " ", -1)
	datastring = strings.Replace(datastring, "?", " ", -1)
	datastring = strings.Replace(datastring, "!", " ", -1)
	datastring = strings.Replace(datastring, ":", " ", -1)
	datastring = strings.Replace(datastring, ";", " ", -1)
	datastring = strings.Replace(datastring, "(", " ", -1)
	datastring = strings.Replace(datastring, ")", " ", -1)
	datastring = strings.Replace(datastring, "\"", " ", -1)
	datastring = strings.Replace(datastring, "'", " ", -1)
	datastring = strings.Replace(datastring, "/", " ", -1)
	datastring = strings.Replace(datastring, "\\", " ", -1)
	datastring = strings.Replace(datastring, "|", " ", -1)
	datastring = strings.Replace(datastring, "_", " ", -1)
	datastring = strings.Replace(datastring, "*", " ", -1)
	datastring = strings.Replace(datastring, "&", " ", -1)
	datastring = strings.Replace(datastring, "+", " ", -1)
	datastring = strings.Replace(datastring, "=", " ", -1)
	datastring = strings.Replace(datastring, "[", " ", -1)
	datastring = strings.Replace(datastring, "]", " ", -1)
	datastring = strings.Replace(datastring, "{", " ", -1)
	datastring = strings.Replace(datastring, "}", " ", -1)
	datastring = strings.Replace(datastring, "<", " ", -1)
	datastring = strings.Replace(datastring, ">", " ", -1)

	fields := strings.Split(datastring, " ")
	count := make(map[string]int)
	plainwordsstring := ""

	for _, field := range fields {
		if field != "" && field != "-" && field != "--" && field != "---" {
			count[strings.ToLower(field)]++
			if plainwords {
				plainwordsstring += strings.ToLower(field) + "\t"
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

	if plainwords {
		err = ioutil.WriteFile(outplainwords, []byte(plainwordsstring), 0644)
		if err != nil {
			fmt.Println("Could not write plainwords for collocational analysis")
			panic(err)
		}
	}

	fmt.Println(filetotal)
	fmt.Println("OK")
}

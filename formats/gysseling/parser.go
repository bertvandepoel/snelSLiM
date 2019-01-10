package main

import (
	"bytes"
	"fmt"
	"io/ioutil"
	"os"
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

	data, err := ioutil.ReadFile(filename)
	if err != nil {
		fmt.Println("Could not read file")
		panic(err)
	}

	datastring := string(data)
	count := make(map[string]int)

	rows := strings.Split(datastring, "\n")
	for _, row := range rows {
		if strings.Contains(row, "<C ") {
			fields := strings.Split(row, "<C")
			for _, field := range fields {
				if strings.Contains(field, "_") {
					// only split into 2 items, to accomodate for <A>-tags with unabbreviations
					items := strings.SplitN(field, ">", 2)
					if lemma {
						subs := strings.Split(items[0], "_")
						count[strings.ToLower(subs[1])]++
					} else {
						text := strings.Trim(strings.ToLower(items[1]), " ")
						count[text]++
					}
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

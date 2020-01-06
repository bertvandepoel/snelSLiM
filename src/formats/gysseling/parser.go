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
	plainwordsstring := ""
	count := make(map[string]int)

	rows := strings.Split(datastring, "\n")
	for _, row := range rows {
		if strings.Contains(row, "<C ") {
			fields := strings.Split(row, "<C")
			for i, field := range fields {
				if i == 0 {
					// don't process information before the first <C
					continue
				}
				if strings.Contains(field, "_") {
					// only split into 2 items, to accomodate for <A>-tags with unabbreviations
					items := strings.SplitN(field, ">", 2)
					if lemma {
						subs := strings.Split(items[0], "_")
						count[strings.ToLower(subs[1])]++
						if plainwords {
							plainwordsstring += strings.ToLower(subs[1]) + "\t"
						}
					} else {
						qfree := strings.Split(items[1], "<q")
						vnfree := strings.Split(qfree[0], "<VN")
						text := strings.Trim(strings.ToLower(vnfree[0]), " ")
						count[text]++
						if plainwords {
							plainwordsstring += text + "\t"
						}
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

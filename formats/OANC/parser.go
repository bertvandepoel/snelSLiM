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
	outfilename := os.Args[2]
	outplainwords := os.Args[3]
	plainwords := false
	if outplainwords != "-" {
		plainwords = true
	}

	count := make(map[string]int)

	cmd := "/bin/sed -e \"s/xmlns=/ignore=/\" \"" + filename + "\" | /usr/bin/xmllint --xpath '//struct/feat[@name=\"base\"]/@value' - "
	output, err := exec.Command("/bin/bash", "-c", cmd).Output()
	if err != nil {
		fmt.Println("There was a problem executing xmllint")
		panic(err)
	}
	datastring := string(output)
	plainwordsstring := ""
	rows := strings.Split(datastring, "value=\"")
	for _, row := range rows {
		if row != "" && row != " " {
			value := strings.Split(row, "\"")
			count[strings.ToLower(html.UnescapeString(value[0]))]++
			if plainwords {
				plainwordsstring += strings.ToLower(html.UnescapeString(value[0])) + "\t"
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

	fmt.Println("OK")
}

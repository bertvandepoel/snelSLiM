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

func main() {
	filename := os.Args[1]
	xpath := os.Args[2]
	outfilename := os.Args[3]
	outplainwords := os.Args[4]
	plainwords := false
	if outplainwords != "-" {
		plainwords = true
	}

	count := make(map[string]int)

	cmd := "/bin/sed -e \"s/xmlns=/ignore=/\" \"" + filename + "\" | /usr/bin/xmllint --xpath '" + xpath + "' - "
	output, err := exec.Command("/bin/bash", "-c", cmd).Output()
	if err != nil {
		fmt.Println("There was a problem executing xmllint")
		panic(err)
	}

	datastring := string(output)

	component := strings.Split(xpath, "/")
	last := component[len(component)-1]
	plainwordsstring := ""

	if strings.Contains(last, "@") {
		attr := strings.Split(last, "@")
		rows := strings.Split(datastring, attr[1]+"=\"")
		for _, row := range rows {
			if row != "" && row != " " {
				value := strings.Split(row, "\"")
				count[strings.ToLower(html.UnescapeString(value[0]))]++
				if plainwords {
					plainwordsstring += strings.ToLower(html.UnescapeString(value[0])) + "\t"
				}
			}
		}
	} else {
		// split on close because open might have attributes
		rows := strings.Split(datastring, "</"+last+">")
		for _, row := range rows {
			if row != "" && row != " " {
				value := strings.Split(row, ">")
				count[strings.ToLower(html.UnescapeString(value[1]))]++
				if plainwords {
					plainwordsstring += strings.ToLower(html.UnescapeString(value[1])) + "\t"
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

	fmt.Println("OK")
}

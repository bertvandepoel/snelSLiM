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
	outplainwords := os.Args[4]
	plainwords := false
	if outplainwords != "-" {
		plainwords = true
	}

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
	plainwordsstring := ""
	if lemma {
		rows := strings.Split(datastring, "hw=\"")
		for _, row := range rows {
			if row != "" && row != " " {
				value := strings.Split(row, "\"")
				token := cleanToken(value[0])
				if len(token) < 1 {
					continue
				}
				count[token]++
				if plainwords {
					plainwordsstring += token + "\t"
				}
			}
		}
	} else {
		// split on close because open might have attributes
		rows := strings.Split(datastring, "</w>")
		for _, row := range rows {
			if row != "" && row != " " {
				value := strings.Split(row, ">")
				token := cleanToken(value[1])
				if len(token) < 1 {
					continue
				}
				count[token]++
				if plainwords {
					plainwordsstring += token + "\t"
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

func cleanToken(token string) string {
	var newtoken string
	newtoken = html.UnescapeString(token)                  //decode all HTML entities
	newtoken = strings.ToLower(newtoken)                   //make the string lower case
	newtoken = strings.Replace(newtoken, "\t", "    ", -1) //while tabs shouldn't feature in tokens, they sometimes do (for example as &#9;)
	newtoken = strings.Replace(newtoken, "\r\n", " ", -1)  //while newlines shouldn't feature in tokens, they sometimes do (for example as &#10;)
	newtoken = strings.Replace(newtoken, "\n", " ", -1)    //while newlines shouldn't feature in tokens, they sometimes do (for example as &#10;)
	newtoken = strings.Replace(newtoken, "\r", " ", -1)    //while newlines shouldn't feature in tokens, they sometimes do (for example as &#10;)
	newtoken = strings.Trim(newtoken, " ")                 //remove whitespace at the edges of the string
	return newtoken
}

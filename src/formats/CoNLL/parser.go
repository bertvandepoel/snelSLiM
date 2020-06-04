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
	colnum, err := strconv.Atoi(os.Args[2])
	if err != nil {
		fmt.Println("Please supply the column number as the second argument")
		panic(err)
	}
	outfilename := os.Args[3]
	outplainwords := os.Args[4]
	plainwords := false
	if outplainwords != "-" {
		plainwords = true
	}

	//regular users will start counting cols from 1
	colnum--

	data, err := ioutil.ReadFile(filename)
	if err != nil {
		fmt.Println("Could not read file")
		panic(err)
	}

	datastring := string(data)
	rows := strings.Split(datastring, "\n")

	count := make(map[string]int)
	plainwordsstring := ""

	for _, row := range rows {
		//ignore XML lines
		if !strings.HasPrefix(row, "<") && !strings.HasPrefix(row, "#") && row != "" && row != " " {
			fields := strings.Split(row, "\t")
			if fields[colnum] != "." && fields[colnum] != "..." && fields[colnum] != "?" && fields[colnum] != "!" && fields[colnum] != ":" &&
				fields[colnum] != ";" && fields[colnum] != "_" && fields[colnum] != "," && fields[colnum] != "?!" && fields[colnum] != "!?" &&
				fields[colnum] != "???" && fields[colnum] != "!!!" && fields[colnum] != "!!" && fields[colnum] != "??" && fields[colnum] != "&" &&
				fields[colnum] != "(" && fields[colnum] != ")" && fields[colnum] != "*" && fields[colnum] != "'" && fields[colnum] != "\"" &&
				fields[colnum] != "\\" && fields[colnum] != "/" && fields[colnum] != "|" && fields[colnum] != "+" && fields[colnum] != "=" &&
				fields[colnum] != "[" && fields[colnum] != "]" && fields[colnum] != "{" && fields[colnum] != "}" && fields[colnum] != "<" &&
				fields[colnum] != ">" && fields[colnum] != "-" {
				token := strings.Trim(strings.ToLower(fields[colnum]), " ")
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

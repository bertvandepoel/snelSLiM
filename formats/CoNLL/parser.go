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

	for _, row := range rows {
		//ignore XML lines
		if !strings.HasPrefix(row, "<") && row != "" {
			fields := strings.Split(row, "\t")
			if fields[colnum] != "." && fields[colnum] != "..." && fields[colnum] != "?" && fields[colnum] != "!" && fields[colnum] != ":" &&
				fields[colnum] != ";" && fields[colnum] != "_" && fields[colnum] != "," && fields[colnum] != "?!" && fields[colnum] != "!?" &&
				fields[colnum] != "???" && fields[colnum] != "!!!" && fields[colnum] != "!!" && fields[colnum] != "??" && fields[colnum] != "&" &&
				fields[colnum] != "(" && fields[colnum] != ")" && fields[colnum] != "*" && fields[colnum] != "'" && fields[colnum] != "\"" &&
				fields[colnum] != "\\" && fields[colnum] != "/" && fields[colnum] != "|" && fields[colnum] != "+" && fields[colnum] != "=" &&
				fields[colnum] != "[" && fields[colnum] != "]" && fields[colnum] != "{" && fields[colnum] != "}" && fields[colnum] != "<" &&
				fields[colnum] != ">" && fields[colnum] != "-" {
				count[strings.ToLower(fields[colnum])]++
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

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
	outdir := os.Args[2]

	data, err := ioutil.ReadFile(filename)
	if err != nil {
		fmt.Println("Could not read file")
		panic(err)
	}

	datastring := string(data)

	fragments := strings.Split(datastring, "]")

	for _, fragment := range fragments {
		if fragment == "" || fragment == "\n" || fragment == "\n\n" {
			continue
		}
		var outfilename string
		count := make(map[string]int)
		rows := strings.Split(fragment, "\n")
		for _, row := range rows {
			if strings.HasPrefix(row, "[") {
				fields := strings.Split(row, "<")
				field := strings.Split(fields[1], ">")
				if strings.Contains(field[0], "/") {
					field[0] = strings.Replace(field[0], "/", "_", -1)
				}
				outfilename = outdir + "/" + field[0] + ".snelslim"
			} else {
				fields := strings.Split(row, " ")
				for _, field := range fields {
					if strings.Contains(field, "_") {
						subs := strings.Split(field, "_")
						count[strings.ToLower(subs[0])]++
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
	}
	fmt.Println("OK")
}

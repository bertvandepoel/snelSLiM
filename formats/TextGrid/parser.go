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
	outfilename := os.Args[2]
	outplainwords := os.Args[3]
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

	lines := strings.Split(datastring, "\n")

	// This file relies on the documentation provided by the PRAAT team at http://www.fon.hum.uva.nl/praat/manual/TextGrid_file_formats.html
	// use currentline so in case it's possible to have empty lines or other data before the ooTextFile header, a loop can be easily inserted
	// HasPrefix is used throughout this file to make sure there's no failure because of whitespace or \r at the end of lines
	currentline := 0
	var textgridshort bool
	if strings.HasPrefix(lines[currentline], "File type = \"ooTextFile short\"") {
		textgridshort = true
	} else if strings.HasPrefix(lines[currentline], "File type = \"ooTextFile\"") {
		textgridshort = false
	} else {
		panic("Error: this file does not seem to be TextGrid")
	}
	currentline += 5
	if textgridshort {
		if !strings.HasPrefix(lines[currentline], "<exists>") {
			panic("Error: there are no tiers in this TextGrid file")
		}
	} else {
		if !strings.HasPrefix(lines[currentline], "tiers? <exists>") {
			panic("Error: there are no tiers in this TextGrid file")
		}
	}
	currentline++
	var numtiers int
	if textgridshort {
		numtiers, err = strconv.Atoi(trim_whitespace(lines[currentline]))
		if err != nil {
			fmt.Println("Error: could not cast numbers of tiers to an integer")
			panic(err)
		}
	} else {
		split := strings.Split(lines[currentline], "size = ")
		numtiers, err = strconv.Atoi(trim_whitespace(split[1]))
		if err != nil {
			fmt.Println("Error: could not cast numbers of tiers to an integer")
			panic(err)
		}
	}
	if textgridshort {
		currentline++
	} else {
		currentline += 3 // more structure between "size = ..." and "class = ..."
	}

	count := make(map[string]int)
	plainwordsstring := ""

	for i := 0; i < numtiers; i++ {
		if textgridshort {
			if strings.HasPrefix(lines[currentline], "TextTier") {
				// only IntervalTiers contain actual transcriptions, TextTiers contain information such as prompt bells or contextual sounds
				currentline += 4
				texttiersize, err := strconv.Atoi(trim_whitespace(lines[currentline]))
				if err != nil {
					fmt.Println("Error: could not tier size to an integer")
					panic(err)
				}
				currentline += texttiersize * 2
				currentline++
				continue
			} else { // we can presume this is an IntervalTier
				currentline += 4
				intervaltiersize, err := strconv.Atoi(trim_whitespace(lines[currentline]))
				if err != nil {
					fmt.Println("Error: could not tier size to an integer")
					panic(err)
				}
				for j := 0; j < intervaltiersize; j++ {
					currentline += 3
					textline := trim_outerquotes(lines[currentline])
					fields := strings.Split(textline, " ")
					for _, field := range fields {
						if field != "" {
							count[field]++
							if plainwords {
								plainwordsstring += field + "\t"
							}
						}
					}
				}
				currentline++
			}
		} else {
			if strings.Contains(lines[currentline], "class = \"TextTier\"") {
				currentline += 4
				split := strings.Split(lines[currentline], "size = ")
				texttiersize, err := strconv.Atoi(trim_whitespace(split[1]))
				if err != nil {
					fmt.Println("Error: could not tier size to an integer")
					panic(err)
				}
				currentline += texttiersize * 3
				currentline += 2 // in the non-short form, we also need to skip the "item [x]:" marker listed before the next interval classification
			} else { // we can presume this is an IntervalTier
				currentline += 4
				split := strings.Split(lines[currentline], "size = ")
				intervaltiersize, err := strconv.Atoi(trim_whitespace(split[1]))
				if err != nil {
					fmt.Println("Error: could not tier size to an integer")
					panic(err)
				}
				for j := 0; j < intervaltiersize; j++ {
					currentline += 4
					textline := trim_whitespace(lines[currentline])
					textline = strings.TrimPrefix(textline, "text = ")
					textline = trim_outerquotes(textline)
					fields := strings.Split(textline, " ")
					for _, field := range fields {
						if field != "" {
							count[field]++
							if plainwords {
								plainwordsstring += field + "\t"
							}
						}
					}
				}
				currentline += 2 // in the non-short form, we also need to skip the "item [x]:" marker listed before the next interval classification
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

func trim_whitespace(text string) string {
	// this is a function so it's easier to support trimming comments in the future (if ever necessary)
	return strings.Trim(text, " \r\t")
}

func trim_outerquotes(text string) string {
	text = trim_whitespace(text)
	text = strings.TrimPrefix(text, "\"")
	text = strings.TrimSuffix(text, "\"")
	return text
}

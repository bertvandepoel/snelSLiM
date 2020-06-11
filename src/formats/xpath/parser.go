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
	"unicode"
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

	cmd_bash := exec.Command("/bin/bash", "-c", "/usr/bin/xmllint --shell <(/bin/sed -e \"s/xmlns=/ignore=/\" \""+filename+"\")")
	cmd_echo := exec.Command("/bin/echo", "cat "+xpath)
	cmd_bash.Stdin, _ = cmd_echo.StdoutPipe()
	output := new(bytes.Buffer)
	cmd_bash.Stdout = output
	err := cmd_bash.Start()
	if err != nil {
		fmt.Println("There was a problem executing xmllint using bash")
		panic(err)
	}
	err = cmd_echo.Run()
	if err != nil {
		fmt.Println("There was a problem executing echo")
		panic(err)
	}
	err = cmd_bash.Wait()
	if err != nil {
		fmt.Println("There was a problem executing xmllint using bash")
		panic(err)
	}

	datastring := output.String()

	plainwordsstring := ""
	lines := strings.Split(datastring, " -------\n")
	for _, line := range lines {
		if line == "/ > " {
			continue
		}
		if strings.HasSuffix(line, "\n/ > ") {
			nosuffix := strings.Split(line, "\n/ > ")
			line = nosuffix[0]
		}
		if strings.HasPrefix(line, " ") && strings.HasSuffix(line, "\"\n") && strings.Contains(line, "=\"") {
			attrvalue := strings.Split(line, "=\"")
			value := strings.Split(attrvalue[1], "\"")
			token := cleanToken(value[0])
			if isIrrelevantToken(token) {
				continue
			}
			count[token]++
			if plainwords {
				plainwordsstring += token + "\t"
			}
		} else {
			token := cleanToken(line)
			if isIrrelevantToken(token) {
				continue
			}
			count[token]++
			if plainwords {
				plainwordsstring += token + "\t"
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
	newtoken = strings.Replace(newtoken, "\n", " ", -1)    //while newlines shouldn't feature in tokens, they sometimes do (for example as &#10;)
	newtoken = strings.Trim(newtoken, " \r")               //remove whitespace at the edges of the string
	return newtoken
}

func isIrrelevantToken(token string) bool {
	for _, rune := range token {
		if !unicode.In(rune, unicode.Cf, unicode.Punct, unicode.Sk, unicode.Sm, unicode.So, unicode.Z) {
			return false
		}
	}
	return true
}

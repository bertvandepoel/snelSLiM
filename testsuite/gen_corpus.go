package main

import (
	"fmt"
	"io/ioutil"
	"math"
	"math/rand"
	"strconv"
	"strings"
	"time"
)

func main() {
	data, err := ioutil.ReadFile("google-10000-english-no-swears.txt")
	if err != nil {
		panic("could not read google wordlist google-10000-english-no-swears.txt")
	}
	datastring := string(data)
	words := strings.Split(datastring, "\n")

	rand.Seed(time.Now().UnixNano())
	numfiles_target := 10 + rand.Intn(10)             // how many files the target corpus will contain (10-19)
	numfiles_reference := 20 + rand.Intn(10)          // how many files the reference corpus will contain (20-29)
	numtopwords := 10 + rand.Intn(5)                  // how many words will be selected for high frequency (10-14)
	numlowwords := 250 + rand.Intn(250) - numtopwords // how many words will be selected to pad out the file (250 - 499)

	rand.Shuffle(len(words), func(i, j int) {
		words[i], words[j] = words[j], words[i]
	})

	topwords := words[0:numtopwords]
	lowwords := words[numtopwords+1 : numlowwords+numtopwords]

	for i := 1; i <= numfiles_target; i++ {
		fmt.Println("Generating target corpus file " + strconv.Itoa(i))

		var filewords []string
		freq := 500 + rand.Intn(500) // start off with the most frequent words at 500-999 and then divide it by 1.2 for every word
		for _, word := range topwords {
			for j := 1; j <= freq; j++ {
				filewords = append(filewords, word)
			}
			freq = int(math.Ceil(float64(freq) / float64(1.2)))
		}
		for _, word := range lowwords {
			for j := 1; j <= freq; j++ {
				filewords = append(filewords, word)
			}
			freq = int(math.Ceil(float64(freq) / float64(1.2)))
		}

		// mix the words (they are currently grouped together)
		rand.Shuffle(len(filewords), func(i, j int) {
			filewords[i], filewords[j] = filewords[j], filewords[i]
		})

		filestring := strings.Join(filewords, " ")
		err = ioutil.WriteFile("target/"+strconv.Itoa(i)+".txt", []byte(filestring), 0644)
		if err != nil {
			panic("could not write corpus file")
		}

		// shuffle the words for the next file
		rand.Shuffle(len(topwords), func(i, j int) {
			topwords[i], topwords[j] = topwords[j], topwords[i]
		})
		rand.Shuffle(len(lowwords), func(i, j int) {
			lowwords[i], lowwords[j] = lowwords[j], lowwords[i]
		})
	}

	for i := 1; i <= numfiles_reference; i++ {
		fmt.Println("Generating reference corpus file " + strconv.Itoa(i))

		var filewords []string
		freq := 500 + rand.Intn(500) // start off with the most frequent words at 500-999 and then divide it by 1.2 for every word
		for _, word := range topwords {
			for j := 1; j <= freq; j++ {
				filewords = append(filewords, word)
			}
			freq = int(math.Ceil(float64(freq) / float64(1.2)))
		}
		for _, word := range lowwords {
			for j := 1; j <= freq; j++ {
				filewords = append(filewords, word)
			}
			freq = int(math.Ceil(float64(freq) / float64(1.2)))
		}

		// mix the words (they are currently grouped together)
		rand.Shuffle(len(filewords), func(i, j int) {
			filewords[i], filewords[j] = filewords[j], filewords[i]
		})

		filestring := strings.Join(filewords, " ")
		err = ioutil.WriteFile("reference/"+strconv.Itoa(i)+".txt", []byte(filestring), 0644)
		if err != nil {
			panic("could not write corpus file")
		}

		// shuffle the words for the next file
		rand.Shuffle(len(topwords), func(i, j int) {
			topwords[i], topwords[j] = topwords[j], topwords[i]
		})
		rand.Shuffle(len(lowwords), func(i, j int) {
			lowwords[i], lowwords[j] = lowwords[j], lowwords[i]
		})
	}
}

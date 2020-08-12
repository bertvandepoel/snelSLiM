package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io/ioutil"
	"math"
	"net/http"
	"os"
	"path/filepath"
	"sort"
	"strconv"
	"strings"
	"sync"
	"time"
)

func main() {
	if len(os.Args) < 7 {
		fmt.Println("The snelSLiM analyser requires 6 arguments:")
		fmt.Println("1. the path to the first preparsed corpus")
		fmt.Println("2. the path to the second preparsed corpus")
		fmt.Println("3. the number of most frequent items to analyse from the primary corpus")
		fmt.Println("4. the cut-off value for the G squared statics test (Chi squared PPF result)")
		fmt.Println("5. the directory to write the results report to")
		fmt.Println("6. timeout value in seconds for each corpus, the analysis will fail if preparsing hasn't completed after waiting this amount of seconds")
		fmt.Println("7. whether to export visualization data as part of the report, 1 for yes, 0 for no, optional (0 is then presumed)")
		fmt.Println("8. left lookup space to perform collocational analysis on for each keyword, if both left and right are 0, no CA is performed, optional (0 is then presumed)")
		fmt.Println("9. right lookup space to perform collocational analysis on for each keyword, if both left and right are 0, no CA is performed, optional (0 is then presumed)")
		fmt.Println("10. the callback URL to signal successful completion to, optional")
		os.Exit(1)
	}
	c1 := os.Args[1] + "/"
	c2 := os.Args[2] + "/"
	reportdir := os.Args[5] + "/"
	freqnum, err := strconv.Atoi(os.Args[3])
	if err != nil {
		err = ioutil.WriteFile(reportdir+"error", []byte("error: Could not cast freqnum to integer"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
	}
	cutoff, err := strconv.ParseFloat(os.Args[4], 64)
	if err != nil {
		err = ioutil.WriteFile(reportdir+"error", []byte("error: Could not cast cutoff to float"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
	}
	timeout, err := strconv.Atoi(os.Args[6])
	if err != nil {
		err = ioutil.WriteFile(reportdir+"error", []byte("error: Could not cast timeout to integer"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
	}
	exportviz := false
	if len(os.Args) > 7 {
		vizarg, err := strconv.Atoi(os.Args[7])
		if err != nil {
			err = ioutil.WriteFile(reportdir+"error", []byte("error: Could not cast vizarg to integer"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
		if vizarg == 1 {
			exportviz = true
		}
	}
	colloc := false
	collocleft := 0
	collocright := 0
	if len(os.Args) > 8 {
		collocleft, err := strconv.Atoi(os.Args[8])
		if err != nil {
			err = ioutil.WriteFile(reportdir+"error", []byte("error: Could not cast collocleft to integer"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
		if len(os.Args) > 9 {
			collocright, err = strconv.Atoi(os.Args[9])
			if err != nil {
				err = ioutil.WriteFile(reportdir+"error", []byte("error: Could not cast collocright to integer"), 0644)
				if err != nil {
					fmt.Println("Could not write error")
					panic(err)
				}
				panic(err)
			}
		}

		// collocation is presumed if either collocleft or collocright are not zero
		if collocleft > 0 || collocright > 0 {
			colloc = true
		}
	}

	timer := 0
	for {
		_, err := os.Stat(c1 + "done") // done file exists when preparsing is done
		if err == nil {
			break
		}
		if !os.IsNotExist(err) {
			err = ioutil.WriteFile(reportdir+"error", []byte("error: could not check if preparse of corpus 1 is done"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
		_, err = os.Stat(c1 + "error") // check if preparser has written an error file
		if err == nil {
			c1error, err := ioutil.ReadFile(c1 + "error")
			if err != nil {
				err = ioutil.WriteFile(reportdir+"error", []byte("Error: could not read corpus 1 error message"), 0644)
				if err != nil {
					fmt.Println("Could not write error")
					panic(err)
				}
				panic(err)
			}
			err = ioutil.WriteFile(reportdir+"error", []byte("error: corpus 1 could not be parsed correctly and reported the following error: "+string(c1error)), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
		time.Sleep(5 * time.Second) // wait 5 more seconds, then continue the loop
		timer += 5
		if timer > timeout {
			err = ioutil.WriteFile(reportdir+"error", []byte("error: preparse of corpus 1 took more than "+strconv.Itoa(timeout)+" seconds, timeout reached"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
	}

	timer = 0
	for {
		_, err := os.Stat(c2 + "done") // done file exists when preparsing is done
		if err == nil {
			break
		}
		if !os.IsNotExist(err) {
			err = ioutil.WriteFile(reportdir+"error", []byte("error: could not check if preparse of corpus 2 is done"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
		_, err = os.Stat(c2 + "error") // check if preparser has written an error file
		if err == nil {
			c2error, err := ioutil.ReadFile(c2 + "error")
			if err != nil {
				err = ioutil.WriteFile(reportdir+"error", []byte("Error: could not read corpus 2 error message"), 0644)
				if err != nil {
					fmt.Println("Could not write error")
					panic(err)
				}
				panic(err)
			}
			err = ioutil.WriteFile(reportdir+"error", []byte("error: corpus 2 could not be parsed correctly and reported the following error: "+string(c2error)), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
		time.Sleep(5 * time.Second) // wait 5 more seconds, then continue the loop
		timer += 5
		if timer > timeout {
			err = ioutil.WriteFile(reportdir+"error", []byte("error: preparse of corpus 2 took more than "+strconv.Itoa(timeout)+" seconds, timeout reached"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
	}

	c1files, err := ioutil.ReadDir(c1) // create a list of all preparsed files
	if err != nil {
		err = ioutil.WriteFile(reportdir+"error", []byte("Error: Could not read corpus 1"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
	}

	c2files, err := ioutil.ReadDir(c2) // create a list of all preparsed files
	if err != nil {
		err = ioutil.WriteFile(reportdir+"error", []byte("Error: Could not read corpus 2"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
	}

	c1globalcount := make(map[string]int)
	c1fragmentcount := make(map[string]map[string]int)
	var c1fragments []string
	var collocwords [][]string
	c1totalsize := 0

	// loop through every file, if the filename ends in .snelslim it will be a preparsed frequency list,
	// then read that frequency list into both a counter for the file and a global counter for the corpus
	// if the filename ends in .plainwords and a collocational analysis is required, read every word into a list per file
	for _, file := range c1files {
		if strings.HasSuffix(file.Name(), "snelslim") {
			data, err := ioutil.ReadFile(c1 + file.Name())
			if err != nil {
				err = ioutil.WriteFile(reportdir+"error", []byte("Error: Could not read corpus 1 fragment"), 0644)
				if err != nil {
					fmt.Println("Could not write error")
					panic(err)
				}
				panic(err)
			}

			fragname := filepath.Base(file.Name())
			// remove the .snelslim extension (9 chars)
			fragname = fragname[0 : len(fragname)-9]
			c1fragments = append(c1fragments, fragname)

			localcount := make(map[string]int)

			datastring := string(data)
			rows := strings.Split(datastring, "\n")
			for _, row := range rows {
				if row != "" {
					fields := strings.Split(row, "\t")
					count, err := strconv.Atoi(fields[1])
					if err != nil {
						err = ioutil.WriteFile(reportdir+"error", []byte("Error: Could not cast count in file to integer"), 0644)
						if err != nil {
							fmt.Println("Could not write error")
							panic(err)
						}
						panic(err)
					}
					localcount[fields[0]] += count
					if fields[0] != "total.snelslim" {
						c1globalcount[fields[0]] += count
					} else {
						c1totalsize += count
					}
				}
			}
			c1fragmentcount[fragname] = localcount
		} else if colloc && strings.HasSuffix(file.Name(), "plainwords") {
			data, err := ioutil.ReadFile(c1 + file.Name())
			if err != nil {
				err = ioutil.WriteFile(reportdir+"error", []byte("Error: Could not read corpus 1 fragment collocation plain words file"), 0644)
				if err != nil {
					fmt.Println("Could not write error")
					panic(err)
				}
				panic(err)
			}
			datastring := string(data)
			wordlist := strings.Split(datastring, "\t")
			collocwords = append(collocwords, wordlist)
		}
	}

	c2fragmentcount := make(map[string]map[string]int)
	var c2fragments []string

	// loop through every file, if the filename ends in .snelslim it will be a preparsed frequency list,
	// then read that frequency list into a counter for the file, there is no global count for the reference corpus
	for _, file := range c2files {
		if strings.HasSuffix(file.Name(), "snelslim") {
			data, err := ioutil.ReadFile(c2 + file.Name())
			if err != nil {
				err = ioutil.WriteFile(reportdir+"error", []byte("Error: Could not read corpus 2 fragment"), 0644)
				if err != nil {
					fmt.Println("Could not write error")
					panic(err)
				}
				panic(err)
			}

			fragname := filepath.Base(file.Name())
			// remove the .snelslim extension (9 chars)
			fragname = fragname[0 : len(fragname)-9]
			c2fragments = append(c2fragments, fragname)

			localcount := make(map[string]int)

			datastring := string(data)
			rows := strings.Split(datastring, "\n")
			for _, row := range rows {
				if row != "" {
					fields := strings.Split(row, "\t")
					count, err := strconv.Atoi(fields[1])
					if err != nil {
						err = ioutil.WriteFile(reportdir+"error", []byte("Error: Could not cast count in file to integer"), 0644)
						if err != nil {
							fmt.Println("Could not write error")
							panic(err)
						}
						panic(err)
					}
					localcount[fields[0]] += count
				}
			}
			c2fragmentcount[fragname] = localcount
		}
	}

	// These structures are required for sorting and make passing data through channels for multithreading much easier
	type structkeyvalue struct {
		Key   string
		Value int
	}

	type structkeyfloat struct {
		Key   string
		Value float64
	}

	type structresult struct {
		Keyword          string
		Absolute_score   int
		Normalised_score float64
		Attraction       int
		Repulsion        int
		Lormin           float64
		Lormax           float64
		Lor_stddev       float64
		Lor_score        float64
	}

	var sortedc1globalcount []structkeyvalue
	for key, value := range c1globalcount {
		sortedc1globalcount = append(sortedc1globalcount, structkeyvalue{key, value})
	}

	sort.Slice(sortedc1globalcount, func(i, j int) bool {
		return sortedc1globalcount[i].Value > sortedc1globalcount[j].Value
	})

	i := 0
	freqposition := make(map[string]int)
	channel_c1results := make(chan structresult, freqnum)
	channel_corpusA_vectorvalues := make(chan map[string]map[string]float64, freqnum)
	channel_corpusB_vectorvalues := make(chan map[string]map[string]float64, freqnum)
	var wg sync.WaitGroup
	for _, kv := range sortedc1globalcount {
		if i == freqnum {
			break
		}
		i++
		freqposition[kv.Key] = i

		wg.Add(1)
		go func(kv structkeyvalue) {
			defer wg.Done()

			attraction := 0
			repulsion := 0
			lortotal := float64(0)
			lormin := math.MaxFloat64  // any number will be lower than math.MaxFloat64
			lormax := -math.MaxFloat64 // any number will be higher than the negative of math.MaxFloat64
			var lorlist []float64
			corpusA_kwvectorvalues := make(map[string]float64)
			corpusB_kwvectorvalues := make(map[string]float64)

			for corpusA_fragmentname, c1localcount := range c1fragmentcount {
				/*
				 *            W       !W
				 * corpus1   cel1    cel2
				 * corpus2   cel3    cel4
				 *
				 */
				cel1 := float64(c1localcount[kv.Key])
				cel2 := float64(c1localcount["total.snelslim"] - c1localcount[kv.Key])
				// cA_zero and cB_zero are used to track if in the current file of the target/reference corpus contians a zero value
				// if a zero value is detected, all cels get +0.5
				cA_zero := false
				cB_zero := false
				if cel1 == 0 || cel2 == 0 {
					cA_zero = true
					cel1 += 0.5
					cel2 += 0.5
				}
				kw_freq_c1 := float64(c1localcount[kv.Key]) / float64(c1localcount["total.snelslim"])

				for corpusB_fragmentname, c2localcount := range c2fragmentcount {
					cel3 := float64(c2localcount[kv.Key])
					cel4 := float64(c2localcount["total.snelslim"] - c2localcount[kv.Key])
					if cB_zero == true {
						cel1 -= 0.5
						cel2 -= 0.5
						cB_zero = false
					}
					if cA_zero == true {
						cel3 += 0.5
						cel4 += 0.5
					} else if cel3 == 0 || cel4 == 0 {
						cB_zero = true
						cel1 += 0.5
						cel2 += 0.5
						cel3 += 0.5
						cel4 += 0.5
					}
					N := cel1 + cel2 + cel3 + cel4
					R1 := cel1 + cel2
					R2 := cel3 + cel4
					C1 := cel1 + cel3
					C2 := cel2 + cel4
					Gcel1 := 2 * cel1 * math.Log(cel1/((R1*C1)/N))
					Gcel2 := 2 * cel2 * math.Log(cel2/((R1*C2)/N))
					Gcel3 := 2 * cel3 * math.Log(cel3/((R2*C1)/N))
					Gcel4 := 2 * cel4 * math.Log(cel4/((R2*C2)/N))
					Gsquared := Gcel1 + Gcel2 + Gcel3 + Gcel4

					// Check if the keyword is significant
					if Gsquared > cutoff {
						kw_freq_c2 := float64(c2localcount[kv.Key]) / float64(c2localcount["total.snelslim"])
						ratio := (cel1 / cel2) / (cel3 / cel4)
						logratio := math.Log(ratio)
						if kw_freq_c1 > kw_freq_c2 { // this checks if the keyword has a higher relative frequency in the target or the reference corpus
							// this keyword is a stable lexical marker for corpus 1 for this text combination
							attraction++
							if exportviz {
								corpusA_kwvectorvalues[corpusA_fragmentname] = corpusA_kwvectorvalues[corpusA_fragmentname] + (logratio / float64(len(c2fragmentcount)))
								corpusB_kwvectorvalues[corpusB_fragmentname] = corpusB_kwvectorvalues[corpusB_fragmentname] + 0.0
							}
						} else {
							// this keyword is actually a stable lexical marker for corpus 2 for this text combination
							repulsion++
							if exportviz {
								corpusA_kwvectorvalues[corpusA_fragmentname] = corpusA_kwvectorvalues[corpusA_fragmentname] + 0.0
								corpusB_kwvectorvalues[corpusB_fragmentname] = corpusB_kwvectorvalues[corpusB_fragmentname] + (logratio / float64(len(c1fragmentcount)))
							}
						}
						lortotal += logratio
						if logratio < lormin {
							lormin = logratio
						}
						if logratio > lormax {
							lormax = logratio
						}
						lorlist = append(lorlist, logratio)
					} else {
						if exportviz {
							corpusA_kwvectorvalues[corpusA_fragmentname] = corpusA_kwvectorvalues[corpusA_fragmentname] + 0.0
							corpusB_kwvectorvalues[corpusB_fragmentname] = corpusB_kwvectorvalues[corpusB_fragmentname] + 0.0
						}
					}
				}

			}

			// If none of the text combinations had a significant G test value, the lorlist will be empty since no log odds ratio will have been calculated
			if len(lorlist) > 0 {
				combinations := float64(len(c1fragmentcount) * len(c2fragmentcount))
				absolute_score := attraction - repulsion
				normalised_score := float64(absolute_score) / combinations
				lor_score := lortotal / combinations
				lor_stddev := stdDev(lorlist)
				channel_c1results <- structresult{kv.Key, absolute_score, normalised_score, attraction, repulsion, lormin, lormax, lor_stddev, lor_score}

				if exportviz {
					corpusA_kwvectorvalues_wrapped := make(map[string]map[string]float64)
					corpusA_kwvectorvalues_wrapped[kv.Key] = corpusA_kwvectorvalues
					channel_corpusA_vectorvalues <- corpusA_kwvectorvalues_wrapped
					corpusB_kwvectorvalues_wrapped := make(map[string]map[string]float64)
					corpusB_kwvectorvalues_wrapped[kv.Key] = corpusB_kwvectorvalues
					channel_corpusB_vectorvalues <- corpusB_kwvectorvalues_wrapped
				}
			}
		}(kv)
	}

	wg.Wait()
	close(channel_c1results)
	var c1results []structresult
	for result := range channel_c1results {
		c1results = append(c1results, result)
	}
	close(channel_corpusA_vectorvalues)
	corpusA_vectorvalues := make(map[string]map[string]float64)
	if exportviz {
		for corpusA_kwvectorvalues_wrapped := range channel_corpusA_vectorvalues {
			for keyword, vectorvalues := range corpusA_kwvectorvalues_wrapped {
				corpusA_vectorvalues[keyword] = vectorvalues
			}
		}
	}
	close(channel_corpusB_vectorvalues)
	corpusB_vectorvalues := make(map[string]map[string]float64)
	if exportviz {
		for corpusB_kwvectorvalues_wrapped := range channel_corpusB_vectorvalues {
			for keyword, vectorvalues := range corpusB_kwvectorvalues_wrapped {
				corpusB_vectorvalues[keyword] = vectorvalues
			}
		}
	}

	c1fragviz := make(map[string]map[string]int)
	if exportviz {
		for _, fragment := range c1fragments {
			c1fragviz[fragment] = make(map[string]int)
			c1fragviz[fragment]["total"] = c1fragmentcount[fragment]["total.snelslim"]
		}
	}

	sort.Slice(c1results, func(i, j int) bool {
		return c1results[i].Lor_score > c1results[j].Lor_score
	})

	var c1buffer bytes.Buffer
	c1fragresult := make(map[string]int)
	c2fragresult := make(map[string]int)
	c1keyfrags := make(map[string]map[string]int)
	colloccounts := make(map[string]map[string]int)
	for _, kv := range c1results {
		var valuestring string

		// write the result in the same format as mclm, specifically the following order separated by tabs
		// marker, absolute score, normalised score, attraction, repulsion, minimum LOR, maximum LOR, stddev LOR, average LOR
		c1buffer.WriteString(kv.Keyword)
		c1buffer.WriteString("\t")
		valuestring = strconv.Itoa(kv.Absolute_score)
		c1buffer.WriteString(valuestring)
		c1buffer.WriteString("\t")
		valuestring = strconv.FormatFloat(kv.Normalised_score, 'f', -1, 64)
		c1buffer.WriteString(valuestring)
		c1buffer.WriteString("\t")
		valuestring = strconv.Itoa(kv.Attraction)
		c1buffer.WriteString(valuestring)
		c1buffer.WriteString("\t")
		valuestring = strconv.Itoa(kv.Repulsion)
		c1buffer.WriteString(valuestring)
		c1buffer.WriteString("\t")
		valuestring = strconv.FormatFloat(kv.Lormin, 'f', -1, 64)
		c1buffer.WriteString(valuestring)
		c1buffer.WriteString("\t")
		valuestring = strconv.FormatFloat(kv.Lormax, 'f', -1, 64)
		c1buffer.WriteString(valuestring)
		c1buffer.WriteString("\t")
		valuestring = strconv.FormatFloat(kv.Lor_stddev, 'f', -1, 64)
		c1buffer.WriteString(valuestring)
		c1buffer.WriteString("\t")
		valuestring = strconv.FormatFloat(kv.Lor_score, 'f', -1, 64)
		c1buffer.WriteString(valuestring)
		c1buffer.WriteString("\n")

		c1keyfrags[kv.Keyword] = make(map[string]int)
		// for each marker, the count within every fragment is checked to create the list of markers per file
		// similar details are stored, but including information on attraction/repulsion for visualisations
		for _, fragment := range c1fragments {
			c1fragresult[fragment] += c1fragmentcount[fragment][kv.Keyword]
			if c1fragmentcount[fragment][kv.Keyword] > 0 {
				c1keyfrags[kv.Keyword][fragment] = c1fragmentcount[fragment][kv.Keyword]
			}
			if exportviz {
				if c1fragmentcount[fragment][kv.Keyword] > 0 {
					c1fragviz[fragment]["keyword_total"] += c1fragmentcount[fragment][kv.Keyword]
					c1fragviz[fragment]["keyword_unique"] += 1
					if kv.Absolute_score < 0 {
						c1fragviz[fragment]["repulsion_total"] += c1fragmentcount[fragment][kv.Keyword]
						c1fragviz[fragment]["repulsion_unique"] += 1
					} else if kv.Absolute_score > 0 {
						c1fragviz[fragment]["attraction_total"] += c1fragmentcount[fragment][kv.Keyword]
						c1fragviz[fragment]["attraction_unique"] += 1
					} else {
						c1fragviz[fragment]["balanced_total"] += c1fragmentcount[fragment][kv.Keyword]
						c1fragviz[fragment]["balanced_unique"] += 1
					}
				}
			}
		}
		for _, fragment := range c2fragments {
			c2fragresult[fragment] += c2fragmentcount[fragment][kv.Keyword]
		}

		// if performing CA, for every marker all potential collocates within the the left and right search space are counted
		if colloc {
			colloccounts[kv.Keyword] = make(map[string]int)
			for _, wordlist := range collocwords {
				max := len(wordlist) - 1
				for index, word := range wordlist {
					if word == kv.Keyword {
						for i := 1; i <= collocleft; i++ {
							newindex := index - i
							if newindex < 0 {
								break
							}
							colloccounts[kv.Keyword][wordlist[newindex]]++
						}
						for i := 1; i <= collocright; i++ {
							newindex := index + i
							if newindex > max {
								break
							}
							colloccounts[kv.Keyword][wordlist[newindex]]++
						}
					}
				}
			}
		}
	}

	var collocbuffer bytes.Buffer
	// calculation of the log dice is performed for every potential collocate
	// those that have a score larger than 0 are added to a structure and then sorted
	// A report is written with the marker, then lines of the collocates and their scores, an empty line is left before the next marker
	if colloc {
		for keyword, wordlist := range colloccounts {
			collocbuffer.WriteString(keyword)
			collocbuffer.WriteString("\n")
			var collocates []structkeyfloat
			for word, count := range wordlist {
				var dice float64
				var logdice float64
				dice = float64(2) * float64(count)
				dice = dice / (float64(c1globalcount[keyword]) + float64(c1globalcount[word]))
				logdice = float64(14) + math.Log2(dice)
				if logdice > 0 {
					collocates = append(collocates, structkeyfloat{word, logdice})
				}
			}
			sort.Slice(collocates, func(i, j int) bool {
				return collocates[i].Value > collocates[j].Value
			})
			for _, row := range collocates {
				collocbuffer.WriteString(row.Key)
				collocbuffer.WriteString("\t")
				valuestring := strconv.FormatFloat(row.Value, 'f', -1, 64)
				collocbuffer.WriteString(valuestring)
				collocbuffer.WriteString("\n")
			}
			collocbuffer.WriteString("\n")
		}
		err = ioutil.WriteFile(reportdir+"collocates.report", collocbuffer.Bytes(), 0644)
		if err != nil {
			err = ioutil.WriteFile(reportdir+"error", []byte("error: could not write collocational analysis results"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
	}

	// this structure facilitates the automatic conversion of visualisation arrays into a JSON that can be read directly by VEGA
	type viznode struct {
		Id                             int     `json:"id"`
		Name                           string  `json:"name"`
		Size_total                     int     `json:"size_total,omitempty"`
		Size_keyword_total             int     `json:"size_keyword_total,omitempty"`
		Size_keyword_unique            int     `json:"size_keyword_unique,omitempty"`
		Size_keyword_percentage_total  float64 `json:"size_keyword_percentage_total,omitempty"`
		Size_keyword_percentage_unique float64 `json:"size_keyword_percentage_unique,omitempty"`
		Parent_total                   int     `json:"parent_total,omitempty"`
		Parent_unique                  int     `json:"parent_unique,omitempty"`
	}

	var vizfraglist []viznode
	if exportviz {
		vizfraglist = append(vizfraglist, viznode{Id: 1, Name: "corpus"})
		vizfraglist = append(vizfraglist, viznode{Id: 2, Name: "attracted", Parent_total: 1, Parent_unique: 1})
		vizfraglist = append(vizfraglist, viznode{Id: 3, Name: "repulsed", Parent_total: 1, Parent_unique: 1})
		vizfraglist = append(vizfraglist, viznode{Id: 4, Name: "balanced", Parent_total: 1, Parent_unique: 1})
		i = 5
		for fragname, vizdata := range c1fragviz {
			if vizdata["total"] > 0 {
				var parent_total int
				if vizdata["attraction_total"] > vizdata["repulsion_total"] && vizdata["attraction_total"] > vizdata["balanced_total"] {
					parent_total = 2
				} else if vizdata["repulsion_total"] > vizdata["attraction_total"] && vizdata["repulsion_total"] > vizdata["balanced_total"] {
					parent_total = 3
				} else {
					parent_total = 4
				}
				var parent_unique int
				if vizdata["attraction_unique"] > vizdata["repulsion_unique"] && vizdata["attraction_unique"] > vizdata["balanced_unique"] {
					parent_unique = 2
				} else if vizdata["repulsion_unique"] > vizdata["attraction_unique"] && vizdata["repulsion_unique"] > vizdata["balanced_unique"] {
					parent_unique = 3
				} else {
					parent_unique = 4
				}
				perc_total := float64(vizdata["keyword_total"]) / float64(vizdata["total"])
				perc_unique := float64(vizdata["keyword_unique"]) / float64(vizdata["total"])
				vizfraglist = append(vizfraglist, viznode{i, fragname, vizdata["total"], vizdata["keyword_total"], vizdata["keyword_unique"], perc_total, perc_unique, parent_total, parent_unique})
				i++
			}
		}

		corpusA_vectors := make(map[string][]float64)
		corpusB_vectors := make(map[string][]float64)
		for keyword, vectorvalues := range corpusA_vectorvalues {
			for filename, value := range vectorvalues {
				corpusA_vectors[filename] = append(corpusA_vectors[filename], value)
			}
			for filename, value := range corpusB_vectorvalues[keyword] {
				corpusB_vectors[filename] = append(corpusB_vectors[filename], value)
			}
		}

		var corpusA_vectorbuffer bytes.Buffer
		for filename, values := range corpusA_vectors {
			corpusA_vectorbuffer.WriteString(filename)
			corpusA_vectorbuffer.WriteString("\t[ ")
			for _, value := range values {
				valuestring := strconv.FormatFloat(value, 'f', -1, 64)
				corpusA_vectorbuffer.WriteString(valuestring)
				corpusA_vectorbuffer.WriteString(" ")
			}
			corpusA_vectorbuffer.WriteString("]\n")
		}
		err = ioutil.WriteFile(reportdir+"corpusA.vectors", corpusA_vectorbuffer.Bytes(), 0644)
		if err != nil {
			err = ioutil.WriteFile(reportdir+"error", []byte("error: could not write vector representation for corpus A"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}

		var corpusB_vectorbuffer bytes.Buffer
		for filename, values := range corpusB_vectors {
			corpusB_vectorbuffer.WriteString(filename)
			corpusB_vectorbuffer.WriteString("\t[ ")
			for _, value := range values {
				valuestring := strconv.FormatFloat(value, 'f', -1, 64)
				corpusB_vectorbuffer.WriteString(valuestring)
				corpusB_vectorbuffer.WriteString(" ")
			}
			corpusB_vectorbuffer.WriteString("]\n")
		}
		err = ioutil.WriteFile(reportdir+"corpusB.vectors", corpusB_vectorbuffer.Bytes(), 0644)
		if err != nil {
			err = ioutil.WriteFile(reportdir+"error", []byte("error: could not write vector representation for corpus A"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}

		var distancelines []string
		var labellines []string
		for _, row_fragmentname := range c1fragments {
			labellines = append(labellines, "{'filename': '"+row_fragmentname+"', 'corpus': 'A'}")
			distanceline := "["
			for _, col_fragmentname := range c1fragments {
				var distance float64
				if row_fragmentname == col_fragmentname { // both from corpus A so potentially same file
					distance = 0.0
				} else {
					distance = euclidean_distance(corpusA_vectors[row_fragmentname], corpusA_vectors[col_fragmentname])
				}
				valuestring := strconv.FormatFloat(distance, 'f', -1, 64)
				distanceline += valuestring + ", "
			}
			for _, col_fragmentname := range c2fragments {
				distance := euclidean_distance(corpusA_vectors[row_fragmentname], corpusB_vectors[col_fragmentname])
				valuestring := strconv.FormatFloat(distance, 'f', -1, 64)
				distanceline += valuestring + ", "
			}
			distanceline = distanceline[0 : len(distanceline)-2]
			distanceline += "]"
			distancelines = append(distancelines, distanceline)
		}
		for _, row_fragmentname := range c2fragments {
			labellines = append(labellines, "{'filename': '"+row_fragmentname+"', 'corpus': 'B'}")
			distanceline := "["
			for _, col_fragmentname := range c1fragments {
				distance := euclidean_distance(corpusB_vectors[row_fragmentname], corpusA_vectors[col_fragmentname])
				valuestring := strconv.FormatFloat(distance, 'f', -1, 64)
				distanceline += valuestring + ", "
			}
			for _, col_fragmentname := range c2fragments {
				var distance float64
				if row_fragmentname == col_fragmentname { // both from corpus B so potentially same file
					distance = 0.0
				} else {
					distance = euclidean_distance(corpusB_vectors[row_fragmentname], corpusB_vectors[col_fragmentname])
				}
				valuestring := strconv.FormatFloat(distance, 'f', -1, 64)
				distanceline += valuestring + ", "
			}
			distanceline = distanceline[0 : len(distanceline)-2]
			distanceline += "]"
			distancelines = append(distancelines, distanceline)
		}

		var distancematrix bytes.Buffer
		distancematrix.WriteString("var distancematrix = [\n")
		for index, distanceline := range distancelines {
			distancematrix.WriteString("\t")
			distancematrix.WriteString(distanceline)
			if index != (len(distancelines) - 1) {
				distancematrix.WriteString(",\n")
			}
		}
		distancematrix.WriteString("\n];")

		distancematrix.WriteString("\n\nvar distancelabels = [\n")
		for index, labelline := range labellines {
			distancematrix.WriteString("\t")
			distancematrix.WriteString(labelline)
			if index != (len(labellines) - 1) {
				distancematrix.WriteString(",\n")
			}
		}
		distancematrix.WriteString("\n];")

		err = ioutil.WriteFile(reportdir+"distancematrix.js", distancematrix.Bytes(), 0644)
		if err != nil {
			err = ioutil.WriteFile(reportdir+"error", []byte("error: could not write distance matrix for vectors"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
	}

	var sortedc1fragresult []structkeyvalue
	for key, value := range c1fragresult {
		sortedc1fragresult = append(sortedc1fragresult, structkeyvalue{key, value})
	}

	sort.Slice(sortedc1fragresult, func(i, j int) bool {
		return sortedc1fragresult[i].Value > sortedc1fragresult[j].Value
	})

	err = ioutil.WriteFile(reportdir+"c1.report", c1buffer.Bytes(), 0644)
	if err != nil {
		err = ioutil.WriteFile(reportdir+"error", []byte("error: could not write score report for corpus 1"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
	}

	var c1fragbuffer bytes.Buffer
	for _, kv := range sortedc1fragresult {
		c1fragbuffer.WriteString(kv.Key)
		c1fragbuffer.WriteString("\t")
		valuestring := strconv.Itoa(kv.Value)
		c1fragbuffer.WriteString(valuestring)
		c1fragbuffer.WriteString("\n")
	}

	err = ioutil.WriteFile(reportdir+"c1frag.report", c1fragbuffer.Bytes(), 0644)
	if err != nil {
		err = ioutil.WriteFile(reportdir+"error", []byte("error: could not write fragment report for corpus 1"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
	}

	var sortedc2fragresult []structkeyvalue
	for key, value := range c2fragresult {
		sortedc2fragresult = append(sortedc2fragresult, structkeyvalue{key, value})
	}

	sort.Slice(sortedc2fragresult, func(i, j int) bool {
		return sortedc2fragresult[i].Value > sortedc2fragresult[j].Value
	})

	var c2fragbuffer bytes.Buffer
	for _, kv := range sortedc2fragresult {
		c2fragbuffer.WriteString(kv.Key)
		c2fragbuffer.WriteString("\t")
		valuestring := strconv.Itoa(kv.Value)
		c2fragbuffer.WriteString(valuestring)
		c2fragbuffer.WriteString("\n")
	}

	err = ioutil.WriteFile(reportdir+"c2frag.report", c2fragbuffer.Bytes(), 0644)
	if err != nil {
		err = ioutil.WriteFile(reportdir+"error", []byte("error: could not write fragment report for corpus 2"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
	}

	var c1keybuffer bytes.Buffer
	for keyword, frags := range c1keyfrags {
		c1keybuffer.WriteString(keyword)
		c1keybuffer.WriteString("\t")
		valuestring := strconv.Itoa(freqposition[keyword])
		c1keybuffer.WriteString(valuestring)
		c1keybuffer.WriteString("\t")
		valuestring = strconv.Itoa(c1globalcount[keyword])
		c1keybuffer.WriteString(valuestring)
		c1keybuffer.WriteString("\t")
		percentage := float64(c1globalcount[keyword]) / float64(c1totalsize)
		valuestring = strconv.FormatFloat(percentage, 'f', -1, 64)
		c1keybuffer.WriteString(valuestring)
		c1keybuffer.WriteString("\n")
		for frag, count := range frags {
			c1keybuffer.WriteString(frag)
			c1keybuffer.WriteString("\t")
			valuestring = strconv.Itoa(count)
			c1keybuffer.WriteString(valuestring)
			c1keybuffer.WriteString("\n")
		}
		c1keybuffer.WriteString("\n")
	}

	err = ioutil.WriteFile(reportdir+"keyword_details.report", c1keybuffer.Bytes(), 0644)
	if err != nil {
		err = ioutil.WriteFile(reportdir+"error", []byte("error: could not write keyword details"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
	}

	if exportviz {
		exportjson, err := json.Marshal(vizfraglist) // thanks to our structure, this can immediatly be converted into VEGA ready JSON
		if err != nil {
			err = ioutil.WriteFile(reportdir+"error", []byte("error: could convert visualisation data to correct format"+err.Error()), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
		err = os.Mkdir(reportdir+"visuals", 0755)
		if err != nil {
			err = ioutil.WriteFile(reportdir+"error", []byte("error: could not create visuals folder in the report folder"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
		err = ioutil.WriteFile(reportdir+"visuals/treemap.json", exportjson, 0644)
		if err != nil {
			err = ioutil.WriteFile(reportdir+"error", []byte("error: could not write treemap json data for visualisations"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
	}

	err = ioutil.WriteFile(reportdir+"done", []byte("done"), 0644)
	if err != nil {
		fmt.Println("Could not write done signal")
		panic(err)
	}

	// if a callback URL is specified, trigger it
	if len(os.Args) == 11 {
		http.Get(os.Args[10])
	}
}

// this function calculates standard deviation, since it is not part of any go core library
func stdDev(list []float64) float64 {
	total := 0.0
	mean := float64(0)
	for _, value := range list {
		mean += value
	}
	mean = mean / float64(len(list))
	for _, value := range list {
		total += math.Pow(value-mean, 2)
	}
	variance := total / float64(len(list)-1)
	return math.Sqrt(variance)
}

func euclidean_distance(plist []float64, qlist []float64) float64 {
	var summed_squares float64
	for index, _ := range plist {
		summed_squares += math.Pow(qlist[index]-plist[index], 2)
	}
	return math.Sqrt(summed_squares)
}

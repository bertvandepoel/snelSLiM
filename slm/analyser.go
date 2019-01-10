package main

import (
	"bytes"
	"fmt"
	"io/ioutil"
	"math"
	"os"
	"path/filepath"
	"sort"
	"strconv"
	"strings"
	"time"
)

func main() {
	if len(os.Args) < 6 {
		fmt.Println("The snelSLiM analyser requires 6 arguments:")
		fmt.Println("1. the path to the first preparsed corpus")
		fmt.Println("2. the path to the second preparsed corpus")
		fmt.Println("3. the number of most frequent items to analyse from the primary corpus")
		fmt.Println("4. the directory to write the results report to")
		fmt.Println("5. timeout value in seconds for each corpus, the analysis will fail if preparsing hasn't completed after waiting this amount of seconds")
		os.Exit(1)
	}
	c1 := os.Args[1] + "/"
	c2 := os.Args[2] + "/"
	reportdir := os.Args[4] + "/"
	freqnum, err := strconv.Atoi(os.Args[3])
	if err != nil {
		err = ioutil.WriteFile(reportdir+"error", []byte("error: Could not cast freqnum to integer"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
	}
	timeout, err := strconv.Atoi(os.Args[5])
	if err != nil {
		err = ioutil.WriteFile(reportdir+"error", []byte("error: Could not cast timeout to integer"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
	}

	if _, err := os.Stat(c1 + "error"); !os.IsNotExist(err) {
		err = ioutil.WriteFile(reportdir+"error", []byte("error: preparse of corpus 1 errored"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
	}

	if _, err := os.Stat(c2 + "error"); !os.IsNotExist(err) {
		err = ioutil.WriteFile(reportdir+"error", []byte("error: preparse of corpus 2 errored"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
	}

	timer := 0
	for {
		_, err := os.Stat(c1 + "done")
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
		time.Sleep(5 * time.Second)
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
		_, err := os.Stat(c2 + "done")
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
		time.Sleep(5 * time.Second)
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

	c1files, err := ioutil.ReadDir(c1)
	if err != nil {
		err = ioutil.WriteFile(reportdir+"error", []byte("Error: Could not read corpus 1"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
	}

	c2files, err := ioutil.ReadDir(c2)
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
					}
				}
			}
			c1fragmentcount[fragname] = localcount
		}
	}

	c2fragmentcount := make(map[string]map[string]int)
	var c2fragments []string

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

	type structkeyvalue struct {
		Key   string
		Value int
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
	var c1results []structresult
	for _, kv := range sortedc1globalcount {
		if i == freqnum {
			break
		}
		i++

		attraction := 0
		repulsion := 0
		lortotal := float64(0)
		lormin := math.MaxFloat64
		lormax := -math.MaxFloat64
		var lorlist []float64

		for _, c1localcount := range c1fragmentcount {
			/*
			 *            W       !W
			 * corpus1   cel1    cel2
			 * corpus2   cel3    cel4
			 *
			 */
			cel1 := float64(c1localcount[kv.Key])
			cel2 := float64(c1localcount["total.snelslim"] - c1localcount[kv.Key])
			if cel1 == 0 {
				cel1 = 0.00001
			}
			if cel2 == 0 {
				cel2 = 0.00001
			}
			kw_freq_c1 := float64(c1localcount[kv.Key]) / float64(c1localcount["total.snelslim"])

			for _, c2localcount := range c2fragmentcount {
				cel3 := float64(c2localcount[kv.Key])
				cel4 := float64(c2localcount["total.snelslim"] - c2localcount[kv.Key])
				if cel3 == 0 {
					cel3 = 0.00001
				}
				if cel4 == 0 {
					cel4 = 0.00001
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

				// 3.841 is the cut-off point for significance of the keyword
				if Gsquared > 3.841 {
					kw_freq_c2 := float64(c2localcount[kv.Key]) / float64(c2localcount["total.snelslim"])
					if kw_freq_c1 > kw_freq_c2 {
						// this keyword is a stable lexical marker for corpus 1 for this text combination
						attraction++
					} else {
						// this keyword is actually a stable lexical marker for corpus 2 for this text combination
						repulsion++
					}
					ratio := (cel1 / cel2) / (cel3 / cel4)
					logratio := math.Log(ratio)
					lortotal += logratio
					if logratio < lormin {
						lormin = logratio
					}
					if logratio > lormax {
						lormax = logratio
					}
					lorlist = append(lorlist, logratio)
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
			c1results = append(c1results, structresult{kv.Key, absolute_score, normalised_score, attraction, repulsion, lormin, lormax, lor_stddev, lor_score})
		}
	}

	sort.Slice(c1results, func(i, j int) bool {
		return c1results[i].Lor_score > c1results[j].Lor_score
	})

	var c1buffer bytes.Buffer
	c1fragresult := make(map[string]int)
	c2fragresult := make(map[string]int)
	for _, kv := range c1results {
		var valuestring string

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

		for _, fragment := range c1fragments {
			c1fragresult[fragment] += c1fragmentcount[fragment][kv.Keyword]
		}
		for _, fragment := range c2fragments {
			c2fragresult[fragment] += c2fragmentcount[fragment][kv.Keyword]
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

	err = ioutil.WriteFile(reportdir+"done", []byte("done"), 0644)
	if err != nil {
		fmt.Println("Could not write done signal")
		panic(err)
	}
}

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

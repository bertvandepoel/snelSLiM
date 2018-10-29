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
	c1 := os.Args[1] + "/"
	c2 := os.Args[2] + "/"
	reportdir := os.Args[6] + "/"
	freqnum, err := strconv.Atoi(os.Args[3])
	if err != nil {
		err = ioutil.WriteFile(reportdir+"error", []byte("error: Could not cast freqnum to integer"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
	}
	am := os.Args[4]
	resultnum, err := strconv.Atoi(os.Args[5])
	if err != nil {
		err = ioutil.WriteFile(reportdir+"error", []byte("error: Could not cast resultnum to integer"), 0644)
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
		if timer > 120 {
			err = ioutil.WriteFile(reportdir+"error", []byte("error: preparse of corpus 1 took more than 2 minutes, timeout reached"), 0644)
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
		if timer > 120 {
			err = ioutil.WriteFile(reportdir+"error", []byte("error: preparse of corpus 2 took more than 2 minutes, timeout reached"), 0644)
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
	c1total := 0
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
					c1globalcount[fields[0]] += count
					c1total += count
				}
			}
			c1fragmentcount[fragname] = localcount
		}
	}

	c2globalcount := make(map[string]int)
	c2fragmentcount := make(map[string]map[string]int)
	c2total := 0
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
					c2globalcount[fields[0]] += count
					c2total += count
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
		Key   string
		Value float64
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
		/*
		 *            W       !W
		 * corpus1   cel1    cel2
		 * corpus2   cel3    cel4
		 *
		 */
		cel1 := float64(kv.Value)
		cel2 := float64(c1total - kv.Value)
		cel3 := float64(c2globalcount[kv.Key])
		cel4 := float64(c2total - c2globalcount[kv.Key])
		if am == "likelihood" {
			sensitivity := cel1 / (cel1 + cel3)
			specificity := cel4 / (cel4 + cel2)
			ratio := sensitivity / (1 - specificity)
			logratio := math.Log(ratio)
			c1results = append(c1results, structresult{kv.Key, logratio})
		} else { //odds ratio
			denominator := (cel3 / cel4)
			if denominator == 0 {
				denominator = 0.00001
			}
			ratio := (cel1 / cel2) / denominator
			logratio := math.Log(ratio)
			c1results = append(c1results, structresult{kv.Key, logratio})
		}
	}

	sort.Slice(c1results, func(i, j int) bool {
		return c1results[i].Value > c1results[j].Value
	})

	i = 0
	var c1buffer bytes.Buffer
	c1fragresult := make(map[string]int)
	for _, kv := range c1results {
		if i == resultnum {
			break
		}
		i++
		c1buffer.WriteString(kv.Key)
		c1buffer.WriteString(" - ")
		valuestring := strconv.FormatFloat(kv.Value, 'f', -1, 64)
		c1buffer.WriteString(valuestring)
		c1buffer.WriteString("\n")

		for _, fragment := range c1fragments {
			c1fragresult[fragment] += c1fragmentcount[fragment][kv.Key]
		}
	}

	var sortedc1fragresult []structkeyvalue
	for key, value := range c1fragresult {
		sortedc1fragresult = append(sortedc1fragresult, structkeyvalue{key, value})
	}

	sort.Slice(sortedc1fragresult, func(i, j int) bool {
		return sortedc1fragresult[i].Value > sortedc1fragresult[j].Value
	})

	var c1fragbuffer bytes.Buffer
	for _, kv := range sortedc1fragresult {
		c1fragbuffer.WriteString(kv.Key)
		c1fragbuffer.WriteString(" - ")
		valuestring := strconv.Itoa(kv.Value)
		c1fragbuffer.WriteString(valuestring)
		c1fragbuffer.WriteString("\n")
	}

	err = ioutil.WriteFile(reportdir+"c1.report", c1buffer.Bytes(), 0644)
	if err != nil {
		err = ioutil.WriteFile(reportdir+"error", []byte("error: could not write report for corpus 1"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
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

	var sortedc2globalcount []structkeyvalue
	for key, value := range c2globalcount {
		sortedc2globalcount = append(sortedc2globalcount, structkeyvalue{key, value})
	}

	sort.Slice(sortedc2globalcount, func(i, j int) bool {
		return sortedc2globalcount[i].Value > sortedc2globalcount[j].Value
	})

	i = 0
	var c2results []structresult
	for _, kv := range sortedc2globalcount {
		if i == freqnum {
			break
		}
		i++
		/*
		 *            W       !W
		 * corpus1   cel1    cel2
		 * corpus2   cel3    cel4
		 *
		 */
		cel1 := float64(kv.Value)
		cel3 := float64(c2total - kv.Value)
		cel2 := float64(c1globalcount[kv.Key])
		cel4 := float64(c1total - c1globalcount[kv.Key])
		if am == "likelihood" {
			sensitivity := cel1 / (cel1 + cel3)
			specificity := cel4 / (cel4 + cel2)
			ratio := sensitivity / (1 - specificity)
			logratio := math.Log(ratio)
			c2results = append(c2results, structresult{kv.Key, logratio})
		} else { //odds ratio
			denominator := (cel3 / cel4)
			if denominator == 0 {
				denominator = 0.00001
			}
			ratio := (cel1 / cel2) / denominator
			logratio := math.Log(ratio)
			c2results = append(c2results, structresult{kv.Key, logratio})
		}
	}

	sort.Slice(c2results, func(i, j int) bool {
		return c2results[i].Value > c2results[j].Value
	})

	i = 0
	var c2buffer bytes.Buffer
	c2fragresult := make(map[string]int)
	for _, kv := range c2results {
		if i == resultnum {
			break
		}
		i++
		c2buffer.WriteString(kv.Key)
		c2buffer.WriteString(" - ")
		valuestring := strconv.FormatFloat(kv.Value, 'f', -1, 64)
		c2buffer.WriteString(valuestring)
		c2buffer.WriteString("\n")

		for _, fragment := range c2fragments {
			c2fragresult[fragment] += c2fragmentcount[fragment][kv.Key]
		}
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
		c2fragbuffer.WriteString(" - ")
		valuestring := strconv.Itoa(kv.Value)
		c2fragbuffer.WriteString(valuestring)
		c2fragbuffer.WriteString("\n")
	}

	err = ioutil.WriteFile(reportdir+"c2.report", c2buffer.Bytes(), 0644)
	if err != nil {
		err = ioutil.WriteFile(reportdir+"error", []byte("error: could not write report for corpus 1"), 0644)
		if err != nil {
			fmt.Println("Could not write error")
			panic(err)
		}
		panic(err)
	}

	err = ioutil.WriteFile(reportdir+"c2frag.report", c2fragbuffer.Bytes(), 0644)
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

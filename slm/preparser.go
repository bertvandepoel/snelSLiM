package main

import (
	"fmt"
	"io/ioutil"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
)

func main() {
	filename := os.Args[1]
	outdir := os.Args[2] + "/"
	format := os.Args[3]
	option := os.Args[4]
	extra := os.Args[5]
	savedir := os.Args[6] + "/"

	if strings.HasSuffix(filename, "zip") {
		err := exec.Command("/usr/bin/unzip", filename, "-d", outdir).Run()
		if err != nil {
			err = ioutil.WriteFile(savedir+"error", []byte("error: "+err.Error()), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
	} else {
		err := exec.Command("/bin/tar", "-xf", filename, "-C", outdir).Run()
		if err != nil {
			err = ioutil.WriteFile(savedir+"error", []byte("error: "+err.Error()), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
	}

	files := []string{}
	filepath.Walk(outdir, func(path string, info os.FileInfo, err error) error {
		if !info.IsDir() {
			abs, err := filepath.Abs(path)
			if err != nil {
				fmt.Println("Could not convert relative to absolute path")
				panic(err)
			}
			files = append(files, abs)
		}
		return nil
	})

	for _, file := range files {
		var output []byte
		var err error
		base := filepath.Base(file)
		if format == "conll" {
			output, err = exec.Command("../formats/CoNLL/parser", file, extra, savedir+"/"+base+".snelslim").Output()
		} else if format == "folia" {
			output, err = exec.Command("../formats/FoLiA/parser", file, option, extra, savedir+"/"+base+".snelslim").Output()
		} else if format == "dcoi" {
			output, err = exec.Command("../formats/DCOI/parser", file, option, savedir+"/"+base+".snelslim").Output()
		} else if format == "plain" {
			output, err = exec.Command("../formats/plain/parser", file, savedir+"/"+base+".snelslim").Output()
		} else if format == "alpino" {
			output, err = exec.Command("../formats/alpino/parser", file, option, savedir+"/"+base+".snelslim").Output()
		} else if format == "bnc" {
			output, err = exec.Command("../formats/BNC/parser", file, option, savedir+"/"+base+".snelslim").Output()
		} else if format == "eindhoven" {
			output, err = exec.Command("../formats/eindhoven/parser", file, savedir+"/").Output()
		} else if format == "gysseling" {
			output, err = exec.Command("../formats/gysseling/parser", file, option, savedir+"/"+base+".snelslim").Output()
		} else if format == "masc" {
			output, err = exec.Command("../formats/MASC/parser", file, option, savedir+"/"+base+".snelslim").Output()
		} else if format == "oanc" {
			output, err = exec.Command("../formats/OANC/parser", file, savedir+"/"+base+".snelslim").Output()
		} else if format == "xpath" {
			output, err = exec.Command("../formats/xpath/parser", file, extra, savedir+"/"+base+".snelslim").Output()
		} else {
			err := ioutil.WriteFile(savedir+"error", []byte("error: unknown format"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
		if err != nil || string(output) != "OK\n" {
			err := ioutil.WriteFile(savedir+"error", []byte("error executing parser"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
	}
	err := ioutil.WriteFile(savedir+"done", []byte("done"), 0644)
	if err != nil {
		fmt.Println("Could not write done signal")
		panic(err)
	}
	err = os.RemoveAll(outdir)
	err = os.RemoveAll(filename)
}

package main

import (
	"fmt"
	"io/ioutil"
	"os"
	"os/exec"
	"path/filepath"
	"strconv"
	"strings"
)

func main() {
	if len(os.Args) < 7 {
		fmt.Println("The snelSLiM prepraser requires 6 arguments:")
		fmt.Println("1. the zip or tar file containing the corpus")
		fmt.Println("2. the folder the corpus should be extracted to")
		fmt.Println("3. the format of the corpus (autodetect, conll, folia, dcoi, plain, alpino, bnc, eindhoven, gysseling, graf, textgrid, xpath)")
		fmt.Println("4. option for the relevant format (usually lemma or text), enter - if not using")
		fmt.Println("5. extra option for very specific formats (e.g. fast or xpath for folia), enter - if not using")
		fmt.Println("6. the directory to write the preparsed results to")
		fmt.Println("7. whether to write plaintext wordlists, 1 for yes, 0 for no, optional (0 is then presumed)")
		os.Exit(1)
	}
	filename := os.Args[1]
	outdir := os.Args[2] + "/"
	format := os.Args[3]
	option := os.Args[4]
	extra := os.Args[5]
	savedir := os.Args[6] + "/"
	plainwords := false
	if len(os.Args) > 7 {
		plainwordsarg, err := strconv.Atoi(os.Args[7])
		if err != nil {
			err = ioutil.WriteFile(savedir+"error", []byte("error: Could not cast plainwordsarg to integer"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
		if plainwordsarg == 1 {
			plainwords = true
		}
	}

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
		// skip any hidden folder starting with a . (such as .git, .DS_Store and .Trashes)
		if info.IsDir() && strings.HasPrefix(info.Name(), ".") {
			return filepath.SkipDir
		}
		// skip hidden windows trash folder
		if info.IsDir() && info.Name() == "$RECYCLE.BIN" {
			return filepath.SkipDir
		}
		// ignore file: windows thumbnails, windows ini settings, linux/mac dotfiles and windows link files
		if info.Name() == "Thumbs.db" || info.Name() == "Thumbs.db:encryptable" || info.Name() == "ehthumbs.db" || info.Name() == "ehthumbs_vista.db" ||
			info.Name() == "desktop.ini" || info.Name() == "Desktop.ini" || strings.HasPrefix(info.Name(), ".") || strings.HasSuffix(info.Name(), ".lnk") {
			return nil
		}

		// any file (not a folder) left over is added to the file list with absolute filepaths
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

	if format == "autodetect" {
		autodetect, err := exec.Command("../formats/autodetect", outdir).Output()
		if err != nil {
			err = ioutil.WriteFile(savedir+"error", []byte("error: "+err.Error()), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
		format = string(autodetect)
		err = ioutil.WriteFile(savedir+"autodetect", autodetect, 0644)
		if err != nil {
			fmt.Println("Could not write done signal")
			panic(err)
		}
		if format == "unknown" {
			err := ioutil.WriteFile(savedir+"error", []byte("error: corpus format autodetection was unable to detect the format of your corpus. Please refer to help page for more information."), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		} else if format == "partknown" {
			err := ioutil.WriteFile(savedir+"error", []byte("error: corpus format autodetection detected some known and some unknown formats. You may have to clean up your corpus files."), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		} else if format == "mixed" {
			err := ioutil.WriteFile(savedir+"error", []byte("error: corpus format autodetection detected files in several corpus formats. You may have to clean up your corpus files."), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		} else if format == "xml" {
			err := ioutil.WriteFile(savedir+"error", []byte("error: corpus format autodetection detected files an unknown XML format. Please refer to help page for more information."), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		} else if format == "tabs" {
			err := ioutil.WriteFile(savedir+"error", []byte("error: corpus format autodetection detected files that might be tab seperated (CoNLL) but does not know which column to use."), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		} else if format != "graf" && format != "alpino" && format != "bnc" && format != "dcoi" &&
			format != "folia" && format != "textgrid" && format != "gysseling" && format != "eindhoven" {

			err := ioutil.WriteFile(savedir+"error", []byte("error: corpus format autodetection did not return a valid response"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		} else {
			option = "lemma"
			extra = "fast"
		}
	}

	corpussize := 0
	for _, file := range files {
		var output []byte
		var err error
		base := filepath.Base(file)
		plainwordsfile := "-"
		if plainwords {
			plainwordsfile = savedir + "/" + base + ".plainwords"
		}
		if format == "conll" {
			output, err = exec.Command("../formats/CoNLL/parser", file, extra, savedir+"/"+base+".snelslim", plainwordsfile).Output()
		} else if format == "folia" {
			output, err = exec.Command("../formats/FoLiA/parser", file, option, extra, savedir+"/"+base+".snelslim", plainwordsfile).Output()
		} else if format == "dcoi" {
			output, err = exec.Command("../formats/DCOI/parser", file, option, savedir+"/"+base+".snelslim", plainwordsfile).Output()
		} else if format == "plain" {
			output, err = exec.Command("../formats/plain/parser", file, savedir+"/"+base+".snelslim", plainwordsfile).Output()
		} else if format == "alpino" {
			output, err = exec.Command("../formats/alpino/parser", file, option, savedir+"/"+base+".snelslim", plainwordsfile).Output()
		} else if format == "bnc" {
			output, err = exec.Command("../formats/BNC/parser", file, option, savedir+"/"+base+".snelslim", plainwordsfile).Output()
		} else if format == "eindhoven" {
			output, err = exec.Command("../formats/eindhoven/parser", file, savedir+"/", plainwordsfile).Output()
		} else if format == "gysseling" {
			output, err = exec.Command("../formats/gysseling/parser", file, option, savedir+"/"+base+".snelslim", plainwordsfile).Output()
		} else if format == "graf" {
			output, err = exec.Command("../formats/XCES-GrAF/parser", file, option, savedir+"/"+base+".snelslim", plainwordsfile).Output()
		} else if format == "textgrid" {
			output, err = exec.Command("../formats/TextGrid/parser", file, savedir+"/"+base+".snelslim", plainwordsfile).Output()
		} else if format == "xpath" {
			output, err = exec.Command("../formats/xpath/parser", file, extra, savedir+"/"+base+".snelslim", plainwordsfile).Output()
		} else {
			err := ioutil.WriteFile(savedir+"error", []byte("error: unknown format"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
		outputsplit := strings.Split(string(output), "\n")
		filesize, err := strconv.Atoi(outputsplit[0])
		if err != nil {
			err = ioutil.WriteFile(savedir+"error", []byte("error: Could not cast corpussize to integer"), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
		corpussize += filesize
		status := outputsplit[1]
		if err != nil || status != "OK" {
			err := ioutil.WriteFile(savedir+"error", []byte("error executing parser: "+string(output)), 0644)
			if err != nil {
				fmt.Println("Could not write error")
				panic(err)
			}
			panic(err)
		}
	}

	err := ioutil.WriteFile(savedir+"corpussize", []byte(strconv.Itoa(corpussize)), 0644)
	if err != nil {
		fmt.Println("Could not write the corpus size")
		panic(err)
	}

	if plainwords {
		err := ioutil.WriteFile(savedir+"plainwords", []byte("active"), 0644)
		if err != nil {
			fmt.Println("Could not write plainwords signal")
			panic(err)
		}
	}

	err = ioutil.WriteFile(savedir+"done", []byte("done"), 0644)
	if err != nil {
		fmt.Println("Could not write done signal")
		panic(err)
	}
	err = os.RemoveAll(outdir)
	err = os.RemoveAll(filename)
}

package main

import (
	"fmt"
	"io/ioutil"
	"strconv"
	"strings"
)

func main() {
	data, err := ioutil.ReadFile("result_mclm/result.tsv")
	if err != nil {
		panic("could not read mclm results")
	}
	datastring := string(data)
	mclm_lines := strings.Split(datastring, "\n")

	data, err = ioutil.ReadFile("result_snelslim/c1.report")
	if err != nil {
		panic("could not read snelSLiM results")
	}
	datastring = string(data)
	snelslim_lines := strings.Split(datastring, "\n")

	maxlen := 0
	if len(mclm_lines) > len(snelslim_lines) {
		maxlen = len(mclm_lines) - 1
	} else {
		maxlen = len(snelslim_lines) - 1
	}

	globalerror := false
	for i := 0; i < maxlen; i++ {
		if strings.HasSuffix(mclm_lines[i], "NA\tNA\tNA\tNA") {
			fmt.Println("Skipping mclm line for non-marker")
			continue
		} else {
			// NA and NaN can't be converted to a float, so the easiest solution is to replace it with the imposible value 99999
			mclm_lines[i] = strings.Replace(mclm_lines[i], "NA", "99999", -1)
			snelslim_lines[i] = strings.Replace(snelslim_lines[i], "NaN", "99999", -1)
		}
		if mclm_lines[i] == snelslim_lines[i] {
			continue
		}
		mclm_fields := strings.Split(mclm_lines[i], "\t")
		snelslim_fields := strings.Split(snelslim_lines[i], "\t")
		if mclm_fields[0] != snelslim_fields[0] {
			mclm_nextfields := strings.Split(mclm_lines[i+1], "\t")
			snelslim_nextfields := strings.Split(snelslim_lines[i+1], "\t")
			mclm_nextnextfields := strings.Split(mclm_lines[i+2], "\t")
			snelslim_nextnextfields := strings.Split(snelslim_lines[i+2], "\t")
			if mclm_fields[0] == snelslim_nextfields[0] && mclm_nextfields[0] == snelslim_fields[0] {
				fmt.Println("Line " + strconv.Itoa(i) + " is switched with the next line (due to identical LOR), making switched comparison")
				if mclm_fields[1] != snelslim_nextfields[1] {
					fmt.Println("Absolute score on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				// cast normalised scores to floats for comparison
				mclm_norm, err := strconv.ParseFloat(mclm_fields[2], 64)
				if err != nil {
					panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_norm, err := strconv.ParseFloat(snelslim_nextfields[2], 64)
				if err != nil {
					panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_norm-snelslim_norm > 0.00000000000001 || mclm_norm-snelslim_norm < -0.00000000000001 { // R has lower significance than go, so we have to adjust for this
					fmt.Println("Normalised score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
				}

				if mclm_fields[3] != snelslim_nextfields[3] {
					fmt.Println("Attraction on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				if mclm_fields[4] != snelslim_nextfields[4] {
					fmt.Println("Repulsion on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				mclm_min, err := strconv.ParseFloat(mclm_fields[5], 64)
				if err != nil {
					panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_min, err := strconv.ParseFloat(snelslim_nextfields[5], 64)
				if err != nil {
					panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_min-snelslim_min > 0.00000000000001 || mclm_min-snelslim_min < -0.00000000000001 {
					fmt.Println("Minimum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_max, err := strconv.ParseFloat(mclm_fields[6], 64)
				if err != nil {
					panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_max, err := strconv.ParseFloat(snelslim_nextfields[6], 64)
				if err != nil {
					panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_max-snelslim_max > 0.00000000000001 || mclm_max-snelslim_max < -0.00000000000001 {
					fmt.Println("Maximum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_sd, err := strconv.ParseFloat(mclm_fields[7], 64)
				if err != nil {
					panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_sd, err := strconv.ParseFloat(snelslim_nextfields[7], 64)
				if err != nil {
					panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_sd-snelslim_sd > 0.00000000000001 || mclm_sd-snelslim_sd < -0.00000000000001 {
					fmt.Println("StdDev LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_lor, err := strconv.ParseFloat(mclm_fields[8], 64)
				if err != nil {
					panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_lor, err := strconv.ParseFloat(snelslim_nextfields[8], 64)
				if err != nil {
					panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_lor-snelslim_lor > 0.00000000000001 || mclm_lor-snelslim_lor < -0.00000000000001 {
					fmt.Println("LOR score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				if mclm_nextfields[1] != snelslim_fields[1] {
					fmt.Println("Absolute score on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				// cast normalised scores to floats for comparison
				mclm_norm, err = strconv.ParseFloat(mclm_nextfields[2], 64)
				if err != nil {
					panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_norm, err = strconv.ParseFloat(snelslim_fields[2], 64)
				if err != nil {
					panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_norm-snelslim_norm > 0.00000000000001 || mclm_norm-snelslim_norm < -0.00000000000001 { // R has lower significance than go, so we have to adjust for this
					fmt.Println("Normalised score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
				}

				if mclm_nextfields[3] != snelslim_fields[3] {
					fmt.Println("Attraction on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				if mclm_nextfields[4] != snelslim_fields[4] {
					fmt.Println("Repulsion on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				mclm_min, err = strconv.ParseFloat(mclm_nextfields[5], 64)
				if err != nil {
					panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_min, err = strconv.ParseFloat(snelslim_fields[5], 64)
				if err != nil {
					panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_min-snelslim_min > 0.00000000000001 || mclm_min-snelslim_min < -0.00000000000001 {
					fmt.Println("Minimum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_max, err = strconv.ParseFloat(mclm_nextfields[6], 64)
				if err != nil {
					panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_max, err = strconv.ParseFloat(snelslim_fields[6], 64)
				if err != nil {
					panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_max-snelslim_max > 0.00000000000001 || mclm_max-snelslim_max < -0.00000000000001 {
					fmt.Println("Maximum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_sd, err = strconv.ParseFloat(mclm_nextfields[7], 64)
				if err != nil {
					panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_sd, err = strconv.ParseFloat(snelslim_fields[7], 64)
				if err != nil {
					panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_sd-snelslim_sd > 0.00000000000001 || mclm_sd-snelslim_sd < -0.00000000000001 {
					fmt.Println("StdDev LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_lor, err = strconv.ParseFloat(mclm_nextfields[8], 64)
				if err != nil {
					panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_lor, err = strconv.ParseFloat(snelslim_fields[8], 64)
				if err != nil {
					panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_lor-snelslim_lor > 0.00000000000001 || mclm_lor-snelslim_lor < -0.00000000000001 {
					fmt.Println("LOR score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				i++
				continue
			} else if mclm_fields[0] == snelslim_nextnextfields[0] && mclm_nextnextfields[0] == snelslim_fields[0] && mclm_nextfields[0] == snelslim_nextfields[0] {
				fmt.Println("Line " + strconv.Itoa(i) + " is switched with the next 2 lines (due to identical LOR), making switched comparison")

				if mclm_fields[1] != snelslim_nextnextfields[1] {
					fmt.Println("Absolute score on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				// cast normalised scores to floats for comparison
				mclm_norm, err := strconv.ParseFloat(mclm_fields[2], 64)
				if err != nil {
					panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_norm, err := strconv.ParseFloat(snelslim_nextnextfields[2], 64)
				if err != nil {
					panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_norm-snelslim_norm > 0.00000000000001 || mclm_norm-snelslim_norm < -0.00000000000001 { // R has lower significance than go, so we have to adjust for this
					fmt.Println("Normalised score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
				}

				if mclm_fields[3] != snelslim_nextnextfields[3] {
					fmt.Println("Attraction on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				if mclm_fields[4] != snelslim_nextnextfields[4] {
					fmt.Println("Repulsion on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				mclm_min, err := strconv.ParseFloat(mclm_fields[5], 64)
				if err != nil {
					panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_min, err := strconv.ParseFloat(snelslim_nextnextfields[5], 64)
				if err != nil {
					panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_min-snelslim_min > 0.00000000000001 || mclm_min-snelslim_min < -0.00000000000001 {
					fmt.Println("Minimum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_max, err := strconv.ParseFloat(mclm_fields[6], 64)
				if err != nil {
					panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_max, err := strconv.ParseFloat(snelslim_nextnextfields[6], 64)
				if err != nil {
					panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_max-snelslim_max > 0.00000000000001 || mclm_max-snelslim_max < -0.00000000000001 {
					fmt.Println("Maximum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_sd, err := strconv.ParseFloat(mclm_fields[7], 64)
				if err != nil {
					panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_sd, err := strconv.ParseFloat(snelslim_nextnextfields[7], 64)
				if err != nil {
					panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_sd-snelslim_sd > 0.00000000000001 || mclm_sd-snelslim_sd < -0.00000000000001 {
					fmt.Println("StdDev LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_lor, err := strconv.ParseFloat(mclm_fields[8], 64)
				if err != nil {
					panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_lor, err := strconv.ParseFloat(snelslim_nextnextfields[8], 64)
				if err != nil {
					panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_lor-snelslim_lor > 0.00000000000001 || mclm_lor-snelslim_lor < -0.00000000000001 {
					fmt.Println("LOR score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				if mclm_nextnextfields[1] != snelslim_fields[1] {
					fmt.Println("Absolute score on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				// cast normalised scores to floats for comparison
				mclm_norm, err = strconv.ParseFloat(mclm_nextnextfields[2], 64)
				if err != nil {
					panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_norm, err = strconv.ParseFloat(snelslim_fields[2], 64)
				if err != nil {
					panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_norm-snelslim_norm > 0.00000000000001 || mclm_norm-snelslim_norm < -0.00000000000001 { // R has lower significance than go, so we have to adjust for this
					fmt.Println("Normalised score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
				}

				if mclm_nextnextfields[3] != snelslim_fields[3] {
					fmt.Println("Attraction on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				if mclm_nextnextfields[4] != snelslim_fields[4] {
					fmt.Println("Repulsion on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				mclm_min, err = strconv.ParseFloat(mclm_nextnextfields[5], 64)
				if err != nil {
					panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_min, err = strconv.ParseFloat(snelslim_fields[5], 64)
				if err != nil {
					panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_min-snelslim_min > 0.00000000000001 || mclm_min-snelslim_min < -0.00000000000001 {
					fmt.Println("Minimum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_max, err = strconv.ParseFloat(mclm_nextnextfields[6], 64)
				if err != nil {
					panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_max, err = strconv.ParseFloat(snelslim_fields[6], 64)
				if err != nil {
					panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_max-snelslim_max > 0.00000000000001 || mclm_max-snelslim_max < -0.00000000000001 {
					fmt.Println("Maximum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_sd, err = strconv.ParseFloat(mclm_nextnextfields[7], 64)
				if err != nil {
					panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_sd, err = strconv.ParseFloat(snelslim_fields[7], 64)
				if err != nil {
					panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_sd-snelslim_sd > 0.00000000000001 || mclm_sd-snelslim_sd < -0.00000000000001 {
					fmt.Println("StdDev LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_lor, err = strconv.ParseFloat(mclm_nextnextfields[8], 64)
				if err != nil {
					panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_lor, err = strconv.ParseFloat(snelslim_fields[8], 64)
				if err != nil {
					panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_lor-snelslim_lor > 0.00000000000001 || mclm_lor-snelslim_lor < -0.00000000000001 {
					fmt.Println("LOR score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				if mclm_nextfields[1] != snelslim_nextfields[1] {
					fmt.Println("Absolute score on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				// cast normalised scores to floats for comparison
				mclm_norm, err = strconv.ParseFloat(mclm_nextfields[2], 64)
				if err != nil {
					panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_norm, err = strconv.ParseFloat(snelslim_nextfields[2], 64)
				if err != nil {
					panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_norm-snelslim_norm > 0.00000000000001 || mclm_norm-snelslim_norm < -0.00000000000001 { // R has lower significance than go, so we have to adjust for this
					fmt.Println("Normalised score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
				}

				if mclm_nextfields[3] != snelslim_nextfields[3] {
					fmt.Println("Attraction on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				if mclm_nextfields[4] != snelslim_nextfields[4] {
					fmt.Println("Repulsion on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				mclm_min, err = strconv.ParseFloat(mclm_nextfields[5], 64)
				if err != nil {
					panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_min, err = strconv.ParseFloat(snelslim_nextfields[5], 64)
				if err != nil {
					panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_min-snelslim_min > 0.00000000000001 || mclm_min-snelslim_min < -0.00000000000001 {
					fmt.Println("Minimum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_max, err = strconv.ParseFloat(mclm_nextfields[6], 64)
				if err != nil {
					panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_max, err = strconv.ParseFloat(snelslim_nextfields[6], 64)
				if err != nil {
					panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_max-snelslim_max > 0.00000000000001 || mclm_max-snelslim_max < -0.00000000000001 {
					fmt.Println("Maximum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_sd, err = strconv.ParseFloat(mclm_nextfields[7], 64)
				if err != nil {
					panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_sd, err = strconv.ParseFloat(snelslim_nextfields[7], 64)
				if err != nil {
					panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_sd-snelslim_sd > 0.00000000000001 || mclm_sd-snelslim_sd < -0.00000000000001 {
					fmt.Println("StdDev LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_lor, err = strconv.ParseFloat(mclm_nextfields[8], 64)
				if err != nil {
					panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_lor, err = strconv.ParseFloat(snelslim_nextfields[8], 64)
				if err != nil {
					panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_lor-snelslim_lor > 0.00000000000001 || mclm_lor-snelslim_lor < -0.00000000000001 {
					fmt.Println("LOR score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				i++
				i++
				continue
			} else if mclm_fields[0] == snelslim_nextfields[0] && mclm_nextnextfields[0] == snelslim_fields[0] && mclm_nextfields[0] == snelslim_nextnextfields[0] {
				fmt.Println("Line " + strconv.Itoa(i) + " is switched with the next 2 lines (due to identical LOR), making switched comparison")

				if mclm_fields[1] != snelslim_nextfields[1] {
					fmt.Println("Absolute score on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				// cast normalised scores to floats for comparison
				mclm_norm, err := strconv.ParseFloat(mclm_fields[2], 64)
				if err != nil {
					panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_norm, err := strconv.ParseFloat(snelslim_nextfields[2], 64)
				if err != nil {
					panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_norm-snelslim_norm > 0.00000000000001 || mclm_norm-snelslim_norm < -0.00000000000001 { // R has lower significance than go, so we have to adjust for this
					fmt.Println("Normalised score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
				}

				if mclm_fields[3] != snelslim_nextfields[3] {
					fmt.Println("Attraction on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				if mclm_fields[4] != snelslim_nextfields[4] {
					fmt.Println("Repulsion on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				mclm_min, err := strconv.ParseFloat(mclm_fields[5], 64)
				if err != nil {
					panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_min, err := strconv.ParseFloat(snelslim_nextfields[5], 64)
				if err != nil {
					panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_min-snelslim_min > 0.00000000000001 || mclm_min-snelslim_min < -0.00000000000001 {
					fmt.Println("Minimum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_max, err := strconv.ParseFloat(mclm_fields[6], 64)
				if err != nil {
					panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_max, err := strconv.ParseFloat(snelslim_nextfields[6], 64)
				if err != nil {
					panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_max-snelslim_max > 0.00000000000001 || mclm_max-snelslim_max < -0.00000000000001 {
					fmt.Println("Maximum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_sd, err := strconv.ParseFloat(mclm_fields[7], 64)
				if err != nil {
					panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_sd, err := strconv.ParseFloat(snelslim_nextfields[7], 64)
				if err != nil {
					panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_sd-snelslim_sd > 0.00000000000001 || mclm_sd-snelslim_sd < -0.00000000000001 {
					fmt.Println("StdDev LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_lor, err := strconv.ParseFloat(mclm_fields[8], 64)
				if err != nil {
					panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_lor, err := strconv.ParseFloat(snelslim_nextfields[8], 64)
				if err != nil {
					panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_lor-snelslim_lor > 0.00000000000001 || mclm_lor-snelslim_lor < -0.00000000000001 {
					fmt.Println("LOR score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				if mclm_nextnextfields[1] != snelslim_fields[1] {
					fmt.Println("Absolute score on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				// cast normalised scores to floats for comparison
				mclm_norm, err = strconv.ParseFloat(mclm_nextnextfields[2], 64)
				if err != nil {
					panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_norm, err = strconv.ParseFloat(snelslim_fields[2], 64)
				if err != nil {
					panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_norm-snelslim_norm > 0.00000000000001 || mclm_norm-snelslim_norm < -0.00000000000001 { // R has lower significance than go, so we have to adjust for this
					fmt.Println("Normalised score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
				}

				if mclm_nextnextfields[3] != snelslim_fields[3] {
					fmt.Println("Attraction on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				if mclm_nextnextfields[4] != snelslim_fields[4] {
					fmt.Println("Repulsion on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				mclm_min, err = strconv.ParseFloat(mclm_nextnextfields[5], 64)
				if err != nil {
					panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_min, err = strconv.ParseFloat(snelslim_fields[5], 64)
				if err != nil {
					panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_min-snelslim_min > 0.00000000000001 || mclm_min-snelslim_min < -0.00000000000001 {
					fmt.Println("Minimum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_max, err = strconv.ParseFloat(mclm_nextnextfields[6], 64)
				if err != nil {
					panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_max, err = strconv.ParseFloat(snelslim_fields[6], 64)
				if err != nil {
					panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_max-snelslim_max > 0.00000000000001 || mclm_max-snelslim_max < -0.00000000000001 {
					fmt.Println("Maximum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_sd, err = strconv.ParseFloat(mclm_nextnextfields[7], 64)
				if err != nil {
					panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_sd, err = strconv.ParseFloat(snelslim_fields[7], 64)
				if err != nil {
					panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_sd-snelslim_sd > 0.00000000000001 || mclm_sd-snelslim_sd < -0.00000000000001 {
					fmt.Println("StdDev LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_lor, err = strconv.ParseFloat(mclm_nextnextfields[8], 64)
				if err != nil {
					panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_lor, err = strconv.ParseFloat(snelslim_fields[8], 64)
				if err != nil {
					panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_lor-snelslim_lor > 0.00000000000001 || mclm_lor-snelslim_lor < -0.00000000000001 {
					fmt.Println("LOR score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				if mclm_nextfields[1] != snelslim_nextnextfields[1] {
					fmt.Println("Absolute score on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				// cast normalised scores to floats for comparison
				mclm_norm, err = strconv.ParseFloat(mclm_nextfields[2], 64)
				if err != nil {
					panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_norm, err = strconv.ParseFloat(snelslim_nextnextfields[2], 64)
				if err != nil {
					panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_norm-snelslim_norm > 0.00000000000001 || mclm_norm-snelslim_norm < -0.00000000000001 { // R has lower significance than go, so we have to adjust for this
					fmt.Println("Normalised score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
				}

				if mclm_nextfields[3] != snelslim_nextnextfields[3] {
					fmt.Println("Attraction on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				if mclm_nextfields[4] != snelslim_nextnextfields[4] {
					fmt.Println("Repulsion on line " + strconv.Itoa(i) + " does not match")
					globalerror = true
					continue
				}

				mclm_min, err = strconv.ParseFloat(mclm_nextfields[5], 64)
				if err != nil {
					panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_min, err = strconv.ParseFloat(snelslim_nextnextfields[5], 64)
				if err != nil {
					panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_min-snelslim_min > 0.00000000000001 || mclm_min-snelslim_min < -0.00000000000001 {
					fmt.Println("Minimum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_max, err = strconv.ParseFloat(mclm_nextfields[6], 64)
				if err != nil {
					panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_max, err = strconv.ParseFloat(snelslim_nextnextfields[6], 64)
				if err != nil {
					panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_max-snelslim_max > 0.00000000000001 || mclm_max-snelslim_max < -0.00000000000001 {
					fmt.Println("Maximum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_sd, err = strconv.ParseFloat(mclm_nextfields[7], 64)
				if err != nil {
					panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_sd, err = strconv.ParseFloat(snelslim_nextnextfields[7], 64)
				if err != nil {
					panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_sd-snelslim_sd > 0.00000000000001 || mclm_sd-snelslim_sd < -0.00000000000001 {
					fmt.Println("StdDev LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				mclm_lor, err = strconv.ParseFloat(mclm_nextfields[8], 64)
				if err != nil {
					panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				snelslim_lor, err = strconv.ParseFloat(snelslim_nextnextfields[8], 64)
				if err != nil {
					panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
				}
				if mclm_lor-snelslim_lor > 0.00000000000001 || mclm_lor-snelslim_lor < -0.00000000000001 {
					fmt.Println("LOR score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
					globalerror = true
					continue
				}

				i++
				i++
				continue
			} else {
				fmt.Println("Marker on line " + strconv.Itoa(i) + " does not match, please investigate. Checks were done for 2 or 3 rows to be mixed, more require manual checks")
				globalerror = true
				continue
			}
		}

		if mclm_fields[1] != snelslim_fields[1] {
			fmt.Println("Absolute score on line " + strconv.Itoa(i) + " does not match")
			globalerror = true
			continue
		}

		// cast normalised scores to floats for comparison
		mclm_norm, err := strconv.ParseFloat(mclm_fields[2], 64)
		if err != nil {
			panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
		}
		snelslim_norm, err := strconv.ParseFloat(snelslim_fields[2], 64)
		if err != nil {
			panic("Value for normalised field on line " + strconv.Itoa(i) + " can't be converted to a float")
		}
		if mclm_norm-snelslim_norm > 0.00000000000001 || mclm_norm-snelslim_norm < -0.00000000000001 { // R has lower significance than go, so we have to adjust for this
			fmt.Println("Normalised score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
			globalerror = true
		}

		if mclm_fields[3] != snelslim_fields[3] {
			fmt.Println("Attraction on line " + strconv.Itoa(i) + " does not match")
			globalerror = true
			continue
		}

		if mclm_fields[4] != snelslim_fields[4] {
			fmt.Println("Repulsion on line " + strconv.Itoa(i) + " does not match")
			globalerror = true
			continue
		}

		mclm_min, err := strconv.ParseFloat(mclm_fields[5], 64)
		if err != nil {
			panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
		}
		snelslim_min, err := strconv.ParseFloat(snelslim_fields[5], 64)
		if err != nil {
			panic("Value for minimum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
		}
		if mclm_min-snelslim_min > 0.00000000000001 || mclm_min-snelslim_min < -0.00000000000001 {
			fmt.Println("Minimum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
			globalerror = true
			continue
		}

		mclm_max, err := strconv.ParseFloat(mclm_fields[6], 64)
		if err != nil {
			panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
		}
		snelslim_max, err := strconv.ParseFloat(snelslim_fields[6], 64)
		if err != nil {
			panic("Value for maximum LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
		}
		if mclm_max-snelslim_max > 0.00000000000001 || mclm_max-snelslim_max < -0.00000000000001 {
			fmt.Println("Maximum LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
			globalerror = true
			continue
		}

		mclm_sd, err := strconv.ParseFloat(mclm_fields[7], 64)
		if err != nil {
			panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
		}
		snelslim_sd, err := strconv.ParseFloat(snelslim_fields[7], 64)
		if err != nil {
			panic("Value for StdDev LOR on line " + strconv.Itoa(i) + " can't be converted to a float")
		}
		if mclm_sd-snelslim_sd > 0.00000000000001 || mclm_sd-snelslim_sd < -0.00000000000001 {
			fmt.Println("StdDev LOR on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
			globalerror = true
			continue
		}

		mclm_lor, err := strconv.ParseFloat(mclm_fields[8], 64)
		if err != nil {
			panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
		}
		snelslim_lor, err := strconv.ParseFloat(snelslim_fields[8], 64)
		if err != nil {
			panic("Value for LOR score on line " + strconv.Itoa(i) + " can't be converted to a float")
		}
		if mclm_lor-snelslim_lor > 0.00000000000001 || mclm_lor-snelslim_lor < -0.00000000000001 {
			fmt.Println("LOR score on line " + strconv.Itoa(i) + " differs more than 0.00000000000001")
			globalerror = true
			continue
		}
	}
	if globalerror {
		fmt.Println("An error has occured, the test might be unsuccessful. Please check the details above.")
	} else {
		fmt.Println("Test successful! These results are identical")
	}
}

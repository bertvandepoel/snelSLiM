# snelSLiM

A linguistic set of tools in Go and web interface in PHP to do quick Stable Lexical Marker Analysis.

## Supported formats

Alpino XML, TEI XML BNC/Brown Corpus Variant, CoNLL, DCOI XML, Eindehoven corpus, FoLiA XML, Gysseling corpus, OANC, OANC MASC, plain text and XML with XPath query.

## Getting Started

### Prerequisites

* Unix-style Operating system
* Golang 1.8
* PHP 5.5 (might work with older versions)
  * With no restrictions on the use of shell_exec
  * Preferably the option to enlarge upload_max_filesize and post_max_size
* MySQL
* xmllint (usually part of libxml)
* gcc (for foliafolie)
* Basic tools: unzip, tar, sed, bash

### Installing
> We assume you go to the correct folder for these commands.

Compile every parser 

```
go build parser.go
```

Compile the preparser

```
go build preparser.go
```

Compile the analyzer

```
go build analyzer.go
```

It is highly advised you compile the most recent version of foliafolie from https://github.com/VincentVanlaer/foliafolie

```
gcc foliafolie.c -std=c99 -Ofast -o foliafolie
```

Import the database structure from db.sql using the mysql command line client or your favourite database administration tool. 

Point your webserver documentroot to the web folder. You should be able to login using test@example.com with password test. Of course you will want to create new accounts and delete the test account.

### Testing functionality

To test whether the application is working correctly, first try and upload a zip and a tar of plain text files from My Corpora. If those succeed move on to FoLiA fast and another XML format. Then try and generate a full report. When corpora or uploads fail, check your webserver's error logs as well as potential error files in the preparsed, reports and unpacked folders in the slm folder. 

## Code quality

This is my first project with elements in Go. I am quite aware my error handling is far from perfect and my handling of sorting in the analyzer is not as clean and readable as it could have been. I however look forward to learning more about go. 

## Contributing

I will certainly accept pull requests for bug fixes, extra formats, code cleanup and extra association measures. I am however not looking at external rewrites.

Feel free to report issues and file feature requests on GitHub.

Under the terms of the AGPL you are free to adapt my format parsers for your project (or any other part of the code of course). I hope they can be of more broad use in the future. 

## License

This project is licensed under the AGPL license - see the [LICENSE](LICENSE) file for details

## Acknowledgements

* This project was developed as my Bachelorpaper for my Bachelor in Linguistics and Literature under the supervision of Prof. dr. Dirk Speelman
* Further acknowledgements see the [ACKNOWLEDGEMENTS](ACKNOWLEDGEMENTS) file


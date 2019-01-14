# snelSLiM

A linguistic set of tools in Go and web interface in PHP to do quick Stable Lexical Marker Analysis.

## Supported formats

Alpino XML, TEI XML BNC/Brown Corpus Variant, CoNLL, DCOI XML, Eindehoven corpus, FoLiA XML, Gysseling corpus, OANC, OANC MASC, plain text and XML with XPath query.

## Screenshots

![screenshot main page](/screenshots/overview.png?raw=true)
![screenshot corpora overview](/screenshot2.png?raw=true)
![screenshot report overview](/screenshot3.png?raw=true)
![screenshot report details](/report.png?raw=true)

## Getting Started

### Prerequisites

#### Build requirements ####

* gcc (for foliafolie)
* Golang 1.8 or higher

#### Hosting requirements ####

* Unix-style Operating system
  * Basic tools: unzip, tar, sed, bash (should be installed by default)
* PHP 5.5 (or higher, including PHP 7.2)
  * With no restrictions on the use of shell_exec
  * Preferably the option to enlarge upload_max_filesize and post_max_size
* MySQL
* xmllint (usually part of libxml)


### Installing
> We assume you go to the correct folder for these commands.

If you are not supplied with a version of this software that includes binaries, you will have to build them. After clonen the repository, execute the build script.

```
./build.sh
```

Upload all files including the binaries to your hosting.

Import the database structure from db.sql using the mysql command line client or your favourite database administration tool. 

Point your webserver documentroot to the web folder. You should be able to login using test@example.com with password test. Of course you will want to create new accounts and delete the test account.

### Testing functionality

To test whether the application is working correctly, first try and upload a zip and a tar of plain text files from My Corpora. If those succeed move on to FoLiA fast and another XML format. Then try and generate a full report. When corpora or uploads fail, check your webserver's error logs as well as potential error files in the preparsed, reports and unpacked folders in the slm folder. 


## Contributing

I will certainly accept pull requests for bug fixes, extra formats, code cleanup and extra association measures. I am however not looking at external rewrites.

Feel free to report issues and file feature requests on GitHub.

Under the terms of the AGPL you are free to adapt my format parsers for your project (or any other part of the code of course). I hope they can be of more broad use in the future. 

## License

This project is licensed under the AGPL license - see the [LICENSE](LICENSE) file for details

## Acknowledgements

* This project was developed as my Bachelorpaper for my Bachelor in Linguistics and Literature under the supervision of Prof. dr. Dirk Speelman
* Further acknowledgements see the [ACKNOWLEDGEMENTS](ACKNOWLEDGEMENTS) file


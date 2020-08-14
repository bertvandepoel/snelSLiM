# snelSLiM

A linguistic set of tools in Go and web interface in PHP to do quick Stable Lexical Marker Analysis and investigate the results.

## Contact information

If you have suggestions for features, feedback or a question, feel free to contact to create a GitHub issue or me about snelSLiM at bert.vandepoel AT student.kuleuven DOT be.

Having issues building or installing my software? I would love to have more users at different universities, so I don't mind if you email me for help or advice!

## Supported formats

Alpino XML, TEI XML BNC/Brown Corpus Variant, CoNLL, DCOI XML, Eindhoven corpus, FoLiA XML, Gysseling corpus, PRAAT TextGrid, XCES GrAF, plain text and XML with XPath query.

## Screenshots

![screenshot main page](/screenshots/form.png?raw=true)
![screenshot main page with forms opened](/screenshots/formdetailed.png?raw=true)
![screenshot my corpora list](/screenshots/mycorpora.png?raw=true)
![screenshot my reports list](/screenshots/myreports.png?raw=true)
![screenshot report](/screenshots/report.png?raw=true)
![screenshot report: keyword details](/screenshots/markerdetail.png?raw=true)

## Installation

SnelSLiM is a web application, it can easily be installed on cheap shared web hosting, a VPS or private (virtual or physical) server. This makes it possible for individuals, research groups, companies and faculties to deploy snelSLiM on infrastructure that suits their needs. Of course, more storage makes it possible to store larger corpora, and more CPU cores and power greatly increases analysis speed.

Please refer to the installation guide in the [INSTALL.md](INSTALL.md) file for details about the requirements, installation and configuration of snelSLiM.

## Contributing

I will certainly accept pull requests for bug fixes, extra formats, code cleanup and new functionality (if useful). I am however not looking at external rewrites. By submitting submitting a pull request, you agree to license your submission under the same license as this project, the AGPL.

Feel free to report issues and file feature requests on GitHub.

Under the terms of the AGPL you are free to adapt my format parsers for your project (or any other part of the code of course). I hope they can be of more broad use in the future. 

## License

This project is licensed under the AGPL license - see the [LICENSE](LICENSE) file for details

## Acknowledgements

* This project was initially developed as my Bachelor Paper for my Bachelor in Linguistics and Literature under the supervision of Prof. Dr. Dirk Speelman
* I have largely rewritten the application, correcting mistakes in the statistics, extending the functionality and improving user experience as my Master Thesis for my Master in Linguistics under the supervision of Prof. Dr. Dirk Speelman
* Development has continued for the Thesis of my Advanced Master in Advanced Studies of Linguistics under the supervision of Prof. Dr. Dirk Speelman, introducing new visualizations, checks for corpus quality, new corpus formats, the option to share reports, demo mode, multithreading and many small improvements
* Further acknowledgements see the [ACKNOWLEDGEMENTS](ACKNOWLEDGEMENTS) file


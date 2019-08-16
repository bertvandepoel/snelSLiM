library(mclm)

files_a <- dir(path = "target", recursive = TRUE, full.names = TRUE, pattern="\\.txt$")
files_b <- dir(path = "reference", recursive = TRUE, full.names = TRUE, pattern="\\.txt$")

analysis <- slma(files_a, files_b, , keep_intermediate = FALSE, max_rank = 5000)

df <- as.data.frame(analysis)
slor <- df["S_lor"]
sorted_analysis <- df[with(df, order(-slor)), ]

write.table(sorted_analysis, file='result_mclm/result.tsv', quote=FALSE, sep='\t', row.names = FALSE, col.names = FALSE)

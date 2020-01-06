/***
 *  Foliafolie - A fast parser for to extract lemma and text elements from folia xml
 *  Copyright (C) 2017 Vincent Vanlaer
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>. 
***/


#include <stdio.h>
#include <stdbool.h>
#include <string.h>
#include <unistd.h>
#include <fcntl.h>

//#define FOLIA_DEBUG

#ifdef FOLIA_DEBUG
#define folia_log(string, ...) fprintf(stderr, string, ##__VA_ARGS__)
#else
#define folia_log(string, ...)
#endif


#define check_quoted(callback, write_out) if (buffer[i] == '"') { \
	next_step = &&quoted; \
	after_quoted = callback; \
	write_out_quoted = write_out; \
    letters_found = 0; \
	continue;}

#define check_letter(letter) if (buffer[i] == letter) { \
	letters_found++; \
} \
else { letters_found = 0; }

#define finished_letters(count, callback) if(letters_found == count) {  folia_log("\nState change to " #callback "\n" ); letters_found = 0; next_step = callback; continue;  }

const char w_begin[] = "<w ";
const char w_end[] = "</w>";
const char t_begin[] = "<t>";
const char t_ignore_begin[] = "<t ";
const char t_end[] = "</t>";
const char lemma_begin[] = "<lemma ";
const char alt_begin[] = "<alt";
const char alt_end[] = "</alt>";
const char w_class[] = "class=";

void *next_step;
void *after_quoted;
bool write_out_quoted;

unsigned int letters_found;
char t_buffer[3];
int t_buffer_next;

unsigned int quoted_count, not_in_w_count, in_w_count, not_in_lemma_count; 


void print_char_something(char c) {
	putc(c, stdout);
}



int
main( int argc, char *argv[] )
{
	next_step = &&not_in_w;
	letters_found = 0;

	unsigned short n;
	char buffer[4096];
	quoted_count = 0;
	not_in_w_count = 0;
	in_w_count = 0;
	not_in_lemma_count = 0;

	int fd = 0;

	if (argc == 2) {
		fd = open(argv[1], O_RDONLY);
	}

	while ((n = read(fd, buffer, 4096)) > 0) {
		for (int i = 0; i < n; i++) {
	
			folia_log("%c", buffer[i]);

			goto *next_step;
quoted:
#ifdef FOLIA_DEBUG
			quoted_count++;
#endif
			if (buffer[i] == '"') {
				if (write_out_quoted) {
					print_char_something(' ');
				}
				next_step = after_quoted;
				continue;
			}
			
			if (write_out_quoted) {
				print_char_something(buffer[i]);
			}

			continue;
		
pre_not_in_w:	
			print_char_something('\n');
			next_step = &&not_in_w;
not_in_w:
#ifdef FOLIA_DEBUG
			not_in_w_count++;
#endif
			//check_quoted(&&not_in_w, false);
			check_letter(w_begin[letters_found]);
			finished_letters(strlen(w_begin), &&in_w);

			continue;

in_w:
#ifdef FOLIA_DEBUG
			in_w_count++;
#endif
			check_quoted(&&in_w, false);
			check_letter(w_class[letters_found]);
			finished_letters(strlen(w_class), &&reading_class);

			continue;

reading_class:

			check_quoted(&&not_in_lemma, true);

			fprintf(stderr, "Couldn't find '\"' after 'class='");
			return 1;

space_pre_not_in_lemma:
			print_char_something(' ');
			next_step = &&not_in_lemma;

not_in_lemma:
#ifdef FOLIA_DEBUG
			not_in_lemma_count++;
#endif
//			check_quoted(&&not_in_lemma, false);

			if (buffer[i] == '<') {
				folia_log("\nStarting candidates\n");
				next_step = &&not_in_lemma_candidate_start;
			}

			continue;

not_in_lemma_candidate_start:

			switch (buffer[i]) {
				case 't':
					next_step = &&not_in_lemma_candidate_t;
					continue;
				case 'a':
					next_step = &&not_in_lemma_candidate_a;
					letters_found = 2;
					continue;
				case 'l':
					next_step = &&not_in_lemma_candidate_l;
					letters_found = 2;
					continue;
				case '/':
					next_step = &&not_in_lemma_candidate_w;
					letters_found = 2;
					continue;
				default:
					next_step = &&not_in_lemma;
					continue;					
			}

not_in_lemma_candidate_t:

			switch (buffer[i]) {
				case ' ':
					folia_log("\nCandidate '<t ' found\n");
					next_step = &&in_t_ignore;
					letters_found = 0;
					continue;
				case '>':
					next_step = &&pre_in_t;
					folia_log("\nCandidate '<t>' found\n");
					letters_found = 0;
					continue;
				default:
					next_step = &&not_in_lemma;
					continue;
			}

not_in_lemma_candidate_l:

			if (buffer[i] == lemma_begin[letters_found]) {
				if (letters_found == strlen(lemma_begin) - 1) {
					folia_log("\nCandidate '<lemma ' found\n");
					letters_found = 0;
					next_step = &&in_lemma;
					continue;
				}
				
				letters_found++;
			}
			else {
				next_step = &&not_in_lemma;
			}

			continue;

not_in_lemma_candidate_a:

			if (buffer[i] == alt_begin[letters_found]) {
				if (letters_found == strlen(alt_begin) - 1) {
					folia_log("\nCandidate '<alt ' found\n");
					letters_found = 0;
					next_step = &&in_alt;
				}
				
				letters_found++;
			}
			else {
				next_step = &&not_in_lemma;
			}

			continue;

not_in_lemma_candidate_w:

			if (buffer[i] == w_end[letters_found]) {
				if (letters_found == strlen(w_end) - 1) {
					folia_log("\nCandidate '</w>' found\n");
					print_char_something('\n');
					letters_found = 0;
					next_step = &&not_in_w;
				}
				
				letters_found++;
			}
			else {
				next_step = &&not_in_lemma;
			}

			continue;

in_lemma:
			check_quoted(&&in_lemma, false);
			check_letter(w_class[letters_found]);
			finished_letters(strlen(w_class), &&reading_class);

			continue;

pre_in_t:
			t_buffer[0] = '-';
			t_buffer[1] = 't';
			t_buffer[2] = ':';

			t_buffer_next = 0;

			next_step = &&in_t;
			letters_found = 0;

in_t:
			//fprintf(stderr, "%d %d\n", t_buffer_next, letters_found);
			check_letter(t_end[letters_found]);
			finished_letters(strlen(t_end), &&space_pre_not_in_lemma);

			print_char_something(t_buffer[t_buffer_next]);
			t_buffer[t_buffer_next] = buffer[i];
			t_buffer_next = ((t_buffer_next + 1) % 3);

			continue;

in_t_preamble_ignore:

			check_quoted(&&in_t_preamble_ignore, false);

			if (buffer[i] == '>') next_step = &&in_t_ignore;

			continue;

in_t_ignore:
			
			check_letter(t_end[letters_found]);
			finished_letters(strlen(t_end), &&not_in_lemma);

			continue;
in_alt:

			check_quoted(&&in_alt, false);
			check_letter(alt_end[letters_found]);
			finished_letters(strlen(alt_end), &&not_in_lemma);
			continue;

		}
	}
	folia_log("\n%d %d %d %d\n", quoted_count, not_in_w_count, in_w_count, not_in_lemma_count);
}


# extractSubtitles
PHP cli to extract subtitle from video files with numerous options. ffmpeg does a (really) great job but on video files with a lot embedded subtitles things get a bit messy. This script allows for extraction of said subtitles with numerous UI friendly(-ish) options, most important of which is the language based on title (e.g. for cases that the language attibute for value 'eng' has 2 options, the title 'English' and 'English [SDH]') without having to guess

  Input arguments
  -i	 		    parameter required, if set requires value: the input file to extract subtitles from
  -v          parameter optional, accepts no value: makes the execution verbose
  -o	 		    parameter optional, if set requires value: the filename (including extension) of the output / extracted subtitle, default value is equal to input file with extension srt. If set, takes precedence over --type, i.e. -o a.srt --type vtt will lead to srt formating and filename extension
  --ext 		  parameter optional, if set requires value: the extension (and implicitly the format) of the subtitle to export, default value is srt
  --language	parameter optional, if set requires value: extract the subtitles with language definition equal to value of argument
  --title		  parameter optional, if set requires value: extract the subtitles with title definition equal to value of argument
  --auto		  parameter optional, accepts no value: attempts to extract the subtitles with title='English [SDH]', else with title='English', else with title='Greek', else asks the user (change titles and ordering at the $defaultTitles variable

The script requires ffmpeg & ffprobe to be installed and callable from the command line

Execution example - At the command line run
  php extractSubtitles.php -v -i aMovie.mp4 --language=ita
  php extractSubtitles.php -i path/to/aMovie.mp4 --auto -ext vvt

# extractSubtitles
PHP cli to extract subtitle from video files with numerous options. ffmpeg does a (really) great job but on video files with a lot embedded subtitles things get a bit messy. This script allows for extraction of said subtitles with numerous UI friendly(-ish) options, most important of which is the language based on title (e.g. for cases that the language attibute for value 'eng' has 2 options, the title 'English' and 'English [SDH]') without having to guess

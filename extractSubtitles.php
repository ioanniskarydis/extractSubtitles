<?php 
	/*
		Input arguments
		-i	 		parameter required, if set requires value: the input file to extract subtitles from
		-v	 		parameter optional, accepts no value: makes the execution verbose
		-o	 		parameter optional, if set requires value: the filename (including extension) of the output / extracted subtitle, default value is equal to input file with extension srt. If set, takes precedence over --type, i.e. -o a.srt --type vtt will lead to srt formating and filename extension
		--ext 		parameter optional, if set requires value: the extension (and implicitly the format) of the subtitle to export, default value is srt
		--language	parameter optional, if set requires value: extract the subtitles with language definition equal to value of argument
		--title		parameter optional, if set requires value: extract the subtitles with title definition equal to value of argument
		--auto		parameter optional, accepts no value: attempts to extract the subtitles with title='English [SDH]', else with 	title='English', else with title='Greek', else asks the user (change titles and ordering at the $defaultTitles variable
		--overwrite	parameter optional, accepts no value: if the output file exists, then auto overwrite, otherwise user's verification is required
		
		e.g.: 	php extractSubtitles.php -v -i aMovie.mp4 --language=ita
				php extractSubtitles.php -i aMovie.mp4 --auto -ext vvt
		
		Created by Ioannis Karydis https://users.ionio.gr/~karydis/
		The GNU General Public License v3.0 - https://tldrlegal.com/license/gnu-general-public-license-v3-(gpl-3)
	*/
	
	//Check ffmpeg && ffprobe are available
	if (command_exists("ffmpeg") === false || command_exists("ffprobe") === false)
		die ("\033[31mThis script requires ffmpeg & ffprobe accessible by their respective commands which seem to not be available, exiting.\n");
	
	//Get the parameters
	$options = getopt("vi:o:", array("language:", "title:", "auto", "ext:", "overwrite"));
	
	//Default title ordering for --auto
	$defaultTitles = array('English [SDH]', 'English', 'Greek');
	
	//Make execution verbose
	$verbose = false;
	if (isset($options['v']) === true)
		$verbose = true;
	
	//Check -i is set and the file to operate on exists
	if (isset($options['i']) === false)
		die ("\033[31mNo input, exiting.\n");
	elseif (file_exists($options['i']) === false)
		die ("\033[31mInput file not found, exiting.\n");
	else {
		
		//Split the filename to parts in order to change name the subtitle to export
		$path_parts = pathinfo($options['i']);
		
		//Address the extension / formating of the exported subtitle
		$subtitleExt = "srt";
		if (isset($options['ext']) === true && $options['ext'] !== false) {
			if (strpos($options['ext'], ".") === 0)
				$options['ext'] = substr($options['ext'], 1);
			
			$subtitleExt = $options['ext'];
		}
		
		//Address the filename of the exported subtitle
		$subtitleFilename = $path_parts['dirname']."/".$path_parts['filename'].".".$subtitleExt;
		if (isset($options['o']) === true && $options['o'] !== false) {
			$subtitleFilename = $options['o'];
		}
		
		//Check and deal with already existing same filename for output
		$reply = null;
		$overwrite = false;
		while ($reply === null && file_exists($subtitleFilename) && isset($options['overwrite']) !== true) {
			$reply = readline('File already exists. Overwrite? [y/N] ');
			
			if (strcasecmp($reply, 'y') === 0)
				$overwrite = true;
			elseif (strcasecmp($reply, '') === 0 || strcasecmp($reply, 'n') === 0)
				die("\033[31mFile exists, overwrite was not allowed, exiting.\r\n");
			else
				$reply = null;
		}
		
		//Get list of all subtitles
		$allSubtitlesCommand = 'ffprobe -loglevel error -select_streams s -show_entries stream=index:stream_tags=language:stream_tags=title -print_format json '.$options['i'];	
		
		//Get details of the available subtitles
		$allSubtitles = null;
		$result_code = null;
		try {
			exec($allSubtitlesCommand, $allSubtitles, $result_code);
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		
		//In case the ffprobe did not execute correctly
		if ($result_code === 1)
			die ("\033[31mResult_code of ffprobe was: $result_code, exiting.\n");
		
		//Combine the result from array to string
		$allSubtitles = implode($allSubtitles, "\r\n");
		
		//Convert string JSON to assoc array
		try {
			$allSubtitles = json_decode($allSubtitles, true);
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			die ("\033[31mThis is not a JSON file\n");
		}
		
		//In case there was an error in the convertion
		if ($allSubtitles === null)
			die ("\033[31mResult_code of ffprobe was not JSON\n");
		
		//Returned assoc array has some extra fields
		$allSubtitles = $allSubtitles['streams'];
		
		//Count all available subtitles
		$noOfAllSubtitles = count($allSubtitles);
		
		//Give some feedback
		if ($verbose) { 
			echo ("\033[32mFound ".count($allSubtitles)." subtitles\033[0m\n");
		}
		
		$extractSubtitleCommand = null;
		
		//Get all available titles in an array
		$allTitles = array_map(function($element){return $element['tags']['title'];}, $allSubtitles);
		
		
		if ($noOfAllSubtitles === 0) {	//If the input file has no sutitles
			die("\033[31mThe file '".$options['i']."' has no embedded subtitles, exiting.\r\n");
		}
		elseif ($noOfAllSubtitles === 1) {	//if only one subtitle exists in the input file, extract it no questions asked
			$extractSubtitleCommand = 'ffmpeg -i "'.$options['i'].'" -map 0:s:0 "'.$subtitleFilename.'"';
		} elseif (isset($options['language']) === true) {	//If the --language is set
			
			//Get all available languages
			$allLanguages = array_map(function($element){return $element['tags']['language'];}, $allSubtitles);
			
			if (in_array($options['language'], $allLanguages))
				$extractSubtitleCommand = 'ffmpeg -i "'.$options['i'].'" -map 0:s:m:language:"'.$options['language'].'" "'.$subtitleFilename.'"';
			else
				die("\033[31mLanguage '".$options["language"]."' does not exist in the input file, exiting.\r\n");
		} elseif (isset($options['title']) === true) {	//If the --title is set
			if (in_array($options['title'], $allTitles))
				$extractSubtitleCommand = 'ffmpeg -i "'.$options['i'].'" -map 0:s:m:title:"'.$options['title'].'" "'.$subtitleFilename.'"';
			else
				die("\033[31mTitle '".$options['title']."' does not exist in the input file, exiting.\r\n");
		} else {
			//If the --auto is set
			if (isset($options['auto']) === true){	
				
				//Select a subtitle based on title and the $defaultTitles array's values (ordered)
				foreach ($defaultTitles as $aDefaultTitle) {
					if (in_array($aDefaultTitle, $allTitles)) {
						$extractSubtitleCommand = 'ffmpeg -i "'.$options['i'].'" -map 0:s:m:title:"'.$aDefaultTitle.'" "'.$subtitleFilename.'"';
						
						break;
					}
				} 
			} 
			
			//If still there's no selection of what subtitle to extract
			if ($extractSubtitleCommand === null) {
				
				//Show the alternatives
				foreach($allSubtitles as $key => $aSubtitle)
					echo "\t".($key+1)." Title: ".$aSubtitle['tags']['title']." (language: ".$aSubtitle['tags']['language'].")\r\n";
				
				//As long as the user has not selected a valid id
				do {
					$theId = readline('Please select a language id (1 to '.$noOfAllSubtitles.'): ');
				} while(
					is_numeric($theId) === false || 
					(is_numeric($theId) && in_array(intval($theId), range(1,$noOfAllSubtitles)) === false )
				);
				
				$extractSubtitleCommand = 'ffmpeg -i "'.$options['i'].'" -map 0:s:'.(intval($theId)-1).' "'.$subtitleFilename.'"';
			}
		}
		
		//If user allowed the file to be overwritten, add the -y flag to ffmpeg
		if($overwrite === true)
			$extractSubtitleCommand = 'ffmpeg -y '.substr($extractSubtitleCommand, 7);
			
		$result = null;
		$result_code = null;
		
		//Do the actual extraction of the selected subtitle
		try {			
			exec($extractSubtitleCommand, $result, $result_code);
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		
		//In case there's an error
		if ($result_code === 1)
			die ("\033[31mResult_code of ffmpeg was: $result_code\n");
		
	}
	
	//Checks if the command exists in the shell
	function command_exists($cmd) {
		$return = shell_exec(sprintf("which %s", escapeshellarg($cmd)));
		return !empty($return);
	}

?>
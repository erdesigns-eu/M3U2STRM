<?php

/************************************************************************************/
/*																					*/
/*				m3u2strm.php [ ERDesigns.eu Convert M3U to STRM ]					*/
/*																					*/
/*				Author	: Ernst Reidinga											*/
/*				Date 	: 21/11/2020 20:30											*/
/*				Version	: 1.0														*/
/*																					*/
/************************************************************************************/

// Read the command line parameters and parse them into the $_GET array.
parse_str(implode('&', array_slice($argv, 1)), $_GET);

class converter {

	// converter class constructor.
	function __construct () {
		
	}

	// CURL HTTP.
	private function curl_http_get ($url, $useragent = 'Mozilla/5.0 like Gecko', $headers = []) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}

	// Create strm file
	private function create_strm_file ($filename, $content) {
		$directory = dirname($filename);

		echo "Directory: $directory" . PHP_EOL;
		echo "Content: $content" . PHP_EOL;
		echo "Filename: $filename" . PHP_EOL;

		if (!is_dir($directory)) {
		    mkdir($directory , 0777, true);
		}
		$file = fopen($filename, "w");
		fwrite($file, $content);
		fclose($file);
	}


	// Clean filename so we can use it in the zipfile
	private function clean_filename ($filename) {
		return $filename;
		/*return rtrim(mb_ereg_replace("([\.]{2,})", '', mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename)), ' (');*/
	}

	// Try to extract name, season/episode, year
	private function extract_series_info ($filename) {
		if (preg_match("'^(.+)\.*(19\d{2}|20(?:0\d|1[0-9]|2[0-9])).*S([0-9]+).*E([0-9]+).*$'i", $filename, $n)) {
			return [
				'name'		=> $this->clean_filename(trim(rtrim(preg_replace("'\.'", " ", $n[1]), ' - '))),
				'season'	=> str_pad(intval($n[3], 10), 2, '0', STR_PAD_LEFT),
		    	'episode'	=> str_pad(intval($n[4], 10), 2, '0', STR_PAD_LEFT)
			];
		} elseif (preg_match("'^(.+)\.*S([0-9]+).*E([0-9]+).*$'i", $filename, $n)) {
		    return [
		    	'name'		=> $this->clean_filename(trim(rtrim(preg_replace("'\.'", " ", $n[1]), ' - '))),
		    	'season'	=> str_pad(intval($n[2], 10), 2, '0', STR_PAD_LEFT),
		    	'episode'	=> str_pad(intval($n[3], 10), 2, '0', STR_PAD_LEFT)
		    ];
		} else {
			return [
				'name'		=> $this->clean_filename($filename),
		    	'season'	=> '',
		    	'episode'	=> ''
			];
		}
	}

	// Extract movie title and year from filename
	private function extract_movie_info ($filename) {
		if (preg_match("/^.+?(?=\\s*[(.]?(\\d{4}))/mi", $filename, $n)) {
			return [
				'name'	=> $this->clean_filename(trim(rtrim($n[0], '-'))),
				'year'	=> $n[1]
			];
		} else {
			return [
				'name'	=> $this->clean_filename($filename),
				'year'	=> ''
			];
		}
	}

	// Extract year from filename (movie/serie)
	private function extract_year ($filename) {
		if (preg_match("(19\d{2}|20(?:0\d|1[0-9]|2[0-9]))", $filename, $n)) {
			return $n[0];
		}
		return '';
	}

	// Parse M3U file to array
	public function parse_m3u ($url) {
		$content = preg_split('/\r\n|\r|\n/', file_get_contents($url));
		$output  = [];
		$entry   = [];
		$group   = '';
		foreach ($content as $line) {
			if (preg_match('/\#EXTM3U/i', $line)) {
				continue;
			}
			if (preg_match('/\#EXTINF/i', $line)) {
				$group = '';
				if (preg_match('/\#EXTINF:(?P<play_length>-?\d*\.?\d+)/i', $line, $result)) {
					$entry['play_length'] = $result['play_length'];
				}
				if (preg_match('/(?<=channel-id=")(?P<channel_id>.*?)(?=")/i', $line, $result)) {
					$entry['channel_id'] = $result['channel_id'];
				}
				if (preg_match('/(?<=radio=")(?P<radio>.*?)(?=")/i', $line, $result)) {
					$entry['radio'] = json_decode($result['radio']) == true;
				}
				if (preg_match('/(?<=tvg-id=")(?P<tvg_id>.*?)(?=")/i', $line, $result)) {
					$entry['tvg_id'] = $result['tvg_id'];
				}
				if (preg_match('/(?<=tvg-name=")(?P<tvg_name>.*?)(?=")/i', $line, $result)) {
					$entry['tvg_name'] = $result['tvg_name'];
				}
				if (preg_match('/(?<=tvg-logo=")(?P<tvg_logo>.*?)(?=")/i', $line, $result)) {
					$entry['tvg_logo'] = $result['tvg_logo'];
				}
				if (preg_match('/(?<=tvg-shift=")(?P<tvg_shift>.*?)(?=")/i', $line, $result)) {
					$entry['tvg_shift'] = $result['tvg_shift'];
				}
				if (preg_match('/(?<=tvg-chno=")(?P<tvg_chno>.*?)(?=")/i', $line, $result)) {
					$entry['tvg_chno'] = $result['tvg_chno'];
				}
				if (preg_match('/(?<=group-title=")(?P<group_title>.*?)(?=")/i', $line, $result)) {
					$group = $result['group_title'];
				}
				if (preg_match('/(?<=parent-code=")(?P<parent_code>.*?)(?=")/i', $line, $result)) {
					$entry['parent_code'] = $result['parent_code'];
				}
				if (preg_match('/(?<=audio-track=")(?P<audio_track>.*?)(?=")/i', $line, $result)) {
					$entry['audio_track'] = $result['audio_track'];
				}
				if (preg_match('/(?<=,)(?P<name>.*?)$/i', $line, $result)) {
					$entry['name'] = $result['name'];
				} else {
					$entry['name'] = '';
				}
			} elseif (preg_match("#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#iS", $line)) {
				$entry['url'] = $line;
				$entry['type'] = 'Live';
				if (preg_match('/(?P<ext>[0-9a-z]+)(?:[\?#]|$)/i', $line, $result)) {
					$entry['ext'] = $result['ext'];
				} 
				if (preg_match('/.*\/play\/vod\//', $line)) {
					$entry['type'] = 'VOD';
				}
				if (preg_match('/.*\/movie\//i', $line)) {
					$entry['type'] = 'Movie';
				}
				if (preg_match('/.*\/series\//i', $line) || preg_match('/.*\/tvshow\//i', $line)) {
					$entry['type'] = 'Series';
				}
				if (array_key_exists($group, $output)) {
					array_push($output[$group], $entry);
				} else {
					$output[$group] = [$entry];
				}
				$entry = [];
			}
		}
		return $output;
	}

	private function absolute_filename ($directory, $filename) {
		return str_replace('\'', '', join(DIRECTORY_SEPARATOR, array($directory, $filename)));
	}

	// Convert to strm files
	public function convert_strm ($url, $directory, $name_tag = 'tvg_name') {
		$playlist = $this->parse_m3u($url);
		foreach ($playlist as $group => $stations) {
			foreach ($stations as $station) {
				if ($station['type'] === 'Movie') {
					$movie = $this->extract_movie_info($station[$name_tag]);
					$movie_name = !empty($movie['year']) ? sprintf('%s/%s (%s).strm', $this->clean_filename($group), $movie['name'], $movie['year']) : sprintf('%s/%s.strm', $this->clean_filename($group), $movie['name']);
					$this->create_strm_file($this->absolute_filename($directory, $movie_name), $station['url']);
				}
				if ($station['type'] === 'Series') {
					$series = $this->extract_series_info($station[$name_tag]);
					$year   = $this->extract_year($station[$name_tag]);
					$series_season = !empty($series['season']) ? sprintf('S%s', $series['season']) : '';
					$series_episod = !empty($series['episode']) ? sprintf('E%s', $series['episode']) : '';
					$series_filenm = !empty($year) ? sprintf('%s (%s) %s%s', $series['name'], $year, $series_season, $series_episod) : sprintf('%s %s%s', $series['name'], $series_season, $series_episod);
					$series_folder = !empty($year) ? sprintf('%s/%s (%s)/%s.strm', $this->clean_filename($group), $series['name'], $year, trim($series_filenm)) : sprintf('%s/%s/%s.strm', $this->clean_filename($group), $series['name'], trim($series_filenm));
					$this->create_strm_file($this->absolute_filename($directory, $series_folder), $station['url']);
				}
			}
		}
	}
}

$converter = new converter();
echo "Filename / URL: " . $_GET['filename'] . PHP_EOL . "Output Directory: " . $_GET['directory'] . PHP_EOL;
$converter->convert_strm($_GET['filename'], $_GET['directory']);
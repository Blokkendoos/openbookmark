<?php
if (basename($_SERVER['SCRIPT_NAME']) == basename (__FILE__)) {
	die ("no direct access allowed");
}

/*

PHP Grab favicon

### favicon info: https://github.com/audreyr/favicon-cheat-sheet
### source: https://github.com/gaffling/PHP-Grab-Favicon/blob/master/get-fav.php
### Copyright 2019-2020 Igor Gaffling

*/ 


class favicon {

	function favicon($url) {

		global $settings, $convert_favicons;

		if ($settings['show_bookmark_icon']) {
			if ($this->get_favicon($url)) {
				if ($convert_favicons) {
					$this->favicon = $this->convert_favicon();
				}
				else {
					$this->favicon = "./favicons/" . $this->icon_name;
				}
			}
		}
	}

	function get_favicon($url) {
		$retval = false;
		
		// avoid script runtime timeout
		$max_execution_time = ini_get("max_execution_time");
		set_time_limit(0); // 0 = no timelimit

		$url = strtolower($url);
		$domain = $this->check_domain(parse_url($url, PHP_URL_HOST));

		$this->get_favicon_url($url, $domain);
		if (empty($this->favicon_url)) {
			$this->get_favicon_api($url, $domain);
		}

		if ($this->favicon_url) {
			$file_name = basename($this->favicon_url);
			$file_ext = substr(strrchr($file_name,'.'), 1);
			$this->icon_name = rand() . "_" . hash("sha1", $file_name) . ".$file_ext";
			$retval = $this->get_favicon_image();
		}

		// restore script runtime timeout
		set_time_limit($max_execution_time);
		
		return $retval;
	}

	/*
	Try to load the favicon from the given URL
	*/
	function get_favicon_url($url, $domain) {
		$html = $this->load($url);
		// Find Favicon with RegEx
		$regExPattern = '/((<link[^>]+rel=.(icon|shortcut icon|alternate icon)[^>]+>))/i';
		if (@preg_match($regExPattern, $html, $matchTag)) {
			$regExPattern = '/href=(\'|\")(.*?)\1/i';
			if (isset($matchTag[1]) and @preg_match($regExPattern, $matchTag[1], $matchUrl)) {
				if (isset($matchUrl[2])) {
					// Build Favicon Link
					$favicon = $this->rel2abs(trim($matchUrl[2]), 'http://'.$domain.'/');
				}
			}
		}

		// If there is no Match: Try if there is a Favicon in the Root of the Domain
		if (empty($favicon)) { 
			$favicon = 'http://'.$domain.'/favicon.ico';
			// Try to Load Favicon
			if (!@getimagesize($favicon)) {
				$favicon = NULL;
			}
		}

		$this->favicon_url = $favicon;
	}

	/*
	Try to load the favicon using a public API
	*/
	function get_favicon_api($url, $domain) {
		// Select API by Random
		$random = 2;
		$random = rand(1,3);

		// Faviconkit
		if ($random == 1 or empty($favicon)) {
			$favicon = 'https://api.faviconkit.com/'.$domain.'/16';
		}

		// Favicongrabber
		if ($random == 2 or empty($favicon)) {
			$echo = json_decode($this->load('http://favicongrabber.com/api/grab/'.$domain), TRUE);
			// Get Favicon URL from Array out of json data (@ if something went wrong)
			$favicon = @$echo['icons']['0']['src'];
		}

		// Google (check also md5() later)
		if ($random == 3) {
			$favicon = 'http://www.google.com/s2/favicons?domain='.$domain;
		} 

		$this->favicon_url = $favicon;
	}

	function check_domain($domain) {
		$domainParts = explode('.', $domain);
		if (count($domainParts) == 3 and $domainParts[0] != 'www') {
			// with Subdomain (if not www)
			$domain = $domainParts[0].'.'.
					$domainParts[count($domainParts)-2].'.'.
					$domainParts[count($domainParts)-1];

		} else if (count($domainParts) >= 2) {
			// without subdomain
			$domain = $domainParts[count($domainParts)-2].'.'.$domainParts[count($domainParts)-1];

		} else {
			// without http(s)
			$domain = $url;
		}
		return $domain;
	}

	/*
	get and save the favicon image,
	returns true when successful, otherwise false
	*/
	function get_favicon_image() {

		$image = $this->load($this->favicon_url);

		if ($fp = @fopen("./favicons/" . $this->icon_name, "w")) {
			fwrite($fp, $image);
			fclose($fp);
			return true;
		}
		else {
			return false;
		}
	}

	function load($url) {

		// use an agent that is likely to be accepted by the host
		$user_agent = "Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0";

		// use curl or file_get_contents (both with user_agent) and fopen/fread as fallback
		if (function_exists('curl_version')) {

			$ch = curl_init($url);

			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$content = curl_exec($ch);
			curl_close($ch);

			unset($ch);

		} else {

			$context = array('http' => array('user_agent' => $user_agent,));
			$context = stream_context_create($context);

			if (function_exists('file_get_contents')) {

				$content = file_get_contents($url, NULL, $context);

			} else {
				$fh = fopen($url, 'r', FALSE, $context);
				$content = '';
				while (!feof($fh)) {
					$content .= fread($fh, 128); // because filesize() will not work on URLS?
				}
				fclose($fh);
			}
		}
		return $content;
	}

	/* make absolute URL from relative */
	function rel2abs($rel, $base) {
		extract(parse_url( $base ));

		if (strpos($rel,"//") === 0) return $scheme . ':' . $rel;
		if (parse_url( $rel, PHP_URL_SCHEME) != '' ) return $rel;
		if ($rel[0] == '#' or $rel[0] == '?') return $base . $rel;

		$path = preg_replace('#/[^/]*$#', '', $path);
		if ($rel[0] == '/') $path = '';

		$abs = $host . $path . "/" . $rel;
		$abs = preg_replace("/(\/\.?\/)/", "/", $abs);
		$abs = preg_replace("/\/(?!\.\.)[^\/]+\/\.\.\//", "/", $abs);

		return $scheme . '://' . $abs;
	}

	/*
	check the image type and convert & resize it if required
	returns the absolute path of the (converted) .png file 
	*/
	function convert_favicon() {
		global $convert, $identify;

		$tmp_file = "./favicons/" . $this->icon_name;

		# find out file type
		if (@exec("$identify $tmp_file", $output)) {
			$ident = explode(" ", $output[0]);
			if (count($output) > 1) {
				$file_to_convert = $ident[0];
			}
			else {
				$file_to_convert = $tmp_file;
			}
			# convert image in any case to 16x16 and .png
			system("$convert $file_to_convert -resize 16x16 $tmp_file.png");
			@unlink($tmp_file);
			return $tmp_file . ".png";
		}
		else {
			@unlink ($tmp_file);
			return false;
		}
	}
}

?>

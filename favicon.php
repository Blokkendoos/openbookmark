<?php
/**
 PHP Grab favicon

 @category  Bookmarks
 @package   Openbookmark
 @author    J. van Oostrum <jvo@chaosgeordend.nl>
 @copyright 2010 Brendan LaMarche
 @license   GNU General Public License version 2
 @link      https://github.com/blamarche/openbookmark
 @link      https://github.com/blokkendoos/openbookmark
 */

if (basename($_SERVER['SCRIPT_NAME']) == basename (__FILE__)) {
    die('no direct access allowed');
}


/**
 PHP Grab favicon

 @category  Favicon
 @package   Openbookmark
 @author    J. van Oostrum <jvo@chaosgeordend.nl>
 @copyright 2019-2020 Igor Gaffling
 @license   MIT License
 @link      https://github.com/gaffling/PHP-Grab-Favicon/blob/master/get-fav.php
 @link      https://github.com/blokkendoos/openbookmark
 */
class favicon
{
    private $debug = false;
    private $favicon_dir = './favicons/';

    function favicon($url)
    {
        global $settings, $convert_favicons;

        if ($settings['show_bookmark_icon']) {
            if ($this->get_favicon($url)) {
                if ($convert_favicons) {
                    $this->favicon = $this->convert_favicon();
                } else {
                    $this->favicon = $this->icon_name;
                }
            }
        }
    }

    /**
     Get the favicon for the given URL and save it, if it has not been saved before.

     @param $url the URL

     @return true if favicon was found, otherwise false
     */
    function get_favicon($url)
    {
        $retval = false;
        
        // avoid script runtime timeout
        $max_execution_time = ini_get('max_execution_time');
        set_time_limit(0);  // 0 = no timelimit

        $url = strtolower($url);
        $domain = $this->check_domain(parse_url($url, PHP_URL_HOST));

        $this->get_favicon_url($url, $domain);
        if (empty($this->favicon_url)) {
            $this->get_favicon_api($domain);
        }

        if ($this->favicon_url) {
            // HTML data icon?
            $data_pos = strpos($this->favicon_url, 'data:'); 
            if ($data_pos === false) {
                if ($this->debug) error_log("Favicon URL found: $this->favicon_url");
                // strip URL parameters, if any
                $qm_pos = strpos($this->favicon_url, '?');
                if ($qm_pos !== false) {
                    $this->favicon_url = substr($this->favicon_url, 0, $qm_pos);
                }
                $retval = $this->get_favicon_image();
            } else {
                if ($this->debug) error_log('Data URI found');
                include_once ABSOLUTE_PATH . 'DataUri.php';
                $data_uri = null;
                if (DataUri::tryParse($this->favicon_url, $data_uri)) {
                    $data = false;
                    if ($data_uri->tryDecodeData($data)) {
                        if ($data !== false) {
                            $retval = $this->get_favicon_data($data);
                        }
                    }
                }
            }
        }
        // restore script runtime timeout
        set_time_limit($max_execution_time);
        
        return $retval;
    }

    /**
     Load the favicon from the given URL.

     @param $url    the URL
     @param $domain domain-name

     @return $this->favicon_url
     */
    function get_favicon_url($url, $domain)
    {
        $html = $this->load($url);
        // find favicon with RegEx
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

        // if there is no match, try if there is a favicon in the root of the domain
        if (empty($favicon)) { 
            $favicon = 'http://'.$domain.'/favicon.ico';
        }

        // try to load favicon
        if (@getimagesize($favicon)) {
            if ($this->debug) error_log("URL: $favicon");
            $this->favicon_url = $favicon;
        } else {
            $this->favicon_url = null;
        }
    }

    /**
     Load the favicon using a public API.

     @param $domain The domain 

     @return $this->favicon_url
     */
    function get_favicon_api($domain)
    {
        // Select API at random
        $random = rand(1, 3);

        // Faviconkit
        if ($random == 1) {
            if ($this->debug) error_log('FaviconKit');
            $this->favicon_url = 'https://api.faviconkit.com/'.$domain.'/16';
        }

        // Favicongrabber
        if ($random == 2) {
            if ($this->debug) error_log('FaviconGrabber');
            $echo = json_decode($this->load('http://favicongrabber.com/api/grab/'.$domain), true);
            // Get Favicon URL from Array out of json data (@ if something went wrong)
            $this->favicon_url = @$echo['icons']['0']['src'];
        }

        // Google (check also md5() later)
        if ($random == 3) {
            if ($this->debug) error_log('Google');
            $this->favicon_url = 'http://www.google.com/s2/favicons?domain='.$domain;
        } 
    }

    function check_domain($domain)
    {
        $domainParts = explode('.', $domain);
        if (count($domainParts) == 3 and $domainParts[0] != 'www') {
            // with Subdomain (if not www)
            $domain = $domainParts[0].'.'.
                    $domainParts[count($domainParts)-2] . '.'.
                    $domainParts[count($domainParts)-1];

        } else if (count($domainParts) >= 2) {
            // without subdomain
            $domain = $domainParts[count($domainParts)-2] . '.' . $domainParts[count($domainParts)-1];

        } else {
            // without http(s)
            $domain = $url;
        }
        return $domain;
    }

    /**
     Get the favicon image and save it, if it has not been saved before.

     @return true when successful, otherwise false
     */
    function get_favicon_image()
    {
        $image = $this->load($this->favicon_url);
        return $this->get_favicon_data($image);
    }

    /**
     Save favicon image, if it has not been saved before.

     @param $image the image data

     @return true when successful, otherwise false
     */
    function get_favicon_data($image)
    {
        $this->icon_name = $this->favicon_dir . hash('sha1', $image) . '.ico';

        if (file_exists($this->icon_name)) {
            if ($this->debug) error_log('hash value exists');
            return true;
        } else if ($fp = @fopen($this->icon_name, 'w')) {
            if ($this->debug) error_log("new hash value, fname: $this->icon_name");
            fwrite($fp, $image);
            fclose($fp);
            return true;
        } else {
            if ($this->debug) error_log("favicon not found, URL: $this->favicon_url");
            return false;
        }
    }

    /**
     Load the page with the given URL.

     @param $url the URL

     @return the content
     */
    function load($url)
    {
        // use an agent that is likely to be accepted by the host
        $user_agent = 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0';

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

                $content = file_get_contents($url, null, $context);

            } else {
                $fh = fopen($url, 'r', false, $context);
                $content = '';
                while (!feof($fh)) {
                    $content .= fread($fh, 128); // because filesize() will not work on URLS?
                }
                fclose($fh);
            }
        }
        return $content;
    }

    /**
     Make absolute URL from relative.

     @param $rel  relative URL
     @param $base the URL base

     @return absolute URL
     */
    function rel2abs($rel, $base)
    {
        extract(parse_url($base));

        if (strpos($rel, '//') === 0) return $scheme . ':' . $rel;
        if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
        if ($rel[0] == '#' or $rel[0] == '?') return $base . $rel;

        $path = preg_replace('#/[^/]*$#', '', $path);
        if ($rel[0] == '/') $path = '';

        $abs = $host . $path . '/' . $rel;
        $abs = preg_replace('/(\/\.?\/)/', '/', $abs);
        $abs = preg_replace('/\/(?!\.\.)[^\/]+\/\.\.\//', '/', $abs);

        return $scheme . '://' . $abs;
    }

    /**
     Check the image type and convert & resize it if required.

     @return absolute path of the (converted) .png file, false when not successful
     */
    function convert_favicon()
    {
        global $convert, $identify;

        $fname = $this->icon_name;

        // find out file type
        if (@exec("$identify $fname", $output)) {
            $ident = explode(' ', $output[0]);
            if (count($output) > 1) {
                $file_to_convert = $ident[0];
            } else {
                $file_to_convert = $fname;
            }
            // convert image in any case to 16x16 and .png
            system("$convert $file_to_convert -resize 16x16 $fname.png");
            @unlink($fname);
            return $fname . '.png';
        } else {
            @unlink($fname);
            return false;
        }
    }
}

?>


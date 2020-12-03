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

require_once "./header.php";
logged_in_only();

$bmlist = set_get_num_list('bmlist');

if (count($bmlist) > 0) {
    ?>

    <h2 class="title">Verify bookmarks:</h2>
    <form action="
    <?php
    echo $_SERVER['SCRIPT_NAME'] . "?folderid=" . $folderid;
    ?>"
    method="POST" name="bookmarksverify">

        <?php
        $qbmlist = implode(',', $bmlist);
        $query = sprintf(
            "SELECT url, title FROM bookmark WHERE id IN(%s) AND user='%s'",
            $mysql->escape($qbmlist),
            $mysql->escape($username)
        );
        ?>
        
        <table border=1>
        <tr><th>Bookmark</th><th>HTTP Response</th></tr>

        <?php
        if ($mysql->query($query)) {
            while ($row = mysqli_fetch_row($mysql->result)) {
                $htmlResponse = verifyUrl($row[0]);
                if ($htmlResponse <> 200 and $htmlResponse <> 301) {
                    ?>

                <tr>
                    <td><a
                        class="bookmark_href"
                        target="_blank"
                        title="<? echo $row[1]; ?>"
                        href="<? echo $row[0]; ?>">
                        <? echo $row[1]; ?>
                        </a>
                    </td>
                    <td><? echo $htmlResponse; ?></td>
                </tr>
                       
                    <?php
                }
            }
        } else {
            message($mysql->error);
        }
        ?>

        </table>
        <p>Bookmarks verified</p><br>
        <input type="hidden" name="bmlist">
        <input type="button" value=" Ok " onClick="self.close()">

    </form>

    <script type="text/javascript">
    document.bookmarksverify.bmlist.value = self.name;
    </script>

    <?php
}

/**
 Verify URL,
 i.e. check whether the content loads successfull (200) or not.

 @param $url the URL to verify

 @return the HTTP response code
 */
function verifyUrl($url)
{
    $ch = curl_init($url);
    // use an agent that is likely to be accepted by the host
    $user_agent = "Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0";
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_exec($ch);
    $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $response;
}

require_once ABSOLUTE_PATH . "footer.php";
?>


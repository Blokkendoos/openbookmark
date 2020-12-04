<?php

/**
 Refresh bookmarks favicon

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
    <h2 class="title">Refresh bookmarks favicon:</h2>
    <form action="
    <?php
    echo $_SERVER['SCRIPT_NAME'] . "?folderid=" . $folderid;
    ?>"
    method="POST" name="bookmarksrefresh">

        <?php
        foreach ($bmlist as $id) {
            //DEBUG error_log("bookmark.id: $id");
            $query = sprintf(
                "SELECT id, url, favicon FROM bookmark WHERE id =%s AND user='%s'", 
                $mysql->escape($id),
                $mysql->escape($username)
            );
            if ($mysql->query($query)) {
                //$row = mysqli_fetch_row($mysql->result)
                $row = mysqli_fetch_object($mysql->result);

                include_once ABSOLUTE_PATH . "favicon.php";
                $favicon = new favicon($row->url);
                if (isset($favicon->favicon)) {
                    // remove the current icon file
                    if (isset($row->favicon)) {
                        @unlink($row->favicon);
                    }
                    $icon = '<img src="' . $favicon->favicon . '" width="16" height="16" alt="">';
                    $query = sprintf(
                        "UPDATE bookmark SET favicon='%s' WHERE id='%d' AND user='%s'",
                        $mysql->escape($favicon->favicon),
                        $mysql->escape($id),
                        $mysql->escape($username)
                    );
                    if (!$mysql->query($query)) {
                        message($mysql->error);
                    }
                }
            }
        }
        ?>

        <p>Bookmarks favicon refreshed</p><br>
        <input type="hidden" name="bmlist">
        <input type="button" value=" Ok " onClick="self.close()">

    </form>

    <script type="text/javascript">
    document.bookmarksrefresh.bmlist.value = self.name;
    </script>

    <?php
}

require_once ABSOLUTE_PATH . "footer.php";
?>

<?php

/**
 Refresh all/selected Favicons

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

// avoid script runtime timeout
$max_execution_time = ini_get('max_execution_time');
set_time_limit(0);  // 0 = no timelimit

?>
<h1 id="caption">Refresh Favicons</h1>

<!-- Wrapper starts here. -->
<div style="min-width: <?php echo 230 + $settings['column_width_folder']; ?>px;">
    <!-- Menu starts here. -->
    <div id="menu">
        <h2 class="nav">Bookmarks</h2>
        <ul class="nav">
          <li><a href="./index.php">My Bookmarks</a></li>
          <li><a href="./shared.php">Shared Bookmarks</a></li>
        </ul>
    
        <h2 class="nav">Tools</h2>
        <ul class="nav">
            <?php if (admin_only()) { ?>
            <li><a href="./admin.php">Admin</a></li>
            <?php } ?>
            <li><a href="./import.php">Import</a></li>
            <li><a href="./export.php">Export</a></li>
            <li><a href="./sidebar.php">View as Sidebar</a></li>
            <li><a href="./settings.php">Settings</a></li>
            <li><a href="./index.php?logout=1">Logout</a></li>
        </ul>
    <!-- Menu ends here. -->
    </div>

    <!-- Main content starts here. -->
    <div id="main">
    
    <?php
    if (!admin_only()) {
        message("You are not an Admin.");
    }
    ?>

    <div style="border: 1px solid #bbb; margin: 10px; padding: 10px;">
    <h2 class="caption">Refresh all/selected favicons</h2>
    <form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="POST">

    <?php
        
    $counter = 0;
    $query = sprintf(
        "SELECT user, id, url, title, UNIX_TIMESTAMP(date) as timestamp, favicon 
        FROM bookmark
        WHERE id > %d
        ORDER BY id
        LIMIT 200",
        10632
    );
    /**
     * Refresh only bookmarks created after the last refresh
     * TODO Enter startdate in a form, and save this date as parameter (in user table)?
    $query = sprintf(
        "SELECT user, id, url, title, UNIX_TIMESTAMP(date) as timestamp, favicon 
        FROM bookmark
        WHERE WHERE DATE(date) >= '2020-12-07'
        ORDER BY id
        LIMIT 100"
    );
    */
    if ($mysql->query($query)) {
        $rowcount = mysqli_num_rows($mysql->result);
        $qbmlist = mysqli_fetch_all($mysql->result, MYSQLI_ASSOC);

        echo "<p>Number of favicons selected: $rowcount</p>";

        foreach ($qbmlist as $row) {
            $counter += 1;

            $last_id = $row['id'];
            $last_url = $row['url'];

            // progress, TODO show progress in the admin page
            error_log("row: $counter, id: $last_id");

            include_once './favicon.php';

            $favicon = new favicon($row['url']);
            if (isset($favicon->favicon)) {
                // the current icon file will not be removed as it may be used in other bookmarks
                $query = sprintf(
                    "UPDATE bookmark SET favicon='%s' WHERE id='%d' AND user='%s'",
                    $mysql->escape($favicon->favicon),
                    $mysql->escape($row['id']),
                    $mysql->escape($row['user'])
                );
                if (!$mysql->query($query)) {
                    message($mysql->error);
                }
            }
        }
    }

    error_log("FINISHED");

    echo "<p>Last bookmark id: $last_id url: $last_url</p>";

    // restore script runtime timeout
    set_time_limit($max_execution_time);

    ?>
    <p>The selected favicons have been refreshed.</p><br>
    <input type="button" value=" Ok " onClick="self.location.href='./index.php'">
    </form>    
    </div>
    </div>
    </div>

<?php
require_once "./footer.php";
?>


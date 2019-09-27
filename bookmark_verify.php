<?php
require_once ("./header.php");
logged_in_only ();

$bmlist = set_get_num_list ('bmlist');

if (count ($bmlist) > 0) {
	?>

	<h2 class="title">Verify bookmarks:</h2>
	<form action="<?php echo $_SERVER['SCRIPT_NAME'] . "?folderid=" . $folderid; ?>" method="POST" name="bookmarksverify">

        <?php
        $qbmlist = implode (",", $bmlist);
        $query = sprintf ("SELECT url, title FROM bookmark WHERE id IN (%s) AND user='%s'",
            $mysql->escape ($qbmlist),
            $mysql->escape ($username) );
        ?>
        
        <table border=1>
        <tr><th>Bookmark</th><th>HTML Status</th></tr>

        <?php
        if ($mysql->query ($query)) {
            while ($row = mysqli_fetch_row($mysql->result)) {
            
       	        $htmlStatus = verifyUrl($row[0]);
       	        if ($htmlStatus <> 200 AND $htmlStatus <> 301) {
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
					<td><? echo $htmlStatus; ?></td>
                </tr>
				       
            <?php	        
				}
            }
        }
        else {
            message ($mysql->error);
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

function verifyUrl($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_TIMEOUT,5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,5);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);  
    return $code;
}

require_once (ABSOLUTE_PATH . "footer.php");
?>
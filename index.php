<?php include("head.php"); ?>

<div style="width: 800px; margin: 10px auto;">

<?php

// where cbPlayer resides on your server
	$cbPlayer_dirname = "/cbplayer";

// Where the media files are stored (if you don't see any tracks in the player,
// but are quite sure that they are there, then it's most likely this path is
// wrong here!)
        $cbPlayer_mediadir = "/content/media";

// start cbPlayer!
	include("cbplayer.php");

?>

</div>

</body>
</html>
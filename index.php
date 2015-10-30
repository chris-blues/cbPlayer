<?php include("head.php"); ?>

<div style="width: 800px; margin: 10px auto;">

<?php

  // where cbPlayer resides on your server
  $cbPlayer_dirname = "/cbplayer";

  // where you have the media files on your server
  $cbPlayer_mediadir = "/content/media";

  // start cbPlayer!
  include("cbplayer.php");
?>

</div>

</body>
</html>
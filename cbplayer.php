<?php
// ====================================================
// ==  copyright: chris_blues <chris@musicchris.de>  ==
// ==     released under GPL-3 public license        ==
// ==   see LICENSE in top directory for details     ==
// ====================================================
?>

<div id="cbplayer">
<?php
$starttime = microtime(true);
$version = "v0.08";

require_once('getID3/getid3/getid3.php');
$getID3 = new getID3;

$search = array("ä", "Ä", "ö", "Ö", "ü", "Ü", "ß", "&", "+", "'", ", ", " ", ",", "__", "__");
$replace = array("ae", "Ae", "oe", "Oe", "ue", "Ue", "ss", "_", "_", "", "_", "_", "_", "_", "_");

$dir = scandir($cbPlayer_mediadir);
$counter = -1;

foreach ($dir as $key => $filename)
  { // screen out dotfiles ("." ".." or ".htaccess") and leave only mp3 in the array!
   if (strncmp($filename,".",1) == 0) { unset($dir[$key]); continue 1; }

   $oldfilename = $filename;
   $filename = str_replace($search, $replace, $filename);
   if (strcmp($filename, $oldfilename) != 0) rename("$cbPlayer_mediadir/$oldfilename", "$cbPlayer_mediadir/$filename");
   unset ($oldfilename);
   $dir[$key] = $filename;
   $lastname = $name;
   $name = substr($filename, 0, strlen($filename) - 4);
   $ext = substr($filename, -3);

 // quick and dirty workaround for 4-letter extension "webm"
   if ($ext == "ebm") { $ext = "webm"; $name = substr($name, 0, -1); }

   $fullname = "$cbPlayer_mediadir/$filename";

   // check if this file is usable, skip if not!
   $supported_filetypes = array("mp3", "mp4", "ogg", "oga", "ogv", "webm");
   foreach ($supported_filetypes as $typenum => $filetype)
     {
      if (strcasecmp($ext,$filetype) == 0) $supported = TRUE;
     }
   if (!isset($supported)) { unset($dir[$key]); continue 1; }
   unset($supported);

   // if this file has the same name as the last run, but different extension -> stay in the same branch, but add extension
   if (strcasecmp($lastname,$name) == 0)
     {
      $ThisFileInfo = $getID3->analyze($fullname);
      getid3_lib::CopyTagsToComments($ThisFileInfo);

      foreach ($files[$counter]["type"] as $extcount => $sth) { }
      $extcount++;
      $files[$counter]["type"][$extcount]["ext"] = $ThisFileInfo["fileformat"];
      $files[$counter]["type"][$extcount]["filesize"] = $ThisFileInfo["filesize"];
      if (isset($ThisFileInfo["video"]["dataformat"]))
        {
         // ===================================================================
         // InternetExplorer doesn't seem to like "video/quicktime" mime-type!
         // So, we name it "video/mp4". In the same spirit I rather "normalize"
         // ogg-mime from "application/ogg" to "audio/ogg" / "video/ogg"... :-/
         // ===================================================================
         if ($ThisFileInfo["fileformat"] == "ogg" or $ThisFileInfo["fileformat"] == "ogv" or $ThisFileInfo["fileformat"] == "mp4") $files[$counter]["type"][$extcount]["mime"] = "video/" . $ThisFileInfo["fileformat"];
         else $files[$counter]["type"][$extcount]["mime"] = $ThisFileInfo["mime_type"];
        }
      else
        {
         if ($ThisFileInfo["fileformat"] == "ogg" or $ThisFileInfo["fileformat"] == "oga") $files[$counter]["type"][$extcount]["mime"] = "audio/" . $ThisFileInfo["fileformat"];
         else $files[$counter]["type"][$extcount]["mime"] = $ThisFileInfo["mime_type"];
        }

      // Check if some tags are missing - try to get it from alternative file!
      if (!isset($files[$counter]["artist"]) or $files[$counter]["artist"] == "")
        {
         $files[$counter]["artist"] = $ThisFileInfo['comments']['artist'][0];
        }
      if (!isset($files[$counter]["title"]) or $files[$counter]["title"] == "")
        {
         $files[$counter]["title"] = $ThisFileInfo['comments']['title'][0];
        }
      if (!isset($files[$counter]["album"]) or $files[$counter]["album"] == "")
        {
         $files[$counter]["album"] = $ThisFileInfo['comments']['album'][0];
        }
      if (!isset($files[$counter]["year"]) or $files[$counter]["year"] == "")
        {
         $files[$counter]["year"] = $ThisFileInfo['comments'][$year][0];
        }
     }
   else // new filename, so make a new branch
     {
      $counter++;
      // Read audio tags
      $ThisFileInfo = $getID3->analyze($fullname);
      getid3_lib::CopyTagsToComments($ThisFileInfo);

      if (stristr($filename,".ogg",-4)) $year = "date";
      if (stristr($filename,".mp3",-4)) $year = "year";

      if (isset($ThisFileInfo["video"]["dataformat"]))
        {
         // ===================================================================
         // InternetExplorer doesn't seem to like "video/quicktime" mime-type!
         // So, we name it "video/mp4". In the same spirit I rather "normalize"
         // ogg-mime from "application/ogg" to "audio/ogg" / "video/ogg"... :-/
         // ===================================================================
         $files[$counter]["mediatype"] = "video";
         if ($ThisFileInfo["fileformat"] == "ogg" or $ThisFileInfo["fileformat"] == "mp4") $files[$counter]["type"][0]["mime"] = "video/" . $ThisFileInfo["fileformat"];
         else $files[$counter]["type"][0]["mime"] = $ThisFileInfo["mime_type"];
        }
      else
        {
         $files[$counter]["mediatype"] = "audio";
         if ($ThisFileInfo["fileformat"] == "ogg") $files[$counter]["type"][0]["mime"] = "audio/" . $ThisFileInfo["fileformat"];
         else $files[$counter]["type"][0]["mime"] = $ThisFileInfo["mime_type"];
        }
      $files[$counter]["playtime"] = ceil($ThisFileInfo["playtime_seconds"]);
      $files[$counter]["id"] = $counter;
      $files[$counter]["filename"] = $name;
      $files[$counter]["type"][0]["ext"] = $ThisFileInfo["fileformat"];
      $files[$counter]["type"][0]["filesize"] = $ThisFileInfo["filesize"];
      $files[$counter]["artist"] = $ThisFileInfo['comments']['artist'][0];
      $files[$counter]["title"] = $ThisFileInfo['comments']['title'][0];
      $files[$counter]["album"] = $ThisFileInfo['comments']['album'][0];
      $files[$counter]["year"] = $ThisFileInfo['comments'][$year][0];
     }
   unset($ThisFileInfo);
  }
?>
  <div id="cbPlayer_statusbar"></div>
<?php
echo "  <div id=\"cbPlayer_mediaFiles\">\n";
foreach ($files as $key => $id)
  {
   if ($files[$key]["mediatype"] == "video") $mediatag = $files[$key]["mediatype"] . " width=\"100%\"";
     else $mediatag = $files[$key]["mediatype"];
?>

    <div id="cbPlayer_<?php echo $files[$key]["id"]; ?>"
         class="cbPlayer_mediaContent"
         data-preload="metadata"
         data-onended="nextMedia();"
         data-onloadstart="showMedia(<?php echo $files[$key]["id"]; ?>);"
         data-onloadedmetadata="activateMedia(<?php echo $files[$key]["id"]; ?>);"
         data-onprogress="finishMedia(<?php echo $files[$key]["id"]; ?>);"
         data-oncanplaythrough="finishMedia(<?php echo $files[$key]["id"]; ?>);"
         data-artist="<?php echo $files[$key]["artist"]; ?>"
         data-title="<?php echo $files[$key]["title"]; ?>"
         data-album="<?php echo $files[$key]["album"]; ?>"
         data-year="<?php echo $files[$key]["year"]; ?>"
         data-filename="<?php echo rawurlencode($files[$key]["filename"]); ?>"
         data-mediatype="<?php echo $files[$key]["mediatype"]; ?>">
<?php
   foreach ($files[$key]["type"] as $extkey => $ext)
     { ?>
         <div id="cbPlayer_playlistItem_<?php echo $files[$key]["id"] . "_" . $files[$key]["type"][$extkey]["ext"]; ?>"
              class="cbPlayer_src_<?php echo $files[$key]["id"]; ?>"
              data-src="<?php echo "$cbPlayer_mediadir/" . rawurlencode($files[$key]["filename"]) . ".{$files[$key]["type"][$extkey]["ext"]}"; ?>"
              data-type="<?php echo $files[$key]["type"][$extkey]["mime"]; ?>"
              data-filesize="<?php echo $files[$key]["type"][$extkey]["filesize"]; ?>"
              data-fileformat="<?php echo $files[$key]["type"][$extkey]["ext"]; ?>"></div>
<?php
     } ?>
     </div>

<?php
  }
echo "</div>\n"; ?>

<div id="cbPlayer_media"></div>

<?php
echo " <div id=\"cbPlayer_playlist\"></div>\n";

echo "<hr>\n";
//echo "FILES:<pre style=\"width: 40%; float: left;\">"; print_r($files); echo "</pre>\n";
//echo "DIR:<pre style=\"width: 40%; float: left;\">"; print_r($dir); echo "</pre>\n";
?>
    <div id="cbPlayer_progressbar" onclick="getCursorPosition(event);">
      <div id="cbPlayer_progressbarWrapper">
        <div id="cbPlayer_progressbarIndicator"></div>
      </div>
    </div>
<div id="cbPlayer_leftSideBox">
  <div class="cbPlayer_mediacontrols_wrapper">
    <a href="javascript:prevMedia();" class="cbPlayer_controls"><img id="cbPlayer_prev" class="cbPlayer_mediacontrols" src="<?php echo $cbPlayer_dirname; ?>/pics/rwd.png" alt="prev" title="prev"></a>
    <a href="javascript:playMedia(currentMediaId);" class="cbPlayer_controls"><img id="cbPlayer_play" class="cbPlayer_mediacontrols" src="<?php echo $cbPlayer_dirname; ?>/pics/play.png" alt="play" title="play"></a>
    <a href="javascript:pauseMedia(currentMediaId);" class="cbPlayer_controls"><img id="cbPlayer_pause" class="cbPlayer_mediacontrols" src="<?php echo $cbPlayer_dirname; ?>/pics/pause.png" alt="pause" title="pause"></a>
    <a href="javascript:stopMedia(true);" id="cbPlayer_stopButton" class="cbPlayer_controls"><img id="cbPlayer_stop" class="cbPlayer_mediacontrols" src="<?php echo $cbPlayer_dirname; ?>/pics/stop.png" alt="stop" title="stop"></a>
    <a href="javascript:nextMedia();" class="cbPlayer_controls"><img id="cbPlayer_next" class="cbPlayer_mediacontrols" src="<?php echo $cbPlayer_dirname; ?>/pics/fwd.png" alt="next" title="next"></a>
    <a class="cbPlayer_fullscreen cbPlayer_controls" href="javascript:fullscreen();" onclick="fullscreen();">
       <img id="cbPlayer_fullscreen" class="cbPlayer_mediacontrols cbPlayer_fullscreen" src="<?php echo $cbPlayer_dirname; ?>/pics/fullscreen.png" alt="fullscreen" title="fullscreen" style="display: none;">
    </a>
  </div>
  <div id="cbPlayer_progressinfo">
    <span id="cbPlayer_mediaItems" class="cbPlayer_progressinfo"></span> <span id="cbPlayer_progress" class="cbPlayer_progressinfo">0:00 / 0:00</span>
  </div>
  <div id="cbPlayer_progInfo">
    <a href="https://github.com/chris-blues/cbPlayer" target="_blank">cbPlayer <?php echo $version; ?></a>
  </div>
</div>
  <table id="cbPlayer_infobox">
    <tbody>
      <tr><td class="cbPlayer_mediaInfo cbPlayer_leftside" id="cbPlayer_title"></td><td id="cbPlayer_currentTitle" class="cbPlayer_mediaInfo"></td></tr>
      <tr><td class="cbPlayer_mediaInfo cbPlayer_leftside" id="cbPlayer_artist"></td><td id="cbPlayer_currentArtist" class="cbPlayer_mediaInfo"></td></tr>
      <tr><td class="cbPlayer_mediaInfo cbPlayer_leftside" id="cbPlayer_album"></td><td id="cbPlayer_currentAlbum" class="cbPlayer_mediaInfo"></td></tr>
      <tr><td class="cbPlayer_mediaInfo cbPlayer_leftside" id="cbPlayer_download"></td><td id="cbPlayer_currentDownload" class="cbPlayer_mediaInfo"></td></tr>
    </tbody>
  </table>

<script>

var version = "<?php echo $version; ?>";
var cbPlayer_dir = "<?php echo $cbPlayer_dirname; ?>";
initPlayer();

</script>
<noscript>Dieser Medienplayer benötigt JavaScript um zu funktionieren. Dazu müssen Sie JavaScript aktivieren.</noscript>
</div>
<?php
$endtime = microtime(true);
//echo "<p id=\"footer\" style=\"font-size: 0.7em; text-align: center;\">Processing needed " . number_format($endtime - $starttime, 3) . " seconds.</p>\n";
//echo "<pre>"; print_r($files); echo "</pre>\n";
?>

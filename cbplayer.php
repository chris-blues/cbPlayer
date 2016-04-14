<?php
// ====================================================
// ==  copyright: chris_blues <chris@musicchris.de>  ==
// ==     released under GPL-3 public license        ==
// ==   see LICENSE in top directory for details     ==
// ====================================================
require_once('cbplayer.conf.php');
?>

<div id="cbplayer">
<?php
$starttime = microtime(true);
$version = "v0.12";

require_once('getID3/getid3/getid3.php');
$getID3 = new getID3;

// ==============
// read media dir
// ==============
$search = array("ä", "Ä", "ö", "Ö", "ü", "Ü", "ß", "&", "+", "'", ", ", " ", ",", "__", "__", "ogv", "oga");
$replace = array("ae", "Ae", "oe", "Oe", "ue", "Ue", "ss", "_", "_", "", "_", "_", "_", "_", "_", "ogg", "ogg");

$dir = scandir($cbPlayer_mediadir);
$cbPlayer_cache_dir = realpath($cbPlayer_dirname) . "/cache";
$timestampFile = $cbPlayer_cache_dir . "/timestamps.dat";
$playlistFile = $cbPlayer_cache_dir . "/playlist.dat";

$dircounter = 0;
foreach ($dir as $key => $filename)
  { // screen out dotfiles ("." ".." or ".htaccess") and dirs - leave only normal files in the array! And make filenames URL compatible.
   if (strncmp($filename,".",1) == 0) { unset($dir[$key]); continue 1; }

   $oldfilename = $filename;
   $filename = str_replace($search, $replace, $filename);
   if (strcmp($filename, $oldfilename) != 0) rename("$cbPlayer_mediadir/$oldfilename", "$cbPlayer_mediadir/$filename");
   unset ($oldfilename);
   $dir[$key] = $filename;
   $ext = substr($filename, -3);

   // quick and dirty workaround for 4-letter extension "webm"
   if ($ext == "ebm") { $ext = "webm"; $name = substr($name, 0, -1); }

   // check if this file is usable, skip if not!
   $supported_filetypes = array("mp3", "mp4", "ogg", "oga", "ogv", "webm");
   foreach ($supported_filetypes as $typenum => $filetype)
     {
      if (strcasecmp($ext,$filetype) == 0) $supported = TRUE;
     }
   if (!isset($supported)) { unset($dir[$key]); continue 1; }
   unset($supported);

   $dircontents[$dircounter] = $filename;
   $dircounter++;
  }

// Check for cache dir
if (!file_exists($cbPlayer_cache_dir) or !is_dir($cbPlayer_cache_dir))
  {
   mkdir($cbPlayer_cache_dir, 0755);
  }

// read playlist file if exists
$playlistexists = FALSE;
if (file_exists($playlistFile))
  {
   //echo "$playlistFile exists!<br>\n";
   $playlistContent = file($playlistFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
   foreach ($playlistContent as $key => $value)
     {
      $playlistitem = explode("\0", $value);
      $files[$key]["id"] = $key;
      $files[$key]["filename"] = $playlistitem[0]; if (strlen($files[$key]["filename"]) > 1) $playlistexists = TRUE;
      $files[$key]["mediatype"] = $playlistitem[1];
      $files[$key]["artist"] = $playlistitem[2];
      $files[$key]["album"] = $playlistitem[3];
      $files[$key]["title"] = $playlistitem[4];
      $files[$key]["year"] = $playlistitem[5];
      $files[$key]["playtime"] = $playlistitem[6];
      $length = count($playlistitem) -6;
      $subitems = floor($length / 4);
      $counter = 7;
      for ($i = 0; $i < $subitems; $i++)
        {
         $files[$key]["type"][$i]["ext"] = $playlistitem[$counter]; $counter++;
         $files[$key]["type"][$i]["mime"] = $playlistitem[$counter]; $counter++;
         $files[$key]["type"][$i]["codec"] = $playlistitem[$counter]; $counter++;
         $files[$key]["type"][$i]["filesize"] = $playlistitem[$counter]; $counter++;
         $dircontentsCached[] = $files[$key]["filename"] . "." . $files[$key]["type"][$i]["ext"];
        }
     }
  }
else 
  {
   $playlistexists = FALSE;
   $playlistUpdateNeeded = TRUE;
  }

//echo "<pre style=\"width: 40%; float: left;\">FILES:\n"; print_r($files); echo "</pre>\n";

// Check for missing files
foreach ($dircontents as $key => $value)
  {
   if (!in_array($value, $dircontentsCached))
     {
      unset($files, $playlistContent);
      $playlistexists = FALSE;
      $playlistUpdateNeeded = TRUE;
      unlink($playlistFile);
      unlink($timestampFile);
      //echo "Check: $value not found in \$dircontentsCached (line 99)<br>\n";
      continue 1;
     }
  }
foreach ($dircontentsCached as $key => $value)
  {
   if (!in_array($value, $dircontents))
     {
      unset($files, $playlistContent);
      $playlistexists = FALSE;
      $playlistUpdateNeeded = TRUE;
      unlink($playlistFile);
      unlink($timestampFile);
      //echo "Check: $value not found in \$dircontents (line 110)<br>\n";
      continue 1;
     }
  }
foreach ($playlistContent as $key => $value)
  {
   if (!isset($files)) continue 1;
   foreach($files[$key]["type"] as $file => $types)
     {
      $thisFile = "$cbPlayer_mediadir/{$files[$key]["filename"]}.{$files[$key]["type"][$file]["ext"]}";
      if (!file_exists($thisFile))
        {
         unset($files);
         $playlistUpdateNeeded = TRUE;
         unlink($playlistFile);
         unlink($timestampFile);
         //echo "Check: $thisFile file not found (line 125)<br>\n";
         continue 2;
        }
      $checkmissing = array_column($files[$key]["type"], "ext");
      $thisMediaItem = $files[$key]["artist"] . " - " . $files[$key]["title"];
      if (!isset($checkmissing[0]))
        {
	 unset($files);
	 $playlistUpdateNeeded = TRUE;
         unlink($playlistFile);
         unlink($timestampFile);
	 //echo "Check: No file found for $thisMediaItem (line 134)<br>\n";
	 continue 2;
	}
     }
  }

//echo "<pre style=\"width: 40%; float: left;\">FILES:\n"; print_r($files); echo "</pre>\n";

// read timestamp file if exists
$timestampchanged = FALSE;
if (file_exists($timestampFile) and !$playlistUpdateNeeded)
  {
   $timestampexists = TRUE;
   $timestampFileContent = file($timestampFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
   foreach ($timestampFileContent as $key => $value)
     {
      $timestamptmp = explode(" ", $value);
      $timestamps[$key]["file"] = $timestamptmp[0];
      $timestamps[$key]["time"] = $timestamptmp[1];
      unset($timestamptmp);
     }
   unset($key, $value, $timestampFileContent);
  }
//else
if (!isset($timestamps[0]["time"]) or $timestamps[0]["time"] == "")
  {
   $timestampexists = FALSE;
   $playlistUpdateNeeded = TRUE;
   //echo "$timestampFile does not exist or empty! (line 149)<br>\n";
  }

// compare timestamps and check for missing media files
$timestampchanged = FALSE;
$counter = 0;
foreach ($dircontents as $key => $filename)
  {
   if (strncmp($filename, ".", 1) == 0 or is_dir("$cbPlayer_mediadir/$filename") or !$playlistUpdateNeeded) continue;
   $filesTimestamp[$counter]["file"] = $filename;
   $filesTimestamp[$counter]["time"] = filemtime("$cbPlayer_mediadir/$filename");
   $actualfile = array_search($filename, array_column($timestamps, "file"));
   $actualtimestamp = $timestamps[$actualfile]["time"];
   $fileexists = file_exists("$cbPlayer_mediadir/$filename");
   if ($filesTimestamp[$counter]["time"] != $actualtimestamp or $fileexists == FALSE)
     {
      $timestampchanged = TRUE;
      $filesTimestamp[$counter]["changed"] = TRUE;
      $playlistUpdateNeeded = TRUE;
      //echo "Check: $filename not found or timestamp mismatch (line 172)<br>\n";
     }
   $filesTimestamp[$counter]["timestamp"] = $timestamps[$actualfile]["time"];
   $counter++;
  }

$counter = -1;

 foreach ($dircontents as $key => $filename)
  {
   // if this file has the same name as the last run, but different extension -> stay in the same branch, but add extension
   $lastname = $name;
   $name = substr($filename, 0, strlen($filename) - 4);
   $ext = substr($filename, -3);

   // quick and dirty workaround for 4-letter extension "webm"
   if ($ext == "ebm") { $ext = "webm"; $name = substr($name, 0, -1); }

   $fullname = "$cbPlayer_mediadir/$filename";

   if (strcasecmp($lastname,$name) == 0)
     {
      if (!isset($filesTimestamp[$key]["changed"]) and $files[$counter]["filename"] != "" and isset($files[$counter]["filename"])) continue;
      $ext_exists = array_search($ext, array_column($files[$counter]["type"], "ext"));
      if ($ext_exists) unset($files[$counter]["type"][$ext_exists]);
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
         
         if ($ThisFileInfo["fileformat"] == "ogg") $files[$counter]["type"][$extcount]["codec"] = $ThisFileInfo["video"]["dataformat"] . ", " . $ThisFileInfo["audio"]["dataformat"];
	 else $files[$counter]["type"][$extcount]["codec"] = $ThisFileInfo["video"]["fourcc"] . ", " . $ThisFileInfo["audio"]["codec"];
        }
      else
        {
         if ($ThisFileInfo["fileformat"] == "ogg" or $ThisFileInfo["fileformat"] == "oga") $files[$counter]["type"][$extcount]["mime"] = "audio/" . $ThisFileInfo["fileformat"];
         else $files[$counter]["type"][$extcount]["mime"] = $ThisFileInfo["mime_type"];
         $files[$counter]["type"][$extcount]["codec"] = $ThisFileInfo["audio"]["codec"];
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
      if (!isset($filesTimestamp[$key]["changed"]) and $files[$counter]["filename"] != "" and isset($files[$counter]["filename"])) continue;
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
	 if ($ThisFileInfo["fileformat"] == "ogg") $files[$counter]["type"][0]["codec"] = $ThisFileInfo["video"]["dataformat"] . ", " . $ThisFileInfo["audio"]["dataformat"];
	 else $files[$counter]["type"][0]["codec"] = $ThisFileInfo["video"]["fourcc"] . ", " . $ThisFileInfo["audio"]["codec"];
        }
      else
        {
         $files[$counter]["mediatype"] = "audio";
         if ($ThisFileInfo["fileformat"] == "ogg")
           {
            $files[$counter]["type"][0]["mime"] = "audio/" . $ThisFileInfo["fileformat"];
            $files[$counter]["type"][0]["codec"] = $ThisFileInfo["audio"]["dataformat"];
	   }
         else
           {
            $files[$counter]["type"][0]["mime"] = $ThisFileInfo["mime_type"];
            $files[$counter]["type"][0]["codec"] = $ThisFileInfo["audio"]["codec"];
	   }
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

// update playlist.dat and timestamps.dat
if (!$timestampexists or $timestampchanged or !$playlistexists or $playlistUpdateNeeded)
  {
   //echo "Updating $timestampFile<br>\n";
   $timestamphandle = fopen($timestampFile,"w");
   foreach ($filesTimestamp as $key => $value)
     {
      fwrite($timestamphandle, "{$filesTimestamp[$key]["file"]} {$filesTimestamp[$key]["time"]}\n");
     }
   fclose($timestamphandle);

   //echo "Updating $playlistFile<br>\n";
   $playlisthandle = fopen($playlistFile,"w");
   foreach ($files as $key => $value)
     {
      fwrite($playlisthandle, $files[$key]["filename"] . "\0");
      fwrite($playlisthandle, $files[$key]["mediatype"] . "\0");
      fwrite($playlisthandle, $files[$key]["artist"] . "\0");
      fwrite($playlisthandle, $files[$key]["album"] . "\0");
      fwrite($playlisthandle, $files[$key]["title"] . "\0");
      fwrite($playlisthandle, $files[$key]["year"] . "\0");
      fwrite($playlisthandle, $files[$key]["playtime"] . "\0");
      foreach ($files[$key]["type"] as $typekey => $typevalue)
        {
         fwrite($playlisthandle, $files[$key]["type"][$typekey]["ext"] . "\0");
         fwrite($playlisthandle, $files[$key]["type"][$typekey]["mime"] . "\0");
         fwrite($playlisthandle, $files[$key]["type"][$typekey]["codec"] . "\0");
         fwrite($playlisthandle, $files[$key]["type"][$typekey]["filesize"] . "\0");
        }
      fwrite($playlisthandle, "\n");
     }
   fclose($playlisthandle);
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
         data-preload="auto"
         data-onended="nextMedia();"
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
              data-fileformat="<?php echo $files[$key]["type"][$extkey]["ext"]; ?>"
              data-codec="<?php echo $files[$key]["type"][$extkey]["codec"]; ?>">
              </div>
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
<div style="clear: both;"></div>
<script>

var version = "<?php echo $version; ?>";
var cbPlayer_dir = "<?php echo $cbPlayer_dirname; ?>";
var showDownload = <?php if($cbPlayer_showDownload != false or !isset($cbPlayer_showDownload)) { echo "true"; } else { echo "false"; } ?>;
initPlayer();

</script>
<noscript>Dieser Medienplayer benötigt JavaScript um zu funktionieren. Dazu müssen Sie JavaScript aktivieren.</noscript>
</div>
<?php
if ($playlistUpdateNeeded) $cacheUpdated = "<br> Cache needed to be rebuilt, all media files have been rescanned! Sorry for the longer processing time!";
$endtime = microtime(true);
if ($cbPlayer_showTimer == true) echo "<p id=\"cbPlayer_footer\" style=\"font-size: 0.7em; text-align: center;\">Processing needed " . number_format($endtime - $starttime, 3) . " seconds.$cacheUpdated</p>\n";
//echo "<pre style=\"width: 49%; float: left;\">FILES:\n"; print_r($files); echo "</pre>\n";
//echo "<pre style=\"width: 49%; float: left;\">DIR:\n"; print_r($dir); echo "</pre>\n";
//echo "<pre style=\"width: 49%; float: left;\">DIRCONTENTS:\n"; print_r($dircontents); echo "</pre>\n";
//echo "<pre style=\"width: 49%; float: left;\">DIRCONTENTSCACHED:\n"; print_r($dircontentsCached); echo "</pre>\n";
//echo "<pre style=\"width: 49%; float: left;\">FILESTIMESTAMP:\n"; print_r($filesTimestamp); echo "</pre>\n";
//echo "<pre style=\"width: 49%; float: left;\">TIMESTAMPS:\n"; print_r($timestamps); echo "</pre>\n";
?>

<div id="cbplayer">
<?php
$starttime = microtime(true);

$application = $_GET["media"]; // can be "audio" or "video" !!
if ($application == "") $application = "audio";

require_once('getID3/getid3/getid3.php');
$getID3 = new getID3;

//$cbPlayer_dirname = "media";

$search = array("ä", "Ä", "ö", "Ö", "ü", "Ü", "ß", "&", "+", "'", ", ", " ", ",", "__", "__");
$replace = array("ae", "Ae", "oe", "Oe", "ue", "Ue", "ss", "_", "_", "", "_", "_", "_", "_", "_");

$dir = scandir($cbPlayer_dirname);
$counter = -1;

foreach ($dir as $key => $filename)
  { // screen out dotfiles ("." ".." or ".htaccess") and leave only mp3 in the array!
   if (strncmp($filename,".",1) == 0) { unset($dir[$key]); continue 1; }
 //  if (!stristr($filename,".mp3",-4)) { unset($dir[$key]); continue 1; }

   $oldfilename = $filename;
   $filename = str_replace($search, $replace, $filename);
   rename("$cbPlayer_dirname/$oldfilename", "$cbPlayer_dirname/$filename");
   unset ($oldfilename);
   $dir[$key] = $filename;
   $lastname = $name;
   $name = substr($filename,0,strlen($filename) - 4);
   $ext = substr($filename,-3);
   $fullname = "$cbPlayer_dirname/$filename";

   // check if this file is usable, skip if not!
   $supported_filetypes = array("mp3", "ogg", "mp4", "oga", "ogv");
   foreach ($supported_filetypes as $typenum => $filetype)
     {
      if (strcasecmp($ext,$filetype) == 0) $supported = TRUE;
     }
   if (!isset($supported)) { unset($dir[$key]); continue 1; }
   unset($supported);

   // if this file has the same name but different extension, stay in the same branch, but add extension
   if (strcasecmp($lastname,$name) == 0)
     {
      $ThisFileInfo = $getID3->analyze($fullname);
      getid3_lib::CopyTagsToComments($ThisFileInfo);

      foreach ($files[$counter]["type"] as $extcount => $sth) { }
      $extcount++;
      $files[$counter]["type"][$extcount]["ext"] = $ThisFileInfo["fileformat"];
      $files[$counter]["type"][$extcount]["mime"] = $ThisFileInfo["mime_type"];
      $files[$counter]["type"][$extcount]["filesize"] = $ThisFileInfo["filesize"];
      if (isset($ThisFileInfo["video"]["dataformat"]))
        {
         if ($ext == "ogg") $files[$counter]["type"][$extcount]["mime"] = "video/$ext";
        }
      else
        {
         if ($ext == "ogg") $files[$counter]["type"][$extcount]["mime"] = "audio/$ext";
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
         $files[$counter]["mediatype"] = "video";
         if ($ext == "ogg") $files[$counter]["type"][0]["mime"] = "video/$ext";
        }
      else
        {
         $files[$counter]["mediatype"] = "audio";
         if ($ext == "ogg") $files[$counter]["type"][0]["mime"] = "audio/$ext";
        }
      $files[$counter]["playtime"] = ceil($ThisFileInfo["playtime_seconds"]);
      $files[$counter]["id"] = $counter;
      $files[$counter]["filename"] = $name;
      $files[$counter]["type"][0]["ext"] = $ext;
      $files[$counter]["type"][0]["mime"] = $ThisFileInfo["mime_type"];
      $files[$counter]["type"][0]["filesize"] = $ThisFileInfo["filesize"];
      $files[$counter]["artist"] = $ThisFileInfo['comments']['artist'][0];
      $files[$counter]["title"] = $ThisFileInfo['comments']['title'][0];
      $files[$counter]["album"] = $ThisFileInfo['comments']['album'][0];
      $files[$counter]["year"] = $ThisFileInfo['comments'][$year][0];
     }
   unset($ThisFileInfo);
  }
?>
  <div id="cbPlayer_statusbar">Please wait while the meta data is being downloaded.</div>
<?php
echo "  <div id=\"cbPlayer_mediaFiles\">\n";
foreach ($files as $key => $id)
  {
   if ($files[$key]["mediatype"] == "video") $mediatag = $files[$key]["mediatype"] . " width=\"100%\"";
     else $mediatag = $files[$key]["mediatype"];
?>
     <<?php echo $mediatag; ?> class="cbPlayer_mediacontent" id="cbPlayer_<?php echo $files[$key]["id"]; ?>"
        mediagroup="cbplayer"
        preload="auto"
        onended="currentMediaId++; playMedia(currentMediaId);"
        onprogress="showMedia(<?php echo $files[$key]["id"]; ?>);"
        oncanplay="activateMedia(<?php echo $files[$key]["id"]; ?>);"
        oncanplaythrough="finishMedia(<?php echo $files[$key]["id"]; ?>);"
        data-artist="<?php echo $files[$key]["artist"]; ?>"
        data-title="<?php echo $files[$key]["title"]; ?>"
        data-album="<?php echo $files[$key]["album"]; ?>"
        data-year="<?php echo $files[$key]["year"]; ?>"
        data-filename="<?php echo rawurlencode($files[$key]["filename"]); ?>"
        data-mediatype="<?php echo $files[$key]["mediatype"]; ?>"
<?php foreach ($files[$key]["type"] as $extkey => $ext) { ?>
        data-filesize-<?php echo $files[$key]["type"][$extkey]["ext"]; ?>="<?php echo $files[$key]["type"][$extkey]["filesize"]; ?>"<?php } ?>>
<?php
   foreach ($files[$key]["type"] as $extkey => $ext)
     { ?>
       <source src="<?php echo "$cbPlayer_dirname/" . rawurlencode($files[$key]["filename"]) . ".{$files[$key]["type"][$extkey]["ext"]}"; ?>" type="<?php echo $files[$key]["type"][$extkey]["mime"]; ?>">
<?php
     } ?>
     </<?php echo $files[$key]["mediatype"]; ?>>


<?php
  }
echo "</div>\n";

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
    <a href="javascript:prevMedia();"><img id="cbPlayer_prev" class="cbPlayer_mediacontrols" src="cbplayer/pics/rwd.png" alt="prev" title="prev"></a>
    <a href="javascript:playMedia(currentMediaId);"><img id="cbPlayer_play" class="cbPlayer_mediacontrols" src="cbplayer/pics/play.png" alt="play" title="play"></a>
    <a href="javascript:stopMedia();" id="cbPlayer_stopButton"><img id="cbPlayer_stop" class="cbPlayer_mediacontrols" src="cbplayer/pics/stop.png" alt="stop" title="stop"></a>
    <a href="javascript:nextMedia();"><img id="cbPlayer_next" class="cbPlayer_mediacontrols" src="cbplayer/pics/fwd.png" alt="next" title="next"></a>
    <a class="cbPlayer_fullscreen" href="javascript:" onclick="var currentMedia = document.getElementById('cbPlayer_' + currentMediaId);
     // go full-screen
     if (currentMedia.requestFullscreen) { currentMedia.requestFullscreen(); }
     else if (currentMedia.msRequestFullscreen) { currentMedia.msRequestFullscreen(); }
     else if (currentMedia.mozRequestFullScreen) { currentMedia.mozRequestFullScreen(); }
     else if (currentMedia.webkitRequestFullscreen) { currentMedia.webkitRequestFullscreen(); }"><img id="cbPlayer_fullscreen" class="cbPlayer_mediacontrols cbPlayer_fullscreen" src="cbplayer/pics/fullscreen.png" alt="fullscreen" title="fullscreen" style="display: none;"></a>
  </div>
  <div id="cbPlayer_progressinfo">
    <span id="cbPlayer_mediaItems" class="cbPlayer_progressinfo"></span> <span id="cbPlayer_progress" class="cbPlayer_progressinfo">0:00 / 0:00</span>
  </div>
</div>
  <table id="cbPlayer_infobox">
    <tr><td class="cbPlayer_mediaInfo cbPlayer_leftside" id="cbPlayer_title"></td><td id="cbPlayer_currentTitle" class="cbPlayer_mediaInfo"></td><tr>
    <tr><td class="cbPlayer_mediaInfo cbPlayer_leftside" id="cbPlayer_artist"></td><td id="cbPlayer_currentArtist" class="cbPlayer_mediaInfo"></td><tr>
    <tr><td class="cbPlayer_mediaInfo cbPlayer_leftside" id="cbPlayer_album"></td><td id="cbPlayer_currentAlbum" class="cbPlayer_mediaInfo"></td></tr>
  </table>

<script>
  // =====================
  // ==  Init cbplayer  ==
  // ==================================================
  // ==  Read all media items and create a playlist  ==
  // ==================================================
  var currentMediaId = 0;

  // =======================
  // ==  Create Playlist  ==
  // =======================
  var mediaElements = document.getElementsByClassName("cbPlayer_mediacontent");
  for (var i = 0; i < mediaElements.length; i++)
    {
        var a = document.createElement("a"); // create Link - non-clickable yet, until we have the necessary data downloaded!
        a.className = "cbPlayer_playlistitem";
        a.id = "cbPlayer_playlistItemLink_" + i;

        var div = document.createElement("div");
        div.id = i + "_" + mediaElements[i].getAttribute("data-filename");
        div.className = "cbPlayer_playlist " + i;
        var linkname = mediaElements[i].getAttribute("data-artist") + ' - ' + mediaElements[i].getAttribute("data-title");
        var textnode = document.createTextNode(linkname);
        div.appendChild(textnode);

        var span = document.createElement("span"); // for status display
        span.id = "cbPlayer_status_" + i;
        span.className = "cbPlayer_statusfield";
        div.appendChild(span);

        a.appendChild(div);
        var parent = document.getElementById("cbPlayer_playlist");
        parent.appendChild(a);

        if (mediaElements.readyState > 0) showMedia(i);
        if (mediaElements.readyState > 3) activateMedia(i);
    }
  document.getElementById("cbPlayer_progressbar").style.display = "block";
  document.getElementById("cbPlayer_mediaItems").innerHTML = currentMediaId + 1 + "/" + mediaElements.length;

  function showMedia(i)
    {
     var loadedMedia = document.getElementById("cbPlayer_playlistItemLink_" + i);
     loadedMedia.style.display = "block";
     document.getElementById("cbPlayer_status_" + i).innerHTML = "Please wait! Downloading meta-data...";
    }

  function activateMedia(i)
    {
     var loadedMedia = document.getElementById("cbPlayer_playlistItemLink_" + i);
     loadedMedia.style.display = "block";
     loadedMedia.href = "javascript:playMedia(" + i + ");";
     loadedMedia.style.color = "#555555";
     document.getElementById("cbPlayer_status_" + i).innerHTML = "Loading media-data...";
     document.getElementById("cbPlayer_artist").innerHTML = "Artist:";
     document.getElementById("cbPlayer_title").innerHTML = "Title:";
     document.getElementById("cbPlayer_album").innerHTML = "Album:";
     document.getElementById("cbPlayer_leftSideBox").style.display = "block";
     document.getElementById("cbPlayer_statusbar").innerHTML = "Some files may be ready to play.";
    }

  function finishMedia(i)
    {
     var loadedMedia = document.getElementById("cbPlayer_playlistItemLink_" + i);
     loadedMedia.style.display = "block";
     loadedMedia.style.color = "#000000";
     document.getElementById("cbPlayer_status_" + i).innerHTML = "";
     document.getElementById("cbPlayer_statusbar").innerHTML = "";
    }

  function activateMediaControl(cbplayerControllerId)
    {
     var cbplayerController = document.getElementsByClassName("cbPlayer_mediacontrols");
     var controllerButton = document.getElementById("cbPlayer_" + cbplayerControllerId);
     controllerButton.blur();
     for (var i = 0; i < cbplayerController.length; i++)
       {
        cbplayerController[i].blur();
        cbplayerController[i].removeAttribute("style");
        cbplayerController[i].style.boxShadow = "0px 0px 2px 1px gray";
       }
     controllerButton.style.backgroundColor = "#EEEEEE";
     controllerButton.style.boxShadow = "none";
     if (cbplayerControllerId == "play")
       {
        document.getElementById("cbPlayer_play").src = "cbplayer/pics/play_active.png";
        document.getElementById("cbPlayer_stop").src = "cbplayer/pics/stop.png";
       }
     if (cbplayerControllerId == "stop")
       {
        document.getElementById("cbPlayer_play").src = "cbplayer/pics/play.png";
        document.getElementById("cbPlayer_stop").src = "cbplayer/pics/stop_active.png";
       }


     for (var i = 0; i < mediaElements.length; i++)
       {
        mediaElements[i].removeAttribute("style");
       }
     if (currentMediaId >= mediaElements.length)
       {
        currentMediaId = 0;
        for (var i = 0; i < mediaElements.length; i++)
          {
           mediaElements[i].pause();
           mediaElements[i].currentTime = 0;
          }
       }
     var playlist = document.getElementsByClassName("cbPlayer_playlist");
     for (var i = 0; i < playlist.length; i++)
       {
        playlist[i].removeAttribute("style");
       }
     playlist[currentMediaId].style.backgroundColor = "#E6CC99";
     var mediatype = document.getElementById("cbPlayer_" + currentMediaId).getAttribute("data-mediatype");
     if (mediatype == "video")
       {
        document.getElementById("cbPlayer_playlist").style.display = "none";
        document.getElementById("cbPlayer_fullscreen").removeAttribute("style");
       }
     else
       {
        document.getElementById("cbPlayer_playlist").removeAttribute("style");
        document.getElementById("cbPlayer_fullscreen").style.display = "none";
       }
    }

  function secs2minSecs(time)
    {
     var minutes = Math.floor(time / 60);
     var seconds = "0" + (time - minutes * 60);
     return minutes + ":" + seconds.substr(-2);
    }

  function prevMedia()
    {
     stopMedia();
     currentMediaId--;
     if (currentMediaId < 0) { currentMediaId = 0; }
     playMedia(currentMediaId);
    }

  function playMedia(MediaId)
    {
     stopMedia();
     var anchors = document.getElementsByTagName("a");
     for (var i = 0; i < anchors.length; i++) { anchors[i].blur(); }

     currentMediaId = MediaId;

      if (currentMediaId >= mediaElements.length)
       {
        currentMediaId = 0;
        for (var i = 0; i < mediaElements.length; i++)
          {
           mediaElements[i].pause();
           mediaElements[i].currentTime = 0;
           mediaElements[i].style.display = "none";
           document.getElementById("cbPlayer_playlist").removeAttribute("style");
          }
        return;
       }

     activateMediaControl("play");

     document.getElementById("cbPlayer_" + currentMediaId).style.display = "block";
     document.getElementById("cbPlayer_" + currentMediaId).play();

     document.getElementById("cbPlayer_mediaItems").innerHTML = currentMediaId + 1 + "/" + mediaElements.length;
     var currentMedia = document.getElementById("cbPlayer_" + currentMediaId);
     var duration = Math.floor(currentMedia.duration);
     duration = secs2minSecs(duration);
     document.getElementById("cbPlayer_currentTitle").innerHTML = currentMedia.getAttribute("data-title");
     document.getElementById("cbPlayer_currentArtist").innerHTML = currentMedia.getAttribute("data-artist");
     if (currentMedia.getAttribute("data-year") != "")
       {
        document.getElementById("cbPlayer_currentAlbum").innerHTML = currentMedia.getAttribute("data-album") + " (" + currentMedia.getAttribute("data-year") + ")";
       }
     else
       {
        document.getElementById("cbPlayer_currentAlbum").innerHTML = currentMedia.getAttribute("data-album");
       }
     currentMedia.ontimeupdate = function() { updateTime() };

     function updateTime()
       {
        position = Math.floor(currentMedia.currentTime);
        prettyTime = secs2minSecs(position);
        document.getElementById("cbPlayer_progress").innerHTML = prettyTime + " / " + duration;

        var pbwidth = ( currentMedia.currentTime * 100 ) / currentMedia.duration;
        document.getElementById("cbPlayer_progressbarIndicator").style.width = pbwidth + "%";
       }
    }

  function stopMedia()
    {
     activateMediaControl("stop");
     document.getElementById("cbPlayer_stopButton").blur();
     for (var i = 0; i < mediaElements.length; i++)
       {
        if (mediaElements[i].readyState < 1) { return; }
        else
          {
           mediaElements[i].pause();
           mediaElements[i].currentTime = 0;
          }
       }
     document.getElementById("cbPlayer_playlist").removeAttribute("style");
     document.getElementById("cbPlayer_fullscreen").style.display = "none";
     // exit full-screen
     if (document.exitFullscreen) { document.exitFullscreen(); }
     else if (document.webkitExitFullscreen) { document.webkitExitFullscreen(); }
     else if (document.mozCancelFullScreen) { document.mozCancelFullScreen(); }
     else if (document.msExitFullscreen) { document.msExitFullscreen(); }
    }

  function nextMedia()
    {
     stopMedia();
     currentMediaId++;
     playMedia(currentMediaId);
    }

  function fullscreen()
    {
     document.getElementById("cbPlayer_fullscreen").blur();
     var currentMedia = document.getElementById("cbPlayer_" + currentMediaId);
     // go full-screen
     if (currentMedia.requestFullscreen) { currentMedia.requestFullscreen(); }
     else if (currentMedia.msRequestFullscreen) { currentMedia.msRequestFullscreen(); }
     else if (currentMedia.mozRequestFullScreen) { currentMedia.mozRequestFullScreen(); }
     else if (currentMedia.webkitRequestFullscreen) { currentMedia.webkitRequestFullscreen(); }
    }

  function getCursorPosition(event)
    {
     var currentMedia = document.getElementById("cbPlayer_" + currentMediaId);
     var pb = document.getElementById("cbPlayer_progressbar");
     var rect = pb.getBoundingClientRect();
     var x = event.clientX - rect.left;
     currentMedia.currentTime = (currentMedia.duration * x) / rect.width;
    }

</script>
<noscript>Dieser Medienplayer benötigt JavaScript um zu funktionieren. Dazu müssen Sie JavaScript aktivieren.</noscript>
</div>
<?php
$endtime = microtime(true);
//echo "<p id=\"footer\" style=\"font-size: 0.7em; text-align: center;\">Processing needed " . number_format($endtime - $starttime, 3) . " seconds.</p>\n";
//echo "<pre>"; print_r($files); echo "</pre>\n";
?>
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
$version = "v0.07";

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
    <a href="javascript:prevMedia();"><img id="cbPlayer_prev" class="cbPlayer_mediacontrols" src="<?php echo $cbPlayer_dirname; ?>/pics/rwd.png" alt="prev" title="prev"></a>
    <a href="javascript:playMedia(currentMediaId);"><img id="cbPlayer_play" class="cbPlayer_mediacontrols" src="<?php echo $cbPlayer_dirname; ?>/pics/play.png" alt="play" title="play"></a>
    <a href="javascript:pauseMedia(currentMediaId);"><img id="cbPlayer_pause" class="cbPlayer_mediacontrols" src="<?php echo $cbPlayer_dirname; ?>/pics/pause.png" alt="pause" title="pause"></a>
    <a href="javascript:stopMedia(true);" id="cbPlayer_stopButton"><img id="cbPlayer_stop" class="cbPlayer_mediacontrols" src="<?php echo $cbPlayer_dirname; ?>/pics/stop.png" alt="stop" title="stop"></a>
    <a href="javascript:nextMedia();"><img id="cbPlayer_next" class="cbPlayer_mediacontrols" src="<?php echo $cbPlayer_dirname; ?>/pics/fwd.png" alt="next" title="next"></a>
    <a class="cbPlayer_fullscreen" href="javascript:fullscreen();" onclick="fullscreen();">
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

// =====================
// ==  Init cbplayer  ==
// ==================================================
// ==  Read all media items and create a playlist  ==
// ==================================================

function initPlayer()
  {
   currentMediaId = 0;
   version = "<?php echo $version; ?>";
   cbPlayer_dir = "<?php echo $cbPlayer_dirname; ?>";
   mediaElements = document.getElementsByClassName("cbPlayer_mediaContent");
   isPlaying = false;
   isPaused = false;

   addEventListener("mozfullscreenchange",function(){ trackFullScreen(); }, false);
   addEventListener("webkitfullscreenchange",function(){ trackFullScreen(); }, false);
   addEventListener("msfullscreenchange",function(){ trackFullScreen(); }, false);

   createPlaylist();
  }

// =======================
// ==  Create Playlist  ==
// =======================
function createPlaylist()
  {
   for (var i = 0; i < mediaElements.length; i++)
     {
        var a = document.createElement("a"); // create Link - non-clickable yet, until we have the necessary data downloaded!
        a.className = "cbPlayer_playlistitem";
        a.id = "cbPlayer_playlistItemLink_" + i;
        a.href = "javascript:playMedia(" + i + ");";

        var div = document.createElement("div");
        div.id = i + "_" + mediaElements[i].getAttribute("data-filename");
        div.className = "cbPlayer_playlist " + i;

        var img = document.createElement("img");
        img.className = "mediaIcon";
        img.src = cbPlayer_dir + "/pics/" + mediaElements[i].getAttribute("data-mediatype") + ".png";
        div.appendChild(img);

        var textnode = document.createTextNode(mediaElements[i].getAttribute("data-artist") + ' - ' + mediaElements[i].getAttribute("data-title"));
        div.appendChild(textnode);

        a.appendChild(div);
        var parent = document.getElementById("cbPlayer_playlist");
        parent.appendChild(a);

        if (mediaElements.readyState > 0) showMedia(i);
        if (mediaElements.readyState > 2) activateMedia(i);
        if (mediaElements.readyState > 3) finishMedia(i);
     }

// =======================================
// ==  create controller and infoboxes  ==
// =======================================
   document.getElementById("cbPlayer_progressbar").style.display = "block";
   document.getElementById("cbPlayer_mediaItems").innerHTML = currentMediaId + 1 + "/" + mediaElements.length;
   document.getElementById("cbPlayer_leftSideBox").style.display = "block";
   document.getElementById("cbPlayer_artist").innerHTML = "Artist:";
   document.getElementById("cbPlayer_title").innerHTML = "Title:";
   document.getElementById("cbPlayer_album").innerHTML = "Album:";
   document.getElementById("cbPlayer_download").innerHTML = "Download:";
  }

function createMediaItem(i)
  {
   mediaDiv = document.getElementById("cbPlayer_media");
   while (mediaDiv.hasChildNodes())
     {
      mediaDiv.removeChild(mediaDiv.firstChild);
     }
   var currentMedia = document.getElementById("cbPlayer_" + i);
   var currentMediaType = currentMedia.getAttribute("data-mediatype");
   var media = document.createElement(currentMediaType);
   media.className = "cbPlayer_mediaElement";
   media.id = i;
   media.preload = "metadata";
   media.controls = false;
   media.onended = function() { nextMedia() };

   var currentMediaSources = document.getElementsByClassName("cbPlayer_src_" + i);
   for (var sources = 0; sources < currentMediaSources.length; sources++)
     {
      var currentMediaSource = document.createElement("source");
      currentMediaSource.className = "cbPlayer_mediaSources_" + i;
      currentMediaSource.id = "cbPlayer_playlistItem_" + i + "_" + currentMediaSources[sources].getAttribute("data-fileformat");
      currentMediaSource.src = currentMediaSources[sources].getAttribute("data-src");
      currentMediaSource.type = currentMediaSources[sources].getAttribute("data-type");
      media.appendChild(currentMediaSource);
     }

   var parent = document.getElementById("cbPlayer_media");
   parent.appendChild(media);
   document.getElementById(i).load();
  }

function unloadPrevMedia()
  {
   var currentlyPlaying = document.getElementById(currentMediaId);
   var mediaSources = document.getElementsByClassName("cbPlayer_mediaSources_" + currentMediaId);
   for (var i = 0; i < mediaSources.length; i++)
     {
      mediaSources[i].src = "";
     }
   currentlyPlaying.load();
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
     controllerButton.style.boxShadow = "0px 0px 2px 1px #CCC";
     if (cbplayerControllerId == "play")
       {
        document.getElementById("cbPlayer_play").src = cbPlayer_dir + "/pics/play_active.png";
        document.getElementById("cbPlayer_pause").src = cbPlayer_dir + "/pics/pause.png";
        document.getElementById("cbPlayer_stop").src = cbPlayer_dir + "/pics/stop.png";
       }
     if (cbplayerControllerId == "stop")
       {
        document.getElementById("cbPlayer_play").src = cbPlayer_dir + "/pics/play.png";
        document.getElementById("cbPlayer_pause").src = cbPlayer_dir + "/pics/pause.png";
        document.getElementById("cbPlayer_stop").src = cbPlayer_dir + "/pics/stop_active.png";
       }
     if (cbplayerControllerId == "pause")
       {
        document.getElementById("cbPlayer_play").src = cbPlayer_dir + "/pics/play.png";
        document.getElementById("cbPlayer_pause").src = cbPlayer_dir + "/pics/pause_active.png";
        document.getElementById("cbPlayer_stop").src = cbPlayer_dir + "/pics/stop.png";
       }

     if (cbplayerControllerId != "pause")
          {
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
    }

function secs2minSecs(time)
    {
     var minutes = Math.floor(time / 60);
     var seconds = "0" + (time - minutes * 60);
     return minutes + ":" + seconds.substr(-2);
    }

function prevMedia()
    {
     if (isPlaying == true) { stopMedia(false); }
     currentMediaId--;
     if (currentMediaId < 0) { currentMediaId = 0; }
     playMedia(currentMediaId);
    }

function playMedia(MediaId)
    {
     if (isPlaying == true)
       {
        if (isPaused != true)
          {
           stopMedia(false);
          }
       }
     if (isPaused != true)
       {
        createMediaItem(MediaId);
       }

     var anchors = document.getElementsByTagName("a");
     for (var i = 0; i < anchors.length; i++) { anchors[i].blur(); }

     currentMediaId = MediaId;

      // Lets avoid overshooting the playlist!
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

     if (document.getElementById("cbPlayer_" + currentMediaId).getAttribute("data-mediatype") == "video")
       {
        document.getElementById(currentMediaId).style.display = "block";
       }
     document.getElementById(currentMediaId).play();
     isPlaying = true;
     isPaused = false;

     document.getElementById("cbPlayer_mediaItems").innerHTML = currentMediaId + 1 + "/" + mediaElements.length;
     var currentMedia = document.getElementById(currentMediaId);
     var currentMediaData = document.getElementById("cbPlayer_" + currentMediaId);

     document.getElementById("cbPlayer_currentTitle").innerHTML = currentMediaData.getAttribute("data-title");
     document.getElementById("cbPlayer_currentArtist").innerHTML = currentMediaData.getAttribute("data-artist");
     if (currentMedia.getAttribute("data-year") != "")
       {
        document.getElementById("cbPlayer_currentAlbum").innerHTML = currentMediaData.getAttribute("data-album") + " (" + currentMediaData.getAttribute("data-year") + ")";
       }
     else
       {
        document.getElementById("cbPlayer_currentAlbum").innerHTML = currentMediaData.getAttribute("data-album");
       }
     var downloads = document.getElementsByClassName("cbPlayer_src_" + currentMediaId);
        document.getElementById("cbPlayer_currentDownload").innerHTML = "";
     for (var i = 0; i < downloads.length; i++)
       {
        a = document.createElement("a");
        a.class = "cbPlayer_downloadLink";
        a.href = downloads[i].getAttribute("data-src");
        a.download = currentMediaData.getAttribute("data-artist") + " - " + currentMediaData.getAttribute("data-title") + "." + downloads[i].getAttribute("data-fileformat");
        a.text = downloads[i].getAttribute("data-type");
        document.getElementById("cbPlayer_currentDownload").appendChild(a);
        var filesize = downloads[i].getAttribute("data-filesize") / 1024 / 1024;
        var shortenedFilesize = Math.round(filesize * 100) / 100;
        var app = " (" + shortenedFilesize + " MB) ";
        document.getElementById("cbPlayer_currentDownload").innerHTML += app;
       }
     currentMedia.ontimeupdate = function() { updateTime() };

     function updateTime()
       {
        var currentMediaData = document.getElementById("cbPlayer_" + currentMediaId);
        var mediaDuration = Math.floor(currentMedia.duration);
        var duration = secs2minSecs(mediaDuration);

        position = Math.floor(currentMedia.currentTime);
        prettyTime = secs2minSecs(position);
        document.getElementById("cbPlayer_progress").innerHTML = prettyTime + " / " + duration;

        var pbwidth = ( currentMedia.currentTime * 100 ) / currentMedia.duration;
        document.getElementById("cbPlayer_progressbarIndicator").style.width = pbwidth + "%";
       }
    }

function stopMedia(unload)
  {
   activateMediaControl("stop");
   document.getElementById("cbPlayer_stopButton").blur();
   if (isPlaying == true)
     {
      var currentlyPlaying = document.getElementById(currentMediaId);
      currentlyPlaying.pause();
      currentlyPlaying.currentTime = 0;
      if (unload == true) { setTimeout(function(){ unloadPrevMedia(); }, 100); }
     }
   isPlaying = false;
   isPaused = false;

   document.getElementById("cbPlayer_playlist").removeAttribute("style");
   document.getElementById("cbPlayer_fullscreen").style.display = "none";
   document.getElementById(currentMediaId).removeAttribute("style");

   // exit full-screen
   if (document.exitFullscreen) { document.exitFullscreen(); }
   else if (document.webkitExitFullscreen) { document.webkitExitFullscreen(); }
   else if (document.mozCancelFullScreen) { document.mozCancelFullScreen(); }
   else if (document.msExitFullscreen) { document.msExitFullscreen(); }
   document.getElementById(currentMediaId).controls = false;
  }

function pauseMedia()
  {
   activateMediaControl("pause");
   document.getElementById("cbPlayer_pause").blur();
   if (isPlaying == true)
     {
      document.getElementById(currentMediaId).pause();
      isPaused = true;
     }
  }

function nextMedia()
    {
     if (isPlaying == true) { stopMedia(false); }
     currentMediaId++;
     playMedia(currentMediaId);
    }

function fullscreen()
    {
     document.getElementById("cbPlayer_fullscreen").blur();
     var currentMedia = document.getElementById(currentMediaId);

     currentMedia.controls = true;

     // go full-screen
     if (currentMedia.requestFullscreen) { currentMedia.requestFullscreen(); }
     else if (currentMedia.msRequestFullscreen) { currentMedia.msRequestFullscreen(); }
     else if (currentMedia.mozRequestFullScreen) { currentMedia.mozRequestFullScreen(); }
     else if (currentMedia.webkitRequestFullscreen) { currentMedia.webkitRequestFullscreen(); }
    }
    
function trackFullScreen()
    {
     // are we full-screen?
     if ( document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement )
         { document.getElementById(currentMediaId).controls = true; }
     else
         { document.getElementById(currentMediaId).controls = false; }
    }

function getCursorPosition(event)
    {
     var currentMedia = document.getElementById(currentMediaId);
     var pb = document.getElementById("cbPlayer_progressbar");
     var rect = pb.getBoundingClientRect();
     var x = event.clientX - rect.left;
     currentMedia.currentTime = (currentMedia.duration * x) / rect.width;
    }

initPlayer();

</script>
<noscript>Dieser Medienplayer benötigt JavaScript um zu funktionieren. Dazu müssen Sie JavaScript aktivieren.</noscript>
</div>
<?php
$endtime = microtime(true);
//echo "<p id=\"footer\" style=\"font-size: 0.7em; text-align: center;\">Processing needed " . number_format($endtime - $starttime, 3) . " seconds.</p>\n";
//echo "<pre>"; print_r($files); echo "</pre>\n";
?>

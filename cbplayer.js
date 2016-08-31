
// =====================
// ==  Init cbplayer  ==
// ==================================================
// ==  Read all media items and create a playlist  ==
// ==================================================

var cbPlayerInit = false;

function initPlayer()
  {
   console.log("cbPlayer: Initializing cbPlayer...");

   // Stop init from loading twice!
   if ( cbPlayerInit == true ) { console.log("cbPlayer: initPlayer(): already initialized. Exiting."); return; }

   currentMediaId = 0;
   fileSupport = 0;
   mediaElements = document.getElementsByClassName("cbPlayer_mediaContent");
   console.log("cbPlayer: Found " + mediaElements.length + " media items");
   isPlaying = false;
   isPaused = false;
   var programData = document.getElementById('cbPlayer_programLocaleData');
   version = programData.getAttribute("data-version");
   console.log("cbPlayer: Version: " + version);
   cbPlayer_dir = programData.getAttribute("data-cbPlayer_dir");
   console.log("cbPlayer: cbPlayer_dir: " + cbPlayer_dir);
   showDownload = programData.getAttribute("data-showDownload");
   console.log("cbPlayer: showDownload: " + showDownload);
   if ( showDownload == "false" ) { showDownload = false; }
   else { showDownload = true; }
   stringTitle = programData.getAttribute("data-stringTitle");
   stringArtist = programData.getAttribute("data-stringArtist");
   stringAlbum = programData.getAttribute("data-stringAlbum");
   stringDownload = programData.getAttribute("data-stringDownload");
   console.log("cbPlayer: cbPlayer Locale strings: " + stringTitle + " " + stringArtist + " " + stringAlbum + " " + stringDownload);

   document.getElementById("cbPlayer_progressbar").addEventListener("click", getCursorPosition, false );
   document.getElementById("cbPlayer_prev").addEventListener("click", function() { prevMedia(); });
   document.getElementById("cbPlayer_play").addEventListener("click", function() { playMedia(currentMediaId); });
   document.getElementById("cbPlayer_pause").addEventListener("click", function() { pauseMedia(currentMediaId); });
   document.getElementById("cbPlayer_stop").addEventListener("click", function() { stopMedia(true); });
   document.getElementById("cbPlayer_next").addEventListener("click", function() { nextMedia(); });
   document.getElementById("cbPlayer_fullscreen").addEventListener("click", function() { fullscreen(); });

   addEventListener("mozfullscreenchange",function(){ trackFullScreen(); }, false);
   addEventListener("webkitfullscreenchange",function(){ trackFullScreen(); }, false);
   addEventListener("msfullscreenchange",function(){ trackFullScreen(); }, false);

   createPlaylist();
   console.log("cbPlayer: initPlayer() has finished.");
   cbPlayerInit = true;
  }

// =======================
// ==  Create Playlist  ==
// =======================
function createPlaylist()
  {
   // Stop init from loading twice!
   if ( cbPlayerInit == true ) { console.log("cbPlayer: createPlaylist(): already initialized. Exiting."); return; }

   for (var i = 0; i < mediaElements.length; i++)
     {
        var a = document.createElement("a"); // create Link - non-clickable yet, until we have the necessary data downloaded!
        a.className = "cbPlayer_playlistitem";
        a.id = "cbPlayer_playlistItemLink_" + i;
	a.href = "javascript:void(0);";
        a.dataset.id = i;

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

        document.getElementById("cbPlayer_playlistItemLink_" + i).addEventListener("click", function () {
	  var cbPlayer_TrackId = Number(this.getAttribute("data-id"));
	  playMedia(cbPlayer_TrackId);
	});
     }

// =======================================
// ==  create controller and infoboxes  ==
// =======================================
   document.getElementById("cbPlayer_progressbar").style.display = "block";
   document.getElementById("cbPlayer_mediaItems").innerHTML = currentMediaId + 1 + "/" + mediaElements.length;
   document.getElementById("cbPlayer_leftSideBox").style.display = "block";
   document.getElementById("cbPlayer_artist").innerHTML = stringArtist;
   document.getElementById("cbPlayer_title").innerHTML = stringTitle;
   document.getElementById("cbPlayer_album").innerHTML = stringAlbum;
   if (showDownload != false) { document.getElementById("cbPlayer_download").innerHTML = stringDownload; }
   cbPlayerInit = true;
  }

function createMediaTag(i)
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

   fileSupport = 0;
   var currentMediaSources = document.getElementsByClassName("cbPlayer_src_" + i);
   for (var sources = 0; sources < currentMediaSources.length; sources++)
     {
     /* var type = currentMediaSources[sources].getAttribute("data-type");
      var codec = currentMediaSources[sources].getAttribute("data-codec");
      var isSupported = media.canPlayType(type + '; codecs="' + codec + '"');
      if (isSupported != "")
	{
	 fileSupport++;
        } */
      var currentMediaSource = document.createElement("source");
      currentMediaSource.className = "cbPlayer_mediaSources_" + i;
      currentMediaSource.id = "cbPlayer_playlistItem_" + i + "_" + currentMediaSources[sources].getAttribute("data-fileformat");
      currentMediaSource.src = currentMediaSources[sources].getAttribute("data-src");
      currentMediaSource.type = currentMediaSources[sources].getAttribute("data-type");
      media.appendChild(currentMediaSource);
     }

   var parent = document.getElementById("cbPlayer_media");
   parent.appendChild(media);
 /*  document.getElementById("cbPlayer_statusbar").innerHTML = toString(fileSupport);
   if (fileSupport < 1) 
     {
      parent.innerHTML = "Diese Datei liegt nicht in einem unterstützten Format vor und kann leider von ihrem Browser nicht wiedergegeben werden.";
      return;
     } */
   var mediaFile = document.getElementById(i);
   mediaFile.load();
  }

function unloadPrevMedia()
  {
   setTimeout(function()
       {
        var currentlyPlaying = document.getElementById(currentMediaId);
        var mediaSources = document.getElementsByClassName("cbPlayer_mediaSources_" + currentMediaId);

        for (var i = 0; i < mediaSources.length; i++)
            {
             mediaSources[i].src = "";
            }
        currentlyPlaying.load();
       }
   , 500);
  }

function activateMediaControl(cbplayerControllerId)
    {
     var cbplayerController = document.getElementsByClassName("cbPlayer_mediacontrols");
     var controllerButton = document.getElementById("cbPlayer_" + cbplayerControllerId);
     //controllerButton.blur();
     for (var i = 0; i < cbplayerController.length; i++)
       {
        //cbplayerController[i].blur();
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
     console.log("cbPlayer: calling playMedia(" + MediaId + ");");
     if (isPlaying == true)
       {
        if (isPaused != true)
          {
           stopMedia(false);
          }
       }
     if (isPaused != true)
       {
        createMediaTag(MediaId);
       }

     //var anchors = document.getElementsByTagName("a");
     //for (var i = 0; i < anchors.length; i++) { anchors[i].blur(); }

     currentMediaId = MediaId;

      // Lets avoid overshooting the playlist!
      if (currentMediaId >= mediaElements.length)
       {
        currentMediaId = 0;
        stopMedia(true);
        return;
       }

     activateMediaControl("play");

     if (document.getElementById("cbPlayer_" + currentMediaId).getAttribute("data-mediatype") == "video")
       {
        document.getElementById(currentMediaId).style.display = "block";
	document.getElementById("cbPlayer_fullscreen").style.display = "inline";
       }
     document.getElementById(currentMediaId).play();
     isPlaying = true;
     isPaused = false;

     var cbPlayer_trackNumber = currentMediaId;
     cbPlayer_trackNumber += 1;
     document.getElementById("cbPlayer_mediaItems").innerHTML = cbPlayer_trackNumber + "/" + mediaElements.length;
     var currentMedia = document.getElementById(currentMediaId);
     var currentMediaData = document.getElementById("cbPlayer_" + currentMediaId);

     document.getElementById("cbPlayer_currentTitle").innerHTML = currentMediaData.getAttribute("data-title");
     document.getElementById("cbPlayer_currentArtist").innerHTML = currentMediaData.getAttribute("data-artist");
     document.getElementById("cbPlayer_currentAlbum").innerHTML = currentMediaData.getAttribute("data-album");
     if (currentMediaData.getAttribute("data-year") != "" || currentMediaData.getAttribute("data-year") == undefined)
       {
        document.getElementById("cbPlayer_currentAlbum").innerHTML += " (" + currentMediaData.getAttribute("data-year") + ")";
       }
     var downloads = document.getElementsByClassName("cbPlayer_src_" + currentMediaId);
        document.getElementById("cbPlayer_currentDownload").innerHTML = "";

     if (showDownload != false)
        {
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
   //document.getElementById("cbPlayer_stopButton").blur();
   if (isPlaying == true)
     {
      var currentlyPlaying = document.getElementById(currentMediaId);
      currentlyPlaying.pause();
      currentlyPlaying.currentTime = 0;
      if (unload == true) { unloadPrevMedia(); }
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
   //document.getElementById("cbPlayer_pause").blur();
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
     //document.getElementById("cbPlayer_fullscreen").blur();
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

document.addEventListener('DOMContentLoaded', function ()
  {
   console.log("DOMContentLoaded has fired! Calling initPlayer(); ...");
   initPlayer();
  });

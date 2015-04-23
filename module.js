M.local_jwplayer = {};

M.local_jwplayer.init = function(Y, params) {
    var playerid = params.playerid;
    var setupdata = params.setupdata;
    var downloadbtn = params.downloadbtn || undefined;

    jwplayer(playerid).setup(setupdata);
    if (downloadbtn !== undefined) {
        jwplayer(playerid).addButton(downloadbtn.img, downloadbtn.tttext, function() {
                // Grab the file that's currently playing.
                window.location.href = jwplayer(playerid).getPlaylistItem().file + '?forcedownload=true';
            }, "download");
    }
};

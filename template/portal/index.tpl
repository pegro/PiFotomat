<script>
    var server_now = {time()};
    // this example takes 2 seconds to run
    var browser_now = Math.floor(Date.now() / 1000);

    var clock_diff = Math.abs(browser_now - server_now);

    if (clock_diff > 30 && confirm('Die Systemzeit vom PiFotomat scheint nicht zu stimmen. Möchtest du die Zeit neu setzen auf '+
        new Date().toLocaleString('de-DE', { timeZone: 'Europe/Berlin' })+'?')) {
        var xhttp = new XMLHttpRequest();
        xhttp.open("GET", "settings/settime?time=" + browser_now, true);
        xhttp.send();
    }
</script>

Bild aktualisiert: {$latest_mtime|date_format:"%F %T"} - <a href="javascript:location.reload();"><button>Neu laden</button></a>
<div style="width:100%; height: 90%">
    <img src="sdcard/timelapse/latest.jpg" style="width:100%;"/>
</div>

<br/>
<h5>Status</h5>
<ul>
    <li>Kamera: {if empty($pid_raspistill)}<strong style="color:red;">läuft nicht!</strong>{else}<strong style="color:green">läuft</strong>{/if}</li>
    <li>Letztes Bild vor: vor {time()-$latest_mtime} Sekunden</li>
    <li>Freier Speicher SD-Karte: {$disk_free_sdcard}</li>
    <li>Freier Speicher Externe Festplatte: {if !empty($disk_free_external)}{$disk_free_external}{else}<strong style="color: red">Nicht angeschlossen</strong>{/if}</li>
</ul>
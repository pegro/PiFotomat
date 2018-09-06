{if !empty($dates)}
        <h4>SD Karte</h4>
        <div class="container">
                {foreach $dates.sdcard as $date}
                    <a href="{route _name="browser_index" date=$date}"><button type="button" class="btn btn-secondary" title="">{$date}</button></a>
                {/foreach}
        </div>

        <h4>Externe Festplatte</h4>
        <div class="container">
                {foreach $dates.archive as $date}
                        <a href="{route _name="browser_index" date=$date archive=1}"><button type="button" class="btn btn-secondary" title="">{$date}</button></a>
                {/foreach}
        </div>
{/if}

{if !empty($times)}
        <h4>{$date} - <a href="{route _name="browser_index"}"><button>Zurück</button></a></h4>
        <div class="container">
                {foreach $times as $hour}
                        <a href="{route _name="browser_index" date=$date archive=$archive hour=$hour}"><button type="button" class="btn btn-secondary" title="">{$hour}:XX</button></a>
                {/foreach}
        </div>
{/if}

{if !empty($items)}
        <h4>{$date} {$hour}:XX - <a href="{route _name="browser_index" date=$date archive=$archive}"><button>Zurück</button></a></h4>
        <div class="container">
                {foreach $items as $image}
                        <a href="{route _name="browser_image" date=$date archive=$archive image=$image.name}"><button type="button" class="btn btn-secondary" title="">{$image.file_mtime|date_format:"%H:%M:%S"}</button></a>
                {/foreach}
        </div>
{/if}


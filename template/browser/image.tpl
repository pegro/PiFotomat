<h4>{$image.file_mtime|date_format:"%F %H:%M"}</h4>
<h6>{if !empty($image.prev)}<a href="{route _name="browser_image" date=$date archive=$archive image=$image.prev}"><button>Zurück</button></a> -{/if}
    <a href="{route _name="browser_index" date=$date archive=$archive hour=$hour}"><button>Liste</button></a>
    {if !empty($image.next)}- <a href="{route _name="browser_image" date=$date archive=$archive image=$image.next}"><button>Vor</button></a>{/if}
</h6>

<div style="width:100%; height: 90%">
    <img src="{$image.path}/{$image.name}" style="width:100%;"/>
</div>
<div class="row p-y-1">
    <div class="col-md-4">
        <img src="{$link->getMediaLink('/modules/laybuy/logo-full.png')}" alt="{l s='Laybuy' d='Modules.Laybuy.Front'}"
             width="250" height="56" class="img-fluid" />
    </div>
    <div class="col-md-8">
        {l s='Spread the cost over 6 weekly, interest free payments of %s with Laybuy' sprintf=[$laybuyProductAmountByWeek] d='Modules.Laybuy.Front'}
    </div>
</div>
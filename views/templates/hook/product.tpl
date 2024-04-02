<div class="row py-2">
    <div class="col-md-12">
        <div class="d-flex align-items-center">
            {l s='or 6 interest free payments of %s' sprintf=[$laybuyProductAmountByWeek] d='Modules.Laybuy.Front'}

            <img src="{$link->getMediaLink('/modules/laybuy/laybuy_badge_neon_grape.svg')}" alt="{l s='Laybuy' d='Modules.Laybuy.Front'}"
                 width="70" height="80" />
            <a href="https://popup.laybuy.com" onclick="return !window.open(this.href, 'Laybuy', 'width=405,height=620')"
               target="_blank">Learn more</a>
        </div>
    </div>
</div>

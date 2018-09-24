<div class="panel" id="laybuyOrderView">
    <div class="panel-heading">
        <img src="{$link->getMediaLink('/modules/laybuy/logo.png')}" alt="" width="20" height="20"/>
        {l s='Laybuy'}
    </div>

    {if null === $laybuyOrderCurrentState}
        <div class="alert alert-danger">
            {l s='There is not any order match.'}
        </div>
    {else}
        {if 'error' === $laybuyOrderCurrentState}
            <div class="alert alert-danger">
                {l s='There is not any order match.'}
            </div>
        {elseif 'canceled' === $laybuyOrderCurrentState}
            <div class="alert alert-warning">
                {l s='This order has been canceled'}
            </div>
        {elseif 'refunded' === $laybuyOrderCurrentState}
            <div class="alert alert-info">
                {l s='This order has been refunded'}
            </div>
        {elseif 'unconfirmed' === $laybuyOrderCurrentState}
            <div class="alert alert-danger">
                {l s='This order has not a confirmed state on Prestashop\'s side.'}
            </div>
        {/if}
        <p>
            <h4>{l s='Laybuy order details:'}</h4>
        </p>
        <p><strong>{l s='Current state:'}</strong> {$laybuyOrder->current_state}</p>
        <p><strong>{l s='Token:'}</strong> {$laybuyOrder->token}</p>
        <p><strong>{l s='Laybuy order ID:'}</strong> {$laybuyOrder->laybuy_order_id}</p>
    {/if}

    {if null === $laybuyOrderApi}
        <div class="alert alert-danger">
            {l s='There is not any order match.'}
        </div>
    {/if}
</div>
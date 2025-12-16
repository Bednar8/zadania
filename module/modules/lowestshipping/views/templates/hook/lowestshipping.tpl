{if $has_cart && $lowest_delivery_price !== null}
    <p>{l s='Obecna najniższa cena wysyłki w koszyku:' mod='lowestshipping'} {$lowest_delivery_price}</p>
{elseif $has_cart && $lowest_delivery_price === null}
    <p>{l s='Nie ma dostawy w koszyku' mod='lowestshipping'}</p>
{else}
    <p>{l s='Dodaj do koszyka, to wtedy zobaczysz cenę dostawy' mod='lowestshipping'}</p>
{/if}
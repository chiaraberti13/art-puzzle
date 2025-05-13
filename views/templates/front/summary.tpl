{extends file='page.tpl'}

{block name='page_title'}
    {l s='Riepilogo Personalizzazione Puzzle' mod='art_puzzle'}
{/block}

{block name='page_content'}
    <div class="art-puzzle-summary-page">
        <div class="row">
            <div class="col-md-12">
                <a href="{$smarty.server.HTTP_REFERER}" class="btn btn-outline-secondary mb-3">
                    <i class="material-icons">arrow_back</i> {l s='Torna indietro' mod='art_puzzle'}
                </a>

                <div class="art-puzzle-container">
                    <h3>{l s='Riepilogo della tua personalizzazione' mod='art_puzzle'}</h3>

                    <div class="alert alert-info">
                        <p>{l s='Ecco un riepilogo del tuo puzzle personalizzato. Verifica attentamente tutti i dettagli prima di procedere con l\'ordine.' mod='art_puzzle'}</p>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h4>{l s='Anteprima Puzzle' mod='art_puzzle'}</h4>
                            {if isset($uploaded_image)}
                                <img src="{$uploaded_image}" alt="Puzzle" class="img-fluid border" />
                            {/if}
                        </div>

                        <div class="col-md-6">
                            <h4>{l s='Scatola Personalizzata' mod='art_puzzle'}</h4>
                            {if isset($box_image)}
                                <img src="{$box_image}" alt="Scatola" class="img-fluid border" />
                            {/if}
                            {if isset($box_text)}
                                <p class="mt-2"><strong>{l s='Testo:' mod='art_puzzle'}</strong> {$box_text}</p>
                            {/if}
                            {if isset($selected_format)}
                                <p><strong>{l s='Formato:' mod='art_puzzle'}</strong> {$selected_format}</p>
                            {/if}
                        </div>
                    </div>

                    <div class="text-center mt-5">
                        <form method="post" action="{$link->getPageLink('cart', true)}">
                            <input type="hidden" name="add" value="1">
                            <input type="hidden" name="id_product" value="{$id_product}">
                            <input type="hidden" name="token" value="{$static_token}">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="material-icons">shopping_cart</i> {l s='Aggiungi al carrello' mod='art_puzzle'}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
{/block}

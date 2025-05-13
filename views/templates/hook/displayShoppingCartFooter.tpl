{*
* Art Puzzle Module - Shopping Cart Footer Template
*}

{if isset($art_puzzle_items) && count($art_puzzle_items) > 0}
    <div class="art-puzzle-cart-summary">
        <div class="card mt-3">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="material-icons d-inline-block align-middle mr-1">brush</i>
                    {l s='Puzzle personalizzati' mod='art_puzzle'}
                </h4>
            </div>
            <div class="card-body">
                <div class="row">
                    {foreach from=$art_puzzle_items item=item}
                        <div class="col-md-6 mb-3">
                            <div class="puzzle-customization-card">
                                <div class="row no-gutters">
                                    <div class="col-4">
                                        {if isset($item.box_preview) && $item.box_preview}
                                            <img src="{$item.box_preview}" alt="{l s='Scatola personalizzata' mod='art_puzzle'}" class="img-fluid">
                                        {else}
                                            <div class="preview-placeholder">
                                                <i class="material-icons">image</i>
                                            </div>
                                        {/if}
                                    </div>
                                    <div class="col-8">
                                        <div class="customization-details p-2">
                                            <h5 class="mb-1">{$item.product_name}</h5>
                                            
                                            {if isset($item.format_name) && $item.format_name}
                                                <p class="mb-1">{l s='Formato:' mod='art_puzzle'} {$item.format_name}</p>
                                            {/if}
                                            
                                            {if isset($item.dimensions) && $item.dimensions}
                                                <p class="mb-1">{l s='Dimensioni:' mod='art_puzzle'} {$item.dimensions}</p>
                                            {/if}
                                            
                                            {if isset($item.pieces) && $item.pieces}
                                                <p class="mb-1">{l s='Pezzi:' mod='art_puzzle'} {$item.pieces}</p>
                                            {/if}
                                            
                                            {if isset($item.box_text) && $item.box_text}
                                                <p class="mb-1">{l s='Testo:' mod='art_puzzle'} "{$item.box_text|escape:'html':'UTF-8'}"</p>
                                            {/if}
                                        </div>
                                    </div>
                                </div>
                                
                                {if isset($item.edit_url) && $item.edit_url}
                                    <div class="edit-customization text-right mt-1">
                                        <a href="{$item.edit_url}" class="btn btn-sm btn-outline-primary">
                                            <i class="material-icons small">edit</i> {l s='Modifica' mod='art_puzzle'}
                                        </a>
                                    </div>
                                {/if}
                            </div>
                        </div>
                    {/foreach}
                </div>
                
                <div class="alert alert-info mt-3 mb-0">
                    <i class="material-icons d-inline-block align-middle mr-1">info</i>
                    {l s='I nostri puzzle sono prodotti artigianalmente in base alle tue personalizzazioni. Ti raccomandiamo di ricontrollare attentamente i dettagli.' mod='art_puzzle'}
                </div>
            </div>
        </div>
    </div>
{/if}

<style>
    .art-puzzle-cart-summary .puzzle-customization-card {
        border: 1px solid #ddd;
        border-radius: 5px;
        overflow: hidden;
        background-color: #f9f9f9;
    }
    
    .art-puzzle-cart-summary .preview-placeholder {
        background-color: #e0e0e0;
        height: 100%;
        min-height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .art-puzzle-cart-summary .preview-placeholder i {
        font-size: 2rem;
        color: #888;
    }
    
    .art-puzzle-cart-summary .material-icons.small {
        font-size: 1rem;
    }
</style>
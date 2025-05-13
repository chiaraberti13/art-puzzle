{extends file='page.tpl'}

{block name='page_title'}
    {l s='Personalizza il tuo puzzle' mod='art_puzzle'}
{/block}

{block name='page_content'}
    <div class="art-puzzle-customizer-page">
        <div class="row">
            <div class="col-md-12">
                <a href="{$product_url}" class="btn btn-outline-secondary mb-3">
                    <i class="material-icons">arrow_back</i> {l s='Torna al prodotto' mod='art_puzzle'}
                </a>
                
                <div class="art-puzzle-container">
                    <h3>{l s='Personalizza il tuo' mod='art_puzzle'} {$product->name}</h3>
                    <div class="art-puzzle-steps">
                        <div class="step step-1 active">
                            <h4>1. {l s='Carica la tua immagine' mod='art_puzzle'}</h4>
                            <div class="art-puzzle-upload-zone">
                                <p><i class="material-icons">cloud_upload</i></p>
                                <p>{l s='Trascina qui la tua immagine' mod='art_puzzle'}</p>
                                <p>{l s='oppure' mod='art_puzzle'}</p>
                                <input type="file" id="art-puzzle-file" accept="image/*" style="display: none;">
                                <button class="btn btn-primary" id="art-puzzle-browse-btn">{l s='Seleziona file' mod='art_puzzle'}</button>
                            </div>
                            <div class="art-puzzle-preview" style="display: none;">
                                <img id="art-puzzle-preview-img" src="" alt="{l s='Anteprima immagine' mod='art_puzzle'}">
                                <button class="btn btn-link" id="art-puzzle-change-img">{l s='Cambia immagine' mod='art_puzzle'}</button>
                            </div>
                            <div class="text-center mt-3">
                                <button class="btn btn-primary" id="art-puzzle-next-step" disabled>{l s='Avanti' mod='art_puzzle'}</button>
                            </div>
                        </div>
                        <div class="step step-2" style="display: none;">
                            <h4>2. {l s='Regola la tua immagine' mod='art_puzzle'}</h4>
                            <div class="art-puzzle-crop-container">
                                <img id="art-puzzle-crop-img" src="" alt="{l s='Immagine da ritagliare' mod='art_puzzle'}">
                            </div>
                            <div class="art-puzzle-orientation-buttons mt-2">
                                <button class="btn btn-outline-secondary" id="art-puzzle-rotate-left">
                                    <i class="material-icons">rotate_left</i> {l s='Ruota a sinistra' mod='art_puzzle'}
                                </button>
                                <button class="btn btn-outline-secondary" id="art-puzzle-rotate-right">
                                    <i class="material-icons">rotate_right</i> {l s='Ruota a destra' mod='art_puzzle'}
                                </button>
                            </div>
                            <div class="art-puzzle-quality-info mt-3 alert alert-info" style="display: none;"></div>
                            <div class="text-center mt-3">
                                <button class="btn btn-secondary" id="art-puzzle-prev-step-1">{l s='Indietro' mod='art_puzzle'}</button>
                                <button class="btn btn-primary" id="art-puzzle-next-step-2">{l s='Avanti' mod='art_puzzle'}</button>
                            </div>
                        </div>
                        <div class="step step-3" style="display: none;">
                            <h4>3. {l s='Personalizza la scatola' mod='art_puzzle'}</h4>
                            <div class="form-group">
                                <label for="art-puzzle-box-text">{l s='Testo sulla scatola' mod='art_puzzle'}</label>
                                <input type="text" class="form-control" id="art-puzzle-box-text" maxlength="{$max_box_text_length|intval}" placeholder="{$default_box_text|escape:'html':'UTF-8'}">
                                <small class="form-text text-muted">
                                    {l s='Caratteri rimanenti:' mod='art_puzzle'} <span id="art-puzzle-chars-left">{$max_box_text_length|intval}</span>
                                </small>
                            </div>
                            <div class="form-group">
                                <label>{l s='Colore della scatola' mod='art_puzzle'}</label>
                                <div id="art-puzzle-box-colors" class="d-flex flex-wrap">
                                    {* I colori verranno riempiti dinamicamente *}
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{l s='Font del testo' mod='art_puzzle'}</label>
                                <div id="art-puzzle-fonts">
                                    {* I font verranno riempiti dinamicamente *}
                                </div>
                            </div>
                            <div class="art-puzzle-box-preview mt-3 mb-3">
                                <div id="art-puzzle-box-simulation"></div>
                            </div>
                            <div class="text-center mt-3">
                                <button class="btn btn-secondary" id="art-puzzle-prev-step-2">{l s='Indietro' mod='art_puzzle'}</button>
                                <button class="btn btn-primary" id="art-puzzle-finish">{l s='Aggiungi al carrello' mod='art_puzzle'}</button>
                            </div>
                        </div>
                    </div>
                    <div class="art-puzzle-loading" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">{l s='Caricamento in corso...' mod='art_puzzle'}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {* CSS per i font personalizzati *}
    {if isset($fonts) && $fonts|@count > 0}
        <style type="text/css">
            {foreach from=$fonts key=index item=font}
                @font-face {
                    font-family: 'puzzle-font-{$index}';
                    src: url('{$urls.base_url}modules/art_puzzle/views/fonts/{$font}') format('truetype');
                    font-weight: normal;
                    font-style: normal;
                }
            {/foreach}
        </style>
    {/if}
{/block}

{block name='javascript_bottom'}
    {$smarty.block.parent}
    <script type="text/javascript" src="{$urls.base_url}modules/art_puzzle/views/js/art_puzzle_customizer.js"></script>
    <script type="text/javascript">
        // Configurazione globale
        var artPuzzleProductId = {$id_product|intval};
        var artPuzzleMaxUploadSize = {$upload_max_size|intval};
        var artPuzzleAllowedFileTypes = [{foreach from=$allowed_file_types item=type name=types}'{$type|escape:'javascript'}'{if !$smarty.foreach.types.last},{/if}{/foreach}];
        var artPuzzleDefaultBoxText = "{$default_box_text|escape:'javascript'}";
        var artPuzzleMaxBoxTextLength = {$max_box_text_length|intval};
        var artPuzzleEnableOrientation = {if $enable_orientation}true{else}false{/if};
        var artPuzzleEnableCropTool = {if $enable_crop_tool}true{else}false{/if};
        var artPuzzleAjaxUrl = "{$puzzleAjaxUrl|escape:'javascript'}";
        var artPuzzleToken = "{$securityToken|escape:'javascript'}";
        var baseUrl = "{$urls.base_url}";
        
        // Debug per identificare problemi di caricamento
        $(document).ready(function() {
            console.log('Customizer inizializzato');
            console.log('Configurazione:', {
                productId: artPuzzleProductId,
                maxUploadSize: artPuzzleMaxUploadSize,
                allowedFileTypes: artPuzzleAllowedFileTypes,
                ajaxUrl: artPuzzleAjaxUrl
            });
        });
    </script>
{/block}

<script>
    var artPuzzleAllowedFileTypes = ['jpg', 'jpeg', 'png', 'gif'];
    var artPuzzleTranslations = {
        invalidFileType: "Tipo di file non supportato. Utilizza solo immagini JPG, PNG o GIF."
    };
</script>

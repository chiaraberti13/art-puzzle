{*
* Art Puzzle Module - Product Tab Template
*}

<div id="art-puzzle-tab-content" class="tab-pane">
    <div class="art-puzzle-intro">
        <p>{l s='Personalizza il tuo puzzle con un\'immagine a tua scelta e rendi unico il tuo regalo!' mod='art_puzzle'}</p>
        <p>{l s='Clicca sul pulsante per iniziare la personalizzazione.' mod='art_puzzle'}</p>
        <div class="text-center">
            <button id="art-puzzle-start-customize" class="btn btn-primary">
                {l s='Inizia a personalizzare' mod='art_puzzle'}
            </button>
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

{* Questo script sarà incluso nel template *}
<script type="text/javascript">
    // Variabili globali per il modulo
    var artPuzzleTranslations = {
        customizeTitle: "{l s='Personalizza il tuo puzzle' mod='art_puzzle' js=1}",
        uploadImage: "{l s='Carica la tua immagine' mod='art_puzzle' js=1}",
        dragDropImage: "{l s='Trascina qui la tua immagine' mod='art_puzzle' js=1}",
        or: "{l s='oppure' mod='art_puzzle' js=1}",
        browseFiles: "{l s='Seleziona file' mod='art_puzzle' js=1}",
        changeImage: "{l s='Cambia immagine' mod='art_puzzle' js=1}",
        nextStep: "{l s='Avanti' mod='art_puzzle' js=1}",
        previousStep: "{l s='Indietro' mod='art_puzzle' js=1}",
        adjustImage: "{l s='Regola la tua immagine' mod='art_puzzle' js=1}",
        customizeBox: "{l s='Personalizza la scatola' mod='art_puzzle' js=1}",
        boxText: "{l s='Testo sulla scatola' mod='art_puzzle' js=1}",
        boxColor: "{l s='Colore della scatola' mod='art_puzzle' js=1}",
        textFont: "{l s='Font del testo' mod='art_puzzle' js=1}",
        charactersLeft: "{l s='Caratteri rimanenti' mod='art_puzzle' js=1}",
        addToCart: "{l s='Aggiungi al carrello' mod='art_puzzle' js=1}",
        loading: "{l s='Caricamento in corso...' mod='art_puzzle' js=1}",
        successMessage: "{l s='La tua personalizzazione è stata salvata e il prodotto è stato aggiunto al carrello!' mod='art_puzzle' js=1}",
        errorMessage: "{l s='Si è verificato un errore durante il salvataggio della personalizzazione.' mod='art_puzzle' js=1}",
        onlyImages: "{l s='Puoi caricare solo immagini.' mod='art_puzzle' js=1}",
        fileTooLarge: "{l s='L\'immagine è troppo grande. La dimensione massima è %s MB.' mod='art_puzzle' js=1}",
        fileTypeNotAllowed: "{l s='Tipo di file non supportato. Formati consentiti: {$allowed_file_types|implode:', '}.' mod='art_puzzle' js=1}"
    };
    
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
</script>
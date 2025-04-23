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

{* Questo script sarà incluso nel template *}
<script type="text/javascript">
    // Variabili globali per il modulo
    var puzzleTranslations = {
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
        fileTypeNotAllowed: "{l s='Tipo di file non supportato. Formati consentiti: jpg, png.' mod='art_puzzle' js=1}"
    };
    
    var maxUploadSize = {$maxUploadSize|intval};
    var allowedFileTypes = [{foreach from=$allowedFileTypes item=type name=types}'{$type|escape:'javascript'}'{if !$smarty.foreach.types.last},{/if}{/foreach}];
    var boxColors = {$boxColors|json_encode nofilter};
    var fonts = {$fonts|json_encode nofilter};
    var defaultBoxText = "{$defaultBoxText|escape:'javascript'}";
    var boxTextMaxLength = {$maxBoxTextLength|intval};
    var enableOrientation = {if $enableOrientation}true{else}false{/if};
    var enableCropTool = {if $enableCropTool}true{else}false{/if};
    var puzzleAjaxUrl = "{$puzzleAjaxUrl|escape:'javascript'}";
    
    // Inizializzazione quando il documento è pronto
    $(document).ready(function() {
        $('#art-puzzle-start-customize').on('click', function() {
            openPuzzleCustomizer();
        });
    });
</script>
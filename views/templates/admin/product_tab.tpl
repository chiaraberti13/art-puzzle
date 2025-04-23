{*
* Art Puzzle Module - Admin Product Tab
*}

<div class="panel product-tab">
    <h3><i class="icon-puzzle-piece"></i> {l s='Art Puzzle - Configurazione personalizzazione' mod='art_puzzle'}</h3>
    
    <div class="form-group">
        <label class="control-label col-lg-3">
            {l s='Abilita personalizzazione puzzle' mod='art_puzzle'}
        </label>
        <div class="col-lg-9">
            <span class="switch prestashop-switch fixed-width-lg">
                <input type="radio" name="art_puzzle_enabled" id="art_puzzle_enabled_on" value="1" {if $is_puzzle_product}checked="checked"{/if}>
                <label for="art_puzzle_enabled_on">{l s='Sì' d='Admin.Global'}</label>
                <input type="radio" name="art_puzzle_enabled" id="art_puzzle_enabled_off" value="0" {if !$is_puzzle_product}checked="checked"{/if}>
                <label for="art_puzzle_enabled_off">{l s='No' d='Admin.Global'}</label>
                <a class="slide-button btn"></a>
            </span>
            <p class="help-block">{l s='Abilita la personalizzazione puzzle per questo prodotto.' mod='art_puzzle'}</p>
        </div>
    </div>
    
    <div class="panel-footer">
        <button type="button" class="btn btn-default pull-right" id="art_puzzle_save_config">
            <i class="process-icon-save"></i> {l s='Salva' d='Admin.Actions'}
        </button>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $('#art_puzzle_save_config').click(function() {
        var enabled = $('#art_puzzle_enabled_on').is(':checked') ? 1 : 0;
        var idProduct = {$id_product|intval};
        
        $.ajax({
            url: '{$module_dir}ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'togglePuzzleProduct',
                id_product: idProduct,
                enabled: enabled,
                token: '{$smarty.get.token|escape:'html':'UTF-8'}'
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.message);
                } else {
                    showErrorMessage(response.message);
                }
            },
            error: function() {
                showErrorMessage('{l s='Si è verificato un errore durante il salvataggio.' mod='art_puzzle' js=1}');
            }
        });
    });
});
</script>
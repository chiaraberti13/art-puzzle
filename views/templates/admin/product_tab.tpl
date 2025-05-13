{*
* Art Puzzle Module - Admin Product Tab
* @author      Chiara Berti
* @copyright   Copyright (c) 2024
* @license     https://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
* @version     1.0.0
*}

<div class="panel product-tab">
    <h3><i class="icon-puzzle-piece"></i> {l s='Art Puzzle - Configurazione personalizzazione' mod='art_puzzle'}</h3>
    
    <div class="alert alert-info">
        <p>
            {l s='Configura le opzioni di personalizzazione puzzle per questo prodotto. Quando abilitato, i clienti potranno caricare un\'immagine e personalizzare il loro puzzle.' mod='art_puzzle'}
        </p>
    </div>
    
    <div class="form-group row">
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
    
    <div id="puzzle_options" class="{if !$is_puzzle_product}hidden{/if}">
        <hr>
        <h4>{l s='Opzioni formato puzzle' mod='art_puzzle'}</h4>
        
        <!-- Dimensioni Puzzle -->
        <div class="form-group row">
            <label class="control-label col-lg-3">
                {l s='Dimensioni (cm)' mod='art_puzzle'}
            </label>
            <div class="col-lg-9">
                <div id="puzzle_sizes_container">
                    {if isset($puzzle_config.sizes) && is_array($puzzle_config.sizes) && count($puzzle_config.sizes) > 0}
                        {foreach from=$puzzle_config.sizes key=size_key item=size_data}
                            <div class="row puzzle-size-row mb-3">
                                <div class="col-lg-3">
                                    <div class="input-group">
                                        <input type="text" name="puzzle_size_name[]" class="form-control" 
                                               value="{$size_data.name|escape:'html':'UTF-8'}" 
                                               placeholder="{l s='Nome formato' mod='art_puzzle'}">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="input-group">
                                        <input type="number" name="puzzle_size_width[]" class="form-control" 
                                               value="{$size_data.width|intval}" min="20" max="100">
                                        <span class="input-group-addon">x</span>
                                        <input type="number" name="puzzle_size_height[]" class="form-control" 
                                               value="{$size_data.height|intval}" min="15" max="80">
                                        <span class="input-group-addon">cm</span>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="input-group">
                                        <input type="number" name="puzzle_size_price[]" class="form-control" 
                                               value="{$size_data.price|floatval}" min="0" step="0.01">
                                        <span class="input-group-addon">€</span>
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <button type="button" class="btn btn-danger remove-size-btn">
                                        <i class="icon-trash"></i>
                                    </button>
                                </div>
                            </div>
                        {/foreach}
                    {else}
                        <div class="row puzzle-size-row mb-3">
                            <div class="col-lg-3">
                                <div class="input-group">
                                    <input type="text" name="puzzle_size_name[]" class="form-control" 
                                           value="Piccolo" placeholder="{l s='Nome formato' mod='art_puzzle'}">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="input-group">
                                    <input type="number" name="puzzle_size_width[]" class="form-control" 
                                           value="30" min="20" max="100">
                                    <span class="input-group-addon">x</span>
                                    <input type="number" name="puzzle_size_height[]" class="form-control" 
                                           value="20" min="15" max="80">
                                    <span class="input-group-addon">cm</span>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="input-group">
                                    <input type="number" name="puzzle_size_price[]" class="form-control" 
                                           value="0" min="0" step="0.01">
                                    <span class="input-group-addon">€</span>
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <button type="button" class="btn btn-danger remove-size-btn">
                                    <i class="icon-trash"></i>
                                </button>
                            </div>
                        </div>
                    {/if}
                </div>
                
                <div class="row mt-2 mb-3">
                    <div class="col-lg-12">
                        <button type="button" class="btn btn-default" id="add_puzzle_size">
                            <i class="icon-plus"></i> {l s='Aggiungi dimensione' mod='art_puzzle'}
                        </button>
                    </div>
                </div>
                <p class="help-block">
                    {l s='Configura le dimensioni disponibili per il puzzle. Per ogni dimensione, specifica un nome, le dimensioni in cm e l\'eventuale sovrapprezzo.' mod='art_puzzle'}
                </p>
            </div>
        </div>
        
        <!-- Numero di pezzi -->
        <div class="form-group row">
            <label class="control-label col-lg-3">
                {l s='Numero di pezzi' mod='art_puzzle'}
            </label>
            <div class="col-lg-9">
                <div id="puzzle_pieces_container">
                    {if isset($puzzle_config.pieces_options) && is_array($puzzle_config.pieces_options) && count($puzzle_config.pieces_options) > 0}
                        {foreach from=$puzzle_config.pieces_options key=pieces_key item=pieces_data}
                            <div class="row puzzle-pieces-row mb-3">
                                <div class="col-lg-3">
                                    <div class="input-group">
                                        <input type="number" name="puzzle_pieces_count[]" class="form-control" 
                                               value="{$pieces_data.count|intval}" min="20" max="5000">
                                        <span class="input-group-addon">{l s='pezzi' mod='art_puzzle'}</span>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="input-group">
                                        <input type="text" name="puzzle_pieces_name[]" class="form-control" 
                                               value="{$pieces_data.name|escape:'html':'UTF-8'}" 
                                               placeholder="{l s='Descrizione (opzionale)' mod='art_puzzle'}">
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="input-group">
                                        <input type="number" name="puzzle_pieces_price[]" class="form-control" 
                                               value="{$pieces_data.price|floatval}" min="0" step="0.01">
                                        <span class="input-group-addon">€</span>
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <button type="button" class="btn btn-danger remove-pieces-btn">
                                        <i class="icon-trash"></i>
                                    </button>
                                </div>
                            </div>
                        {/foreach}
                    {else}
                        <div class="row puzzle-pieces-row mb-3">
                            <div class="col-lg-3">
                                <div class="input-group">
                                    <input type="number" name="puzzle_pieces_count[]" class="form-control" 
                                           value="500" min="20" max="5000">
                                    <span class="input-group-addon">{l s='pezzi' mod='art_puzzle'}</span>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="input-group">
                                    <input type="text" name="puzzle_pieces_name[]" class="form-control" 
                                           value="" placeholder="{l s='Descrizione (opzionale)' mod='art_puzzle'}">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="input-group">
                                    <input type="number" name="puzzle_pieces_price[]" class="form-control" 
                                           value="0" min="0" step="0.01">
                                    <span class="input-group-addon">€</span>
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <button type="button" class="btn btn-danger remove-pieces-btn">
                                    <i class="icon-trash"></i>
                                </button>
                            </div>
                        </div>
                    {/if}
                </div>
                
                <div class="row mt-2 mb-3">
                    <div class="col-lg-12">
                        <button type="button" class="btn btn-default" id="add_puzzle_pieces">
                            <i class="icon-plus"></i> {l s='Aggiungi opzione pezzi' mod='art_puzzle'}
                        </button>
                    </div>
                </div>
                <p class="help-block">
                    {l s='Configura il numero di pezzi disponibili. Per ogni opzione, specifica la quantità, una descrizione opzionale e l\'eventuale sovrapprezzo.' mod='art_puzzle'}
                </p>
            </div>
        </div>
        
        <!-- Personalizzazione Scatola -->
        <div class="form-group row">
            <label class="control-label col-lg-3">
                {l s='Abilita personalizzazione scatola' mod='art_puzzle'}
            </label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="custom_box_enabled" id="custom_box_enabled_on" value="1" 
                           {if isset($puzzle_config.custom_box) && $puzzle_config.custom_box == 1}checked="checked"{/if}>
                    <label for="custom_box_enabled_on">{l s='Sì' d='Admin.Global'}</label>
                    <input type="radio" name="custom_box_enabled" id="custom_box_enabled_off" value="0" 
                           {if !isset($puzzle_config.custom_box) || $puzzle_config.custom_box == 0}checked="checked"{/if}>
                    <label for="custom_box_enabled_off">{l s='No' d='Admin.Global'}</label>
                    <a class="slide-button btn"></a>
                </span>
                <p class="help-block">{l s='Consenti ai clienti di personalizzare anche la scatola del puzzle.' mod='art_puzzle'}</p>
            </div>
        </div>
        
        <!-- Requisiti immagine -->
        <div class="form-group row">
            <label class="control-label col-lg-3">
                {l s='Requisiti minimi immagine' mod='art_puzzle'}
            </label>
            <div class="col-lg-9">
                <div class="row">
                    <div class="col-lg-3">
                        <div class="input-group">
                            <input type="number" id="min_resolution" name="min_resolution" class="form-control" 
                                   value="{if isset($puzzle_config.min_resolution)}{$puzzle_config.min_resolution|escape:'html':'UTF-8'}{else}1500{/if}" 
                                   min="800" max="5000">
                            <span class="input-group-addon">px</span>
                        </div>
                    </div>
                </div>
                <p class="help-block">
                    {l s='Dimensione minima del lato minore dell\'immagine (in pixel). Raccomandiamo almeno 1500px per risultati di qualità.' mod='art_puzzle'}
                </p>
            </div>
        </div>
        
        <!-- Limite dimensione file -->
        <div class="form-group row">
            <label class="control-label col-lg-3">
                {l s='Limite dimensione file' mod='art_puzzle'}
            </label>
            <div class="col-lg-9">
                <div class="row">
                    <div class="col-lg-3">
                        <div class="input-group">
                            <input type="number" id="max_file_size" name="max_file_size" class="form-control" 
                                   value="{if isset($puzzle_config.max_file_size)}{$puzzle_config.max_file_size|escape:'html':'UTF-8'}{else}8{/if}" 
                                   min="1" max="20">
                            <span class="input-group-addon">MB</span>
                        </div>
                    </div>
                </div>
                <p class="help-block">
                    {l s='Dimensione massima del file immagine che i clienti possono caricare (in MB).' mod='art_puzzle'}
                </p>
            </div>
        </div>
        
        <!-- Formati accettati -->
        <div class="form-group row">
            <label class="control-label col-lg-3">
                {l s='Formati immagine accettati' mod='art_puzzle'}
            </label>
            <div class="col-lg-9">
                <div class="row">
                    <div class="col-lg-9">
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="format_jpg" id="format_jpg" value="1" 
                                    {if !isset($puzzle_config.formats) || isset($puzzle_config.formats.jpg)}checked="checked"{/if}>
                                <label for="format_jpg">JPG</label>
                            </div>
                            
                            <div class="checkbox-item">
                                <input type="checkbox" name="format_jpeg" id="format_jpeg" value="1" 
                                    {if !isset($puzzle_config.formats) || isset($puzzle_config.formats.jpeg)}checked="checked"{/if}>
                                <label for="format_jpeg">JPEG</label>
                            </div>
                            
                            <div class="checkbox-item">
                                <input type="checkbox" name="format_tiff" id="format_tiff" value="1" 
                                    {if isset($puzzle_config.formats.tiff)}checked="checked"{/if}>
                                <label for="format_tiff">TIFF</label>
                            </div>
                            
                            <div class="checkbox-item">
                                <input type="checkbox" name="format_png" id="format_png" value="1" 
                                    {if !isset($puzzle_config.formats) || isset($puzzle_config.formats.png)}checked="checked"{/if}>
                                <label for="format_png">PNG</label>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="help-block">
                    {l s='Seleziona i formati di immagine che i clienti possono caricare.' mod='art_puzzle'}
                </p>
            </div>
        </div>
    </div>
</div>

{* Modelli per le righe dinamiche *}
<script id="puzzle-size-template" type="text/template">
    <div class="row puzzle-size-row mb-3">
        <div class="col-lg-3">
            <div class="input-group">
                <input type="text" name="puzzle_size_name[]" class="form-control" 
                       value="" placeholder="{l s='Nome formato' mod='art_puzzle'}">
            </div>
        </div>
        <div class="col-lg-4">
            <div class="input-group">
                <input type="number" name="puzzle_size_width[]" class="form-control" 
                       value="40" min="20" max="100">
                <span class="input-group-addon">x</span>
                <input type="number" name="puzzle_size_height[]" class="form-control" 
                       value="30" min="15" max="80">
                <span class="input-group-addon">cm</span>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="input-group">
                <input type="number" name="puzzle_size_price[]" class="form-control" 
                       value="0" min="0" step="0.01">
                <span class="input-group-addon">€</span>
            </div>
        </div>
        <div class="col-lg-2">
            <button type="button" class="btn btn-danger remove-size-btn">
                <i class="icon-trash"></i>
            </button>
        </div>
    </div>
</script>

<script id="puzzle-pieces-template" type="text/template">
    <div class="row puzzle-pieces-row mb-3">
        <div class="col-lg-3">
            <div class="input-group">
                <input type="number" name="puzzle_pieces_count[]" class="form-control" 
                       value="1000" min="20" max="5000">
                <span class="input-group-addon">{l s='pezzi' mod='art_puzzle'}</span>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="input-group">
                <input type="text" name="puzzle_pieces_name[]" class="form-control" 
                       value="" placeholder="{l s='Descrizione (opzionale)' mod='art_puzzle'}">
            </div>
        </div>
        <div class="col-lg-3">
            <div class="input-group">
                <input type="number" name="puzzle_pieces_price[]" class="form-control" 
                       value="0" min="0" step="0.01">
                <span class="input-group-addon">€</span>
            </div>
        </div>
        <div class="col-lg-2">
            <button type="button" class="btn btn-danger remove-pieces-btn">
                <i class="icon-trash"></i>
            </button>
        </div>
    </div>
</script>

<style type="text/css">
.mb-3 {
    margin-bottom: 15px;
}

.mt-2 {
    margin-top: 10px;
}

.puzzle-size-row,
.puzzle-pieces-row {
    display: flex;
    align-items: center;
}

.checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    margin-right: 15px;
}

.checkbox-item input[type="checkbox"] {
    margin-right: 5px;
}

.input-group {
    display: flex;
}

.input-group-addon {
    display: flex;
    align-items: center;
    padding: 6px 12px;
    font-size: 14px;
    font-weight: 400;
    line-height: 1;
    color: #555;
    text-align: center;
    background-color: #eee;
    border: 1px solid #ccc;
    border-radius: 0 4px 4px 0;
}

.form-group.row {
    margin-bottom: 15px;
}

.control-label {
    font-weight: bold;
    padding-top: 7px;
}

.help-block {
    color: #737373;
    margin-top: 5px;
}

/* Stile dei bottoni */
.btn-danger {
    color: #fff;
    background-color: #d9534f;
    border-color: #d43f3a;
}

/* Responsive */
@media (max-width: 992px) {
    .puzzle-size-row,
    .puzzle-pieces-row {
        flex-wrap: wrap;
    }
    
    .puzzle-size-row > div,
    .puzzle-pieces-row > div {
        margin-bottom: 10px;
    }
}
</style>

<script type="text/javascript">
$(document).ready(function() {
    // Mostra/nasconde le opzioni quando lo stato viene modificato
    $('input[name="art_puzzle_enabled"]').change(function() {
        if ($('#art_puzzle_enabled_on').is(':checked')) {
            $('#puzzle_options').removeClass('hidden');
        } else {
            $('#puzzle_options').addClass('hidden');
        }
    });
    
    // Aggiunge un nuovo formato dimensionale
    $('#add_puzzle_size').click(function() {
        var template = $('#puzzle-size-template').html();
        $('#puzzle_sizes_container').append(template);
        attachSizeRemoveEvent();
    });
    
    // Aggiunge una nuova opzione pezzi
    $('#add_puzzle_pieces').click(function() {
        var template = $('#puzzle-pieces-template').html();
        $('#puzzle_pieces_container').append(template);
        attachPiecesRemoveEvent();
    });
    
    // Funzione per collegare gli eventi di rimozione ai pulsanti
    function attachSizeRemoveEvent() {
        $('.remove-size-btn').off('click').on('click', function() {
            // Non rimuovere se è l'ultima riga
            if ($('.puzzle-size-row').length > 1) {
                $(this).closest('.puzzle-size-row').remove();
            } else {
                alert('{l s='È necessaria almeno una dimensione puzzle' mod='art_puzzle' js=1}');
            }
        });
    }
    
    function attachPiecesRemoveEvent() {
        $('.remove-pieces-btn').off('click').on('click', function() {
            // Non rimuovere se è l'ultima riga
            if ($('.puzzle-pieces-row').length > 1) {
                $(this).closest('.puzzle-pieces-row').remove();
            } else {
                alert('{l s='È necessaria almeno un\'opzione per il numero di pezzi' mod='art_puzzle' js=1}');
            }
        });
    }
    
    // Attacca gli eventi di rimozione al caricamento
    attachSizeRemoveEvent();
    attachPiecesRemoveEvent();
    
    // Validazione input
    $(document).on('change', 'input[type="number"]', function() {
        var $this = $(this);
        var val = parseFloat($this.val());
        var min = parseFloat($this.attr('min'));
        var max = parseFloat($this.attr('max'));
        var step = parseFloat($this.attr('step') || 1);
        
        if (isNaN(val) || val < min) {
            $this.val(min);
        } else if (val > max) {
            $this.val(max);
        }
        
        // Arrotonda in base allo step se necessario
        if (step && step < 1) {
            var decimals = step.toString().split('.')[1].length;
            $this.val(parseFloat(val).toFixed(decimals));
        }
    });
    
    // Verifica che almeno un formato sia selezionato
    $('input[name^="format_"]').on('change', function() {
        if ($('input[name^="format_"]:checked').length === 0) {
            $(this).prop('checked', true);
            alert('{l s='È necessario selezionare almeno un formato di immagine' mod='art_puzzle' js=1}');
        }
    });
    
    // Salvataggio configurazione
    $('#art_puzzle_save_config').click(function() {
        var btnSave = $(this);
        var originalHtml = btnSave.html();
        btnSave.html('<i class="icon-spinner icon-spin"></i> {l s='Salvataggio...' mod='art_puzzle' js=1}');
        btnSave.prop('disabled', true);
        
        var enabled = $('#art_puzzle_enabled_on').is(':checked') ? 1 : 0;
        var idProduct = {$id_product|intval};
        
        // Raccogli tutti i dati di configurazione
        var configData = {
            id_product: idProduct,
            enabled: enabled,
            ajax: 1
        };
        
        // Aggiungi dati di configurazione solo se il modulo è abilitato
        if (enabled) {
            // Raccogli le dimensioni
            var sizes = [];
            $('.puzzle-size-row').each(function() {
                var row = $(this);
                sizes.push({
                    name: row.find('input[name="puzzle_size_name[]"]').val(),
                    width: parseInt(row.find('input[name="puzzle_size_width[]"]').val()),
                    height: parseInt(row.find('input[name="puzzle_size_height[]"]').val()),
                    price: parseFloat(row.find('input[name="puzzle_size_price[]"]').val())
                });
            });
            configData.sizes = sizes;
            
            // Raccogli le opzioni per il numero di pezzi
            var piecesOptions = [];
            $('.puzzle-pieces-row').each(function() {
                var row = $(this);
                piecesOptions.push({
                    count: parseInt(row.find('input[name="puzzle_pieces_count[]"]').val()),
                    name: row.find('input[name="puzzle_pieces_name[]"]').val(),
                    price: parseFloat(row.find('input[name="puzzle_pieces_price[]"]').val())
                });
            });
            configData.pieces_options = piecesOptions;
            
            configData.custom_box = $('#custom_box_enabled_on').is(':checked') ? 1 : 0;
            configData.min_resolution = $('#min_resolution').val();
            configData.max_file_size = $('#max_file_size').val();
            configData.formats = {
                jpg: $('#format_jpg').is(':checked') ? 1 : 0,
                jpeg: $('#format_jpeg').is(':checked') ? 1 : 0,
                tiff: $('#format_tiff').is(':checked') ? 1 : 0,
                png: $('#format_png').is(':checked') ? 1 : 0
            };
        }
        
        $.ajax({
            url: '{$ajax_url nofilter}&action=savePuzzleConfig',
            type: 'POST',
            dataType: 'json',
            data: configData,
            success: function(response) {
                btnSave.html(originalHtml);
                btnSave.prop('disabled', false);
                
                if (response.success) {
                    showSuccessMessage(response.message);
                } else {
                    showErrorMessage(response.message || '{l s='Errore durante il salvataggio.' mod='art_puzzle' js=1}');
                }
            },
            error: function() {
                btnSave.html(originalHtml);
                btnSave.prop('disabled', false);
                showErrorMessage('{l s='Si è verificato un errore durante il salvataggio. Verifica la connessione e riprova.' mod='art_puzzle' js=1}');
            }
        });
    });
});
</script>
/**
 * Art Puzzle - Front End JavaScript
 */
$(document).ready(function() {
    // Verifica se siamo in una pagina prodotto con puzzle configurato
    if ($('.art-puzzle-customize-button').length) {
        initArtPuzzle();
    }
});

function initArtPuzzle() {
    // Gestione del pulsante di personalizzazione
    $('.art-puzzle-customize-button button').on('click', function() {
        openPuzzleCustomizer();
    });
}

function openPuzzleCustomizer() {
    // Ottieni l'ID del prodotto dalla pagina
    var productId = $('input[name="id_product"]').val();
    
    // Prepara il contenitore per il personalizzatore
    var $customizer = $('<div class="art-puzzle-container"></div>');
    
    // Aggiungi contenuto al customizer
    $customizer.html(`
        <h3>${puzzleTranslations.customizeTitle}</h3>
        <div class="art-puzzle-steps">
            <div class="step step-1 active">
                <h4>1. ${puzzleTranslations.uploadImage}</h4>
                <div class="art-puzzle-upload-zone">
                    <p><i class="material-icons">cloud_upload</i></p>
                    <p>${puzzleTranslations.dragDropImage}</p>
                    <p>${puzzleTranslations.or}</p>
                    <input type="file" id="art-puzzle-file" accept="image/*" style="display: none;">
                    <button class="btn btn-primary" id="art-puzzle-browse-btn">${puzzleTranslations.browseFiles}</button>
                </div>
                <div class="art-puzzle-preview" style="display: none;">
                    <img id="art-puzzle-preview-img" src="">
                    <button class="btn btn-link" id="art-puzzle-change-img">${puzzleTranslations.changeImage}</button>
                </div>
                <div class="text-center">
                    <button class="btn btn-primary" id="art-puzzle-next-step" disabled>${puzzleTranslations.nextStep}</button>
                </div>
            </div>
            <div class="step step-2" style="display: none;">
                <h4>2. ${puzzleTranslations.adjustImage}</h4>
                <div class="art-puzzle-crop-container">
                    <img id="art-puzzle-crop-img" src="">
                </div>
                <div class="art-puzzle-orientation-buttons">
                    <button class="btn btn-outline-secondary" id="art-puzzle-rotate-left">
                        <i class="material-icons">rotate_left</i>
                    </button>
                    <button class="btn btn-outline-secondary" id="art-puzzle-rotate-right">
                        <i class="material-icons">rotate_right</i>
                    </button>
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-secondary" id="art-puzzle-prev-step-1">${puzzleTranslations.previousStep}</button>
                    <button class="btn btn-primary" id="art-puzzle-next-step-2">${puzzleTranslations.nextStep}</button>
                </div>
            </div>
            <div class="step step-3" style="display: none;">
                <h4>3. ${puzzleTranslations.customizeBox}</h4>
                <div class="form-group">
                    <label for="art-puzzle-box-text">${puzzleTranslations.boxText}</label>
                    <input type="text" class="form-control" id="art-puzzle-box-text" 
                           maxlength="${boxTextMaxLength}" 
                           placeholder="${defaultBoxText}">
                    <small class="form-text text-muted">
                        ${puzzleTranslations.charactersLeft}: <span id="art-puzzle-chars-left">${boxTextMaxLength}</span>
                    </small>
                </div>
                <div class="form-group">
                    <label>${puzzleTranslations.boxColor}</label>
                    <div id="art-puzzle-box-colors">
                        <!-- I colori verranno riempiti dinamicamente -->
                    </div>
                </div>
                <div class="form-group">
                    <label>${puzzleTranslations.textFont}</label>
                    <div id="art-puzzle-fonts">
                        <!-- I font verranno riempiti dinamicamente -->
                    </div>
                </div>
                <div class="art-puzzle-box-preview">
                    <div id="art-puzzle-box-simulation"></div>
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-secondary" id="art-puzzle-prev-step-2">${puzzleTranslations.previousStep}</button>
                    <button class="btn btn-primary" id="art-puzzle-finish">${puzzleTranslations.addToCart}</button>
                </div>
            </div>
        </div>
        <div class="art-puzzle-loading">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">${puzzleTranslations.loading}</span>
            </div>
        </div>
    `);
    
    // Aggiungi il customizer alla pagina prodotto
    $('#art-puzzle-tab-content').html($customizer);
    
    // Inizializza il file uploader
    initFileUploader();
    
    // Inizializza la navigazione tra i passaggi
    initStepsNavigation();
    
    // Carica i colori disponibili
    loadBoxColors();
    
    // Carica i font disponibili
    loadFonts();
    
    // Inizializza gli eventi per il testo della scatola
    initBoxTextEvents();
}

function initFileUploader() {
    // Gestione del pulsante di browse
    $('#art-puzzle-browse-btn').on('click', function() {
        $('#art-puzzle-file').click();
    });
    
    // Gestione del caricamento file
    $('#art-puzzle-file').on('change', function(e) {
        handleFileUpload(e.target.files[0]);
    });
    
    // Drag and drop
    var $dropZone = $('.art-puzzle-upload-zone');
    
    $dropZone.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('highlight');
    });
    
    $dropZone.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('highlight');
    });
    
    $dropZone.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('highlight');
        
        var files = e.originalEvent.dataTransfer.files;
        if (files.length) {
            handleFileUpload(files[0]);
        }
    });
    
    // Pulsante per cambiare immagine
    $('#art-puzzle-change-img').on('click', function() {
        $('.art-puzzle-preview').hide();
        $('.art-puzzle-upload-zone').show();
        $('#art-puzzle-next-step').prop('disabled', true);
    });
}

function handleFileUpload(file) {
    // Verifica che sia un'immagine
    if (!file.type.match('image.*')) {
        alert(puzzleTranslations.onlyImages);
        return;
    }
    
    // Verifica la dimensione del file
    if (file.size > maxUploadSize * 1024 * 1024) {
        alert(puzzleTranslations.fileTooLarge.replace('%s', maxUploadSize));
        return;
    }
    
    // Verifica il tipo di file
    var fileExt = file.name.split('.').pop().toLowerCase();
    if (allowedFileTypes.indexOf(fileExt) === -1) {
        alert(puzzleTranslations.fileTypeNotAllowed);
        return;
    }
    
    // Mostra l'anteprima
    var reader = new FileReader();
    reader.onload = function(e) {
        $('#art-puzzle-preview-img').attr('src', e.target.result);
        $('.art-puzzle-upload-zone').hide();
        $('.art-puzzle-preview').show();
        $('#art-puzzle-next-step').prop('disabled', false);
        
        // Salva l'immagine per l'uso nei passaggi successivi
        puzzleCustomizerData.image = e.target.result;
    };
    reader.readAsDataURL(file);
}

function initStepsNavigation() {
    // Passaggio 1 -> 2
    $('#art-puzzle-next-step').on('click', function() {
        $('.step-1').removeClass('active').hide();
        $('.step-2').addClass('active').show();
        
        // Inizializza lo strumento di ritaglio
        initCropTool();
    });
    
    // Passaggio 2 -> 1
    $('#art-puzzle-prev-step-1').on('click', function() {
        $('.step-2').removeClass('active').hide();
        $('.step-1').addClass('active').show();
    });
    
    // Passaggio 2 -> 3
    $('#art-puzzle-next-step-2').on('click', function() {
        $('.step-2').removeClass('active').hide();
        $('.step-3').addClass('active').show();
        
        // Aggiorna l'anteprima della scatola
        updateBoxPreview();
    });
    
    // Passaggio 3 -> 2
    $('#art-puzzle-prev-step-2').on('click', function() {
        $('.step-3').removeClass('active').hide();
        $('.step-2').addClass('active').show();
    });
    
    // Finalizza personalizzazione
    $('#art-puzzle-finish').on('click', function() {
        finalizePuzzleCustomization();
    });
}

function initCropTool() {
    // Carica l'immagine nel contenitore di crop
    $('#art-puzzle-crop-img').attr('src', puzzleCustomizerData.image);
    
    // Se lo strumento di crop è abilitato
    if (enableCropTool) {
        // Implementa qui il tuo strumento di ritaglio
        // Ad esempio usando una libreria come Cropper.js
        
        // Esempio di codice per Cropper.js (richiede l'inclusione della libreria)
        /*
        var cropper = new Cropper(document.getElementById('art-puzzle-crop-img'), {
            aspectRatio: 1,
            viewMode: 1,
            ready: function() {
                // Cropper pronto
            }
        });
        
        // Salva l'istanza del cropper per un uso successivo
        puzzleCustomizerData.cropper = cropper;
        */
    }
    
    // Gestione pulsanti di rotazione se l'orientamento è abilitato
    if (enableOrientation) {
        $('#art-puzzle-rotate-left').on('click', function() {
            // Implementa la rotazione a sinistra
            if (puzzleCustomizerData.cropper) {
                puzzleCustomizerData.cropper.rotate(-90);
            }
        });
        
        $('#art-puzzle-rotate-right').on('click', function() {
            // Implementa la rotazione a destra
            if (puzzleCustomizerData.cropper) {
                puzzleCustomizerData.cropper.rotate(90);
            }
        });
    } else {
        // Nascondi i pulsanti di rotazione se non abilitati
        $('.art-puzzle-orientation-buttons').hide();
    }
}

function loadBoxColors() {
    // Carica i colori della scatola dalla configurazione
    var $colorsContainer = $('#art-puzzle-box-colors');
    
    boxColors.forEach(function(colorSet, index) {
        var $colorOption = $('<div class="color-option"></div>')
            .css('background-color', colorSet.box)
            .attr('data-box-color', colorSet.box)
            .attr('data-text-color', colorSet.text)
            .attr('data-index', index);
        
        if (index === 0) {
            $colorOption.addClass('selected');
            puzzleCustomizerData.boxColor = colorSet.box;
            puzzleCustomizerData.textColor = colorSet.text;
        }
        
        $colorOption.on('click', function() {
            $('.color-option').removeClass('selected');
            $(this).addClass('selected');
            puzzleCustomizerData.boxColor = $(this).data('box-color');
            puzzleCustomizerData.textColor = $(this).data('text-color');
            updateBoxPreview();
        });
        
        $colorsContainer.append($colorOption);
    });
}

function loadFonts() {
    // Carica i font disponibili
    var $fontsContainer = $('#art-puzzle-fonts');
    
    fonts.forEach(function(font, index) {
        var fontName = font.replace('font_', '').replace('.ttf', '');
        
        var $fontOption = $('<div class="font-option"></div>')
            .html('<span style="font-family: \'puzzle-font-' + index + '\'">Aa Bb Cc</span> - ' + fontName)
            .attr('data-font', 'puzzle-font-' + index);
        
        if (index === 0) {
            $fontOption.addClass('selected');
            puzzleCustomizerData.font = 'puzzle-font-' + index;
        }
        
        $fontOption.on('click', function() {
            $('.font-option').removeClass('selected');
            $(this).addClass('selected');
            puzzleCustomizerData.font = $(this).data('font');
            updateBoxPreview();
        });
        
        $fontsContainer.append($fontOption);
    });
}

function initBoxTextEvents() {
    // Imposta il testo predefinito
    if (defaultBoxText) {
        $('#art-puzzle-box-text').val(defaultBoxText);
        puzzleCustomizerData.boxText = defaultBoxText;
    }
    
    // Conta caratteri rimanenti
    $('#art-puzzle-box-text').on('input', function() {
        var remainingChars = boxTextMaxLength - $(this).val().length;
        $('#art-puzzle-chars-left').text(remainingChars);
        puzzleCustomizerData.boxText = $(this).val();
        updateBoxPreview();
    });
}

function updateBoxPreview() {
    var $boxPreview = $('#art-puzzle-box-simulation');
    
    $boxPreview.css({
        'background-color': puzzleCustomizerData.boxColor,
        'color': puzzleCustomizerData.textColor,
        'font-family': puzzleCustomizerData.font,
        'padding': '10px',
        'min-height': '80px',
        'display': 'flex',
        'align-items': 'center',
        'justify-content': 'center'
    });
    
    $boxPreview.text(puzzleCustomizerData.boxText || defaultBoxText);
}

function finalizePuzzleCustomization() {
    // Mostra il loader
    $('.art-puzzle-loading').show();
    
    // Prepara i dati della personalizzazione
    var finalData = {
        product_id: $('input[name="id_product"]').val(),
        customization: {
            image: puzzleCustomizerData.image,
            boxColor: puzzleCustomizerData.boxColor,
            textColor: puzzleCustomizerData.textColor,
            boxText: puzzleCustomizerData.boxText || defaultBoxText,
            font: puzzleCustomizerData.font
        }
    };
    
    // Se è stato utilizzato lo strumento di ritaglio, ottieni l'immagine ritagliata
    if (puzzleCustomizerData.cropper) {
        finalData.customization.image = puzzleCustomizerData.cropper.getCroppedCanvas().toDataURL();
    }
    
    // Invia i dati al server
    $.ajax({
        url: puzzleAjaxUrl,
        type: 'POST',
        data: {
            action: 'savePuzzleCustomization',
            data: JSON.stringify(finalData)
        },
        success: function(response) {
            $('.art-puzzle-loading').hide();
            
            if (response.success) {
                // Aggiungi il prodotto al carrello con la personalizzazione
                prestashop.emit('updateCart', {
                    reason: {
                        idProduct: finalData.product_id,
                        idProductAttribute: response.idProductAttribute,
                        idCustomization: response.idCustomization,
                        linkAction: 'add-to-cart'
                    }
                });
                
                // Chiudi il customizer e mostra messaggio di successo
                $('#art-puzzle-modal').modal('hide');
                showSuccessMessage(puzzleTranslations.successMessage);
            } else {
                // Mostra errore
                showErrorMessage(response.message || puzzleTranslations.errorMessage);
            }
        },
        error: function() {
            $('.art-puzzle-loading').hide();
            showErrorMessage(puzzleTranslations.errorMessage);
        }
    });
}

// Variabili globali che verranno impostate dal PHP
var puzzleTranslations = {};
var maxUploadSize = 20;
var allowedFileTypes = ['jpg', 'png'];
var boxColors = [];
var fonts = [];
var defaultBoxText = '';
var boxTextMaxLength = 30;
var enableOrientation = true;
var enableCropTool = true;
var puzzleAjaxUrl = '';

// Dati del customizer
var puzzleCustomizerData = {
    image: null,
    cropper: null,
    boxColor: null,
    textColor: null,
    boxText: '',
    font: null
};

// Helper functions
function showSuccessMessage(message) {
    var $alert = $('<div class="alert alert-success"></div>').text(message);
    $('body').append($alert);
    setTimeout(function() {
        $alert.fadeOut(function() {
            $(this).remove();
        });
    }, 3000);
}

function showErrorMessage(message) {
    var $alert = $('<div class="alert alert-danger"></div>').text(message);
    $('body').append($alert);
    setTimeout(function() {
        $alert.fadeOut(function() {
            $(this).remove();
        });
    }, 3000);
}
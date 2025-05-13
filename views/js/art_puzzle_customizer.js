/**
 * Art Puzzle - Script per la personalizzazione dei puzzle
 */
$(document).ready(function() {
    // Configurazione
    var fileInput = $('#art-puzzle-file');
    var uploadZone = $('.art-puzzle-upload-zone');
    var browseBtn = $('#art-puzzle-browse-btn');
    var previewContainer = $('.art-puzzle-preview');
    var previewImg = $('#art-puzzle-preview-img');
    var changeImgBtn = $('#art-puzzle-change-img');
    var nextStepBtn = $('#art-puzzle-next-step');
    var loadingSpinner = $('.art-puzzle-loading');
    
    console.log('Inizializzazione customizer JS...');
    console.log('Configurazione:', {
        fileInput: fileInput.length > 0,
        uploadZone: uploadZone.length > 0,
        browseBtn: browseBtn.length > 0,
        previewContainer: previewContainer.length > 0,
        previewImg: previewImg.length > 0,
        changeImgBtn: changeImgBtn.length > 0,
        nextStepBtn: nextStepBtn.length > 0,
        loadingSpinner: loadingSpinner.length > 0
    });
    
    // Inizializza gli eventi per il caricamento dell'immagine
    if (fileInput.length && browseBtn.length) {
        // Click sul pulsante di selezione file
        browseBtn.on('click', function(e) {
            e.preventDefault();
            fileInput.click();
        });
        
        // Cambio del file selezionato
        fileInput.on('change', function(e) {
            if (this.files && this.files[0]) {
                handleFileUpload(this.files[0]);
            }
        });
        
        // Drag & Drop
        uploadZone.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
        }).on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
        }).on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
            
            if (e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files.length) {
                handleFileUpload(e.originalEvent.dataTransfer.files[0]);
            }
        });
        
        // Cambio immagine
        changeImgBtn.on('click', function(e) {
            e.preventDefault();
            previewContainer.hide();
            uploadZone.show();
            nextStepBtn.prop('disabled', true);
        });
        
        console.log('Handler di eventi inizializzati correttamente');
    }
    
    /**
     * Gestisce il caricamento di un file
     */
    function handleFileUpload(file) {
        console.log('File selezionato:', file.name, file.type, file.size);
        
        // Verifica che il file sia un'immagine
        var allowedTypes = artPuzzleAllowedFileTypes || ['jpg', 'jpeg', 'png', 'gif'];
        var fileExtension = file.name.split('.').pop().toLowerCase();
        
        if (allowedTypes.indexOf(fileExtension) === -1) {
            alert(artPuzzleTranslations.fileTypeNotAllowed || 'Tipo di file non supportato. Utilizza solo immagini JPG, PNG o GIF.');
            return;
        }
        
        // Verifica che il file non sia troppo grande
        var maxSize = (artPuzzleMaxUploadSize || 10) * 1024 * 1024; // Default 10MB
        if (file.size > maxSize) {
            alert(artPuzzleTranslations.fileTooLarge || 'File troppo grande. Dimensione massima: 10MB');
            return;
        }
        
        // Mostra spinner di caricamento
        loadingSpinner.show();
        
        // Preparazione del FormData per l'upload
        var formData = new FormData();
        formData.append('image', file);
        formData.append('action', 'uploadImage');
        formData.append('ajax', true);
        formData.append('token', artPuzzleToken || '');
        
        // URL per la richiesta AJAX
        var ajaxUrl = artPuzzleAjaxUrl || (baseUrl + 'index.php?fc=module&module=art_puzzle&controller=ajax');
        
        // Log dei parametri di invio
        console.log('Parametri di invio:', {
            url: ajaxUrl,
            fileSize: file.size,
            fileType: file.type,
            fileName: file.name
        });
        
        // Invio della richiesta AJAX
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                console.log('Risposta server:', response);
                loadingSpinner.hide();
                
                if (response.success) {
                    // Mostra l'anteprima dell'immagine
                    previewImg.attr('src', response.data.url || response.data.path);
                    uploadZone.hide();
                    previewContainer.show();
                    nextStepBtn.prop('disabled', false);
                    
                    // Mostra messaggio di qualità se disponibile
                    if (response.data.quality && response.data.message) {
                        var alertClass = 'alert-' + (response.data.alert_type || 'info');
                        var qualityInfo = $('.art-puzzle-quality-info');
                        
                        qualityInfo.html(response.data.message);
                        qualityInfo.removeClass('alert-info alert-success alert-warning alert-danger');
                        qualityInfo.addClass(alertClass);
                        qualityInfo.show();
                    }
                } else {
                    alert(response.message || 'Errore durante il caricamento dell\'immagine.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Errore AJAX:', status, error);
                console.log('Dettagli risposta:', xhr.responseText);
                loadingSpinner.hide();
                alert('Si è verificato un errore durante il caricamento dell\'immagine. Riprova più tardi.');
            }
        });
    }
    
    // Gestione dei passi di personalizzazione
    var steps = $('.step');
    var stepButtons = {
        next1: $('#art-puzzle-next-step'),
        prev2: $('#art-puzzle-prev-step-1'),
        next2: $('#art-puzzle-next-step-2'),
        prev3: $('#art-puzzle-prev-step-2'),
        finish: $('#art-puzzle-finish')
    };
    
    if (steps.length && stepButtons.next1.length) {
        // Paso 1 -> Passo 2
        stepButtons.next1.on('click', function(e) {
            e.preventDefault();
            $('.step-1').hide();
            $('.step-2').show();
        });
        
        // Passo 2 -> Passo 1
        stepButtons.prev2.on('click', function(e) {
            e.preventDefault();
            $('.step-2').hide();
            $('.step-1').show();
        });
        
        // Passo 2 -> Passo 1
        stepButtons.prev2.on('click', function(e) {
            e.preventDefault();
            $('.step-2').hide();
            $('.step-1').show();
        });
        
        // Passo 2 -> Passo 3
        stepButtons.next2.on('click', function(e) {
            e.preventDefault();
            $('.step-2').hide();
            $('.step-3').show();
            
            // Inizializza anteprima della scatola
            initBoxPreview();
        });
        
        // Passo 3 -> Passo 2
        stepButtons.prev3.on('click', function(e) {
            e.preventDefault();
            $('.step-3').hide();
            $('.step-2').show();
        });
    }
    
    // Gestione della rotazione dell'immagine
    $('#art-puzzle-rotate-left').on('click', function(e) {
        e.preventDefault();
        rotateImage(-90);
    });
    
    $('#art-puzzle-rotate-right').on('click', function(e) {
        e.preventDefault();
        rotateImage(90);
    });
    
    function rotateImage(degrees) {
        var img = document.getElementById('art-puzzle-crop-img');
        if (!img) return;
        
        // Angolo di rotazione corrente
        var currentRotation = img.dataset.rotation || 0;
        currentRotation = parseInt(currentRotation);
        
        // Nuovo angolo
        var newRotation = (currentRotation + degrees) % 360;
        if (newRotation < 0) newRotation += 360;
        
        // Applica la rotazione
        img.style.transform = 'rotate(' + newRotation + 'deg)';
        img.dataset.rotation = newRotation;
    }
    
    // Gestione della personalizzazione della scatola
    var boxTextInput = $('#art-puzzle-box-text');
    var boxCharsLeft = $('#art-puzzle-chars-left');
    
    if (boxTextInput.length && boxCharsLeft.length) {
        var maxLength = boxTextInput.attr('maxlength') || 50;
        
        boxTextInput.on('input', function() {
            var remaining = maxLength - $(this).val().length;
            boxCharsLeft.text(remaining);
            
            // Aggiorna anteprima
            updateBoxPreview();
        });
    }
    
    // Caricamento dei colori e font disponibili
    function loadBoxColors() {
        $.ajax({
            url: artPuzzleAjaxUrl,
            type: 'POST',
            data: {
                action: 'getBoxColors',
                ajax: true,
                token: artPuzzleToken
            },
            success: function(response) {
                if (response.success) {
                    renderBoxColors(response.data);
                }
            }
        });
    }
    
    function loadFonts() {
        $.ajax({
            url: artPuzzleAjaxUrl,
            type: 'POST',
            data: {
                action: 'getFonts',
                ajax: true,
                token: artPuzzleToken
            },
            success: function(response) {
                if (response.success) {
                    renderFonts(response.data);
                }
            }
        });
    }
    
    function renderBoxColors(colors) {
        var container = $('#art-puzzle-box-colors');
        if (!container.length || !colors || !colors.length) return;
        
        container.empty();
        
        colors.forEach(function(color, index) {
            var colorElement = $('<div class="art-puzzle-color-item" data-color="' + color.hex + '"></div>');
            colorElement.css('background-color', color.hex);
            colorElement.attr('title', color.name);
            
            // Seleziona il primo colore come default
            if (index === 0) {
                colorElement.addClass('selected');
            }
            
            colorElement.on('click', function() {
                $('.art-puzzle-color-item').removeClass('selected');
                $(this).addClass('selected');
                updateBoxPreview();
            });
            
            container.append(colorElement);
        });
        
        // Aggiorna l'anteprima con il colore di default
        updateBoxPreview();
    }
    
    function renderFonts(fonts) {
        var container = $('#art-puzzle-fonts');
        if (!container.length || !fonts || !fonts.length) return;
        
        container.empty();
        
        fonts.forEach(function(font, index) {
            var fontName = font.replace('.ttf', '').replace('.TTF', '');
            var fontClass = 'puzzle-font-' + index;
            
            var fontElement = $('<div class="art-puzzle-font-item" data-font="' + font + '"></div>');
            fontElement.html('<span style="font-family: \'' + fontClass + '\'">' + fontName + '</span>');
            
            // Seleziona il primo font come default
            if (index === 0) {
                fontElement.addClass('selected');
            }
            
            fontElement.on('click', function() {
                $('.art-puzzle-font-item').removeClass('selected');
                $(this).addClass('selected');
                updateBoxPreview();
            });
            
            container.append(fontElement);
        });
        
        // Aggiorna l'anteprima con il font di default
        updateBoxPreview();
    }
    
    // Inizializzazione e aggiornamento dell'anteprima della scatola
    function initBoxPreview() {
        // Carica colori e font se non sono già stati caricati
        if ($('#art-puzzle-box-colors').children().length === 0) {
            loadBoxColors();
        }
        
        if ($('#art-puzzle-fonts').children().length === 0) {
            loadFonts();
        }
        
        // Imposta il testo predefinito
        if (boxTextInput.val() === '') {
            boxTextInput.val(artPuzzleDefaultBoxText || 'Il mio puzzle');
            boxCharsLeft.text(maxLength - boxTextInput.val().length);
        }
        
        // Inizializza l'anteprima
        updateBoxPreview();
    }
    
    function updateBoxPreview() {
        var boxSimulation = $('#art-puzzle-box-simulation');
        if (!boxSimulation.length) return;
        
        var template = $('.art-puzzle-box-template.selected').data('template') || 'classic';
        var color = $('.art-puzzle-color-item.selected').data('color') || '#ffffff';
        var font = $('.art-puzzle-font-item.selected').data('font') || 'default.ttf';
        var text = boxTextInput.val() || 'Il mio puzzle';
        
        // Mostra spinner di caricamento
        loadingSpinner.show();
        
        $.ajax({
            url: artPuzzleAjaxUrl,
            type: 'POST',
            data: {
                action: 'generateBoxPreview',
                ajax: true,
                token: artPuzzleToken,
                template: template,
                color: color,
                text: text,
                font: font
            },
            success: function(response) {
                loadingSpinner.hide();
                
                if (response.success && response.data && response.data.preview) {
                    boxSimulation.html('<img src="' + response.data.preview + '" alt="Anteprima scatola" class="img-fluid">');
                } else {
                    console.error('Errore nella generazione dell\'anteprima:', response.message);
                }
            },
            error: function() {
                loadingSpinner.hide();
                console.error('Errore nella richiesta AJAX per l\'anteprima della scatola');
            }
        });
    }
    
    // Gestione dell'aggiunta al carrello
    if (stepButtons.finish.length) {
        stepButtons.finish.on('click', function(e) {
            e.preventDefault();
            
            // Raccogli tutti i dati della personalizzazione
            var template = $('.art-puzzle-box-template.selected').data('template') || 'classic';
            var color = $('.art-puzzle-color-item.selected').data('color') || '#ffffff';
            var font = $('.art-puzzle-font-item.selected').data('font') || 'default.ttf';
            var text = boxTextInput.val() || 'Il mio puzzle';
            
            // Mostra spinner di caricamento
            loadingSpinner.show();
            
            $.ajax({
                url: artPuzzleAjaxUrl,
                type: 'POST',
                data: {
                    action: 'add_to_cart',
                    ajax: true,
                    token: artPuzzleToken,
                    id_product: artPuzzleProductId,
                    'confirm-customization': 1,
                    template: template,
                    color: color,
                    text: text,
                    font: font
                },
                success: function(response) {
                    loadingSpinner.hide();
                    
                    if (response.success) {
                        // Aggiunto al carrello con successo, reindirizza
                        if (response.data && response.data.cartUrl) {
                            window.location.href = response.data.cartUrl;
                        } else {
                            // Fallback
                            window.location.href = baseUrl + '?controller=cart';
                        }
                    } else {
                        alert(response.message || 'Errore durante l\'aggiunta al carrello.');
                    }
                },
                error: function() {
                    loadingSpinner.hide();
                    alert('Si è verificato un errore durante l\'aggiunta al carrello. Riprova più tardi.');
                }
            });
        });
    }
    
    // Inizializzazione al caricamento della pagina
    console.log('Inizializzazione completata del customizer JS');
});
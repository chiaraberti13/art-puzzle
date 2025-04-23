/**
 * Art Puzzle - Front End JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    // Verifica se siamo in una pagina prodotto con puzzle configurato
    if (typeof artPuzzleProductId !== 'undefined' && artPuzzleProductId > 0) {
        initArtPuzzle();
    }
});

// Variabili globali per il customizer
var puzzleCustomizerData = {
    image: null,
    cropper: null,
    boxColor: null,
    textColor: null,
    boxText: '',
    font: null,
    productId: 0
};

function initArtPuzzle() {
    // Gestisce il click sul tab del puzzle personalizzato
    document.addEventListener('click', function(event) {
        if (event.target && event.target.matches('#art-puzzle-start-customize')) {
            event.preventDefault();
            openPuzzleCustomizer();
        }
    });
    
    // Gestisce il pulsante nei bottoni del prodotto
    var customizeBtn = document.querySelector('.art-puzzle-customize-button button');
    if (customizeBtn) {
        customizeBtn.addEventListener('click', function(event) {
            event.preventDefault();
            
            // Cerca il tab di personalizzazione e attivalo
            var puzzleTab = document.querySelector('a[href="#art-puzzle-tab-content"]');
            if (puzzleTab) {
                puzzleTab.click();
                
                // Scroll fino al tab
                setTimeout(function() {
                    puzzleTab.scrollIntoView({behavior: 'smooth'});
                }, 300);
            }
            
            openPuzzleCustomizer();
        });
    }
    
    // Imposta l'ID prodotto
    puzzleCustomizerData.productId = artPuzzleProductId;
}

function openPuzzleCustomizer() {
    // Prepara il contenitore per il personalizzatore
    var customizer = document.querySelector('#art-puzzle-tab-content');
    if (!customizer) return;
    
    // Aggiungi contenuto al customizer
    customizer.innerHTML = `
        <div class="art-puzzle-container">
            <h3>Personalizza il tuo puzzle</h3>
            <div class="art-puzzle-steps">
                <div class="step step-1 active">
                    <h4>1. Carica la tua immagine</h4>
                    <div class="art-puzzle-upload-zone">
                        <p><i class="material-icons">cloud_upload</i></p>
                        <p>Trascina qui la tua immagine</p>
                        <p>oppure</p>
                        <input type="file" id="art-puzzle-file" accept="image/*" style="display: none;">
                        <button class="btn btn-primary" id="art-puzzle-browse-btn">Seleziona file</button>
                    </div>
                    <div class="art-puzzle-preview" style="display: none;">
                        <img id="art-puzzle-preview-img" src="">
                        <button class="btn btn-link" id="art-puzzle-change-img">Cambia immagine</button>
                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-primary" id="art-puzzle-next-step" disabled>Avanti</button>
                    </div>
                </div>
                <div class="step step-2" style="display: none;">
                    <h4>2. Regola la tua immagine</h4>
                    <div class="art-puzzle-crop-container">
                        <img id="art-puzzle-crop-img" src="">
                    </div>
                    <div class="art-puzzle-orientation-buttons mt-2">
                        <button class="btn btn-outline-secondary" id="art-puzzle-rotate-left">
                            <i class="material-icons">rotate_left</i> Ruota a sinistra
                        </button>
                        <button class="btn btn-outline-secondary" id="art-puzzle-rotate-right">
                            <i class="material-icons">rotate_right</i> Ruota a destra
                        </button>
                    </div>
                    <div class="art-puzzle-quality-info mt-3 alert alert-info" style="display: none;"></div>
                    <div class="text-center mt-3">
                        <button class="btn btn-secondary" id="art-puzzle-prev-step-1">Indietro</button>
                        <button class="btn btn-primary" id="art-puzzle-next-step-2">Avanti</button>
                    </div>
                </div>
                <div class="step step-3" style="display: none;">
                    <h4>3. Personalizza la scatola</h4>
                    <div class="form-group">
                        <label for="art-puzzle-box-text">Testo sulla scatola</label>
                        <input type="text" class="form-control" id="art-puzzle-box-text" maxlength="30" placeholder="Il mio puzzle">
                        <small class="form-text text-muted">
                            Caratteri rimanenti: <span id="art-puzzle-chars-left">30</span>
                        </small>
                    </div>
                    <div class="form-group">
                        <label>Colore della scatola</label>
                        <div id="art-puzzle-box-colors" class="d-flex flex-wrap">
                            <!-- I colori verranno riempiti dinamicamente -->
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Font del testo</label>
                        <div id="art-puzzle-fonts">
                            <!-- I font verranno riempiti dinamicamente -->
                        </div>
                    </div>
                    <div class="art-puzzle-box-preview mt-3 mb-3">
                        <div id="art-puzzle-box-simulation"></div>
                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-secondary" id="art-puzzle-prev-step-2">Indietro</button>
                        <button class="btn btn-primary" id="art-puzzle-finish">Aggiungi al carrello</button>
                    </div>
                </div>
            </div>
            <div class="art-puzzle-loading" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Caricamento in corso...</span>
                </div>
            </div>
        </div>
    `;
    
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
    var browseBtn = document.getElementById('art-puzzle-browse-btn');
    var fileInput = document.getElementById('art-puzzle-file');
    
    if (browseBtn && fileInput) {
        browseBtn.addEventListener('click', function() {
            fileInput.click();
        });
        
        // Gestione del caricamento file
        fileInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files.length > 0) {
                handleFileUpload(e.target.files[0]);
            }
        });
    }
    
    // Drag and drop
    var dropZone = document.querySelector('.art-puzzle-upload-zone');
    if (dropZone) {
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('highlight');
        });
        
        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('highlight');
        });
        
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('highlight');
            
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                handleFileUpload(e.dataTransfer.files[0]);
            }
        });
    }
    
    // Pulsante per cambiare immagine
    var changeImgBtn = document.getElementById('art-puzzle-change-img');
    if (changeImgBtn) {
        changeImgBtn.addEventListener('click', function() {
            var previewDiv = document.querySelector('.art-puzzle-preview');
            var uploadZone = document.querySelector('.art-puzzle-upload-zone');
            var nextBtn = document.getElementById('art-puzzle-next-step');
            
            if (previewDiv) previewDiv.style.display = 'none';
            if (uploadZone) uploadZone.style.display = 'block';
            if (nextBtn) nextBtn.disabled = true;
            
            // Reset dell'input file
            if (fileInput) fileInput.value = '';
        });
    }
}

function handleFileUpload(file) {
    // Verifica che sia un'immagine
    if (!file.type.match('image.*')) {
        showMessage('Puoi caricare solo immagini.', 'error');
        return;
    }
    
    // Verifica la dimensione del file (max 20MB)
    var maxSize = 20 * 1024 * 1024; // 20MB
    if (file.size > maxSize) {
        showMessage('L\'immagine è troppo grande. La dimensione massima è 20 MB.', 'error');
        return;
    }
    
    // Verifica il tipo di file
    var allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        showMessage('Formato immagine non supportato. Formati consentiti: jpg, png, gif.', 'error');
        return;
    }
    
    // Mostra l'anteprima
    var reader = new FileReader();
    reader.onload = function(e) {
        var previewImg = document.getElementById('art-puzzle-preview-img');
        var previewDiv = document.querySelector('.art-puzzle-preview');
        var uploadZone = document.querySelector('.art-puzzle-upload-zone');
        var nextBtn = document.getElementById('art-puzzle-next-step');
        
        if (previewImg) previewImg.src = e.target.result;
        if (previewDiv) previewDiv.style.display = 'block';
        if (uploadZone) uploadZone.style.display = 'none';
        if (nextBtn) nextBtn.disabled = false;
        
        // Salva l'immagine per l'uso nei passaggi successivi
        puzzleCustomizerData.image = e.target.result;
        
        // Verifica la qualità dell'immagine
        checkImageQuality(e.target.result);
    };
    reader.readAsDataURL(file);
}

function checkImageQuality(imageData) {
    // Crea una nuova immagine per verificarne le dimensioni
    var img = new Image();
    img.onload = function() {
        var quality = 'alta';
        var message = 'L\'immagine è di ottima qualità!';
        
        // Se l'immagine è troppo piccola, avvisa l'utente
        if (img.width < 800 || img.height < 800) {
            quality = 'bassa';
            message = 'L\'immagine è di bassa risoluzione. Potrebbe apparire pixelata sul puzzle.';
        } else if (img.width < 1200 || img.height < 1200) {
            quality = 'media';
            message = 'L\'immagine è di media risoluzione. La qualità dovrebbe essere accettabile.';
        }
        
        // Mostra messaggio nella step-2
        var qualityInfo = document.querySelector('.art-puzzle-quality-info');
        if (qualityInfo) {
            qualityInfo.textContent = message;
            qualityInfo.style.display = 'block';
            
            // Cambia classe di alert in base alla qualità
            qualityInfo.className = 'art-puzzle-quality-info mt-3 alert';
            if (quality === 'alta') {
                qualityInfo.classList.add('alert-success');
            } else if (quality === 'media') {
                qualityInfo.classList.add('alert-warning');
            } else {
                qualityInfo.classList.add('alert-danger');
            }
        }
    };
    img.src = imageData;
}

function initStepsNavigation() {
    // Passaggio 1 -> 2
    var nextStepBtn = document.getElementById('art-puzzle-next-step');
    if (nextStepBtn) {
        nextStepBtn.addEventListener('click', function() {
            document.querySelector('.step-1').classList.remove('active');
            document.querySelector('.step-1').style.display = 'none';
            document.querySelector('.step-2').classList.add('active');
            document.querySelector('.step-2').style.display = 'block';
            
            // Inizializza lo strumento di ritaglio
            initCropTool();
        });
    }
    
    // Passaggio 2 -> 1
    var prevStep1Btn = document.getElementById('art-puzzle-prev-step-1');
    if (prevStep1Btn) {
        prevStep1Btn.addEventListener('click', function() {
            document.querySelector('.step-2').classList.remove('active');
            document.querySelector('.step-2').style.display = 'none';
            document.querySelector('.step-1').classList.add('active');
            document.querySelector('.step-1').style.display = 'block';
            
            // Distruggi il cropper se esiste
            if (puzzleCustomizerData.cropper) {
                puzzleCustomizerData.cropper.destroy();
                puzzleCustomizerData.cropper = null;
            }
        });
    }
    
    // Passaggio 2 -> 3
    var nextStep2Btn = document.getElementById('art-puzzle-next-step-2');
    if (nextStep2Btn) {
        nextStep2Btn.addEventListener('click', function() {
            document.querySelector('.step-2').classList.remove('active');
            document.querySelector('.step-2').style.display = 'none';
            document.querySelector('.step-3').classList.add('active');
            document.querySelector('.step-3').style.display = 'block';
            
            // Aggiorna l'anteprima della scatola
            updateBoxPreview();
            
            // Se è stato usato il cropper, aggiorna l'immagine
            if (puzzleCustomizerData.cropper) {
                puzzleCustomizerData.image = puzzleCustomizerData.cropper.getCroppedCanvas().toDataURL();
            }
        });
    }
    
    // Passaggio 3 -> 2
    var prevStep2Btn = document.getElementById('art-puzzle-prev-step-2');
    if (prevStep2Btn) {
        prevStep2Btn.addEventListener('click', function() {
            document.querySelector('.step-3').classList.remove('active');
            document.querySelector('.step-3').style.display = 'none';
            document.querySelector('.step-2').classList.add('active');
            document.querySelector('.step-2').style.display = 'block';
        });
    }
    
    // Finalizza personalizzazione
    var finishBtn = document.getElementById('art-puzzle-finish');
    if (finishBtn) {
        finishBtn.addEventListener('click', function() {
            finalizePuzzleCustomization();
        });
    }
}

function initCropTool() {
    var cropImg = document.getElementById('art-puzzle-crop-img');
    if (!cropImg) return;
    
    // Carica l'immagine nel contenitore di crop
    cropImg.src = puzzleCustomizerData.image;
    
    // Attendi che l'immagine sia caricata
    cropImg.onload = function() {
        // Inizializza Cropper.js
        if (typeof Cropper !== 'undefined') {
            // Distruggi il cropper esistente se c'è
            if (puzzleCustomizerData.cropper) {
                puzzleCustomizerData.cropper.destroy();
            }
            
            // Crea nuovo cropper
            puzzleCustomizerData.cropper = new Cropper(cropImg, {
                aspectRatio: 1, // Square by default
                viewMode: 1,
                zoomable: true,
                minCropBoxWidth: 100,
                minCropBoxHeight: 100,
                ready: function() {
                    console.log('Cropper ready');
                }
            });
        } else {
            console.error('Cropper.js non è caricato');
        }
    };
    
    // Gestione pulsanti di rotazione
    var rotateLeftBtn = document.getElementById('art-puzzle-rotate-left');
    var rotateRightBtn = document.getElementById('art-puzzle-rotate-right');
    
    if (rotateLeftBtn) {
        rotateLeftBtn.addEventListener('click', function() {
            if (puzzleCustomizerData.cropper) {
                puzzleCustomizerData.cropper.rotate(-90);
            }
        });
    }
    
    if (rotateRightBtn) {
        rotateRightBtn.addEventListener('click', function() {
            if (puzzleCustomizerData.cropper) {
                puzzleCustomizerData.cropper.rotate(90);
            }
        });
    }
}

function loadBoxColors() {
    // Carica i colori tramite AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('POST', artPuzzleAjaxUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success && response.data) {
                        renderBoxColors(response.data);
                    } else {
                        // Colori predefiniti in caso di errore
                        renderBoxColors([
                            {box: '#FFFFFF', text: '#000000'},
                            {box: '#000000', text: '#FFFFFF'},
                            {box: '#FF0000', text: '#FFFFFF'},
                            {box: '#0000FF', text: '#FFFFFF'}
                        ]);
                    }
                } catch (e) {
                    console.error('Errore nel parsing della risposta JSON', e);
                    // Colori predefiniti in caso di errore
                    renderBoxColors([
                        {box: '#FFFFFF', text: '#000000'},
                        {box: '#000000', text: '#FFFFFF'},
                        {box: '#FF0000', text: '#FFFFFF'},
                        {box: '#0000FF', text: '#FFFFFF'}
                    ]);
                }
            }
        }
    };
    
    xhr.send('action=getBoxColors&token=' + artPuzzleToken);
}

function renderBoxColors(colors) {
    var colorsContainer = document.getElementById('art-puzzle-box-colors');
    if (!colorsContainer) return;
    
    colorsContainer.innerHTML = '';
    
    colors.forEach(function(colorSet, index) {
        var colorOption = document.createElement('div');
        colorOption.className = 'color-option';
        colorOption.style.backgroundColor = colorSet.box;
        colorOption.dataset.boxColor = colorSet.box;
        colorOption.dataset.textColor = colorSet.text;
        colorOption.dataset.index = index;
        
        if (index === 0) {
            colorOption.classList.add('selected');
            puzzleCustomizerData.boxColor = colorSet.box;
            puzzleCustomizerData.textColor = colorSet.text;
        }
        
        colorOption.addEventListener('click', function() {
            var allOptions = document.querySelectorAll('.color-option');
            allOptions.forEach(function(opt) {
                opt.classList.remove('selected');
            });
            
            this.classList.add('selected');
            puzzleCustomizerData.boxColor = this.dataset.boxColor;
            puzzleCustomizerData.textColor = this.dataset.textColor;
            updateBoxPreview();
        });
        
        colorsContainer.appendChild(colorOption);
    });
}

function loadFonts() {
    // Carica i font tramite AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('POST', artPuzzleAjaxUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success && response.data) {
                        renderFonts(response.data);
                    }
                } catch (e) {
                    console.error('Errore nel parsing della risposta JSON', e);
                }
            }
        }
    };
    
    xhr.send('action=getFonts&token=' + artPuzzleToken);
}

function renderFonts(fonts) {
    var fontsContainer = document.getElementById('art-puzzle-fonts');
    if (!fontsContainer) return;
    
    fontsContainer.innerHTML = '';
    
    if (fonts.length === 0) {
        // Font predefinito se non ce ne sono
        var defaultFont = document.createElement('div');
        defaultFont.className = 'font-option selected';
        defaultFont.innerHTML = '<span>Aa Bb Cc</span> - Default';
        defaultFont.dataset.font = 'default';
        puzzleCustomizerData.font = 'default';
        
        defaultFont.addEventListener('click', function() {
            var allOptions = document.querySelectorAll('.font-option');
            allOptions.forEach(function(opt) {
                opt.classList.remove('selected');
            });
            
            this.classList.add('selected');
            puzzleCustomizerData.font = this.dataset.font;
            updateBoxPreview();
        });
        
        fontsContainer.appendChild(defaultFont);
    } else {
        fonts.forEach(function(font, index) {
            var fontName = font.replace('font_', '').replace('.ttf', '');
            
            var fontOption = document.createElement('div');
            fontOption.className = 'font-option';
            fontOption.innerHTML = '<span style="font-family: \'puzzle-font-' + index + '\'">Aa Bb Cc</span> - ' + fontName;
            fontOption.dataset.font = 'puzzle-font-' + index;
            
            if (index === 0) {
                fontOption.classList.add('selected');
                puzzleCustomizerData.font = 'puzzle-font-' + index;
            }
            
            fontOption.addEventListener('click', function() {
                var allOptions = document.querySelectorAll('.font-option');
                allOptions.forEach(function(opt) {
                    opt.classList.remove('selected');
                });
                
                this.classList.add('selected');
                puzzleCustomizerData.font = this.dataset.font;
                updateBoxPreview();
            });
            
            fontsContainer.appendChild(fontOption);
        });
    }
}

function initBoxTextEvents() {
    var boxTextInput = document.getElementById('art-puzzle-box-text');
    var charsLeftSpan = document.getElementById('art-puzzle-chars-left');
    
    if (!boxTextInput || !charsLeftSpan) return;
    
    // Imposta il testo predefinito
    boxTextInput.value = 'Il mio puzzle'; // Valore predefinito
    puzzleCustomizerData.boxText = 'Il mio puzzle';
    
    // Conta caratteri rimanenti
    boxTextInput.addEventListener('input', function() {
        var maxLength = parseInt(boxTextInput.getAttribute('maxlength')) || 30;
        var remainingChars = maxLength - this.value.length;
        
        charsLeftSpan.textContent = remainingChars;
        puzzleCustomizerData.boxText = this.value;
        updateBoxPreview();
    });
    
    // Trigger input event per inizializzare
    var event = new Event('input');
    boxTextInput.dispatchEvent(event);
}

function updateBoxPreview() {
    var boxPreview = document.getElementById('art-puzzle-box-simulation');
    if (!boxPreview) return;
    
    boxPreview.style.backgroundColor = puzzleCustomizerData.boxColor || '#FFFFFF';
    boxPreview.style.color = puzzleCustomizerData.textColor || '#000000';
    boxPreview.style.fontFamily = puzzleCustomizerData.font || 'inherit';
    boxPreview.style.padding = '15px';
    boxPreview.style.minHeight = '80px';
    boxPreview.style.display = 'flex';
    boxPreview.style.alignItems = 'center';
    boxPreview.style.justifyContent = 'center';
    boxPreview.style.fontSize = '18px';
    boxPreview.style.fontWeight = 'bold';
    boxPreview.style.borderRadius = '4px';
    boxPreview.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
    
    boxPreview.textContent = puzzleCustomizerData.boxText || 'Il mio puzzle';
}

function finalizePuzzleCustomization() {
    // Mostra il loader
    var loader = document.querySelector('.art-puzzle-loading');
    if (loader) loader.style.display = 'flex';
    
    // Preparazione dei dati
    var finalData = {
        product_id: puzzleCustomizerData.productId,
        customization: {
            image: puzzleCustomizerData.image,
            boxColor: puzzleCustomizerData.boxColor || '#FFFFFF',
            textColor: puzzleCustomizerData.textColor || '#000000',
            boxText: puzzleCustomizerData.boxText || 'Il mio puzzle',
            font: puzzleCustomizerData.font || 'default'
        }
    };
    
    // Invia i dati tramite AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('POST', artPuzzleAjaxUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (loader) loader.style.display = 'none';
            
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Aggiungi al carrello utilizzando l'API prestashop 
                        if (typeof prestashop !== 'undefined') {
                            prestashop.emit('updateCart', {
                                reason: {
                                    idProduct: finalData.product_id,
                                    idProductAttribute: 0,
                                    idCustomization: response.data.idCustomization,
                                    linkAction: 'add-to-cart'
                                }
                            });
                        }
                        
                        // Mostra messaggio di successo
                        showMessage('La tua personalizzazione è stata salvata e il prodotto è stato aggiunto al carrello!', 'success');
                        
                        // Reindirizza al carrello dopo un breve delay
                        setTimeout(function() {
                            window.location.href = prestashop.urls.pages.cart;
                        }, 2000);
                    } else {
                        showMessage(response.message || 'Si è verificato un errore durante il salvataggio della personalizzazione.', 'error');
                    }
                } catch (e) {
                    console.error('Errore nel parsing della risposta JSON', e);
                    showMessage('Si è verificato un errore durante il salvataggio della personalizzazione.', 'error');
                }
            } else {
                showMessage('Errore di comunicazione con il server. Riprova più tardi.', 'error');
            }
        }
    };
    
    xhr.send('action=savePuzzleCustomization&token=' + artPuzzleToken + '&data=' + encodeURIComponent(JSON.stringify(finalData)));
}

function showMessage(message, type) {
    var alertClass = 'alert-info';
    if (type === 'success') alertClass = 'alert-success';
    if (type === 'error') alertClass = 'alert-danger';
    
    var alert = document.createElement('div');
    alert.className = 'alert ' + alertClass + ' art-puzzle-message';
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.left = '50%';
    alert.style.transform = 'translateX(-50%)';
    alert.style.zIndex = '9999';
    alert.style.padding = '15px 20px';
    alert.style.borderRadius = '5px';
    alert.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)';
    alert.style.maxWidth = '80%';
    alert.style.textAlign = 'center';
    
    alert.textContent = message;
    
    document.body.appendChild(alert);
    
    setTimeout(function() {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.5s';
        
        setTimeout(function() {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 500);
    }, 3000);
}
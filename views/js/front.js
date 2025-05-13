/**
 * Art Puzzle - Script Frontend
 * Gestisce l'interfaccia utente per la personalizzazione dei puzzle
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elementi principali dell'interfaccia
    const uploadZone = document.querySelector('.art-puzzle-upload-zone');
    const fileInput = document.getElementById('art-puzzle-file');
    const browseBtn = document.getElementById('art-puzzle-browse-btn');
    const previewContainer = document.querySelector('.art-puzzle-preview');
    const previewImg = document.getElementById('art-puzzle-preview-img');
    const changeImgBtn = document.getElementById('art-puzzle-change-img');
    
    // Elementi per i passi della personalizzazione
    const steps = document.querySelectorAll('.art-puzzle-steps .step');
    const nextStep1Btn = document.getElementById('art-puzzle-next-step');
    const nextStep2Btn = document.getElementById('art-puzzle-next-step-2');
    const prevStep1Btn = document.getElementById('art-puzzle-prev-step-1');
    const prevStep2Btn = document.getElementById('art-puzzle-prev-step-2');
    const finishBtn = document.getElementById('art-puzzle-finish');
    
    // Elementi per il ritaglio dell'immagine
    const cropContainer = document.querySelector('.art-puzzle-crop-container');
    const cropImg = document.getElementById('art-puzzle-crop-img');
    const rotateLeftBtn = document.getElementById('art-puzzle-rotate-left');
    const rotateRightBtn = document.getElementById('art-puzzle-rotate-right');
    const qualityInfo = document.querySelector('.art-puzzle-quality-info');
    
    // Elementi per la personalizzazione della scatola
    const boxTextInput = document.getElementById('art-puzzle-box-text');
    const charsLeftSpan = document.getElementById('art-puzzle-chars-left');
    const boxColorsContainer = document.getElementById('art-puzzle-box-colors');
    const fontsContainer = document.getElementById('art-puzzle-fonts');
    const boxSimulation = document.getElementById('art-puzzle-box-simulation');
    
    // Overlay di caricamento
    const loadingOverlay = document.querySelector('.art-puzzle-loading');
    
    // Variabili globali
    let currentStep = 1;
    let currentRotation = 0;
    let cropper = null;
    let uploadedImage = null;
    let selectedFormat = null;
    let selectedBoxColor = '#ffffff';
    let selectedTextColor = '#000000';
    let selectedFont = '';
    let cropData = null;
    
    // Inizializzazione
    init();
    
    /**
     * Inizializza l'applicazione
     */
    function init() {
        // Inizializza l'upload zone
        if (uploadZone && fileInput && browseBtn) {
            initUploadZone();
        }
        
        // Inizializza i pulsanti di navigazione tra i passi
        initNavigationButtons();
        
        // Imposta il testo predefinito per la scatola
        if (boxTextInput && artPuzzleDefaultBoxText) {
            boxTextInput.value = artPuzzleDefaultBoxText;
            updateCharsLeft();
        }
        
        // Carica i colori disponibili per la scatola
        loadBoxColors();
        
        // Carica i font disponibili
        loadFonts();
        
        // Controlla permessi directory
        checkDirectoryPermissions();
    }
    
    /**
     * Inizializza la zona di caricamento immagini
     */
    function initUploadZone() {
        // Gestisce il click sul pulsante "Seleziona file"
        browseBtn.addEventListener('click', function() {
            fileInput.click();
        });
        
        // Gestisce la selezione del file
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                handleFileUpload(this.files[0]);
            }
        });
        
        // Gestisce il drag & drop
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', function() {
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                handleFileUpload(e.dataTransfer.files[0]);
            }
        });
        
        // Gestisce il pulsante "Cambia immagine"
        if (changeImgBtn) {
            changeImgBtn.addEventListener('click', function() {
                resetUploadZone();
            });
        }
    }
    
    /**
     * Inizializza i pulsanti di navigazione
     */
    function initNavigationButtons() {
        // Pulsante Avanti (passo 1 -> 2)
        if (nextStep1Btn) {
            nextStep1Btn.addEventListener('click', function() {
                if (uploadedImage) {
                    goToStep(2);
                    initCropper();
                    checkImageQuality();
                }
            });
        }
        
        // Pulsante Indietro (passo 2 -> 1)
        if (prevStep1Btn) {
            prevStep1Btn.addEventListener('click', function() {
                goToStep(1);
                destroyCropper();
            });
        }
        
        // Pulsante Avanti (passo 2 -> 3)
        if (nextStep2Btn) {
            nextStep2Btn.addEventListener('click', function() {
                if (cropper) {
                    cropData = cropper.getData();
                    goToStep(3);
                    generateBoxPreview();
                }
            });
        }
        
        // Pulsante Indietro (passo 3 -> 2)
        if (prevStep2Btn) {
            prevStep2Btn.addEventListener('click', function() {
                goToStep(2);
            });
        }
        
        // Pulsante Aggiungi al carrello
        if (finishBtn) {
            finishBtn.addEventListener('click', function() {
                savePuzzleCustomization();
            });
        }
    }
    
    /**
     * Gestisce il caricamento del file
     */
    function handleFileUpload(file) {
        // Verifica tipo file
        if (artPuzzleAllowedFileTypes.indexOf(file.type) === -1) {
            alert('Tipo di file non supportato. Utilizza solo immagini JPG, PNG o GIF.');
            return;
        }
        
        // Verifica dimensione file
        if (file.size > artPuzzleMaxUploadSize) {
            alert('File troppo grande. La dimensione massima è ' + (artPuzzleMaxUploadSize / (1024 * 1024)) + 'MB.');
            return;
        }
        
        // Mostra overlay caricamento
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }
        
        // Prepara form data per l'upload
        const formData = new FormData();
        formData.append('image', file);
        formData.append('action', 'uploadImage');
        formData.append('token', artPuzzleToken);
        
        // Invia richiesta
        fetch(artPuzzleAjaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
            
            if (data.success) {
                // Mostra anteprima
                displayImagePreview(file);
                
                // Salva riferimento all'immagine
                uploadedImage = {
                    file: file,
                    path: data.data.path,
                    width: data.data.width,
                    height: data.data.height,
                    quality: data.data.quality
                };
                
                // Abilita pulsante avanti
                if (nextStep1Btn) {
                    nextStep1Btn.disabled = false;
                }
            } else {
                alert(data.message || 'Errore durante il caricamento dell\'immagine.');
            }
        })
        .catch(error => {
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
            console.error('Errore:', error);
            alert('Si è verificato un errore durante il caricamento dell\'immagine.');
        });
    }
    
    /**
     * Mostra l'anteprima dell'immagine
     */
    function displayImagePreview(file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            if (previewImg) {
                previewImg.src = e.target.result;
            }
            
            if (cropImg) {
                cropImg.src = e.target.result;
            }
            
            if (uploadZone && previewContainer) {
                uploadZone.style.display = 'none';
                previewContainer.style.display = 'block';
            }
        };
        
        reader.readAsDataURL(file);
    }
    
    /**
     * Resetta la zona di upload
     */
    function resetUploadZone() {
        if (fileInput) {
            fileInput.value = '';
        }
        
        if (uploadZone && previewContainer) {
            uploadZone.style.display = 'block';
            previewContainer.style.display = 'none';
        }
        
        if (nextStep1Btn) {
            nextStep1Btn.disabled = true;
        }
        
        uploadedImage = null;
    }
    
    /**
     * Naviga a un passo specifico
     */
    function goToStep(step) {
        currentStep = step;
        
        steps.forEach((stepElement, index) => {
            if (index + 1 === step) {
                stepElement.style.display = 'block';
                stepElement.classList.add('active');
            } else {
                stepElement.style.display = 'none';
                stepElement.classList.remove('active');
            }
        });
    }
    
    /**
     * Inizializza il ritaglio dell'immagine
     */
    function initCropper() {
        if (!cropImg || !artPuzzleEnableCropTool) {
            return;
        }
        
        // Distruggi l'istanza precedente se esiste
        destroyCropper();
        
        // Crea una nuova istanza
        cropper = new Cropper(cropImg, {
            aspectRatio: 1, // Default 1:1, sarà aggiornato in base al formato scelto
            viewMode: 1,
            guides: true,
            center: true,
            movable: true,
            zoomable: true,
            scalable: false,
            rotatable: false, // Gestiamo la rotazione manualmente
            autoCropArea: 0.8,
            responsive: true,
            ready: function() {
                // Carica i formati disponibili per il puzzle
                loadPuzzleFormats();
            }
        });
        
        // Gestisci i pulsanti di rotazione
        if (rotateLeftBtn && artPuzzleEnableOrientation) {
            rotateLeftBtn.addEventListener('click', function() {
                currentRotation = (currentRotation - 90) % 360;
                cropper.rotateTo(currentRotation);
            });
        }
        
        if (rotateRightBtn && artPuzzleEnableOrientation) {
            rotateRightBtn.addEventListener('click', function() {
                currentRotation = (currentRotation + 90) % 360;
                cropper.rotateTo(currentRotation);
            });
        }
    }
    
    /**
     * Distrugge l'istanza del cropper
     */
    function destroyCropper() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    }
    
    /**
     * Carica i formati puzzle disponibili
     */
    function loadPuzzleFormats() {
        // Determina l'orientamento dell'immagine
        let orientation = 'square';
        
        if (uploadedImage) {
            const ratio = uploadedImage.width / uploadedImage.height;
            
            if (ratio > 1.2) {
                orientation = 'landscape';
            } else if (ratio < 0.8) {
                orientation = 'portrait';
            }
        }
        
        // Mostra overlay caricamento
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }
        
        // Prepara i parametri
        const params = new URLSearchParams();
        params.append('action', 'getPuzzleFormats');
        params.append('orientation', orientation);
        params.append('token', artPuzzleToken);
        
        // Invia richiesta
        fetch(artPuzzleAjaxUrl + '?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
            
            if (data.success && data.data) {
                // Seleziona il primo formato come default
                const formatIds = Object.keys(data.data);
                if (formatIds.length > 0) {
                    selectedFormat = formatIds[0];
                    updateCropperAspectRatio(data.data[selectedFormat].ratio);
                }
            } else {
                alert(data.message || 'Errore durante il caricamento dei formati.');
            }
        })
        .catch(error => {
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
            console.error('Errore:', error);
            alert('Si è verificato un errore durante il caricamento dei formati puzzle.');
        });
    }
    
    /**
     * Aggiorna l'aspect ratio del cropper
     */
    function updateCropperAspectRatio(ratio) {
        if (cropper && ratio) {
            cropper.setAspectRatio(ratio);
            cropper.crop();
        }
    }
    
    /**
     * Controlla la qualità dell'immagine
     */
    function checkImageQuality() {
        if (!uploadedImage) {
            return;
        }
        
        // Imposta il messaggio sulla qualità
        if (qualityInfo) {
            let message = '';
            let alertClass = '';
            
            switch (uploadedImage.quality) {
                case 'alta':
                    message = 'L\'immagine è di ottima qualità!';
                    alertClass = 'alert-success';
                    break;
                    
                case 'media':
                    message = 'L\'immagine è di media risoluzione. La qualità dovrebbe essere accettabile.';
                    alertClass = 'alert-warning';
                    break;
                    
                case 'bassa':
                    message = 'L\'immagine è di bassa risoluzione. Potrebbe apparire pixelata sul puzzle.';
                    alertClass = 'alert-danger';
                    break;
            }
            
            if (message) {
                qualityInfo.innerHTML = message;
                qualityInfo.className = 'art-puzzle-quality-info mt-3 alert';
                qualityInfo.classList.add(alertClass);
                qualityInfo.style.display = 'block';
            }
        }
    }
    
    /**
     * Carica i colori disponibili per la scatola
     */
    function loadBoxColors() {
        if (!boxColorsContainer) {
            return;
        }
        
        // Prepara i parametri
        const params = new URLSearchParams();
        params.append('action', 'getBoxColors');
        params.append('token', artPuzzleToken);
        
        // Invia richiesta
        fetch(artPuzzleAjaxUrl + '?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                // Genera i pulsanti colore
                let colorButtons = '';
                
                data.data.forEach(color => {
                    colorButtons += `
                        <div class="color-option m-1">
                            <button type="button" class="btn color-btn" 
                                    style="background-color: ${color.hex};" 
                                    data-color="${color.hex}" 
                                    title="${color.name}">
                            </button>
                        </div>
                    `;
                });
                
                // Inserisce i pulsanti nel container
                boxColorsContainer.innerHTML = colorButtons;
                
                // Aggiunge event listeners
                document.querySelectorAll('.color-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        // Rimuove la classe 'selected' da tutti i pulsanti
                        document.querySelectorAll('.color-btn').forEach(b => {
                            b.classList.remove('selected');
                        });
                        
                        // Aggiunge la classe 'selected' al pulsante cliccato
                        this.classList.add('selected');
                        
                        // Salva il colore selezionato
                        selectedBoxColor = this.getAttribute('data-color');
                        
                        // Aggiorna l'anteprima
                        generateBoxPreview();
                    });
                });
                
                // Seleziona il primo colore come default
                const firstColorBtn = document.querySelector('.color-btn');
                if (firstColorBtn) {
                    firstColorBtn.classList.add('selected');
                    selectedBoxColor = firstColorBtn.getAttribute('data-color');
                }
            }
        })
        .catch(error => {
            console.error('Errore:', error);
        });
    }
    
    /**
     * Carica i font disponibili
     */
    function loadFonts() {
        if (!fontsContainer) {
            return;
        }
        
        // Prepara i parametri
        const params = new URLSearchParams();
        params.append('action', 'getFonts');
        params.append('token', artPuzzleToken);
        
        // Invia richiesta
        fetch(artPuzzleAjaxUrl + '?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                // Genera i pulsanti font
                let fontButtons = '';
                
                data.data.forEach((font, index) => {
                    const fontName = font.replace('.ttf', '').replace('.otf', '');
                    fontButtons += `
                        <div class="font-option m-1">
                            <button type="button" class="btn btn-outline-secondary font-btn" 
                                    style="font-family: 'puzzle-font-${index}';" 
                                    data-font="${font}">
                                ${fontName}
                            </button>
                        </div>
                    `;
                });
                
                // Inserisce i pulsanti nel container
                fontsContainer.innerHTML = fontButtons;
                
                // Aggiunge event listeners
                document.querySelectorAll('.font-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        // Rimuove la classe 'selected' da tutti i pulsanti
                        document.querySelectorAll('.font-btn').forEach(b => {
                            b.classList.remove('selected');
                        });
                        
                        // Aggiunge la classe 'selected' al pulsante cliccato
                        this.classList.add('selected');
                        
                        // Salva il font selezionato
                        selectedFont = this.getAttribute('data-font');
                        
                        // Aggiorna l'anteprima
                        generateBoxPreview();
                    });
                });
                
                // Seleziona il primo font come default
                const firstFontBtn = document.querySelector('.font-btn');
                if (firstFontBtn) {
                    firstFontBtn.classList.add('selected');
                    selectedFont = firstFontBtn.getAttribute('data-font');
                }
            }
        })
        .catch(error => {
            console.error('Errore:', error);
        });
    }
    
    /**
     * Aggiorna il contatore caratteri rimanenti
     */
    function updateCharsLeft() {
        if (!boxTextInput || !charsLeftSpan) {
            return;
        }
        
        const maxLength = parseInt(boxTextInput.getAttribute('maxlength') || artPuzzleMaxBoxTextLength);
        const remaining = maxLength - boxTextInput.value.length;
        
        charsLeftSpan.textContent = remaining;
        
        // Aggiorna l'anteprima della scatola
        generateBoxPreview();
    }
    
    /**
     * Genera l'anteprima della scatola
     */
    function generateBoxPreview() {
        if (!boxSimulation || !boxTextInput) {
            return;
        }
        
        // Ottieni il testo per la scatola
        const boxText = boxTextInput.value || artPuzzleDefaultBoxText;
        
        // Prepara i parametri
        const params = new URLSearchParams();
        params.append('action', 'generateBoxPreview');
        params.append('text', boxText);
        params.append('color', selectedBoxColor);
        params.append('font', selectedFont);
        params.append('template', 'classic'); // Default template
        params.append('token', artPuzzleToken);
        
        // Mostra overlay caricamento
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }
        
        // Invia richiesta
        fetch(artPuzzleAjaxUrl + '?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
            
            if (data.success && data.data.preview) {
                // Mostra l'anteprima
                boxSimulation.innerHTML = `<img src="${data.data.preview}" alt="Anteprima scatola" class="img-fluid">`;
            } else {
                boxSimulation.innerHTML = '<div class="alert alert-warning">Impossibile generare l\'anteprima della scatola.</div>';
            }
        })
        .catch(error => {
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
            console.error('Errore:', error);
            boxSimulation.innerHTML = '<div class="alert alert-danger">Errore durante la generazione dell\'anteprima.</div>';
        });
    }
    
    /**
     * Salva la personalizzazione e aggiunge al carrello
     */
    function savePuzzleCustomization() {
        if (!uploadedImage || !selectedFormat || !selectedBoxColor || !selectedFont) {
            alert('Devi completare tutti i passaggi di personalizzazione.');
            return;
        }
        
        // Ottieni il testo per la scatola
        const boxText = boxTextInput.value || artPuzzleDefaultBoxText;
        
        // Prepara i dati
        const customizationData = {
            product_id: artPuzzleProductId,
            customization: {
                format_id: selectedFormat,
                image: uploadedImage.file ? '' : null, // L'immagine è già stata caricata
                boxText: boxText,
                boxColor: selectedBoxColor,
                textColor: '#000000', // Default
                font: selectedFont,
                cropData: cropData
            }
        };
        
        // Mostra overlay caricamento
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }
        
        // Prepara i parametri
        const formData = new FormData();
        formData.append('action', 'add_to_cart');
        formData.append('id_product', artPuzzleProductId);
        formData.append('token', artPuzzleToken);
        formData.append('confirm-customization', 'true');
        
        // Invia richiesta
        fetch(artPuzzleAjaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
            
            if (data.success) {
                // Reindirizza al carrello
                window.location.href = data.data.cartUrl;
            } else {
                alert(data.message || 'Si è verificato un errore durante l\'aggiunta al carrello.');
            }
        })
        .catch(error => {
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
            console.error('Errore:', error);
            alert('Si è verificato un errore durante il salvataggio della personalizzazione.');
        });
    }
    
    /**
     * Verifica i permessi delle directory
     */
    function checkDirectoryPermissions() {
        // Prepara i parametri
        const params = new URLSearchParams();
        params.append('action', 'checkDirectoryPermissions');
        params.append('token', artPuzzleToken);
        
        // Invia richiesta
        fetch(artPuzzleAjaxUrl + '?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Avviso permessi directory:', data.message);
            }
        })
        .catch(error => {
            console.error('Errore durante la verifica dei permessi:', error);
        });
    }
    
    // Aggiunge event listener per il conteggio caratteri
    if (boxTextInput) {
        boxTextInput.addEventListener('input', updateCharsLeft);
    }
});
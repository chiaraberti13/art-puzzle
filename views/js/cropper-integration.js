/**
 * Art Puzzle - Integrazione CropperJS
 * Gestisce il ritaglio e la manipolazione dell'immagine per i puzzle personalizzati
 */

class ArtPuzzleCropper {
    /**
     * Inizializza il gestore di ritaglio
     * @param {Object} options - Opzioni di configurazione
     */
    constructor(options = {}) {
        // Impostazioni predefinite
        this.settings = {
            imageElement: null,            // Elemento immagine da ritagliare
            previewElement: null,          // Elemento per l'anteprima del ritaglio
            container: null,               // Contenitore del cropper
            aspectRatio: null,             // Rapporto di aspetto iniziale
            minWidth: 800,                 // Larghezza minima dell'immagine ritagliata (px)
            minHeight: 800,                // Altezza minima dell'immagine ritagliata (px)
            qualityWarningCallback: null,  // Callback per avvisi sulla qualità
            cropChangeCallback: null,      // Callback quando cambia il ritaglio
            onReadyCallback: null,         // Callback quando il cropper è pronto
            zoomable: true,                // Abilita zoom
            scalable: false,               // Abilita scaling
            rotatable: true,               // Abilita rotazione
            formats: {}                    // Formati puzzle disponibili
        };
        
        // Sovrascrive le impostazioni predefinite con quelle fornite
        Object.assign(this.settings, options);
        
        // Riferimento all'istanza cropper
        this.cropper = null;
        
        // Stato corrente
        this.state = {
            rotation: 0,           // Rotazione corrente (gradi)
            format: null,          // Formato puzzle selezionato
            cropData: null,        // Dati di ritaglio correnti
            originalImageData: {   // Dati dell'immagine originale
                width: 0,
                height: 0
            }
        };
        
        // Inizializza il cropper
        this.init();
    }
    
    /**
     * Inizializza il cropper
     */
    init() {
        // Verifica che l'elemento immagine esista
        if (!this.settings.imageElement) {
            console.error('ArtPuzzleCropper: Elemento immagine non specificato');
            return;
        }
        
        // Se l'immagine è già caricata, inizializza il cropper
        if (this.settings.imageElement.complete) {
            this.initCropper();
        } else {
            // Altrimenti, attendi il caricamento dell'immagine
            this.settings.imageElement.addEventListener('load', () => {
                this.initCropper();
            });
        }
    }
    
    /**
     * Inizializza il cropper vero e proprio
     */
    initCropper() {
        // Salva le dimensioni originali dell'immagine
        this.state.originalImageData = {
            width: this.settings.imageElement.naturalWidth,
            height: this.settings.imageElement.naturalHeight
        };
        
        // Opzioni per il cropper
        const cropperOptions = {
            viewMode: 1,              // Limita la visuale all'interno del contenitore
            dragMode: 'move',         // Modalità di trascinamento: muovi il canvas invece dell'area di crop
            aspectRatio: this.settings.aspectRatio,
            autoCropArea: 0.8,        // L'80% dell'immagine sarà selezionata per default
            restore: false,           // Non ripristina l'area di ritaglio dopo il ridimensionamento
            guides: true,             // Mostra guide di ritaglio
            center: true,             // Mostra indicatore del centro
            highlight: true,          // Mostra area bianca sull'area oscurata (per evidenziare l'area di ritaglio)
            cropBoxMovable: true,     // Permette di spostare il box di ritaglio
            cropBoxResizable: true,   // Permette di ridimensionare il box di ritaglio
            toggleDragModeOnDblclick: false, // Disattiva il cambio di modalità al doppio click
            
            // Personalizzazione della risposta
            zoomable: this.settings.zoomable,
            scalable: this.settings.scalable,
            rotatable: this.settings.rotatable,
            
            // Callbacks
            ready: () => {
                // Controlla la qualità dell'immagine
                this.checkImageQuality();
                
                // Notifica che il cropper è pronto
                if (typeof this.settings.onReadyCallback === 'function') {
                    this.settings.onReadyCallback(this);
                }
            },
            
            crop: (event) => {
                // Salva i dati di ritaglio correnti
                this.state.cropData = event.detail;
                
                // Aggiorna l'anteprima se è stato specificato un elemento
                if (this.settings.previewElement) {
                    this.updatePreview();
                }
                
                // Chiama il callback se disponibile
                if (typeof this.settings.cropChangeCallback === 'function') {
                    this.settings.cropChangeCallback(event.detail);
                }
            }
        };
        
        // Crea l'istanza del cropper
        this.cropper = new Cropper(this.settings.imageElement, cropperOptions);
    }
    
    /**
     * Aggiorna l'anteprima del ritaglio
     */
    updatePreview() {
        if (!this.settings.previewElement || !this.cropper) {
            return;
        }
        
        // Ottieni l'URL dell'immagine ritagliata
        const canvas = this.cropper.getCroppedCanvas({
            minWidth: this.settings.minWidth / 2,  // Dimensioni ridotte per l'anteprima
            minHeight: this.settings.minHeight / 2,
            maxWidth: 400,  // Limita le dimensioni dell'anteprima
            maxHeight: 400
        });
        
        // Imposta l'anteprima
        if (canvas) {
            this.settings.previewElement.src = canvas.toDataURL();
        }
    }
    
    /**
     * Controlla la qualità dell'immagine ritagliata
     */
    checkImageQuality() {
        if (!this.cropper || !this.state.originalImageData) {
            return;
        }
        
        const canvasData = this.cropper.getCanvasData();
        const containerData = this.cropper.getContainerData();
        
        // Calcola il rapporto tra le dimensioni originali e quelle visualizzate
        const scaleX = this.state.originalImageData.width / canvasData.naturalWidth;
        const scaleY = this.state.originalImageData.height / canvasData.naturalHeight;
        
        // Stima la qualità dell'immagine ritagliata
        let quality = 'alta';
        let message = 'L\'immagine è di ottima qualità per la stampa del puzzle.';
        
        // Se l'immagine originale è piccola, avvisa l'utente
        if (this.state.originalImageData.width < this.settings.minWidth || 
            this.state.originalImageData.height < this.settings.minHeight) {
            quality = 'bassa';
            message = 'L\'immagine è di bassa risoluzione. Il puzzle potrebbe apparire pixelato.';
        } else if (this.state.originalImageData.width < this.settings.minWidth * 1.5 || 
                   this.state.originalImageData.height < this.settings.minHeight * 1.5) {
            quality = 'media';
            message = 'L\'immagine è di media risoluzione. La qualità dovrebbe essere accettabile.';
        }
        
        // Notifica la qualità se è disponibile un callback
        if (typeof this.settings.qualityWarningCallback === 'function') {
            this.settings.qualityWarningCallback({
                quality: quality,
                message: message,
                originalWidth: this.state.originalImageData.width,
                originalHeight: this.state.originalImageData.height,
                minWidth: this.settings.minWidth,
                minHeight: this.settings.minHeight
            });
        }
        
        return {
            quality: quality,
            message: message
        };
    }
    
    /**
     * Ottieni i dati dell'immagine ritagliata come data URL
     */
    getCroppedImageDataURL(options = {}) {
        if (!this.cropper) {
            return null;
        }
        
        // Opzioni predefinite per il canvas
        const defaultOptions = {
            minWidth: this.settings.minWidth,
            minHeight: this.settings.minHeight,
            maxWidth: 4000,  // Limita le dimensioni massime
            maxHeight: 4000,
            fillColor: '#fff',
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        };
        
        // Unisce le opzioni predefinite con quelle fornite
        const canvasOptions = {...defaultOptions, ...options};
        
        // Ottieni il canvas ritagliato
        const canvas = this.cropper.getCroppedCanvas(canvasOptions);
        
        // Restituisci l'URL dei dati
        return canvas ? canvas.toDataURL('image/png') : null;
    }
    
    /**
     * Imposta il rapporto di aspetto per il ritaglio
     */
    setAspectRatio(ratio) {
        if (!this.cropper) {
            return;
        }
        
        this.cropper.setAspectRatio(ratio);
    }
    
    /**
     * Imposta il formato del puzzle
     */
    setFormat(formatId) {
        if (!this.settings.formats || !this.settings.formats[formatId]) {
            console.error('ArtPuzzleCropper: Formato non valido', formatId);
            return;
        }
        
        this.state.format = formatId;
        
        // Imposta il rapporto di aspetto in base al formato
        const format = this.settings.formats[formatId];
        if (format.ratio) {
            this.setAspectRatio(format.ratio);
        }
    }
    
    /**
     * Ruota l'immagine
     */
    rotate(degrees) {
        if (!this.cropper) {
            return;
        }
        
        // Aggiorna lo stato della rotazione
        this.state.rotation = (this.state.rotation + degrees) % 360;
        
        // Ruota l'immagine
        this.cropper.rotateTo(this.state.rotation);
    }
    
    /**
     * Ruota l'immagine a sinistra (90 gradi antiorario)
     */
    rotateLeft() {
        this.rotate(-90);
    }
    
    /**
     * Ruota l'immagine a destra (90 gradi orario)
     */
    rotateRight() {
        this.rotate(90);
    }
    
    /**
     * Zoom dell'immagine
     */
    zoom(ratio) {
        if (!this.cropper) {
            return;
        }
        
        this.cropper.zoom(ratio);
    }
    
    /**
     * Ripristina il cropper alle impostazioni iniziali
     */
    reset() {
        if (!this.cropper) {
            return;
        }
        
        this.cropper.reset();
        this.state.rotation = 0;
    }
    
    /**
     * Distrugge l'istanza del cropper
     */
    destroy() {
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
    }
    
    /**
     * Ottieni i dati di ritaglio correnti
     */
    getCropData() {
        return this.state.cropData;
    }
    
    /**
     * Ottieni lo stato corrente
     */
    getState() {
        return {
            rotation: this.state.rotation,
            format: this.state.format,
            cropData: this.state.cropData,
            originalImageData: this.state.originalImageData
        };
    }
}

// Esporta la classe
window.ArtPuzzleCropper = ArtPuzzleCropper;
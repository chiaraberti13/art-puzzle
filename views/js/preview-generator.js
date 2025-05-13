/**
 * Art Puzzle - Generatore Anteprime
 * Gestisce la generazione delle anteprime per puzzle e scatole personalizzate
 */

class ArtPuzzlePreviewGenerator {
    /**
     * Inizializza il generatore di anteprime
     * @param {Object} options - Opzioni di configurazione
     */
    constructor(options = {}) {
        // Impostazioni predefinite
        this.settings = {
            puzzlePreviewContainer: null,    // Contenitore anteprima puzzle
            boxPreviewContainer: null,       // Contenitore anteprima scatola
            summaryContainer: null,          // Contenitore riepilogo
            ajaxUrl: '',                     // URL per le richieste AJAX
            token: '',                       // Token di sicurezza
            loadingCallback: null,           // Callback durante il caricamento
            errorCallback: null              // Callback in caso di errore
        };
        
        // Sovrascrive le impostazioni predefinite con quelle fornite
        Object.assign(this.settings, options);
        
        // Stato delle anteprime
        this.state = {
            puzzlePreview: null,     // Anteprima del puzzle
            boxPreview: null,        // Anteprima della scatola
            format: null,            // Formato puzzle selezionato
            boxData: null            // Dati della scatola
        };
    }
    
    /**
     * Genera l'anteprima del puzzle
     * @param {Object} options - Opzioni per la generazione
     */
    generatePuzzlePreview(options = {}) {
        // Opzioni predefinite
        const defaultOptions = {
            format: '',              // ID del formato puzzle
            imageData: null,         // Dati immagine in base64
            cropData: null,          // Dati di ritaglio
            rotate: 0                // Rotazione in gradi
        };
        
        // Unisce le opzioni predefinite con quelle fornite
        const previewOptions = {...defaultOptions, ...options};
        
        // Verifica che ci siano i dati necessari
        if (!previewOptions.format) {
            this.handleError('Formato puzzle non specificato');
            return;
        }
        
        // Notifica l'inizio del caricamento
        this.startLoading();
        
        // Prepara i parametri
        const params = new URLSearchParams();
        params.append('action', 'generatePuzzlePreview');
        params.append('format', previewOptions.format);
        params.append('token', this.settings.token);
        
        // Aggiungi i dati di rotazione se presenti
        if (previewOptions.rotate) {
            params.append('rotate', previewOptions.rotate);
        }
        
        // Aggiungi i dati di ritaglio se presenti
        if (previewOptions.cropData) {
            params.append('crop', JSON.stringify(previewOptions.cropData));
        }
        
        // Preparazione per la richiesta
        let requestUrl = this.settings.ajaxUrl;
        let requestMethod = 'GET';
        let requestBody = null;
        
        // Se ci sono dati immagine, usa POST
        if (previewOptions.imageData) {
            requestMethod = 'POST';
            requestBody = new FormData();
            requestBody.append('action', 'generatePuzzlePreview');
            requestBody.append('format', previewOptions.format);
            requestBody.append('token', this.settings.token);
            requestBody.append('image', previewOptions.imageData);
            
            if (previewOptions.rotate) {
                requestBody.append('rotate', previewOptions.rotate);
            }
            
            if (previewOptions.cropData) {
                requestBody.append('crop', JSON.stringify(previewOptions.cropData));
            }
        } else {
            // Altrimenti usa GET
            requestUrl += '?' + params.toString();
        }
        
        // Invia la richiesta
        fetch(requestUrl, {
            method: requestMethod,
            body: requestBody
        })
        .then(response => response.json())
        .then(data => {
            // Termina il caricamento
            this.endLoading();
            
            if (data.success) {
                // Aggiorna lo stato
                this.state.puzzlePreview = data.data.preview;
                this.state.format = data.data.format;
                
                // Aggiorna l'anteprima se è stato specificato un contenitore
                if (this.settings.puzzlePreviewContainer) {
                    this.updatePuzzlePreviewUI();
                }
                
                return data.data;
            } else {
                this.handleError(data.message || 'Errore durante la generazione dell\'anteprima del puzzle');
                return null;
            }
        })
        .catch(error => {
            this.endLoading();
            this.handleError('Errore di connessione: ' + error.message);
            return null;
        });
    }
    
    /**
     * Genera l'anteprima della scatola
     * @param {Object} options - Opzioni per la generazione
     */
    generateBoxPreview(options = {}) {
        // Opzioni predefinite
        const defaultOptions = {
            template: 'classic',     // Template della scatola
            color: '#ffffff',        // Colore di sfondo
            text: '',                // Testo sulla scatola
            font: 'default'          // Font del testo
        };
        
        // Unisce le opzioni predefinite con quelle fornite
        const boxOptions = {...defaultOptions, ...options};
        
        // Notifica l'inizio del caricamento
        this.startLoading();
        
        // Prepara i parametri
        const params = new URLSearchParams();
        params.append('action', 'generateBoxPreview');
        params.append('token', this.settings.token);
        params.append('template', boxOptions.template);
        params.append('color', boxOptions.color);
        params.append('text', boxOptions.text);
        params.append('font', boxOptions.font);
        
        // Invia la richiesta
        fetch(this.settings.ajaxUrl + '?' + params.toString())
        .then(response => response.json())
        .then(data => {
            // Termina il caricamento
            this.endLoading();
            
            if (data.success) {
                // Aggiorna lo stato
                this.state.boxPreview = data.data.preview;
                this.state.boxData = boxOptions;
                
                // Aggiorna l'anteprima se è stato specificato un contenitore
                if (this.settings.boxPreviewContainer) {
                    this.updateBoxPreviewUI();
                }
                
                return data.data;
            } else {
                this.handleError(data.message || 'Errore durante la generazione dell\'anteprima della scatola');
                return null;
            }
        })
        .catch(error => {
            this.endLoading();
            this.handleError('Errore di connessione: ' + error.message);
            return null;
        });
    }
    
    /**
     * Genera l'anteprima completa (puzzle + scatola)
     */
    generateSummaryPreview() {
        // Verifica che ci siano i dati necessari in sessione
        if (!this.state.puzzlePreview && !this.state.boxPreview) {
            this.handleError('Devi prima configurare il puzzle e la scatola');
            return;
        }
        
        // Notifica l'inizio del caricamento
        this.startLoading();
        
        // Prepara i parametri
        const params = new URLSearchParams();
        params.append('action', 'generateSummaryPreview');
        params.append('token', this.settings.token);
        
        // Invia la richiesta
        fetch(this.settings.ajaxUrl + '?' + params.toString())
        .then(response => response.json())
        .then(data => {
            // Termina il caricamento
            this.endLoading();
            
            if (data.success) {
                // Aggiorna lo stato con i dati completi
                this.state.puzzlePreview = data.data.puzzlePreview;
                this.state.boxPreview = data.data.boxPreview;
                this.state.format = data.data.format;
                this.state.boxData = data.data.boxData;
                
                // Aggiorna il riepilogo se è stato specificato un contenitore
                if (this.settings.summaryContainer) {
                    this.updateSummaryUI();
                }
                
                return data.data;
            } else {
                this.handleError(data.message || 'Errore durante la generazione del riepilogo');
                return null;
            }
        })
        .catch(error => {
            this.endLoading();
            this.handleError('Errore di connessione: ' + error.message);
            return null;
        });
    }
    
    /**
     * Aggiorna l'interfaccia dell'anteprima del puzzle
     */
    updatePuzzlePreviewUI() {
        if (!this.settings.puzzlePreviewContainer || !this.state.puzzlePreview) {
            return;
        }
        
        // Aggiorna l'anteprima
        if (typeof this.settings.puzzlePreviewContainer === 'string') {
            // Se è un selettore, trova l'elemento
            const container = document.querySelector(this.settings.puzzlePreviewContainer);
            if (container) {
                container.innerHTML = `<img src="${this.state.puzzlePreview}" alt="Anteprima puzzle" class="img-fluid">`;
            }
        } else if (this.settings.puzzlePreviewContainer instanceof HTMLElement) {
            // Se è un elemento HTML
            this.settings.puzzlePreviewContainer.innerHTML = `<img src="${this.state.puzzlePreview}" alt="Anteprima puzzle" class="img-fluid">`;
        }
        
        // Aggiunge le informazioni sul formato se disponibili
        if (this.state.format) {
            const formatInfo = document.createElement('div');
            formatInfo.className = 'puzzle-format-info mt-3';
            formatInfo.innerHTML = `
                <h5>Dettagli formato</h5>
                <ul class="list-unstyled">
                    <li><strong>Formato:</strong> ${this.state.format.name}</li>
                    <li><strong>Dimensioni:</strong> ${this.state.format.dimensions}</li>
                    <li><strong>Numero pezzi:</strong> ${this.state.format.pieces}</li>
                </ul>
            `;
            
            // Aggiungi le informazioni dopo l'immagine
            if (typeof this.settings.puzzlePreviewContainer === 'string') {
                const container = document.querySelector(this.settings.puzzlePreviewContainer);
                if (container) {
                    // Rimuovi info precedenti se presenti
                    const oldInfo = container.querySelector('.puzzle-format-info');
                    if (oldInfo) {
                        oldInfo.remove();
                    }
                    container.appendChild(formatInfo);
                }
            } else if (this.settings.puzzlePreviewContainer instanceof HTMLElement) {
                // Rimuovi info precedenti se presenti
                const oldInfo = this.settings.puzzlePreviewContainer.querySelector('.puzzle-format-info');
                if (oldInfo) {
                    oldInfo.remove();
                }
                this.settings.puzzlePreviewContainer.appendChild(formatInfo);
            }
        }
    }
    
    /**
     * Aggiorna l'interfaccia dell'anteprima della scatola
     */
    updateBoxPreviewUI() {
        if (!this.settings.boxPreviewContainer || !this.state.boxPreview) {
            return;
        }
        
        // Aggiorna l'anteprima
        if (typeof this.settings.boxPreviewContainer === 'string') {
            // Se è un selettore, trova l'elemento
            const container = document.querySelector(this.settings.boxPreviewContainer);
            if (container) {
                container.innerHTML = `<img src="${this.state.boxPreview}" alt="Anteprima scatola" class="img-fluid">`;
            }
        } else if (this.settings.boxPreviewContainer instanceof HTMLElement) {
            // Se è un elemento HTML
            this.settings.boxPreviewContainer.innerHTML = `<img src="${this.state.boxPreview}" alt="Anteprima scatola" class="img-fluid">`;
        }
        
        // Aggiunge le informazioni sulla scatola se disponibili
        if (this.state.boxData) {
            const boxInfo = document.createElement('div');
            boxInfo.className = 'box-info mt-3';
            boxInfo.innerHTML = `
                <h5>Dettagli scatola</h5>
                <ul class="list-unstyled">
                    <li><strong>Testo:</strong> "${this.state.boxData.text ? this.state.boxData.text : 'Nessun testo'}"</li>
                    <li><strong>Colore:</strong> <span style="display:inline-block; width:20px; height:20px; background-color:${this.state.boxData.color}; vertical-align:middle;"></span> ${this.state.boxData.color}</li>
                    <li><strong>Font:</strong> ${this.state.boxData.font.replace('.ttf', '').replace('.otf', '')}</li>
                </ul>
            `;
            
            // Aggiungi le informazioni dopo l'immagine
            if (typeof this.settings.boxPreviewContainer === 'string') {
                const container = document.querySelector(this.settings.boxPreviewContainer);
                if (container) {
                    // Rimuovi info precedenti se presenti
                    const oldInfo = container.querySelector('.box-info');
                    if (oldInfo) {
                        oldInfo.remove();
                    }
                    container.appendChild(boxInfo);
                }
            } else if (this.settings.boxPreviewContainer instanceof HTMLElement) {
                // Rimuovi info precedenti se presenti
                const oldInfo = this.settings.boxPreviewContainer.querySelector('.box-info');
                if (oldInfo) {
                    oldInfo.remove();
                }
                this.settings.boxPreviewContainer.appendChild(boxInfo);
            }
        }
    }
    
    /**
     * Aggiorna l'interfaccia del riepilogo
     */
    updateSummaryUI() {
        if (!this.settings.summaryContainer) {
            return;
        }
        
        let container;
        if (typeof this.settings.summaryContainer === 'string') {
            // Se è un selettore, trova l'elemento
            container = document.querySelector(this.settings.summaryContainer);
        } else if (this.settings.summaryContainer instanceof HTMLElement) {
            // Se è un elemento HTML
            container = this.settings.summaryContainer;
        }
        
        if (!container) {
            return;
        }
        
        // Crea il riepilogo
        let summaryHTML = '<div class="row">';
        
        // Anteprima puzzle
        summaryHTML += '<div class="col-md-6">';
        summaryHTML += '<div class="card">';
        summaryHTML += '<div class="card-header"><h4>Il tuo puzzle</h4></div>';
        summaryHTML += '<div class="card-body text-center">';
        
        if (this.state.puzzlePreview) {
            summaryHTML += `<img src="${this.state.puzzlePreview}" alt="Anteprima puzzle" class="img-fluid">`;
        } else {
            summaryHTML += '<div class="alert alert-warning">Anteprima non disponibile</div>';
        }
        
        if (this.state.format) {
            summaryHTML += `
                <div class="puzzle-info mt-3">
                    <h5>Dettagli formato</h5>
                    <ul class="list-unstyled">
                        <li><strong>Formato:</strong> ${this.state.format.name}</li>
                        <li><strong>Dimensioni:</strong> ${this.state.format.dimensions}</li>
                        <li><strong>Numero pezzi:</strong> ${this.state.format.pieces}</li>
                        <li><strong>Orientamento:</strong> ${this.state.format.orientation}</li>
                    </ul>
                </div>
            `;
        }
        
        summaryHTML += '</div></div></div>';
        
        // Anteprima scatola
        summaryHTML += '<div class="col-md-6">';
        summaryHTML += '<div class="card">';
        summaryHTML += '<div class="card-header"><h4>La tua scatola</h4></div>';
        summaryHTML += '<div class="card-body text-center">';
        
        if (this.state.boxPreview) {
            summaryHTML += `<img src="${this.state.boxPreview}" alt="Anteprima scatola" class="img-fluid">`;
        } else {
            summaryHTML += '<div class="alert alert-warning">Anteprima non disponibile</div>';
        }
        
        if (this.state.boxData) {
            summaryHTML += `
                <div class="box-info mt-3">
                    <h5>Dettagli scatola</h5>
                    <ul class="list-unstyled">
                        <li><strong>Testo:</strong> "${this.state.boxData.text ? this.state.boxData.text : 'Nessun testo'}"</li>
                        <li><strong>Colore:</strong> <span style="display:inline-block; width:20px; height:20px; background-color:${this.state.boxData.color}; vertical-align:middle;"></span> ${this.state.boxData.color}</li>
                        <li><strong>Font:</strong> ${this.state.boxData.font.replace('.ttf', '').replace('.otf', '')}</li>
                    </ul>
                </div>
            `;
        }
        
        summaryHTML += '</div></div></div>';
        summaryHTML += '</div>'; // Chiude la row
        
        // Imposta l'HTML nel contenitore
        container.innerHTML = summaryHTML;
    }
    
    /**
     * Notifica l'inizio del caricamento
     */
    startLoading() {
        if (typeof this.settings.loadingCallback === 'function') {
            this.settings.loadingCallback(true);
        }
    }
    
    /**
     * Notifica la fine del caricamento
     */
    endLoading() {
        if (typeof this.settings.loadingCallback === 'function') {
            this.settings.loadingCallback(false);
        }
    }
    
    /**
     * Gestisce gli errori
     */
    handleError(message) {
        if (typeof this.settings.errorCallback === 'function') {
            this.settings.errorCallback(message);
        } else {
            console.error('ArtPuzzlePreviewGenerator: ' + message);
        }
    }
    
    /**
     * Ottieni lo stato corrente delle anteprime
     */
    getState() {
        return {
            puzzlePreview: this.state.puzzlePreview,
            boxPreview: this.state.boxPreview,
            format: this.state.format,
            boxData: this.state.boxData
        };
    }
    
    /**
     * Imposta lo stato dalle variabili di sessione
     */
    loadStateFromSession() {
        // Notifica l'inizio del caricamento
        this.startLoading();
        
        // Prepara i parametri
        const params = new URLSearchParams();
        params.append('action', 'getSessionState');
        params.append('token', this.settings.token);
        
        // Invia la richiesta
        fetch(this.settings.ajaxUrl + '?' + params.toString())
        .then(response => response.json())
        .then(data => {
            // Termina il caricamento
            this.endLoading();
            
            if (data.success) {
                // Aggiorna lo stato con i dati completi
                this.state = data.data.state || this.state;
                
                // Aggiorna l'interfaccia se necessario
                if (this.settings.puzzlePreviewContainer && this.state.puzzlePreview) {
                    this.updatePuzzlePreviewUI();
                }
                
                if (this.settings.boxPreviewContainer && this.state.boxPreview) {
                    this.updateBoxPreviewUI();
                }
                
                if (this.settings.summaryContainer && this.state.puzzlePreview && this.state.boxPreview) {
                    this.updateSummaryUI();
                }
                
                return data.data;
            }
        })
        .catch(error => {
            this.endLoading();
            console.error('Errore durante il caricamento dello stato:', error);
        });
    }
}

// Esporta la classe
window.ArtPuzzlePreviewGenerator = ArtPuzzlePreviewGenerator;
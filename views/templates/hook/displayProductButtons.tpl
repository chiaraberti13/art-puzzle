{*
* Art Puzzle Module - Product Buttons Template
*}

<div class="art-puzzle-customize-button my-3">
    <button type="button" class="btn btn-primary btn-block" id="art-puzzle-customize-btn-{$id_product|intval}">
        <i class="material-icons">brush</i> {l s='Personalizza il tuo puzzle' mod='art_puzzle'}
    </button>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Utilizzo ID univoco basato sul prodotto per evitare conflitti in caso di più prodotti in pagina
    var customizeBtn = document.getElementById('art-puzzle-customize-btn-{$id_product|intval}');
    
    if (customizeBtn) {
        customizeBtn.addEventListener('click', function() {
            try {
                // Verifica prima se esistono i tab
                var tabs = document.querySelectorAll('.nav-tabs .nav-link');
                var tabFound = false;
                
                if (tabs && tabs.length > 0) {
                    // Log per debug
                    console.log('Art Puzzle: Trovati ' + tabs.length + ' tab');
                    
                    for (var i = 0; i < tabs.length; i++) {
                        // Migliorata la ricerca del testo indipendentemente dalla lingua e case-insensitive
                        var tabText = tabs[i].textContent.toLowerCase().trim();
                        if (tabText.indexOf('personalizza') !== -1 && tabText.indexOf('puzzle') !== -1) {
                            console.log('Art Puzzle: Tab del puzzle trovato e attivato');
                            // Attiva questo tab
                            tabs[i].click();
                            tabFound = true;
                            
                            // Scroll alla sezione con maggiore affidabilità
                            setTimeout(function() {
                                // Cerca con vari possibili ID
                                var tabContent = document.getElementById('art-puzzle-tab-content') || 
                                                document.querySelector('.tab-pane.active .art-puzzle-container') ||
                                                document.querySelector('.art-puzzle-container');
                                
                                if (tabContent) {
                                    console.log('Art Puzzle: Contenuto tab trovato, scroll in corso');
                                    tabContent.scrollIntoView({ldelim}behavior: "smooth"{rdelim});
                                    
                                    // Trova e attiva il pulsante "Inizia a personalizzare"
                                    var startBtn = document.getElementById('art-puzzle-start-customize') || 
                                                document.querySelector('.art-puzzle-container .btn-primary');
                                    if (startBtn) {
                                        console.log('Art Puzzle: Pulsante start trovato e attivato');
                                        startBtn.click();
                                    } else {
                                        console.log('Art Puzzle: Pulsante start non trovato');
                                    }
                                } else {
                                    console.log('Art Puzzle: Contenuto tab non trovato, reindirizzamento');
                                    // Se non riesce a trovare il contenuto, reindirizza
                                    redirectToCustomizer();
                                }
                            }, 500); // Aumentato timeout per garantire che i tab siano completamente caricati
                            
                            break;
                        }
                    }
                } else {
                    console.log('Art Puzzle: Nessun tab trovato');
                }
                
                // Se non trova il tab, reindirizza alla pagina del personalizzatore
                if (!tabFound) {
                    console.log('Art Puzzle: Tab non trovato, reindirizzamento diretto');
                    redirectToCustomizer();
                }
            } catch (e) {
                // In caso di errore, usa il fallback
                console.error('Art Puzzle: Errore durante la ricerca del tab:', e);
                redirectToCustomizer();
            }
        });
    } else {
        console.error('Art Puzzle: Pulsante personalizza non trovato nel DOM');
    }
    
    // Funzione di fallback per reindirizzare alla pagina customizer
    function redirectToCustomizer() {
        window.location.href = '{$link->getModuleLink('art_puzzle', 'customizer', ['id_product' => $id_product])|escape:'javascript'}';
    }
});
</script>
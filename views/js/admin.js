/**
 * Art Puzzle - Admin JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza colorpicker se disponibile
    if (typeof $.fn.colorpicker !== 'undefined') {
        $('.art-puzzle-colorpicker').colorpicker();
    }
    
    // Visualizza notifica di installazione
    if (document.querySelector('.module_confirmation')) {
        // Simula un click sul primo campo input per attivare la validazione
        setTimeout(function() {
            var firstInput = document.querySelector('input[type="text"]');
            if (firstInput) {
                firstInput.focus();
                firstInput.blur();
            }
        }, 500);
    }
    
    // Verifica permessi di scrittura delle directory
    checkDirectoryPermissions();
});

/**
 * Verifica e notifica le autorizzazioni della directory
 */
function checkDirectoryPermissions() {
    if (typeof artPuzzleConfig !== 'undefined') {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', artPuzzleConfig.ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (!response.success) {
                        showPermissionsWarning(response.message);
                    }
                } catch (e) {
                    console.error('Errore nel parsing della risposta JSON', e);
                }
            }
        };
        
        xhr.send('action=checkDirectoryPermissions&token=' + artPuzzleConfig.token);
    }
}

/**
 * Mostra avviso sulla mancanza di permessi di scrittura
 */
function showPermissionsWarning(message) {
    var warningDiv = document.createElement('div');
    warningDiv.className = 'alert alert-warning';
    warningDiv.innerHTML = '<strong>Attenzione!</strong> ' + message;
    
    var configForm = document.querySelector('.panel');
    if (configForm) {
        configForm.parentNode.insertBefore(warningDiv, configForm);
    }
}
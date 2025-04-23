{*
* Art Puzzle Module - Product Buttons Template
*}

<div class="art-puzzle-customize-button my-3">
    <a href="javascript:void(0);" class="btn btn-primary btn-block" id="art-puzzle-customize-btn">
        <i class="material-icons">brush</i> {l s='Personalizza il tuo puzzle' mod='art_puzzle'}
    </a>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Attiva il click sul pulsante di personalizzazione
    document.getElementById('art-puzzle-customize-btn').addEventListener('click', function() {
        // Cerca il tab del puzzle
        var tabs = document.querySelectorAll('.nav-tabs .nav-link');
        for (var i = 0; i < tabs.length; i++) {
            if (tabs[i].textContent.indexOf('Personalizza il tuo puzzle') !== -1) {
                // Attiva questo tab
                tabs[i].click();
                
                // Scroll alla sezione
                setTimeout(function() {
                    document.getElementById('art-puzzle-tab-content').scrollIntoView({behavior: 'smooth'});
                    
                    // Trova e attiva il pulsante "Inizia a personalizzare"
                    var startBtn = document.getElementById('art-puzzle-start-customize');
                    if (startBtn) {
                        startBtn.click();
                    }
                }, 500);
                
                break;
            }
        }
    });
});
</script>
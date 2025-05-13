<div class="art-puzzle-box-container">
  <h2>{l s='Personalizza la tua scatola' mod='art_puzzle'}</h2>

  <div class="box-preview">
    {if isset($box_image)}
      <img src="{$box_image|escape:'htmlall':'UTF-8'}" alt="Anteprima scatola" class="img-responsive" />
    {else}
      <p>{l s='Nessuna immagine caricata.' mod='art_puzzle'}</p>
    {/if}
  </div>

  <form method="post" action="{$link->getModuleLink('art_puzzle', 'format', ['id_product' => $id_product])|escape:'htmlall':'UTF-8'}">
    <div class="form-group">
      <label for="box_text">{l s='Testo sulla scatola' mod='art_puzzle'}</label>
      <input type="text" name="box_text" id="box_text" value="{$box_text|escape:'htmlall':'UTF-8'}" class="form-control" />
    </div>

    <button type="submit" class="btn btn-primary">
      {l s='Conferma e continua' mod='art_puzzle'}
    </button>
  </form>
</div>

<style>
  .art-puzzle-box-container { max-width: 600px; margin: 2em auto; }
  .box-preview img { max-width: 100%; border: 1px solid #ccc; margin-bottom: 1em; }
</style>

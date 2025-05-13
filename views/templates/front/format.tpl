<div class="art-puzzle-format-container">
  <h2>{l s='Scegli il formato del tuo puzzle' mod='art_puzzle'}</h2>

  <form method="post" action="{$link->getModuleLink('art_puzzle', 'upload', ['id_product' => $id_product])|escape:'htmlall':'UTF-8'}">
    <div class="form-group">
      {foreach from=$formats key=format_id item=format_label}
        <div class="radio">
          <label>
            <input type="radio" name="puzzle_format" value="{$format_id|escape:'htmlall':'UTF-8'}" required>
            {$format_label|escape:'htmlall':'UTF-8'}
          </label>
        </div>
      {/foreach}
    </div>

    <button type="submit" class="btn btn-primary">
      {l s='Continua con il caricamento immagine' mod='art_puzzle'}
    </button>
  </form>
</div>

<style>
  .art-puzzle-format-container { max-width: 600px; margin: 2em auto; }
  .form-group .radio { margin-bottom: 1em; }
</style>

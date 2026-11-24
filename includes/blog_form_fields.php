<?php
// Partial — campos do formulário de blog (usado em create e edit)
$f = $editPost ?? [];
$cats = ['Tutoriais','Reviews','Guias','Notícias','Geral'];
?>
<div class="form-group">
    <label>Título:</label>
    <input type="text" name="title" required maxlength="200"
           value="<?= htmlspecialchars($f['title'] ?? $_POST['title'] ?? '') ?>"
           oninput="autoSlug(this.value)">
</div>
<div class="form-group">
    <label>Slug (URL):</label>
    <input type="text" name="slug" id="slugField" maxlength="220"
           value="<?= htmlspecialchars($f['slug'] ?? $_POST['slug'] ?? '') ?>">
    <small>Gerado automaticamente. Pode editar manualmente.</small>
</div>
<div class="form-group">
    <label>Resumo (aparece na listagem):</label>
    <input type="text" name="summary" required maxlength="300"
           value="<?= htmlspecialchars($f['summary'] ?? $_POST['summary'] ?? '') ?>">
</div>
<div class="form-group">
    <label>Categoria:</label>
    <select name="category">
        <?php foreach ($cats as $c): ?>
            <option value="<?= $c ?>" <?= ($f['category'] ?? 'Geral') === $c ? 'selected' : '' ?>><?= $c ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="form-group">
    <label>URL da imagem de capa (opcional):</label>
    <input type="text" name="cover_url" placeholder="images/blog/minha-foto.jpg"
           value="<?= htmlspecialchars($f['cover_url'] ?? $_POST['cover_url'] ?? '') ?>">
</div>
<div class="form-group">
    <label>Conteúdo (HTML permitido):</label>
    <textarea name="content" required rows="14"><?= htmlspecialchars($f['content'] ?? $_POST['content'] ?? '') ?></textarea>
    <small>Use tags HTML: &lt;h2&gt;, &lt;h3&gt;, &lt;p&gt;, &lt;strong&gt;, &lt;ul&gt;, &lt;li&gt;</small>
</div>
<div class="form-group form-check">
    <label>
        <input type="checkbox" name="is_published" value="1"
               <?= !empty($f['is_published']) || (!isset($f['is_published']) && isset($_POST['is_published'])) ? 'checked' : '' ?>>
        Publicar imediatamente
    </label>
</div>
<script>
function autoSlug(val) {
    const field = document.getElementById('slugField');
    if (!field || field.dataset.manual === 'true') return;
    field.value = val.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .trim().replace(/\s+/g, '-');
}
document.getElementById('slugField').addEventListener('input', function(){
    this.dataset.manual = 'true';
});
</script>

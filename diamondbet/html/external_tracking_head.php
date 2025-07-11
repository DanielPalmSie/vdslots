<?php if (phive()->getSetting('show_google_knowledge_graph', true) === true) { ?>
    <script type="application/ld+json">
        {
            "@context": "http://schema.org",
            "@type": "Organization",
            "name": "<?= phive()->getSetting('google_knowledge_graph')['name']; ?>",
            "description": "<?= phive()->getSetting('google_knowledge_graph')['description']; ?>",
            "logo": "<?= phive()->getSetting('google_knowledge_graph')['logo']; ?>",
            "url": "<?= phive()->getSiteUrl(); ?>",
            "founders": "Alexander Stevendahl, Mattias Sesemann and Magnus Hyltingö",
            "sameAs": <?= json_encode(phive()->getSetting('google_knowledge_graph')['same_as'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>
        }
    </script>
<?php
}

$lang = cuPlAttr('preferred_lang');

if (empty($lang)) {
    $lang = phive('Localizer')->getCurNonSubLang();
}

?>

<script type="text/javascript">
    // Ensure dataLayer & window.dataLayer are always accessible
    if(void 0===dataLayer){var dataLayer=[];window.dataLayer=dataLayer}
    function google_datalayer(d){dataLayer.push(d)}
    function gtag(){dataLayer.push(arguments)}
</script>

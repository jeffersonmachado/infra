jQuery(document).ready(function ($) {
    tinymce.init({
        selector: 'textarea#jform_content',
        menubar: false,
        height: 300,
        content_css: blogfactory_editor_css,
        plugins: 'link spellchecker fullscreen textcolor charmap code readmore blogfactoryimage wordcount advlist media',
        relative_urls: false,
        remove_script_host: false,
        image_advtab: true,
        preview_styles: false,
        toolbar1: 'bold italic strikethrough | numlist bullist blockquote | alignleft aligncenter alignright | link unlink media readmore | fullscreen',
        toolbar2: 'styleselect | underline alignjustify forecolor | paste | removeformat charmap | outdent indent | undo redo | code | blogfactoryimage',
        media_live_embeds: true
    });
});

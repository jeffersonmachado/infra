/*global tinymce:true */

tinymce.PluginManager.add('readmore', function(editor) {
  var cls = 'blogfactory-read-more';
  var pb  = '<hr class="' + cls + '" />';

	// Register buttons
	editor.addButton('readmore', {
		title: 'Read more',
    icon: 'pagebreak',
    onclick: function() {
      editor.execCommand('mceInsertContent', 0, pb);
		}
	});

  editor.on('click', function(e) {
		e = e.target;

		if (e.nodeName === 'HR' && editor.dom.hasClass(e, cls)) {
			editor.selection.select(e);
		}
	});
});

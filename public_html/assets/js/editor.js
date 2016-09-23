var Editor;
$(document).ready(function(){
  if($('.editor-container').length > 0){
    init_editor();
  }
});

function init_editor(name){
  var container = '.editor-container';
  var toolbar_container = '.toolbar-container';
  var editor_change_contents = '.editor_change_contents';
  var editor_contents = '.editor_contents';

// var container = editor.addContainer(container);
 Editor = new Quill(container, {
  modules: {
    'toolbar': {
      container: toolbar_container
    },
    'link-tooltip': true,
    'image-tooltip': true,
    'multi-cursor': true
  },
  theme: 'snow'
});
 Editor.on('text-change', function(delta, source) {
  data = Editor.getHTML();
  $(editor_change_contents).val(data);
});
 Editor.setHTML($(editor_contents).html());
}

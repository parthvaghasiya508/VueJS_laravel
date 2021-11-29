import Vue from 'vue';
import Quill from 'quill';
// import VueQuillEditor from 'vue-quill-editor';
import { quillEditor } from 'vue-quill-editor';

import 'quill/dist/quill.core.css';
import 'quill/dist/quill.snow.css';

const icons = Quill.import('ui/icons');
icons.codeview = icons.code;
icons.copy = '<svg width="18" height="18"><use xlink:href="/assets/icons/icons.svg#copy"></use></svg>';
icons.collapse = '<svg width="20" height="19" class="label"><use xlink:href="/assets/icons/icons.svg#dots-h"></use></svg>';

Vue.component('quill-editor', quillEditor);

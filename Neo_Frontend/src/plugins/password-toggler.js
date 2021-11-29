import Vue from 'vue';

function setAttributes(node, attributes) {
  Object.keys(attributes)
    .forEach((k) => {
      node.setAttribute(k, attributes[k]);
    });
}

const template = document.createElement('a');
template.classList.add('btn-icon');
template.classList.add('btn-icon-round');
template.href = '#';
template.tabIndex = -1;
template.setAttribute('password-toggler', '');

const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
setAttributes(svg, {
  width: '24',
  height: '19',
});
svg.classList.add('password-invisible');
svg.innerHTML = '<use xlink:href="#password_invisible_icon"></use>';
template.append(svg.cloneNode(true));
setAttributes(svg, {
  width: '24',
  height: '17',
});
svg.classList.remove('password-invisible');
svg.classList.add('password-visible');
svg.innerHTML = '<use xlink:href="#password_visible_icon"></use>';
template.append(svg.cloneNode(true));

Vue.directive('password-toggler', {
  bind(el, { arg = 'invisible' }) {
    if (arg === 'visible') el.setAttribute('type', 'text');
    Vue.nextTick(() => {
      const clone = template.cloneNode(true);
      clone.addEventListener('click', (e) => {
        e.preventDefault();
        el.setAttribute('type', el.getAttribute('type') === 'password' ? 'text' : 'password');
      });
      el.parentNode.insertBefore(clone, el.nextElementSibling);
    });
  },
  unbind(el) {
    const a = el.nextElementSibling;
    if (a != null && a.tagName.toLowerCase() === 'a' && a.getAttribute('password-toggler') != null) {
      el.parentNode.removeChild(a);
    }
  },
});

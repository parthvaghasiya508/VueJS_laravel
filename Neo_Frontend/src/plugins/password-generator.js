import Vue from 'vue';
import PasswordHelper from '@/helpers/password.helper';

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
template.setAttribute('password-generator', '');

const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
setAttributes(svg, {
  width: '21',
  height: '21',
});
svg.innerHTML = '<use xlink:href="#generate_password_icon"></use>';
svg.classList.add('password-generator');
template.append(svg.cloneNode(true));

Vue.directive('password-generator', {
  bind(el, { arg }) {
    Vue.nextTick(() => {
      const clone = template.cloneNode(true);
      clone.addEventListener('click', (e) => {
        e.preventDefault();
        el.value = PasswordHelper.generateRandomPwd(10); // eslint-disable-line no-param-reassign
      });
      if (arg) el.parentNode.insertBefore(clone, el.nextElementSibling);
    });
  },
  unbind(el) {
    const a = el.nextElementSibling;
    if (a != null && a.tagName.toLowerCase() === 'a' && a.getAttribute('password-generator') != null) {
      el.parentNode.removeChild(a);
    }
  },
});

<script>
  const svg = '/assets/images/sidenav.svg';

  export default {
    name: 'SideNavMenuPlaceholder',
    functional: true,
    inheritAttrs: true,
    render(createElement, context) {
      const { data } = context;
      data.attrs = { ...data.attrs };
      // data.attrs.xmlns = 'http://www.w3.org/2000/svg';
      // data.attrs['xmlns:xlink'] = 'http://www.w3.org/1999/xlink';
      data.attrs.width = '100%';
      data.attrs.height = '100%';
      const vd = 20;
      const elems = [
        createElement('use', {
          attrs: {
            'xlink:href': `${svg}#p1`,
            y: vd + 3 * 49 - 24,
            class: 'p1',
          },
        }),
        createElement('use', {
          attrs: {
            'xlink:href': `${svg}#p2`,
            y: vd + 4 * 49 + 1,
            class: 'p2',
          },
        }),
        createElement('use', {
          attrs: {
            'xlink:href': `${svg}#p3`,
            y: vd + 3 * 49 + 3,
            x: 10,
            class: 'p3',
          },
        }),
      ];
      for (let i = 0; i < 21; i += 1) {
        const cls = (i + 1) % 3 === 0 ? 'rect1' : 'rect0';
        const cls2 = (i + 1) % 3 === 0 ? 'rect3' : 'rect2';
        elems.push(createElement('use', {
          attrs: {
            'xlink:href': `${svg}#circle0`,
            y: vd + 16 + i * 49,
            x: 29,
            class: `circle0 ${i === 3 ? 'd3' : ''}`,
          },
        }));
        elems.push(createElement('use', {
          attrs: {
            'xlink:href': `${svg}#${cls}`,
            y: vd + 20 + i * 49,
            x: 75,
            class: `rect ${cls} ${i === 3 ? 'd3' : ''}`,
          },
        }));
        elems.push(createElement('use', {
          attrs: {
            'xlink:href': `${svg}#${cls2}`,
            y: vd + 20 + i * 49,
            x: 75,
            class: `rect ${cls2} ${i === 3 ? 'd3' : ''}`,
          },
        }));
      }
      return createElement('svg', data, elems);
    },
  };
</script>

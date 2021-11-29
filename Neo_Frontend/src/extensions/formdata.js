FormData.prototype.appendFromObject = function appendFieldsFromObject(payload) {
  const convertValue = (v) => {
    if (v == null) return '';
    switch (v.constructor) {
      case Boolean:
        return v ? '1' : '0';
      default:
        return v;
    }
  };
  const isTraversable = (o) => o != null && (o.constructor === Array || o.constructor === Object);
  const traverse = (obj, path = '') => {
    const a = obj.constructor === Array;
    const list = a ? obj : Object.keys(obj);
    list.forEach((v, k) => {
      const key = a ? k : v;
      const val = a ? v : obj[v];
      const pathkey = path ? `[${key}]` : key;
      const opath = `${path}${pathkey}`;
      if (isTraversable(val)) {
        traverse(val, opath);
      } else if (val != null && val.constructor === File) {
        this.append(opath, val, val.name);
      } else {
        this.append(opath, convertValue(val));
      }
    });
  };

  traverse(payload);
};

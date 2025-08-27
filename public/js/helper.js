function select2SetProduct($el, product) {
    const text = product.kode + ' - ' + product.nama;
    let option = new Option(text, product.id, true, true);
    $el.append(option).trigger('change');

    $el.trigger({
        type: 'select2:select',
        params: { data: { id: product.id, text: text, satuan_kecil: product.satuan_kecil || '' } }
    });
}

function numberFormat(number, decimals = 0, decPoint = '.', thousandsSep = ',') {
    number = parseFloat(number);
    if (isNaN(number)) {
        return '';
    }
    if (number === 0) {
        return '0';
    }
  let parts = number.toFixed(decimals).split('.');
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSep);
  return parts.join(decPoint);
}

function isString(val) {
  return typeof val === 'string' || val instanceof String;
}

function parseStringToFloat(text) {
  if (!text) return NaN;
  return parseFloat(
    text.replace(/,/g, '')  // remove thousands
  );
}

// make sure it's global
window.select2SetProduct = select2SetProduct;
window.numberFormat = numberFormat;
window.isString = isString;
window.parseStringToFloat = parseStringToFloat; 
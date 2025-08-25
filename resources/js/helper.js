// resources/js/helper.js
export function select2SetProduct($el, product) {
    const text = product.kode + ' - ' + product.nama;
    let option = new Option(text, product.id, true, true);
    $el.append(option).trigger('change');

    $el.trigger({
        type: 'select2:select',
        params: { data: { id: product.id, text: text, satuan_kecil: product.satuan_kecil || '' } }
    });
}

export function numberFormat(number, decimals = 0, decPoint = '.', thousandsSep = ',') {
number = parseFloat(number);
  let parts = number.toFixed(decimals).split('.');
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSep);
  return parts.join(decPoint);
}



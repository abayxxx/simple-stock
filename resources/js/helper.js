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

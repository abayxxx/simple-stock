function refillSelectSticky($sel, list = [], placeholder = '-- Pilih --') {
  const keep = $sel.val();
  const opts = [`<option value="">${placeholder}</option>`];
  list.forEach(v => {
    const sel = (v == keep) ? ' selected' : '';
    opts.push(`<option value="${v}"${sel}>${v}</option>`);
  });
  if (keep && !list.includes(keep)) {
    opts.push(`<option value="${keep}" selected>${keep}</option>`);
  }
  $sel.html(opts.join(''));
}

// expose to global so inline scripts can use it
window.refillSelectSticky = refillSelectSticky;
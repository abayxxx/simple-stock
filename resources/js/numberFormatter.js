// ---- Number formatting helpers (no import needed) ----
function nfFormat(num) {
  return new Intl.NumberFormat("id-ID", { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(num);
}
function nfParse(text) {
  if (!text) return NaN;
  return parseFloat(
    text.toString()
      .replace(/\s+/g, "")
      .replace(/[^\d,.\-]/g, "")
      .replace(/\./g, "")   // remove thousands
      .replace(",", ".")    // decimal comma -> dot
  );
}

// Prettify existing values on load (incl. from hidden pair)
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".format-number").forEach((el) => {
    let source = "";
    if (el.id && el.id.endsWith("_display")) {
      const hidden = document.getElementById(el.id.replace(/_display$/, ""));
      if (hidden && hidden.value !== "") source = hidden.value;
    }
    if (!source) source = el.value;
    const n = nfParse(source);
    if (!isNaN(n)) el.value = nfFormat(n);
  });
});

// Live formatting while typing + keep hidden in sync if present
document.addEventListener("input", (e) => {
  const el = e.target;
  if (!el.classList?.contains("format-number")) return;
  const n = nfParse(el.value);
  if (!isNaN(n)) {
    el.value = nfFormat(n);
    if (el.id && el.id.endsWith("_display")) {
      const hidden = document.getElementById(el.id.replace(/_display$/, ""));
      if (hidden) hidden.value = n.toString();
    }
  } else {
    if (el.id && el.id.endsWith("_display")) {
      const hidden = document.getElementById(el.id.replace(/_display$/, ""));
      if (hidden) hidden.value = "";
    }
  }
});

// Before submit: if NO hidden pair, send raw number
document.addEventListener("submit", (e) => {
  const form = e.target;
  if (!(form instanceof HTMLFormElement)) return;
  form.querySelectorAll(".format-number").forEach((el) => {
    const hasHiddenPair = el.id && el.id.endsWith("_display") && document.getElementById(el.id.replace(/_display$/, ""));
    if (!hasHiddenPair) {
      const n = nfParse(el.value);
      if (!isNaN(n)) el.value = n.toString();
    }
  });
});

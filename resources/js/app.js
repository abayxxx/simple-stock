import "./bootstrap";
import Alpine from "alpinejs";
import { select2SetProduct, numberFormat } from "./helper";
import "./numberFormatter";

window.Alpine = Alpine;
window.select2SetProduct = select2SetProduct;
window.numberFormat = numberFormat;

Alpine.start();
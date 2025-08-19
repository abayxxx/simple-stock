import './bootstrap';
import { select2SetProduct, numberFormat } from './helper';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.select2SetProduct = select2SetProduct; // make it global
window.numberFormat = numberFormat; // make it global

Alpine.start();

import './bootstrap';
import { select2SetProduct } from './helper';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.select2SetProduct = select2SetProduct; // make it global

Alpine.start();

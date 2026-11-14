

import Alpine from 'alpinejs';
import { createLoginSelfie } from './attendance';
import './pwa';

window.Alpine = Alpine;
window.loginSelfie = createLoginSelfie;

Alpine.start();

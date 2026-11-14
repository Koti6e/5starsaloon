

import Alpine from 'alpinejs';
import { createLoginSelfie } from './attendance';
import { initializeLoginGps } from './login-gps';
import './pwa';

window.Alpine = Alpine;
window.loginSelfie = createLoginSelfie;

initializeLoginGps();
Alpine.start();

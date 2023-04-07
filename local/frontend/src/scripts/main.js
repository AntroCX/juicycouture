import promoStrip from './parts/promo-strip';
import mobileMenu from './parts/mobileMenu'

const initScripts = () => {
   promoStrip();
   mobileMenu();
}

document.addEventListener('DOMContentLoaded', initScripts);
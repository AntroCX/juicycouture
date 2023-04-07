import {startPageScrolling, stopPageScrolling} from "./services/pageScrolling";

export default () => {
  const menuBtn = document.querySelector('.b-header__mobile-btn');
  const html = document.querySelector('html');

  if (menuBtn) {
    menuBtn.addEventListener('click', () => {
      if (window.innerWidth < 768) {
        if (html.hasAttribute('data-popup-is-open')) {
          startPageScrolling();
        } else {
          stopPageScrolling();
        }
      }
    })
  }
}
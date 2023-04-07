export default () => {
   const promoItems = document.querySelectorAll('.js-promo-strip-item');
   const promoNextArrow = document.querySelector('.js-promo-strip .swiper-button-next');
   const promoPrevArrow = document.querySelector('.js-promo-strip .swiper-button-prev');

   // Убираем стрелки, если в слайдере остаётся один элемент
   if (promoItems.length !== 1) {
      new Swiper('.js-promo-strip', {
         navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
         },
         loop: true,
         autoplay: {
            delay: 5000,
         },
      });
   } else {
      promoNextArrow.style.display = "none";
      promoPrevArrow.style.display = "none";
   }

}
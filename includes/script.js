// Carrossel de Banners
var swiperBanner = new Swiper(".mySwiperBanner", {
  slidesPerView: 1,
  loop: true,
  autoplay: { delay: 5000, disableOnInteraction: false },
  pagination: { el: ".mySwiperBanner .swiper-pagination", clickable: true },
  navigation: {
    nextEl: ".mySwiperBanner .swiper-button-next",
    prevEl: ".mySwiperBanner .swiper-button-prev",
  },
});

// Segundo carrossel de banners
var swiperBanner2 = new Swiper(".mySwiperBanner2", {
  slidesPerView: 1,
  loop: true,
  autoplay: { delay: 5000, disableOnInteraction: false },
  pagination: { el: ".mySwiperBanner2 .swiper-pagination", clickable: true },
  navigation: {
    nextEl: ".mySwiperBanner2 .swiper-button-next",
    prevEl: ".mySwiperBanner2 .swiper-button-prev",
  },
});

// Carrossel A
var swiperA = new Swiper(".mySwiperA", {
  slidesPerView: 1,
  spaceBetween: 20,
  loop: true,
  autoplay: { delay: 3000, disableOnInteraction: false },
  pagination: { el: ".mySwiperA .swiper-pagination", clickable: true },
  navigation: {
    nextEl: ".mySwiperA .swiper-button-next",
    prevEl: ".mySwiperA .swiper-button-prev",
  },
  breakpoints: { 640: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } },
});

// Carrossel B
var swiperB = new Swiper(".mySwiperB", {
  slidesPerView: 1,
  spaceBetween: 20,
  loop: true,
  autoplay: { delay: 4000, disableOnInteraction: false },
  pagination: { el: ".mySwiperB .swiper-pagination", clickable: true },
  navigation: {
    nextEl: ".mySwiperB .swiper-button-next",
    prevEl: ".mySwiperB .swiper-button-prev",
  },
  breakpoints: { 640: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } },
});

document.querySelectorAll('img[loading="lazy"]').forEach(img => {
  img.addEventListener('load', () => {
    img.classList.add('loaded');
  });
});

// Lazy loading com placeholder + blur
document.querySelectorAll('img.lazy-blur').forEach(img => {
  img.addEventListener('load', () => {
    img.classList.add('loaded');
  });
});

// Carrossel de produtos relacionados
var swiperRelated = new Swiper(".mySwiperRelated", {
  slidesPerView: 1,
  spaceBetween: 20,
  loop: true,
  autoplay: { delay: 3500, disableOnInteraction: false },
  pagination: { el: ".mySwiperRelated .swiper-pagination", clickable: true },
  navigation: {
    nextEl: ".mySwiperRelated .swiper-button-next",
    prevEl: ".mySwiperRelated .swiper-button-prev",
  },
  breakpoints: {
    640: { slidesPerView: 2 },
    1024: { slidesPerView: 3 }
  },
});
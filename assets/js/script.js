/* =========================
FILE: script.js
========================= */

document.querySelectorAll(".chip-default").forEach((chip) => {
    chip.addEventListener("mouseenter", () => {
        chip.style.transform = "translateY(-2px)";
    });

    chip.addEventListener("mouseleave", () => {
        chip.style.transform = "translateY(0px)";
    });
});

// Projects
var swiper = new Swiper(".propertySwiper", {

    loop: true,

    speed: 800,

    spaceBetween: 16,

    autoplay: {
        delay: 3000,
        disableOnInteraction: false,
    },

    navigation: {
        nextEl: ".property-next",
        prevEl: ".property-prev",
    },

    breakpoints: {

        0: {
            slidesPerView: 1.05,
            spaceBetween: 14,
        },

        640: {
            slidesPerView: 1.2,
            spaceBetween: 16,
        },

        768: {
            slidesPerView: 2,
            spaceBetween: 20,
        },

        1200: {
            slidesPerView: 3,
            spaceBetween: 24,
        },
        1720: {
            slidesPerView: 4,
            spaceBetween: 24,
        }

    }

});

// Top Developers Projects & Projects Testimonials
window.addEventListener("load", function () {

    // Top Developers Projects
    new Swiper(".developerSwiper", {

        loop: true,

        speed: 800,

        spaceBetween: 20,

        autoplay: {
            delay: 2500,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
        },

        navigation: {
            nextEl: ".developer-next",
            prevEl: ".developer-prev",
        },

        breakpoints: {

            0: {
                slidesPerView: 1,
                spaceBetween: 16,
            },

            640: {
                slidesPerView: 2,
                spaceBetween: 16,
            },

            1024: {
                slidesPerView: 4,
                spaceBetween: 24,
            },

            1400: {
                slidesPerView: 5,
                spaceBetween: 24,
            }

        }

    });

    // Projects Testimonials
    new Swiper(".testimonialSwiper", {

        loop: true,
        speed: 800,
        spaceBetween: 20,

        autoplay: {
            delay: 2500,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
        },

        navigation: {
            nextEl: ".testimonial-next",
            prevEl: ".testimonial-prev",
        },

        breakpoints: {

            0: {
                slidesPerView: 1.1,
                spaceBetween: 16,
            },

            640: {
                slidesPerView: 1.5,
                spaceBetween: 18,
            },

            768: {
                slidesPerView: 2.2,
                spaceBetween: 20,
            },

            1024: {
                slidesPerView: 3,
                spaceBetween: 24,
            },

            1280: {
                slidesPerView: 4,
                spaceBetween: 24,
            }

        }

    });

});

// FAQ's
document.addEventListener("DOMContentLoaded", () => {

    const faqItems = document.querySelectorAll(".faq-item");

    faqItems.forEach((item) => {

        const btn = item.querySelector(".faq-btn");
        const content = item.querySelector(".faq-content");
        const icon = item.querySelector(".faq-icon");

        btn.addEventListener("click", () => {

            const isActive = content.style.maxHeight;

            // CLOSE ALL
            faqItems.forEach((faq) => {

                faq.querySelector(".faq-content").style.maxHeight = null;
                faq.querySelector(".faq-icon").classList.remove("-rotate-90");

            });

            // OPEN CURRENT
            if (!isActive) {

                content.style.maxHeight = content.scrollHeight + "px";
                icon.classList.add("-rotate-90");

            }

        });

    });

});



function scrollCarousel(direction) {
    const carousel = document.getElementById('featureCarousel');
    const scrollAmount = 320;

    carousel.scrollBy({
        left: direction * scrollAmount,
        behavior: 'smooth'
    });
}


function switchTab(tab) {

    const onlineTab = document.getElementById("onlineTab");
    const sitevisitTab = document.getElementById("sitevisitTab");

    const onlineBtn = document.getElementById("onlineBtn");
    const sitevisitBtn = document.getElementById("sitevisitBtn");

    // RESET BUTTONS
    onlineBtn.classList.remove(
        "text-[#1f4f79]",
        "border-[#1f4f79]"
    );

    onlineBtn.classList.add(
        "text-gray-400",
        "border-gray-400"
    );

    sitevisitBtn.classList.remove(
        "text-[#1f4f79]",
        "border-[#1f4f79]"
    );

    sitevisitBtn.classList.add(
        "text-gray-400",
        "border-gray-400"
    );

    // SHOW TAB
    if (tab === "online") {

        onlineTab.classList.remove("hidden");
        sitevisitTab.classList.add("hidden");

        onlineBtn.classList.remove(
            "text-gray-400",
            "border-gray-400"
        );

        onlineBtn.classList.add(
            "text-[#1f4f79]",
            "border-[#1f4f79]"
        );

    } else {

        sitevisitTab.classList.remove("hidden");
        onlineTab.classList.add("hidden");

        sitevisitBtn.classList.remove(
            "text-gray-400",
            "border-gray-400"
        );

        sitevisitBtn.classList.add(
            "text-[#1f4f79]",
            "border-[#1f4f79]"
        );

    }

}

// Active tab

const sections = document.querySelectorAll("section");
const navLinks = document.querySelectorAll(".nav-link");

window.addEventListener("scroll", () => {

    let current = "";

    sections.forEach((section) => {

        const sectionTop = section.offsetTop - 120;
        const sectionHeight = section.clientHeight;

        if (pageYOffset >= sectionTop) {
            current = section.getAttribute("id");
        }

    });

    navLinks.forEach((link) => {

        link.classList.remove("active", "border-b-2", "border-white", "opacity-100");
        link.classList.add("opacity-90");

        if (link.getAttribute("href") === `#${current}`) {
            link.classList.add("active", "border-b-2", "border-white", "opacity-100");
            link.classList.remove("opacity-90");
        }

    });

});
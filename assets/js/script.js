document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".chip-default").forEach((chip) => {
        chip.addEventListener("mouseenter", () => {
            chip.style.transform = "translateY(-2px)";
        });

        chip.addEventListener("mouseleave", () => {
            chip.style.transform = "translateY(0px)";
        });
    });

    if (typeof Swiper !== "undefined" && document.querySelector(".propertySwiper")) {
        new Swiper(".propertySwiper", {
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
                0: { slidesPerView: 1.05, spaceBetween: 14 },
                640: { slidesPerView: 1.2, spaceBetween: 16 },
                768: { slidesPerView: 2, spaceBetween: 20 },
                1200: { slidesPerView: 3, spaceBetween: 24 },
                1720: { slidesPerView: 4, spaceBetween: 24 },
            },
        });
    }

    document.querySelectorAll(".faq-item").forEach((item) => {
        const btn = item.querySelector(".faq-btn");
        const content = item.querySelector(".faq-content");
        const icon = item.querySelector(".faq-icon");

        if (!btn || !content || !icon) {
            return;
        }

        btn.addEventListener("click", () => {
            const isActive = content.style.maxHeight;

            document.querySelectorAll(".faq-item").forEach((faq) => {
                faq.querySelector(".faq-content")?.style.setProperty("max-height", "");
                faq.querySelector(".faq-icon")?.classList.remove("-rotate-90");
            });

            if (!isActive) {
                content.style.maxHeight = content.scrollHeight + "px";
                icon.classList.add("-rotate-90");
            }
        });
    });

    const sections = document.querySelectorAll("section[id]");
    const navLinks = document.querySelectorAll(".nav-link");

    if (sections.length && navLinks.length) {
        window.addEventListener("scroll", () => {
            let current = "";

            sections.forEach((section) => {
                const sectionTop = section.offsetTop - 120;

                if (window.pageYOffset >= sectionTop) {
                    current = section.getAttribute("id") || "";
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
    }

    document.querySelectorAll("[data-wishlist-form]").forEach((form) => {
        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            const button = form.querySelector("[data-wishlist-button]");
            const icon = form.querySelector("[data-wishlist-icon]");
            const label = form.querySelector("[data-wishlist-label]");

            button?.setAttribute("disabled", "disabled");
            button?.classList.add("opacity-70", "pointer-events-none");

            try {
                const response = await fetch(form.action, {
                    method: "POST",
                    body: new FormData(form),
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "Accept": "application/json",
                    },
                });

                const data = await response.json();

                if (response.status === 401 && data.login_url) {
                    window.location.href = data.login_url;
                    return;
                }

                if (!response.ok || !data.success) {
                    throw new Error(data.message || "Could not update wishlist.");
                }

                if (data.saved) {
                    icon?.classList.remove("fa-regular");
                    icon?.classList.add("fa-solid", "text-red-500");
                    button?.setAttribute("aria-label", "Saved in wishlist");

                    if (label) {
                        label.textContent = "Saved Project";
                    }
                } else {
                    icon?.classList.remove("fa-solid", "text-red-500");
                    icon?.classList.add("fa-regular");
                    button?.setAttribute("aria-label", "Add to wishlist");

                    if (label) {
                        label.textContent = "Save Project";
                    }
                }

                document.querySelectorAll("[data-wishlist-count]").forEach((count) => {
                    count.textContent = data.wishlist_count ?? count.textContent;
                });

                document.querySelectorAll(`[data-wishlist-project-id="${form.querySelector('input[name="project_id"]')?.value}"]`).forEach((matchingForm) => {
                    if (matchingForm === form) {
                        return;
                    }

                    const matchingButton = matchingForm.querySelector("[data-wishlist-button]");
                    const matchingIcon = matchingForm.querySelector("[data-wishlist-icon]");
                    const matchingLabel = matchingForm.querySelector("[data-wishlist-label]");

                    if (data.saved) {
                        matchingIcon?.classList.remove("fa-regular");
                        matchingIcon?.classList.add("fa-solid", "text-red-500");
                        matchingButton?.setAttribute("aria-label", "Saved in wishlist");
                        matchingLabel && (matchingLabel.textContent = "Saved Project");
                    } else {
                        matchingIcon?.classList.remove("fa-solid", "text-red-500");
                        matchingIcon?.classList.add("fa-regular");
                        matchingButton?.setAttribute("aria-label", "Add to wishlist");
                        matchingLabel && (matchingLabel.textContent = "Save Project");
                    }
                });
            } catch (error) {
                form.submit();
            } finally {
                button?.removeAttribute("disabled");
                button?.classList.remove("opacity-70", "pointer-events-none");
            }
        });
    });
});

window.addEventListener("load", () => {
    if (typeof Swiper === "undefined") {
        return;
    }

    if (document.querySelector(".developerSwiper")) {
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
                0: { slidesPerView: 1, spaceBetween: 16 },
                640: { slidesPerView: 2, spaceBetween: 16 },
                1024: { slidesPerView: 4, spaceBetween: 24 },
                1400: { slidesPerView: 5, spaceBetween: 24 },
            },
        });
    }

    if (document.querySelector(".testimonialSwiper")) {
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
                    slidesPerView: 1.2,
                    spaceBetween: 12,
                },
                576: {
                    slidesPerView: 1.5,
                    spaceBetween: 14,
                },
                768: {
                    slidesPerView: 2.2,
                    spaceBetween: 16,
                },
                992: {
                    slidesPerView: 3,
                    spaceBetween: 20,
                },
                1200: {
                    slidesPerView: 3.5,
                    spaceBetween: 22,
                },
                1400: {
                    slidesPerView: 4.5,
                    spaceBetween: 24,
                },
                1720: {
                    slidesPerView: 5,
                    spaceBetween: 24,
                },
            },
        });
    }
});

function scrollCarousel(direction) {
    const carousel = document.getElementById("featureCarousel");

    if (!carousel) {
        return;
    }

    carousel.scrollBy({
        left: direction * 320,
        behavior: "smooth",
    });
}

function switchTab(tab) {
    const onlineTab = document.getElementById("onlineTab");
    const sitevisitTab = document.getElementById("sitevisitTab");
    const onlineBtn = document.getElementById("onlineBtn");
    const sitevisitBtn = document.getElementById("sitevisitBtn");

    if (!onlineTab || !sitevisitTab || !onlineBtn || !sitevisitBtn) {
        return;
    }

    onlineBtn.classList.remove("text-[#1f4f79]", "border-[#1f4f79]");
    onlineBtn.classList.add("text-gray-400", "border-gray-400");
    sitevisitBtn.classList.remove("text-[#1f4f79]", "border-[#1f4f79]");
    sitevisitBtn.classList.add("text-gray-400", "border-gray-400");

    if (tab === "online") {
        onlineTab.classList.remove("hidden");
        sitevisitTab.classList.add("hidden");
        onlineBtn.classList.remove("text-gray-400", "border-gray-400");
        onlineBtn.classList.add("text-[#1f4f79]", "border-[#1f4f79]");
    } else {
        sitevisitTab.classList.remove("hidden");
        onlineTab.classList.add("hidden");
        sitevisitBtn.classList.remove("text-gray-400", "border-gray-400");
        sitevisitBtn.classList.add("text-[#1f4f79]", "border-[#1f4f79]");
    }
}

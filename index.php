<?php
// Ensure session and database are loaded
require_once __DIR__ . '/includes/db_connect.php';

require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="relative mt-20">

  <!-- BACKGROUND IMAGE -->
  <img
    src="https://i.ibb.co/gZfMz1nR/Gemini-Generated-Image-othiv4othiv4othi.png"
    alt="Real Estate"
    class="absolute inset-0 w-full min-h-[78%] max-h-[88%] object-cover" />

  <!-- CONTENT -->
  <div class="relative h-full z-10 max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-20">

    <!-- HERO CONTENT -->
    <div class="pt-12 md:pt-16 h-full">

      <!-- TEXT -->
      <div class="max-w-5xl">

        <!-- BADGE -->
        <div
          class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/10 px-4 py-2 rounded-full mb-6">

          <span class="w-2.5 h-2.5 rounded-full bg-accent animate-pulse"></span>

          <p class="text-white text-xs sm:text-sm font-medium">
            India's Trusted Luxury Property Platform
          </p>

        </div>

        <!-- HEADING -->
        <h1
          class="text-white font-semibold
  text-4xl sm:text-5xl lg:text-7xl
  tracking-[-2px] md:tracking-[-4px]
  leading-tight lg:leading-[1.2]">

          India's Largest
          <br />
          <span class="text-accent">
            Real Estate
          </span>
          Platform

        </h1>

      </div>

      <!-- SEARCH AREA -->
      <div class="mt-12 md:mt-20">

        <!-- TOP TABS -->
        <div class="max-w-sm grid grid-cols-2 gap-2 overflow-hidden font-medium text-sm">

          <button
            class="rounded-2xl rounded-b-none bg-primary border-b-4 border-accent-400 
      text-white px-5 py-3 transition">
            Apartment
          </button>

          <button
            class="rounded-2xl rounded-b-none bg-primary text-white/80 hover:text-white 
      px-5 py-3 transition">
            Plots
          </button>

        </div>

        <!-- MAIN SEARCH BOX -->
        <div class="max-w-5xl w-full bg-white shadow-sm border rounded-2xl rounded-tl-none">

          <div class="flex flex-col lg:flex-row items-stretch border border-transparent rounded-2xl rounded-tl-none">

            <!-- FILTERS -->
            <div class="flex flex-row items-stretch divide-x divide-gray-200 bg-white rounded-xl ">

              <!-- LOCATION -->
              <div class="relative w-[190px]">

                <!-- LABEL -->
                <label
                  class="absolute left-5 top-3 text-xs text-gray-400 font-medium pointer-events-none z-20">
                  Location
                </label>

                <!-- BUTTON -->
                <button id="cityBtn"
                  class="w-full h-16 bg-white pt-6 pb-2 px-5 pr-12 text-sm font-semibold text-gray-800 text-left outline-none">

                  <span id="cityText">Select City</span>

                </button>

                <!-- ARROW -->
                <div class="absolute right-4 top-10 -translate-y-1/2 pointer-events-none">

                  <svg id="cityArrow"
                    class="w-4 h-4 text-gray-500 transition-transform duration-300"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24">

                    <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M19 9l-7 7-7-7" />

                  </svg>

                </div>

                <!-- DROPDOWN -->
                <div id="cityDropdown" class="absolute left-0 top-16 w-full bg-white border shadow-sm rounded-b-lg opacity-0 origin-top scale-y-0 invisible transition-all duration-300 ease-out z-50">

                  <ul class="py-2 text-xs text-gray-700">

                    <li>
                      <button type="button"
                        class="cityOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                        Pune
                      </button>
                    </li>

                    <li>
                      <button type="button"
                        class="cityOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                        Mumbai
                      </button>
                    </li>

                    <li>
                      <button type="button"
                        class="cityOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                        Bangalore
                      </button>
                    </li>

                    <li>
                      <button type="button"
                        class="cityOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                        Ahmedabad
                      </button>
                    </li>

                    <li>
                      <button type="button"
                        class="cityOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                        Hyderabad
                      </button>
                    </li>

                    <li>
                      <button type="button"
                        class="cityOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                        Gurugram
                      </button>
                    </li>

                    <li>
                      <button type="button"
                        class="cityOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                        Chennai
                      </button>
                    </li>

                  </ul>

                </div>

              </div>

              <!-- BUDGET -->
              <div class="relative w-[190px]">

                <!-- LABEL -->
                <label
                  class="absolute left-5 top-3 text-xs text-gray-400 font-medium pointer-events-none z-20">
                  Budget
                </label>

                <!-- BUTTON -->
                <button id="budgetBtn"
                  class="w-full h-16 bg-white pt-6 pb-2 px-5 pr-12 text-sm font-semibold text-gray-800 text-left outline-none">

                  <span id="budgetText">Select Budget</span>

                </button>

                <!-- ARROW -->
                <div class="absolute right-4 top-10 -translate-y-1/2 pointer-events-none">

                  <svg id="budgetArrow"
                    class="w-4 h-4 text-gray-500 transition-transform duration-300"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24">

                    <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M19 9l-7 7-7-7" />

                  </svg>

                </div>

                <!-- DROPDOWN -->
                <div id="budgetDropdown"
                  class="absolute left-0 top-12 w-full bg-white border shadow-sm rounded-b-lg opacity-0 origin-top scale-y-0 invisible translate-y-2 transition-all duration-300 ease-out z-50">

                  <ul class="py-2 text-xs text-gray-700">

                    <li>
                      <button type="button"
                        class="budgetOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                        ₹10L - ₹25L
                      </button>
                    </li>

                    <li>
                      <button type="button"
                        class="budgetOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                        ₹25L - ₹50L
                      </button>
                    </li>

                    <li>
                      <button type="button"
                        class="budgetOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                        ₹50L - ₹1Cr
                      </button>
                    </li>

                    <li>
                      <button type="button"
                        class="budgetOption w-full text-left px-4 py-2 hover:bg-gray-100 transition">
                        ₹1Cr+
                      </button>
                    </li>

                  </ul>

                </div>

              </div>

            </div>

            <!-- SCRIPT -->
            <script>
              // LOCATION
              const cityBtn = document.getElementById('cityBtn');
              const cityDropdown = document.getElementById('cityDropdown');
              const cityArrow = document.getElementById('cityArrow');
              const cityText = document.getElementById('cityText');
              const cityOptions = document.querySelectorAll('.cityOption');

              cityBtn.addEventListener('click', () => {

                cityDropdown.classList.toggle('scale-y-0');
                cityDropdown.classList.toggle('opacity-0');
                cityDropdown.classList.toggle('invisible');

                cityArrow.classList.toggle('-rotate-90');

              });

              cityOptions.forEach(option => {

                option.addEventListener('click', () => {

                  cityText.innerText = option.innerText;

                  cityDropdown.classList.add('scale-y-0', 'opacity-0', 'invisible');

                  cityArrow.classList.remove('-rotate-90');

                });

              });


              // CLOSE OUTSIDE
              document.addEventListener('click', (e) => {

                if (!cityBtn.contains(e.target) && !cityDropdown.contains(e.target)) {

                  cityDropdown.classList.add('scale-y-0', 'opacity-0', 'invisible');

                  cityArrow.classList.remove('-rotate-90');

                }

              });

              // BUDGET
              const budgetBtn = document.getElementById('budgetBtn');
              const budgetDropdown = document.getElementById('budgetDropdown');
              const budgetArrow = document.getElementById('budgetArrow');
              const budgetText = document.getElementById('budgetText');
              const budgetOptions = document.querySelectorAll('.budgetOption');

              budgetBtn.addEventListener('click', () => {

                budgetDropdown.classList.toggle('scale-y-0');
                budgetDropdown.classList.toggle('opacity-0');
                budgetDropdown.classList.toggle('invisible');

                budgetArrow.classList.toggle('-rotate-90');

              });

              budgetOptions.forEach(option => {

                option.addEventListener('click', () => {

                  budgetText.innerText = option.innerText;

                  budgetDropdown.classList.add('scale-y-0', 'opacity-0', 'invisible');

                  budgetArrow.classList.remove('-rotate-90');

                });

              });


              // CLOSE ON OUTSIDE CLICK
              document.addEventListener('click', (e) => {

                // LOCATION
                if (!cityBtn.contains(e.target) && !cityDropdown.contains(e.target)) {

                  cityDropdown.classList.add('scale-y-0', 'opacity-0', 'invisible');

                  cityArrow.classList.remove('-rotate-90');

                }

                // BUDGET
                if (!budgetBtn.contains(e.target) && !budgetDropdown.contains(e.target)) {

                  budgetDropdown.classList.add('scale-y-0', 'opacity-0', 'invisible');

                  budgetArrow.classList.remove('-rotate-90');

                }

              });
            </script>

            <!-- SEARCH SECTION -->
            <div class="flex-1 p-4">

              <!-- SEARCH INPUT -->
              <div class="w-full flex items-center h-12 px-5 rounded-xl bg-gray-100 border border-transparent
  focus-within:border-gray-300
  transition-all duration-300">

                <input type="text"
                  placeholder="Search for Project or locality"
                  class="w-full h-full bg-transparent outline-none
            text-sm text-gray-700
            placeholder:text-gray-500" />

                <svg class="w-5 h-5 text-gray-400 flex-shrink-0"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  viewBox="0 0 24 24">

                  <circle cx="11" cy="11" r="7" />
                  <path d="M20 20l-3.5-3.5" />
                </svg>

              </div>

              <!-- POPULAR LOCALITIES -->
              <div class="flex flex-wrap items-center gap-3 mt-4">

                <!-- TITLE -->
                <div class="flex items-center gap-1">

                  <span class="text-sm font-semibold text-black">
                    Popular Localities
                  </span>

                  <svg class="w-4 h-4 text-accent"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24">

                    <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M7 17L17 7" />

                    <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M8 7h9v9" />
                  </svg>

                </div>

                <!-- TAGS -->
                <button
                  class="px-4 py-2 rounded-full bg-accent text-white text-[10px] font-medium">
                  Andheri West
                </button>

                <button
                  class="px-4 py-2 rounded-full bg-gray-200 text-accent text-[10px] font-medium hover:bg-gray-300 transition">
                  Borivali West
                </button>

                <button
                  class="px-4 py-2 rounded-full bg-gray-200 text-accent text-[10px] font-medium hover:bg-gray-300 transition">
                  Dombivli East
                </button>

              </div>

            </div>

          </div>

        </div>

      </div>

    </div>

  </div>

</section>

<!-- Why Choose Us -->
<section class="py-12 md:py-16">
  <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-20">
    <!-- TOP -->
    <div class="mb-10">
      <div
        class="flex items-center gap-2 text-accent uppercase font-semibold text-xs md:text-sm mb-3">
        <i class="fa-regular fa-building"></i>
        <span>Advantages</span>
      </div>

      <h2
        class="text-primary text-2xl md:text-3xl font-semibold leading-tight mb-3">
        Why Choose Us?
      </h2>

      <p class="text-gray-500 text-xs md:text-base">
        Discover the key advantages of investing with us.
      </p>
    </div>

    <!-- GRID -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
      <!-- CARD 1 -->
      <div
        class="bg-white rounded-3xl p-6 py-12 min-h-[280px] duration-300 hover:-translate-y-1 border">
        <div class="mb-8 flex justify-center">

          <div class="text-5xl md:text-6xl flex items-center justify-center text-primary">
            <i class="fa-solid fa-hand-holding-dollar"></i>
          </div>

        </div>

        <h3 class="text-xl font-semibold text-primary mb-4 text-center">
          Bottom Rate Guarantee
        </h3>

        <p class="text-gray-700 text-sm text-center leading-[24px]">
          Housiey guarantees the bottom rate or refunds double the
          difference.
        </p>
      </div>

      <!-- CARD 2 -->
      <div
        class="bg-white rounded-3xl p-6 py-12 min-h-[280px] duration-300 hover:-translate-y-1 border">
        <div class="mb-8 flex justify-center">
          <div
            class="text-5xl md:text-6xl flex items-center justify-center text-primary">
            <i class="fa-solid fa-display"></i>
          </div>
        </div>

        <h3 class="text-xl font-semibold text-primary mb-4 text-center">
          Online Site Visit
        </h3>

        <p class="text-gray-700 text-sm text-center leading-[24px]">
          Visit projects from home with Housiey's Online Site Visit concept.
        </p>
      </div>

      <!-- CARD 3 -->
      <div
        class="bg-white rounded-3xl p-6 py-12 min-h-[280px] duration-300 hover:-translate-y-1 border">
        <div class="mb-8 flex justify-center">
          <div
            class="text-5xl md:text-6xl flex items-center justify-center text-primary">
            <i class="fa-solid fa-location-dot"></i>
          </div>
        </div>

        <h3 class="text-xl font-semibold text-primary mb-4 text-center">
          Free Site Visit
        </h3>

        <p class="text-gray-700 text-sm text-center leading-[24px]">
          Free pickup & drop for unlimited site visits across the city.
        </p>
      </div>

      <!-- CARD 4 -->
      <div
        class="bg-white rounded-3xl p-6 py-12 min-h-[280px] duration-300 hover:-translate-y-1 border">
        <div class="mb-8 flex justify-center">
          <div
            class="text-5xl md:text-6xl flex items-center justify-center text-primary">
            <i class="fa-solid fa-users-gear"></i>
          </div>
        </div>

        <h3 class="text-xl font-semibold text-primary mb-4 text-center">
          No Brokerage Charges
        </h3>

        <p class="text-gray-700 text-sm text-center leading-[24px]">
          Get personalized RM managing everything from site visit to
          booking.
        </p>
      </div>

      <!-- LAST CARD -->


    </div>
  </div>
</section>

<!-- Projects -->
<section class="py-12 md:py-16 overflow-hidden bg-gray-50">
  <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-20">
    <!-- TOP AREA -->
    <div
      class="flex flex-col md:flex-row md:justify-between md:items-start gap-6 mb-8">
      <!-- LEFT -->
      <div>
        <div
          class="flex items-center gap-2 text-green-500 uppercase font-semibold text-xs md:text-sm mb-3">
          <i class="fa-solid fa-building"></i>
          <span>Properties</span>
        </div>

        <h2
          class="text-primary text-2xl md:text-3xl font-semibold leading-tight mb-3">
          Top New Launches In Mumbai
        </h2>

        <p class="text-gray-500 text-xs md:text-base">
          Discover the Latest Real Estate Projects in Mumbai
        </p>
      </div>

      <!-- RIGHT -->
      <div
        class="flex items-center justify-between md:flex-col md:items-end gap-4">
        <button
          class="bg-primary-50 text-primary-500 px-5 md:px-6 py-3 rounded-xl font-medium text-xs md:text-sm hover:opacity-90 duration-300">
          View More
        </button>

        <div class="flex gap-3">
          <button
            class="property-prev w-11 h-11 rounded-full bg-gray-100 text-gray-400 text-xs flex items-center justify-center hover:scale-105 duration-300">
            <i class="fa-solid fa-chevron-left"></i>
          </button>

          <button
            class="property-next w-11 h-11 rounded-full bg-gray-100 text-gray-400 text-xs flex items-center justify-center hover:scale-105 duration-300">
            <i class="fa-solid fa-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- SWIPER -->
    <div class="swiper propertySwiper overflow-visible">
      <div class="swiper-wrapper">
        <!-- CARD -->
        <div class="swiper-slide">
          <div
            class="bg-white rounded-2xl border border-gray-200 p-3 md:p-4 transition duration-300 hover:-translate-y-1 hover:shadow-lg">
            <!-- IMAGE -->
            <div class="relative rounded-2xl overflow-hidden">
              <img
                src="https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?q=80&w=1200&auto=format&fit=crop"
                class="w-full h-[220px] object-cover" />
              <button
                class="absolute top-0 left-4 inline-flex items-center gap-2 mt-3
     bg-green-600 text-white
    px-3 py-1.5 rounded-lg text-xs">

                <!-- BLINK DOT -->
                <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>

                Ready for Sale

              </button>

              <!-- HEART -->
              <button
                class="absolute top-2 right-4 text-white text-xl md:text-2xl">
                <i class="fa-regular fa-heart"></i>
              </button>

              <!-- PLAY -->
              <button
                class="absolute bottom-4 right-4 bg-accent w-10 h-10 rounded-full text-white flex items-center justify-center">
                <i class="fa-solid fa-play"></i>
              </button>
            </div>

            <!-- CONTENT -->
            <div class="pt-4">
              <div
                class="flex flex-col xl:flex-row xl:justify-between gap-4">
                <!-- LEFT -->
                <div>
                  <h3
                    class="text-lg md:text-xl font-semibold text-primary leading-tight">
                    Godrej Emerald
                  </h3>

                  <p
                    class="text-primary underline mt-2 text-xs md:text-sm">
                    <i class="fa-regular fa-building mr-1"></i>
                    Godrej Properties
                  </p>

                  <p
                    class="text-primary underline mt-2 text-xs md:text-sm">
                    <i class="fa-solid fa-location-dot mr-1"></i>
                    Bhayandarpada Thane
                  </p>
                </div>

                <!-- RIGHT -->
                <div class="xl:text-right">
                  <h4
                    class="text-lg md:text-xl font-semibold text-green-500 leading-tight">
                    ₹ 1.25 Cr - 1.70 Cr
                  </h4>

                  <p class="text-gray-500 text-xs">(All inc)</p>

                  <p class="text-primary mt-3 text-sm">
                    <i class="fa-solid fa-expand mr-1"></i>
                    708 - 921 sqft
                  </p>
                </div>
              </div>

              <!-- TABLE -->
              <div class="mt-5 border border-gray-300 rounded-lg overflow-hidden">

                <!-- SCROLLABLE AREA -->
                <div class="max-h-[80px] overflow-y-auto scrollbar-thin">
                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            border-b border-gray-200
            text-primary font-semibold text-xs">

                    <span>3BHK</span>
                    <span>908 sqft</span>
                    <span>₹1.65 Cr</span>

                  </div>

                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            border-b border-gray-200
            text-primary font-semibold text-xs">

                    <span>3BHK</span>
                    <span>921 sqft</span>
                    <span>₹1.70 Cr</span>

                  </div>

                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            border-b border-gray-200
            text-primary font-semibold text-xs">

                    <span>2BHK</span>
                    <span>780 sqft</span>
                    <span>₹1.20 Cr</span>

                  </div>

                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            border-b border-gray-200
            text-primary font-semibold text-xs">

                    <span>4BHK</span>
                    <span>1200 sqft</span>
                    <span>₹2.40 Cr</span>

                  </div>

                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            text-primary font-semibold text-xs">

                    <span>Studio</span>
                    <span>450 sqft</span>
                    <span>₹75 Lakh</span>

                  </div>

                </div>

              </div>

              <!-- BUTTONS -->
              <div class="grid grid-cols-2 gap-3 mt-4">

                <!-- TOUR BUTTON -->
                <button
                  class="flex items-center justify-center gap-2
        bg-primary text-white
        py-3 rounded-lg text-sm
        hover:opacity-90 duration-300">

                  <!-- ICONS -->
                  <div class="flex items-center gap-2">

                    <i class="fa-solid fa-laptop text-xs "></i>

                    <span class="text-white">|</span>

                    <i class="fa-solid fa-car text-xs"></i>

                  </div>

                  <!-- TEXT -->
                  <span>
                    Tour
                  </span>

                </button>

                <!-- LIVE CHAT BUTTON -->
                <button
                  class="flex items-center justify-center gap-2
        bg-accent text-white
        py-3 rounded-lg text-sm
        hover:opacity-90 duration-300">

                  <!-- WHATSAPP ICON -->
                  <i class="fa-brands fa-whatsapp text-sm"></i>

                  <!-- TEXT -->
                  <span>
                    Live Chat
                  </span>

                </button>

              </div>
            </div>
          </div>
        </div>

        <!-- CARD -->
        <div class="swiper-slide">
          <div
            class="bg-white rounded-2xl border border-gray-200 p-3 md:p-4 transition duration-300 hover:-translate-y-1 hover:shadow-lg">
            <!-- IMAGE -->
            <div class="relative rounded-2xl overflow-hidden">
              <img
                src="https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?q=80&w=1200&auto=format&fit=crop"
                class="w-full h-[220px] object-cover" />
              <button
                class="absolute top-0 left-4 inline-flex items-center gap-2 mt-3
     bg-green-600 text-white
    px-3 py-1.5 rounded-lg text-xs">

                <!-- BLINK DOT -->
                <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>

                Ready for Sale

              </button>

              <!-- HEART -->
              <button
                class="absolute top-2 right-4 text-white text-xl md:text-2xl">
                <i class="fa-regular fa-heart"></i>
              </button>

              <!-- PLAY -->
              <button
                class="absolute bottom-4 right-4 bg-accent w-10 h-10 rounded-full text-white flex items-center justify-center">
                <i class="fa-solid fa-play"></i>
              </button>
            </div>

            <!-- CONTENT -->
            <div class="pt-4">
              <div
                class="flex flex-col xl:flex-row xl:justify-between gap-4">
                <!-- LEFT -->
                <div>
                  <h3
                    class="text-lg md:text-xl font-semibold text-primary leading-tight">
                    Godrej Emerald
                  </h3>

                  <p
                    class="text-primary underline mt-2 text-xs md:text-sm">
                    <i class="fa-regular fa-building mr-1"></i>
                    Godrej Properties
                  </p>

                  <p
                    class="text-primary underline mt-2 text-xs md:text-sm">
                    <i class="fa-solid fa-location-dot mr-1"></i>
                    Bhayandarpada Thane
                  </p>
                </div>

                <!-- RIGHT -->
                <div class="xl:text-right">
                  <h4
                    class="text-lg md:text-xl font-semibold text-green-500 leading-tight">
                    ₹ 1.25 Cr - 1.70 Cr
                  </h4>

                  <p class="text-gray-500 text-xs">(All inc)</p>

                  <p class="text-primary mt-3 text-sm">
                    <i class="fa-solid fa-expand mr-1"></i>
                    708 - 921 sqft
                  </p>
                </div>
              </div>

              <!-- TABLE -->
              <div class="mt-5 border border-gray-300 rounded-lg overflow-hidden">

                <!-- SCROLLABLE AREA -->
                <div class="max-h-[80px] overflow-y-auto scrollbar-thin">
                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            border-b border-gray-200
            text-primary font-semibold text-xs">

                    <span>3BHK</span>
                    <span>908 sqft</span>
                    <span>₹1.65 Cr</span>

                  </div>

                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            border-b border-gray-200
            text-primary font-semibold text-xs">

                    <span>3BHK</span>
                    <span>921 sqft</span>
                    <span>₹1.70 Cr</span>

                  </div>

                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            border-b border-gray-200
            text-primary font-semibold text-xs">

                    <span>2BHK</span>
                    <span>780 sqft</span>
                    <span>₹1.20 Cr</span>

                  </div>

                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            border-b border-gray-200
            text-primary font-semibold text-xs">

                    <span>4BHK</span>
                    <span>1200 sqft</span>
                    <span>₹2.40 Cr</span>

                  </div>

                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            text-primary font-semibold text-xs">

                    <span>Studio</span>
                    <span>450 sqft</span>
                    <span>₹75 Lakh</span>

                  </div>

                </div>

              </div>

              <!-- BUTTONS -->
              <div class="grid grid-cols-2 gap-3 mt-4">

                <!-- TOUR BUTTON -->
                <button
                  class="flex items-center justify-center gap-2
        bg-primary text-white
        py-3 rounded-lg text-sm
        hover:opacity-90 duration-300">

                  <!-- ICONS -->
                  <div class="flex items-center gap-2">

                    <i class="fa-solid fa-laptop text-xs "></i>

                    <span class="text-white">|</span>

                    <i class="fa-solid fa-car text-xs"></i>

                  </div>

                  <!-- TEXT -->
                  <span>
                    Tour
                  </span>

                </button>

                <!-- LIVE CHAT BUTTON -->
                <button
                  class="flex items-center justify-center gap-2
        bg-accent text-white
        py-3 rounded-lg text-sm
        hover:opacity-90 duration-300">

                  <!-- WHATSAPP ICON -->
                  <i class="fa-brands fa-whatsapp text-sm"></i>

                  <!-- TEXT -->
                  <span>
                    Live Chat
                  </span>

                </button>

              </div>
            </div>
          </div>
        </div>

        <!-- CARD -->
        <div class="swiper-slide">
          <div
            class="bg-white rounded-2xl border border-gray-200 p-3 md:p-4 transition duration-300 hover:-translate-y-1 hover:shadow-lg">
            <!-- IMAGE -->
            <div class="relative rounded-2xl overflow-hidden">
              <img
                src="https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?q=80&w=1200&auto=format&fit=crop"
                class="w-full h-[220px] object-cover" />
              <button
                class="absolute top-0 left-4 inline-flex items-center gap-2 mt-3
     bg-green-600 text-white
    px-3 py-1.5 rounded-lg text-xs">

                <!-- BLINK DOT -->
                <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>

                Ready for Sale

              </button>

              <!-- HEART -->
              <button
                class="absolute top-2 right-4 text-white text-xl md:text-2xl">
                <i class="fa-regular fa-heart"></i>
              </button>

              <!-- PLAY -->
              <button
                class="absolute bottom-4 right-4 bg-accent w-10 h-10 rounded-full text-white flex items-center justify-center">
                <i class="fa-solid fa-play"></i>
              </button>
            </div>

            <!-- CONTENT -->
            <div class="pt-4">
              <div
                class="flex flex-col xl:flex-row xl:justify-between gap-4">
                <!-- LEFT -->
                <div>
                  <h3
                    class="text-lg md:text-xl font-semibold text-primary leading-tight">
                    Godrej Emerald
                  </h3>

                  <p
                    class="text-primary underline mt-2 text-xs md:text-sm">
                    <i class="fa-regular fa-building mr-1"></i>
                    Godrej Properties
                  </p>

                  <p
                    class="text-primary underline mt-2 text-xs md:text-sm">
                    <i class="fa-solid fa-location-dot mr-1"></i>
                    Bhayandarpada Thane
                  </p>
                </div>

                <!-- RIGHT -->
                <div class="xl:text-right">
                  <h4
                    class="text-lg md:text-xl font-semibold text-green-500 leading-tight">
                    ₹ 1.25 Cr - 1.70 Cr
                  </h4>

                  <p class="text-gray-500 text-xs">(All inc)</p>

                  <p class="text-primary mt-3 text-sm">
                    <i class="fa-solid fa-expand mr-1"></i>
                    708 - 921 sqft
                  </p>
                </div>
              </div>

              <!-- TABLE -->
              <div class="mt-5 border border-gray-300 rounded-lg overflow-hidden">

                <!-- SCROLLABLE AREA -->
                <div class="max-h-[80px] overflow-y-auto scrollbar-thin">
                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            border-b border-gray-200
            text-primary font-semibold text-xs">

                    <span>3BHK</span>
                    <span>908 sqft</span>
                    <span>₹1.65 Cr</span>

                  </div>

                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            border-b border-gray-200
            text-primary font-semibold text-xs">

                    <span>3BHK</span>
                    <span>921 sqft</span>
                    <span>₹1.70 Cr</span>

                  </div>

                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            border-b border-gray-200
            text-primary font-semibold text-xs">

                    <span>2BHK</span>
                    <span>780 sqft</span>
                    <span>₹1.20 Cr</span>

                  </div>

                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            border-b border-gray-200
            text-primary font-semibold text-xs">

                    <span>4BHK</span>
                    <span>1200 sqft</span>
                    <span>₹2.40 Cr</span>

                  </div>

                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            text-primary font-semibold text-xs">

                    <span>Studio</span>
                    <span>450 sqft</span>
                    <span>₹75 Lakh</span>

                  </div>

                </div>

              </div>

              <!-- BUTTONS -->
              <div class="grid grid-cols-2 gap-3 mt-4">

                <!-- TOUR BUTTON -->
                <button
                  class="flex items-center justify-center gap-2
        bg-primary text-white
        py-3 rounded-lg text-sm
        hover:opacity-90 duration-300">

                  <!-- ICONS -->
                  <div class="flex items-center gap-2">

                    <i class="fa-solid fa-laptop text-xs "></i>

                    <span class="text-white">|</span>

                    <i class="fa-solid fa-car text-xs"></i>

                  </div>

                  <!-- TEXT -->
                  <span>
                    Tour
                  </span>

                </button>

                <!-- LIVE CHAT BUTTON -->
                <button
                  class="flex items-center justify-center gap-2
        bg-accent text-white
        py-3 rounded-lg text-sm
        hover:opacity-90 duration-300">

                  <!-- WHATSAPP ICON -->
                  <i class="fa-brands fa-whatsapp text-sm"></i>

                  <!-- TEXT -->
                  <span>
                    Live Chat
                  </span>

                </button>

              </div>
            </div>
          </div>
        </div>


        <!-- CARD -->
        <div class="swiper-slide">
          <div
            class="bg-white rounded-2xl border border-gray-200 p-3 md:p-4 transition duration-300 hover:-translate-y-1 hover:shadow-lg">
            <!-- IMAGE -->
            <div class="relative rounded-2xl overflow-hidden">
              <img
                src="https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?q=80&w=1200&auto=format&fit=crop"
                class="w-full h-[220px] object-cover" />
              <button
                class="absolute top-0 left-4 inline-flex items-center gap-2 mt-3
     bg-green-600 text-white
    px-3 py-1.5 rounded-lg text-xs">

                <!-- BLINK DOT -->
                <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>

                Ready for Sale

              </button>

              <!-- HEART -->
              <button
                class="absolute top-2 right-4 text-white text-xl md:text-2xl">
                <i class="fa-regular fa-heart"></i>
              </button>

              <!-- PLAY -->
              <button
                class="absolute bottom-4 right-4 bg-accent w-10 h-10 rounded-full text-white flex items-center justify-center">
                <i class="fa-solid fa-play"></i>
              </button>
            </div>

            <!-- CONTENT -->
            <div class="pt-4">
              <div
                class="flex flex-col xl:flex-row xl:justify-between gap-4">
                <!-- LEFT -->
                <div>
                  <h3
                    class="text-lg md:text-xl font-semibold text-primary leading-tight">
                    Godrej Emerald
                  </h3>

                  <p
                    class="text-primary underline mt-2 text-xs md:text-sm">
                    <i class="fa-regular fa-building mr-1"></i>
                    Godrej Properties
                  </p>

                  <p
                    class="text-primary underline mt-2 text-xs md:text-sm">
                    <i class="fa-solid fa-location-dot mr-1"></i>
                    Bhayandarpada Thane
                  </p>
                </div>

                <!-- RIGHT -->
                <div class="xl:text-right">
                  <h4
                    class="text-lg md:text-xl font-semibold text-green-500 leading-tight">
                    ₹ 1.25 Cr - 1.70 Cr
                  </h4>

                  <p class="text-gray-500 text-xs">(All inc)</p>

                  <p class="text-primary mt-3 text-sm">
                    <i class="fa-solid fa-expand mr-1"></i>
                    708 - 921 sqft
                  </p>
                </div>
              </div>

              <!-- TABLE -->
              <div class="mt-5 border border-gray-300 rounded-lg overflow-hidden">

                <!-- SCROLLABLE AREA -->
                <div class="max-h-[80px] overflow-y-auto scrollbar-thin">
                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            border-b border-gray-200
            text-primary font-semibold text-xs">

                    <span>3BHK</span>
                    <span>908 sqft</span>
                    <span>₹1.65 Cr</span>

                  </div>

                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            border-b border-gray-200
            text-primary font-semibold text-xs">

                    <span>3BHK</span>
                    <span>921 sqft</span>
                    <span>₹1.70 Cr</span>

                  </div>

                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            border-b border-gray-200
            text-primary font-semibold text-xs">

                    <span>2BHK</span>
                    <span>780 sqft</span>
                    <span>₹1.20 Cr</span>

                  </div>

                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            border-b border-gray-200
            text-primary font-semibold text-xs">

                    <span>4BHK</span>
                    <span>1200 sqft</span>
                    <span>₹2.40 Cr</span>

                  </div>

                  <!-- ROW -->
                  <div
                    class="flex justify-between items-center gap-3
            px-4 py-3 bg-primary-50
            text-primary font-semibold text-xs">

                    <span>Studio</span>
                    <span>450 sqft</span>
                    <span>₹75 Lakh</span>

                  </div>

                </div>

              </div>

              <!-- BUTTONS -->
              <div class="grid grid-cols-2 gap-3 mt-4">

                <!-- TOUR BUTTON -->
                <button
                  class="flex items-center justify-center gap-2
        bg-primary text-white
        py-3 rounded-lg text-sm
        hover:opacity-90 duration-300">

                  <!-- ICONS -->
                  <div class="flex items-center gap-2">

                    <i class="fa-solid fa-laptop text-xs "></i>

                    <span class="text-white">|</span>

                    <i class="fa-solid fa-car text-xs"></i>

                  </div>

                  <!-- TEXT -->
                  <span>
                    Tour
                  </span>

                </button>

                <!-- LIVE CHAT BUTTON -->
                <button
                  class="flex items-center justify-center gap-2
        bg-accent text-white
        py-3 rounded-lg text-sm
        hover:opacity-90 duration-300">

                  <!-- WHATSAPP ICON -->
                  <i class="fa-brands fa-whatsapp text-sm"></i>

                  <!-- TEXT -->
                  <span>
                    Live Chat
                  </span>

                </button>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Top Developers Projects -->
<section class="py-12 md:py-16">
  <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-20">
    <!-- TOP BAR -->
    <div class="flex items-center justify-between">
      <!-- TITLE -->
      <h2
        class="text-primary text-2xl md:text-3xl font-semibold leading-tight">
        Top Developers Projects
      </h2>

      <!-- NAVIGATION -->
      <div class="flex items-center gap-3">
        <button
          class="developer-prev w-11 h-11 rounded-full bg-gray-100 text-gray-400 text-xs flex items-center justify-center hover:scale-105 duration-300">
          <i class="fa-solid fa-chevron-left"></i>
        </button>

        <button
          class="developer-next w-11 h-11 rounded-full bg-gray-100 text-gray-400 text-xs flex items-center justify-center hover:scale-105 duration-300">
          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>
    </div>

    <!-- SWIPER -->
    <div class="swiper developerSwiper py-8">
      <div class="swiper-wrapper">
        <!-- CARD -->
        <div class="swiper-slide">
          <div
            class="group relative bg-white rounded-2xl
    p-5 shadow-sm border
    overflow-hidden hover:-translate-y-1 min-h-[260px] transition-all duration-300 hover:shadow-lg">

            <!-- YEARS -->
            <h3
              class="text-red-500
      text-3xl md:text-4xl
      font-semibold tracking-[-2px] leading-none">

              25y+

            </h3>

            <!-- IMAGE -->
            <div class="flex justify-center items-center px-8 py-14">

              <img
                src="https://i.ibb.co/q3M17dkf/kalpataru-group-1739962980948-856775303.webp"
                alt="Developer"
                class="object-contain mx-auto
        transition duration-300 group-hover:scale-105" />

            </div>

            <!-- BOTTOM -->
            <div class="w-full flex items-center justify-between">

              <!-- TEXT -->
              <div>

                <span
                  class="text-xs
            uppercase tracking-[2px]
            text-gray-500 font-medium">

                  Total

                </span>

                <p
                  class="text-green-600
            text-base
            font-semibold leading-tight mt-1">

                  Projects

                </p>

              </div>

              <!-- NUMBER -->
              <span
                class="relative w-14 h-14
          rounded-full
          bg-green-600
          text-white
          flex items-center justify-center
          shadow-lg font-extrabold text-2xl">
                12

              </span>

            </div>

          </div>
        </div>

        <!-- CARD -->
        <div class="swiper-slide">
          <div
            class="group relative bg-white rounded-2xl
    p-5 shadow-sm border
    overflow-hidden hover:-translate-y-1 min-h-[260px] transition-all duration-300 hover:shadow-lg">

            <!-- YEARS -->
            <h3
              class="text-pink-500
      text-3xl md:text-4xl
      font-semibold tracking-[-2px] leading-none">

              25y+

            </h3>

            <!-- IMAGE -->
            <div class="flex justify-center items-center px-8 py-14">

              <img
                src="https://i.ibb.co/75ZQtnh/mahindra-lifespace-1739963026098-423539378.webp"
                alt="Developer"
                class="object-contain mx-auto
        transition duration-300 group-hover:scale-105" />

            </div>

            <!-- BOTTOM -->
            <div class="w-full flex items-center justify-between">

              <!-- TEXT -->
              <div>

                <span
                  class="text-xs
            uppercase tracking-[2px]
            text-gray-500 font-medium">

                  Total

                </span>

                <p
                  class="text-green-600
            text-base
            font-semibold leading-tight mt-1">

                  Projects

                </p>

              </div>

              <!-- NUMBER -->
              <span
                class="relative w-14 h-14
          rounded-full
          bg-green-600
          text-white
          flex items-center justify-center
          shadow-lg font-extrabold text-2xl">
                12

              </span>

            </div>

          </div>
        </div>

        <!-- CARD -->
        <div class="swiper-slide">
          <div
            class="group relative bg-white rounded-2xl
    p-5 shadow-sm border
    overflow-hidden hover:-translate-y-1 min-h-[260px] transition-all duration-300 hover:shadow-lg">

            <!-- YEARS -->
            <h3
              class="text-orange-500
      text-3xl md:text-4xl
      font-semibold tracking-[-2px] leading-none">

              25y+

            </h3>

            <!-- IMAGE -->
            <div class="flex justify-center items-center px-8 py-14">

              <img
                src="https://i.ibb.co/q3M17dkf/kalpataru-group-1739962980948-856775303.webp"
                alt="Developer"
                class="object-contain mx-auto
        transition duration-300 group-hover:scale-105" />

            </div>

            <!-- BOTTOM -->
            <div class="w-full flex items-center justify-between">

              <!-- TEXT -->
              <div>

                <span
                  class="text-xs
            uppercase tracking-[2px]
            text-gray-500 font-medium">

                  Total

                </span>

                <p
                  class="text-green-600
            text-base
            font-semibold leading-tight mt-1">

                  Projects

                </p>

              </div>

              <!-- NUMBER -->
              <span
                class="relative w-14 h-14
          rounded-full
          bg-green-600
          text-white
          flex items-center justify-center
          shadow-lg font-extrabold text-2xl">
                12

              </span>

            </div>

          </div>
        </div>

        <!-- CARD -->
        <div class="swiper-slide">
          <div
            class="group relative bg-white rounded-2xl
    p-5 shadow-sm border
    overflow-hidden hover:-translate-y-1 min-h-[260px] transition-all duration-300 hover:shadow-lg">

            <!-- YEARS -->
            <h3
              class="text-green-500
      text-3xl md:text-4xl
      font-semibold tracking-[-2px] leading-none">

              25y+

            </h3>

            <!-- IMAGE -->
            <div class="flex justify-center items-center px-8 py-14">

              <img
                src="https://i.ibb.co/75ZQtnh/mahindra-lifespace-1739963026098-423539378.webp"
                alt="Developer"
                class="object-contain mx-auto
        transition duration-300 group-hover:scale-105" />

            </div>

            <!-- BOTTOM -->
            <div class="w-full flex items-center justify-between">

              <!-- TEXT -->
              <div>

                <span
                  class="text-xs
            uppercase tracking-[2px]
            text-gray-500 font-medium">

                  Total

                </span>

                <p
                  class="text-green-600
            text-base
            font-semibold leading-tight mt-1">

                  Projects

                </p>

              </div>

              <!-- NUMBER -->
              <span
                class="relative w-14 h-14
          rounded-full
          bg-green-600
          text-white
          flex items-center justify-center
          shadow-lg font-extrabold text-2xl">
                12

              </span>

            </div>

          </div>
        </div>

        <!-- CARD -->
        <div class="swiper-slide">
          <div
            class="group relative bg-white rounded-2xl
    p-5 shadow-sm border
    overflow-hidden hover:-translate-y-1 min-h-[260px] transition-all duration-300 hover:shadow-lg">

            <!-- YEARS -->
            <h3
              class="text-blue-500
      text-3xl md:text-4xl
      font-semibold tracking-[-2px] leading-none">

              25y+

            </h3>

            <!-- IMAGE -->
            <div class="flex justify-center items-center px-8 py-14">

              <img
                src="https://i.ibb.co/q3M17dkf/kalpataru-group-1739962980948-856775303.webp"
                alt="Developer"
                class="object-contain mx-auto
        transition duration-300 group-hover:scale-105" />

            </div>

            <!-- BOTTOM -->
            <div class="w-full flex items-center justify-between">

              <!-- TEXT -->
              <div>

                <span
                  class="text-xs
            uppercase tracking-[2px]
            text-gray-500 font-medium">

                  Total

                </span>

                <p
                  class="text-green-600
            text-base
            font-semibold leading-tight mt-1">

                  Projects

                </p>

              </div>

              <!-- NUMBER -->
              <span
                class="relative w-14 h-14
          rounded-full
          bg-green-600
          text-white
          flex items-center justify-center
          shadow-lg font-extrabold text-2xl">
                12

              </span>

            </div>

          </div>
        </div>

        <!-- CARD -->
        <div class="swiper-slide">
          <div
            class="group relative bg-white rounded-2xl
    p-5 shadow-sm border
    overflow-hidden hover:-translate-y-1 min-h-[260px] transition-all duration-300 hover:shadow-lg">

            <!-- YEARS -->
            <h3
              class="text-yellow-500
      text-3xl md:text-4xl
      font-semibold tracking-[-2px] leading-none">

              25y+

            </h3>

            <!-- IMAGE -->
            <div class="flex justify-center items-center px-8 py-14">

              <img
                src="https://i.ibb.co/75ZQtnh/mahindra-lifespace-1739963026098-423539378.webp"
                alt="Developer"
                class="object-contain mx-auto
        transition duration-300 group-hover:scale-105" />

            </div>

            <!-- BOTTOM -->
            <div class="w-full flex items-center justify-between">

              <!-- TEXT -->
              <div>

                <span
                  class="text-xs
            uppercase tracking-[2px]
            text-gray-500 font-medium">

                  Total

                </span>

                <p
                  class="text-green-600
            text-base
            font-semibold leading-tight mt-1">

                  Projects

                </p>

              </div>

              <!-- NUMBER -->
              <span
                class="relative w-14 h-14
          rounded-full
          bg-green-600
          text-white
          flex items-center justify-center
          shadow-lg font-extrabold text-2xl">
                12

              </span>

            </div>

          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<!-- Schedule Now -->
<section
  class="relative overflow-hidden
  min-h-[320px] md:min-h-[430px]
  bg-cover bg-center bg-no-repeat
  flex items-center py-14 md:py-0"
  style="background-image:url('https://i.ibb.co/fVyWWqgx/online-Presentation.webp');">

  <!-- CONTENT -->
  <div class="max-w-[1450px] mx-auto w-full px-4 sm:px-6 md:px-10 relative z-10">

    <div class="max-w-[820px]
      text-center md:text-left
      mx-auto md:mx-0">

      <!-- HEADING -->
      <h2
        class="text-white
        text-xl sm:text-3xl md:text-6xl
        leading-[1.15]
        font-semibold
        tracking-[-1px]">

        Online Project Presentation

      </h2>

      <!-- SUBTEXT -->
      <p
        class="mt-3 md:mt-4
        text-white/90
        text-xs sm:text-sm md:text-base
        leading-[1.7]">

        Directly by Builder

        <span class="mx-1 md:mx-2 text-white/50">|</span>

        Latest Offers

        <span class="mx-1 md:mx-2 text-white/50">|</span>

        Live Project Tour

      </p>

      <!-- SEARCH AREA -->
      <div
        class="mt-8 md:mt-10
        flex flex-row
        items-stretch sm:items-center
        bg-white rounded-full
        overflow-hidden
        max-w-[680px]
        shadow-2xl">

        <!-- INPUT -->
        <div class="flex-1">

          <input
            type="text"
            placeholder="Search multiple projects for Online Presentation"
            class="w-full
            h-[56px] sm:h-[58px] md:h-[68px]
            px-5 md:px-8
            text-xs md:text-sm
            text-gray-500
            outline-none" />

        </div>

        <!-- BUTTON -->
        <button
          class="bg-green-600
          hover:bg-green-700
          transition-all duration-300
          text-white
          h-[56px] sm:h-[58px] md:h-[68px]
          px-4 md:px-10
          flex items-center justify-center gap-2
          text-xs md:text-sm
          font-semibold rounded-r-full">

          Schedule Now

        </button>

      </div>

    </div>

  </div>

</section>

<!-- Client Testimonials -->
<section class="py-12 md:py-16 overflow-hidden">

  <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-20">

    <!-- TOP BAR -->
    <div class="flex items-center justify-between mb-8">

      <div>
        <div
          class="flex items-center gap-2 text-red-500 uppercase font-semibold text-xs md:text-sm mb-3">
          <i class="fa-solid fa-video"></i>
          <span>Client Testimonials</span>
        </div>

        <h2 class="text-primary text-2xl md:text-3xl font-semibold leading-tight mb-3">
          Hear What Our Happy Clients Say
        </h2>

        <p class="text-gray-500 text-xs md:text-base">
          Watch real experiences and success stories shared by our valued clients
        </p>
      </div>

      <!-- NAVIGATION -->
      <div class="hidden md:flex items-center gap-3">

        <button
          class="testimonial-prev w-11 h-11 rounded-full bg-gray-100 text-gray-400 text-xs flex items-center justify-center hover:scale-105 duration-300">
          <i class="fa-solid fa-chevron-left"></i>
        </button>

        <button
          class="testimonial-next w-11 h-11 rounded-full bg-gray-100 text-gray-400 text-xs flex items-center justify-center hover:scale-105 duration-300">
          <i class="fa-solid fa-chevron-right"></i>
        </button>

      </div>

    </div>

    <!-- SWIPER -->
    <div class="swiper testimonialSwiper overflow-visible">

      <div class="swiper-wrapper">

        <!-- CARD 1 -->
        <div class="swiper-slide">

          <div
            class="overflow-hidden rounded-2xl min-w-[280px] max-w-[340px] h-[380px] sm:h-[440px] md:h-[480px] bg-black border border-gray-200 shadow-lg">

            <iframe class="w-full h-full" src="https://www.youtube.com/embed/jNQXAC9IVRw" title="YouTube video"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen>
            </iframe>

          </div>

        </div>

        <!-- CARD 2 -->
        <div class="swiper-slide">

          <div
            class="overflow-hidden rounded-2xl min-w-[280px] max-w-[340px] h-[380px] sm:h-[440px] md:h-[480px] bg-black border border-gray-200 shadow-lg">

            <iframe class="w-full h-full" src="https://www.youtube.com/embed/jNQXAC9IVRw" title="YouTube video"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen>
            </iframe>

          </div>

        </div>

        <!-- CARD 3 -->
        <div class="swiper-slide">

          <div
            class="overflow-hidden rounded-2xl min-w-[280px] max-w-[340px] h-[380px] sm:h-[440px] md:h-[480px] bg-black border border-gray-200 shadow-lg">

            <iframe class="w-full h-full" src="https://www.youtube.com/embed/jNQXAC9IVRw" title="YouTube video"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen>
            </iframe>

          </div>

        </div>

        <!-- CARD 4 -->
        <div class="swiper-slide">

          <div
            class="overflow-hidden rounded-2xl min-w-[280px] max-w-[340px] h-[380px] sm:h-[440px] md:h-[480px] bg-black border border-gray-200 shadow-lg">

            <iframe class="w-full h-full" src="https://www.youtube.com/embed/jNQXAC9IVRw" title="YouTube video"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen>
            </iframe>

          </div>

        </div>

        <!-- CARD 5 -->
        <div class="swiper-slide">

          <div
            class="overflow-hidden rounded-2xl min-w-[280px] max-w-[340px] h-[380px] sm:h-[440px] md:h-[480px] bg-black border border-gray-200 shadow-lg">

            <iframe class="w-full h-full" src="https://www.youtube.com/embed/jNQXAC9IVRw" title="YouTube video"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen>
            </iframe>

          </div>

        </div>

      </div>

    </div>

  </div>

</section>

<!-- FAQ's -->
<section class="mx-auto max-w-3xl px-4 pb-12 md:pb-16">

  <!-- Heading -->
  <h2
    class="text-primary text-center text-2xl md:text-3xl font-semibold leading-tight">
    Frequently
    <span class="relative inline-block border-b-2 border-accent border-solid rounded-sm pb-3">
      Asked
    </span>
    Questions
  </h2>

  <!-- FAQ Container -->
  <div class="mt-10 space-y-4">

    <!-- ITEM -->
    <div class="faq-item border rounded-xl overflow-hidden bg-white">

      <button
        class="faq-btn w-full flex items-center justify-between px-5 py-4 text-left font-medium text-xs md:text-base">

        What products do you offer on your platform?

        <!-- SVG ICON -->
        <svg xmlns="http://www.w3.org/2000/svg"
          class="faq-icon w-5 h-5 transition-transform duration-300"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2">

          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M19 9l-7 7-7-7" />

        </svg>

      </button>

      <div
        class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">

        <p class="px-5 py-5 text-xs md:text-sm bg-gray-50 text-gray-600 leading-relaxed">
          We offer electronics, fashion, home essentials, beauty products,
          lifestyle accessories, and many trending collections from trusted sellers.
        </p>

      </div>

    </div>

    <!-- ITEM -->
    <div class="faq-item border rounded-xl overflow-hidden bg-white">

      <button
        class="faq-btn w-full flex items-center justify-between px-5 py-4 text-left font-medium text-xs md:text-base">

        How long does delivery take?

        <!-- SVG ICON -->
        <svg xmlns="http://www.w3.org/2000/svg"
          class="faq-icon w-5 h-5 transition-transform duration-300"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2">

          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M19 9l-7 7-7-7" />

        </svg>

      </button>

      <div
        class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">

        <p class="px-5 py-5 text-xs md:text-sm bg-gray-50 text-gray-600 leading-relaxed">
          Delivery usually takes between 2–7 business days depending on your
          location and shipping option selected.
        </p>

      </div>

    </div>

    <!-- ITEM -->
    <div class="faq-item border rounded-xl overflow-hidden bg-white">

      <button
        class="faq-btn w-full flex items-center justify-between px-5 py-4 text-left font-medium text-xs md:text-base">

        What payment methods are available?

        <!-- SVG ICON -->
        <svg xmlns="http://www.w3.org/2000/svg"
          class="faq-icon w-5 h-5 transition-transform duration-300"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2">

          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M19 9l-7 7-7-7" />

        </svg>

      </button>

      <div
        class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">

        <p class="px-5 py-5 text-xs md:text-sm bg-gray-50 text-gray-600 leading-relaxed">
          We support UPI, debit cards, credit cards, net banking, wallets,
          and cash on delivery for eligible orders.
        </p>

      </div>

    </div>

  </div>

</section>


<?php require_once 'includes/footer.php'; ?>